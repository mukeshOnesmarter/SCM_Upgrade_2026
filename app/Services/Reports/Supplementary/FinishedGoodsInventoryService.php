<?php

namespace App\Services\Reports\Supplementary;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\InventoryStock;
use App\Models\Decisions\InventoryStockFinishedGood;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\PostpondProduction;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\Transportation;
use App\Models\PostpondConversion;
use App\Models\TransportationEmergency;
use App\Services\ActiveUserService;
use App\Services\Reports\ProductPnlService;
use Illuminate\Support\Facades\Log;

class FinishedGoodsInventoryService
{
    protected $activeUserService;

    protected $productPnlService;

    public function __construct(
        ActiveUserService $activeUserService,
        ProductPnlService $productPnlService
    ) {
        $this->activeUserService = $activeUserService;
        $this->productPnlService = $productPnlService;
    }

    /**
     * Calculates finished goods inventory for each distribution center in a given quarter.
     * This updated implementation:
     *  - uses finished goods inventory first to satisfy demand,
     *  - then converts postponed product_0 to final products to fill shortages,
     *  - then computes ending inventory and persists (if $saveToDb = true).
     */
    public function finishedGoodsInventory(
        int $simulation_id,
        int $faculty_id,
        int $quarter_id,
        int $group_id,
        bool $saveToDb = false
    ) {
        try {
            // Fetch distribution centers for this team and quarter
            $dcCenters = Distribution::with('region')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                ])->get(['region_id', 'dc_number']);

            // Fetch production data
            $manufacturing = Manufacturing::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
            ])->get(['product_id', 'production_quantity', 'emergency_production', 'adjustable_production']);

            // Fetch shipment data
            $shipments = Transportation::with('transportationInfo')->where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
            ])->get();

            // Fetch shipment emergency data
            $shipmentEmergency = TransportationEmergency::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
            ])->get();

            // Fetch postpond production data (postponed product_0 staged at DCs)
            $postpondProduction = PostpondProduction::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
            ])->get();

            $actualSales = ActualDemand::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
            ])->get();

            $hasOnlyOneDC = $dcCenters->count() === 1;

            // ✅ Authoritative fulfilled sales from Product P&L
            $productPnl = collect(
                $this->productPnlService->productPnl(
                    $simulation_id,
                    $faculty_id,
                    $group_id,
                    $quarter_id
                )
            )->keyBy('id');

            $dcRegionIds = $dcCenters
                ->pluck('region_id')
                ->map(fn ($r) => (int) $r)
                ->unique()
                ->values();

            // Loop over each distribution center to calculate inventory values
            $dcCenters->each(function ($item) use (
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id,
                $productPnl,
                $manufacturing,
                $postpondProduction,
                $shipments,
                $shipmentEmergency,
                $dcRegionIds,
                $saveToDb
            ) {

                $item->dc_number = (int) $item->dc_number;
                $item->region_id = (int) $item->region_id;

                // Set beginning inventory (from previous quarter)
                $beginningInventory = InventoryStockFinishedGood::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id - 1,
                    'region_id' => $item->region_id,
                ])->first();

                $item->beginning_inventory = (object) [
                    'product_0' => max(0, $beginningInventory->product_0 ?? 0),
                    'product_01' => max(0, $beginningInventory->product_01 ?? 0),
                    'product_02' => max(0, $beginningInventory->product_02 ?? 0),
                ];

                // Fetch postponed (staged) product_0 at this DC
                $postpondProductionDcSpecific = $postpondProduction
                    ->where('region_id', $item->region_id);

                $item->postponed_production = (object) [
                    // product_0 is the postponed base product staged at this DC
                    'product_0' => (int) $postpondProductionDcSpecific->where('product_id', 0)->sum('quantity'),
                    'product_01' => 0,
                    'product_02' => 0,
                ];

                // For DCs other than DC1/Region1, narrow down shipments
                if ($item->dc_number !== 1 && $item->region_id !== 1) {
                    $shipmentDcSpecific = $shipments
                        ->where('distribution_center_id', $item->dc_number);

                    $shipmentEmergencyDcSpecific = $shipmentEmergency
                        ->where('distribution_center_id', $item->dc_number);
                }

                // DC1 and Region1 case (usually plant / HQ)
                if ($item->dc_number === 1 && $item->region_id === 1) {

                    // Group shipments by supply channel and product
                    $item->shipment = (object) [
                        'surface' => $this->sumScheduledShipments($shipments, 2),
                        'air' => $this->sumScheduledShipments($shipments, 1),
                        'emergency' => (object) [
                            'product_0' => (int) $shipmentEmergency->where('product_id', 0)->sum('quantity'),
                            'product_01' => (int) $shipmentEmergency->where('product_id', 1)->sum('quantity'),
                            'product_02' => (int) $shipmentEmergency->where('product_id', 2)->sum('quantity'),
                        ],
                    ];

                    // Regular and emergency production (plant)
                    $item->regular_production = (object) [
                        'product_0' => (int) ($manufacturing->where('product_id', 0)->first()->production_quantity ?? 0),
                        'product_01' => (int) ($manufacturing->where('product_id', 1)->first()->production_quantity ?? 0),
                        'product_02' => (int) ($manufacturing->where('product_id', 2)->first()->production_quantity ?? 0),
                    ];
                    $item->emergency_production = (object) [
                        // Calculate Letter Dynamic
                        // 'product_0'  => (int)($manufacturing->where('product_id', 0)->first()->emergency_production ?? 0),
                        // 'product_01' => (int)($manufacturing->where('product_id', 1)->first()->emergency_production ?? 0),
                        // 'product_02' => (int)($manufacturing->where('product_id', 2)->first()->emergency_production ?? 0),
                        'product_0' => 0,
                        'product_01' => 0,
                        'product_02' => 0,
                    ];

                    // Calculate available inventory at plant (includes postponed staged at plant/DCs)
                    $item->available_inventory = (object) [
                        'product_0' => $item->beginning_inventory->product_0 +
                          $item->postponed_production->product_0 +
                          $item->regular_production->product_0 +
                          $item->emergency_production->product_0,
                        'product_01' => $item->beginning_inventory->product_01 +
                          $item->postponed_production->product_01 +
                          $item->regular_production->product_01 +
                          $item->emergency_production->product_01,
                        'product_02' => $item->beginning_inventory->product_02 +
                          $item->postponed_production->product_02 +
                          $item->regular_production->product_02 +
                          $item->emergency_production->product_02,
                    ];
                } else {
                    // For other DCs: calculate based on shipments in and postponed production
                    $item->shipment = (object) [
                        'surface' => $this->sumShipments($shipmentDcSpecific, 2),
                        'air' => $this->sumShipments($shipmentDcSpecific, 1),
                        'emergency' => (object) [
                            'product_0' => (int) $shipmentEmergencyDcSpecific->where('product_id', 0)->sum('quantity'),
                            'product_01' => (int) $shipmentEmergencyDcSpecific->where('product_id', 1)->sum('quantity'),
                            'product_02' => (int) $shipmentEmergencyDcSpecific->where('product_id', 2)->sum('quantity'),
                        ],
                    ];

                    $in_p0 = (int) $shipmentDcSpecific->flatMap(fn ($s) => $s->transportationInfo)->where('product_id', 0)->sum('quantity');
                    $in_p1 = (int) $shipmentDcSpecific->flatMap(fn ($s) => $s->transportationInfo)->where('product_id', 1)->sum('quantity');
                    $in_p2 = (int) $shipmentDcSpecific->flatMap(fn ($s) => $s->transportationInfo)->where('product_id', 2)->sum('quantity');

                    $item->available_inventory = (object) [
                        'product_0' => $item->beginning_inventory->product_0 + $in_p0 + $item->postponed_production->product_0,
                        'product_01' => $item->beginning_inventory->product_01 + $in_p1 + $item->postponed_production->product_01,
                        'product_02' => $item->beginning_inventory->product_02 + $in_p2 + $item->postponed_production->product_02,
                    ];
                }

                if ($item->dc_number !== 1 && $item->region_id !== 1) {

                    $flatDelayed = $shipmentDcSpecific
                        ->flatMap(fn ($s) => $s->transportationInfo);

                    $item->delayed_shipments = (object) [
                        'product_0' => (int) $flatDelayed
                            ->where('product_id', 0)
                            ->sum('quantity_delay'),

                        'product_01' => (int) $flatDelayed
                            ->where('product_id', 1)
                            ->sum('quantity_delay'),

                        'product_02' => (int) $flatDelayed
                            ->where('product_id', 2)
                            ->sum('quantity_delay'),
                    ];
                } else {
                    // DC-01 → delayed already included in total shipment
                    $item->delayed_shipments = (object) [
                        'product_0' => 0,
                        'product_01' => 0,
                        'product_02' => 0,
                    ];
                }

                // ==============================
                // ✅ FINAL SALES ALLOCATION (NO DOUBLE COUNTING)
                // ==============================

                $sales_p1 = 0;
                $sales_p2 = 0;

                // 👉 If this is DC-01 (plant)
                if ($item->dc_number === 1 && $item->region_id === 1) {
                    // All market regions (keep static if regions are fixed)
                    $allRegions = collect([1, 2, 3]);

                    // Regions that DO NOT have a DC
                    $missingRegions = $allRegions->diff($dcRegionIds);

                    // 1️⃣ Always consume DC-01's own region sales
                    $p1Region = optional($productPnl->get(1))
                        ->marketRegion
                        ->firstWhere('id', $item->region_id);

                    $p2Region = optional($productPnl->get(2))
                        ->marketRegion
                        ->firstWhere('id', $item->region_id);

                    $sales_p1 =
                      (int) (($p1Region->salesVolumechannel_01 ?? 0) +
                            ($p1Region->salesVolumechannel_02 ?? 0));

                    $sales_p2 =
                      (int) (($p2Region->salesVolumechannel_01 ?? 0) +
                            ($p2Region->salesVolumechannel_02 ?? 0));

                    // 2️⃣ Add sales ONLY for regions that do NOT have a DC
                    foreach ($missingRegions as $regionId) {

                        $p1Missing = optional($productPnl->get(1))
                            ->marketRegion
                            ->firstWhere('id', $regionId);

                        $p2Missing = optional($productPnl->get(2))
                            ->marketRegion
                            ->firstWhere('id', $regionId);

                        $sales_p1 +=
                          (int) (($p1Missing->salesVolumechannel_01 ?? 0) +
                                ($p1Missing->salesVolumechannel_02 ?? 0));

                        $sales_p2 +=
                          (int) (($p2Missing->salesVolumechannel_01 ?? 0) +
                                ($p2Missing->salesVolumechannel_02 ?? 0));
                    }

                } else {
                    // 👉 Non-DC-01 → consume only region sales

                    $p1Region = optional($productPnl->get(1))
                        ->marketRegion
                        ->firstWhere('id', $item->region_id);

                    $p2Region = optional($productPnl->get(2))
                        ->marketRegion
                        ->firstWhere('id', $item->region_id);

                    $sales_p1 =
                      (int) (($p1Region->salesVolumechannel_01 ?? 0) +
                        ($p1Region->salesVolumechannel_02 ?? 0));

                    $sales_p2 =
                      (int) (($p2Region->salesVolumechannel_01 ?? 0) +
                        ($p2Region->salesVolumechannel_02 ?? 0));
                }

                $item->sales = (object) [
                    'product_0' => 0,
                    'product_01' => $sales_p1,
                    'product_02' => $sales_p2,
                ];

                /**
                 * FGI Calculation Logic
                 *
                 * 1. Start with available inventory.
                 * 2. For DC1 (plant), first reserve all outbound shipments so they cannot be used to satisfy local demand.
                 * 3. Use finished goods (P1, P2) to meet demand.
                 * 4. Convert postponed P0 units to cover any remaining shortages.
                 * 5. After consumption & conversion, whatever remains in work_stock represents post-demand available inventory.
                 * 6. If a product still has unmet demand (remaining shortage), its ending inventory must be set to zero.
                 * 7. Add delayed shipments (scheduled but not delivered) to ending inventory.
                 * 8. Save final ending inventory (P0, P1, P2).
                 */

                // Starting inventory before applying demand and conversions
                $pre_stock = (object) [
                    'p0' => (int) $item->available_inventory->product_0,
                    'p1' => (int) $item->available_inventory->product_01,
                    'p2' => (int) $item->available_inventory->product_02,
                ];

                // If plant (DC1/region1) reserve outbound shipments first so they are not available for local consumption
                if ($item->dc_number === 1 && $item->region_id === 1) {
                    // total delivered shipments out of the plant (delivered only; excludes quantity_delay)
                    $out_p0 = $this->sumTotalShipment($shipments, 0);
                    $out_p1 = $this->sumTotalShipment($shipments, 1);
                    $out_p2 = $this->sumTotalShipment($shipments, 2);

                    // working inventory for consumption/conversion = pre_stock - shipments_out (no negatives)
                    $work_stock = (object) [
                        'p0' => max(0, $pre_stock->p0 - $out_p0),
                        'p1' => max(0, $pre_stock->p1 - $out_p1),
                        'p2' => max(0, $pre_stock->p2 - $out_p2),
                    ];
                } else {
                    // Non-DC1: use full available inventory for local consumption/conversion
                    $work_stock = (object) [
                        'p0' => $pre_stock->p0,
                        'p1' => $pre_stock->p1,
                        'p2' => $pre_stock->p2,
                    ];
                }

                // Demand values (actual sales) to be satisfied this quarter
                $demand_p1 = (int) $item->sales->product_01;
                $demand_p2 = (int) $item->sales->product_02;

                // Use finished goods (P1,P2) first to satisfy demand (greedy on existing FG)
                $used_fg_p1 = min($work_stock->p1, $demand_p1);
                $used_fg_p2 = min($work_stock->p2, $demand_p2);

                // Remaining unmet demand after using finished goods
                $remaining_p1 = $demand_p1 - $used_fg_p1;
                $remaining_p2 = $demand_p2 - $used_fg_p2;

                // 🔐 Sub-assembly availability for conversion
                // Rule: availability = previous quarter ending inventory + current quarter procurement

                // 1️⃣ Previous quarter ending inventory (physically available at start of quarter)
                $prevSubAssemblyStock = InventoryStock::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id - 1,
                    'region_id' => $item->region_id,
                ])->first();

                $available_audio_modal = (int) ($prevSubAssemblyStock->audio_modal ?? 0);
                $available_control_interface = (int) ($prevSubAssemblyStock->control_interface ?? 0);

                // 2️⃣ Add current-quarter procurement arrivals (normal + emergency)
                $procurement = Procurement::with(
                    'subAssembly',
                    'procurementEmergencySub'
                )->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'region_id' => $item->region_id,
                ])->first();

                if ($procurement) {
                    // Audio Module (sub_assembly_component_id = 2)
                    $available_audio_modal +=
                      (int) optional($procurement->subAssembly)
                          ->where('sub_assembly_component_id', 2)
                          ->sum('quantity')
                      +
                      (int) optional($procurement->procurementEmergencySub)
                          ->where('sub_assembly_component_id', 2)
                          ->sum('quantity');

                    // Control Interface (sub_assembly_component_id = 1)
                    $available_control_interface +=
                      (int) optional($procurement->subAssembly)
                          ->where('sub_assembly_component_id', 1)
                          ->sum('quantity')
                      +
                      (int) optional($procurement->procurementEmergencySub)
                          ->where('sub_assembly_component_id', 1)
                          ->sum('quantity');
                }

                $alloc_p1 = 0;
                $alloc_p2 = 0;

                // Product 1 conversion → needs 1 Audio Module
                if ($remaining_p1 > 0 && $work_stock->p0 > 0 && $available_audio_modal > 0) {
                    $alloc_p1 = min(
                        $remaining_p1,
                        $work_stock->p0,
                        $available_audio_modal
                    );

                    $work_stock->p0 -= $alloc_p1;
                    $available_audio_modal -= $alloc_p1;
                }

                // Product 2 conversion → needs 1 Control Interface
                if ($remaining_p2 > 0 && $work_stock->p0 > 0 && $available_control_interface > 0) {
                    $alloc_p2 = min(
                        $remaining_p2,
                        $work_stock->p0,
                        $available_control_interface
                    );

                    $work_stock->p0 -= $alloc_p2;
                    $available_control_interface -= $alloc_p2;
                }

                // Subtract consumption and conversion from working stock
                $work_stock->p1 -= $used_fg_p1;
                $work_stock->p2 -= $used_fg_p2;

                // Conversions immediately increase availability of final products (they satisfy demand instantly)
                // $work_stock->p1 += $alloc_p1;
                // $work_stock->p2 += $alloc_p2;

                // Save conversion summary for debugging/reporting (used FG, converted, remaining shortages)
                $item->used_conversion = (object) [
                    'used_fg_p1' => $used_fg_p1,
                    'used_fg_p2' => $used_fg_p2,
                    'converted_p1' => $alloc_p1,
                    'converted_p2' => $alloc_p2,
                    // remaining shortage = demand - (used FG + converted); if >0 then product ending must be 0
                    'remaining_shortage_p1' => max(0, $demand_p1 - ($used_fg_p1 + $alloc_p1)),
                    'remaining_shortage_p2' => max(0, $demand_p2 - ($used_fg_p2 + $alloc_p2)),
                ];

                // Delayed shipments (scheduled but not delivered) to be included in ending inventory
                $delayed_p0 = (int) ($item->delayed_shipments->product_0 ?? 0);
                $delayed_p1 = (int) ($item->delayed_shipments->product_01 ?? 0);
                $delayed_p2 = (int) ($item->delayed_shipments->product_02 ?? 0);

                // Determine ending inventory per product:
                $ending_p1 = max(0, (int) $work_stock->p1) + $delayed_p1;
                $ending_p2 = max(0, (int) $work_stock->p2) + $delayed_p2;

                // Postponed base (P0): whatever remains after conversions plus delayed inflows
                $ending_p0 = (int) $work_stock->p0 + $delayed_p0;

                // Ensure no negative inventory values (defensive)
                $item->ending_inventory = (object) [
                    'product_0' => max(0, $ending_p0),
                    'product_01' => max(0, $ending_p1),
                    'product_02' => max(0, $ending_p2),
                ];

                $converted_p1 = (int) ($alloc_p1 ?? 0);
                $converted_p2 = (int) ($alloc_p2 ?? 0);
                $total_converted = $converted_p1 + $converted_p2;

                if ($total_converted > 0) {
                    PostpondConversion::updateOrCreate([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => $item->region_id,
                        'dc_number' => $item->dc_number,
                    ], [
                        'converted_p1' => $converted_p1,
                        'converted_p2' => $converted_p2,
                        'total_converted' => $total_converted,
                    ]);
                }

                if ($saveToDb) {
                    InventoryStockFinishedGood::updateOrCreate(
                        [
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_id,
                            'quarter' => $quarter_id,
                            'region_id' => $item->region_id,
                            'dc_number' => $item->dc_number,
                        ],
                        [
                            'product_0' => max(0, (int) ($item->ending_inventory->product_0 ?? 0)),
                            'product_01' => max(0, (int) ($item->ending_inventory->product_01 ?? 0)),
                            'product_02' => max(0, (int) ($item->ending_inventory->product_02 ?? 0)),
                        ]
                    );
                }
            });

            return $dcCenters;
        } catch (\Exception $e) {
            Log::error('Error Finished Goods Inventory Service: '.$e->getMessage());
            // Re-throw to allow calling job to handle retry/rollback if desired
            throw $e;
        }
    }

    /**
     * Sum scheduled shipment quantities (quantity + quantity_delay) by channel and product.
     */
    private function sumScheduledShipments($shipments, $channel)
    {
        if (! $shipments) {
            return (object) ['product_0' => 0, 'product_01' => 0, 'product_02' => 0];
        }

        $flat = $shipments->flatMap(fn ($s) => $s->transportationInfo);

        $sumFor = fn ($productId) => (int) $flat
            ->where('supply_channel_id', $channel)
            ->where('product_id', $productId)
            ->sum(function ($ti) {
                return $ti->total_shipment ?? 0;
            });

        return (object) [
            'product_0' => $sumFor(0),
            'product_01' => $sumFor(1),
            'product_02' => $sumFor(2),
        ];
    }

    /**
     * Sum shipment quantities by channel and product for a set of shipments.
     */
    private function sumShipments($shipments, $channel)
    {
        if (! $shipments) {
            return (object) ['product_0' => 0, 'product_01' => 0, 'product_02' => 0];
        }

        return (object) [
            'product_0' => (int) $shipments->flatMap(fn ($s) => $s->transportationInfo)->where('supply_channel_id', $channel)->where('product_id', 0)->sum('quantity'),
            'product_01' => (int) $shipments->flatMap(fn ($s) => $s->transportationInfo)->where('supply_channel_id', $channel)->where('product_id', 1)->sum('quantity'),
            'product_02' => (int) $shipments->flatMap(fn ($s) => $s->transportationInfo)->where('supply_channel_id', $channel)->where('product_id', 2)->sum('quantity'),
        ];
    }

    /**
     * Total shipment quantity for a given product across all supply channels.
     */
    private function sumTotalShipment($shipments, $productId)
    {
        if (! $shipments) {
            return 0;
        }

        return (int) $shipments->flatMap(fn ($s) => $s->transportationInfo)->where('product_id', $productId)->sum('total_shipment');
    }
}
