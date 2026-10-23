<?php

namespace App\Services\Reports;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InventoryStockFinishedGood;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\PostpondProduction;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\ProcurementSubAssembly;
use App\Models\DemandChannel;
use App\Models\Packaging;
use App\Models\PostpondConversion;
use App\Models\Product;
use App\Models\RawMaterialCost;
use App\Models\Region;
use App\Models\Supplier;
use App\Models\SupplierComponent;
use App\Services\ActiveUserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Component;

class ProductPnlService
{
    protected $activeUserService;

    protected array $allocationCache = [];

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    public function productPnl($simulation_id, $faculty_id, $group_id, $quarter_id)
    {
        $forecastDemand = Forecasting::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();

        $dcCenters = Distribution::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();

        $procurement = Procurement::with('subAssembly')->where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->first();

        $demandChannel = DemandChannel::all();
        $dutiesTariffs = [0, 8, 12];

        $productPricingAll = DemandPlanning::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();

        $products = Product::all()->each(function ($product) use (
            $dcCenters,
            $demandChannel,
            $dutiesTariffs,
            $productPricingAll,
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id
        ) {

            $product_id = $product->id;

            /* ============================================================
     | 1️⃣ MARKET DEMAND (used ONLY for allocation ratios)
     ============================================================ */
            $productSale = ActualDemand::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
                'product_id' => $product_id,
            ])->get();

            $manufacturing = Manufacturing::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
            ])->get();

            /* ============================================================
     | 2️⃣ CORPORATE UNFILLED / ALLOCATION ENGINE (COPIED)
     ============================================================ */

            $alloc = $this->getProductAllocation(
                $product_id,
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id,
                $dcCenters,
                $manufacturing,
                $productSale
            );

            /* ============================================================
     | 3️⃣ PRODUCT-LEVEL TOTALS (AUTHORITATIVE)
     ============================================================ */
            $product->unfilledUnits = $alloc['unfilled'];

            $product->fulfilledUnits = collect($alloc['alloc_map'])
                ->sum(fn ($r) => $r['allocated']);

            $product->totalSalesVolume = $product->fulfilledUnits;

            /* ============================================================
     | 4️⃣ PRICING
     ============================================================ */
            $productPricing = $productPricingAll->where('product_id', $product_id);

            /* ============================================================
     | 5️⃣ REGIONS
     ============================================================ */
            $product->marketRegion = Region::all()->map(function ($region) use (
                $productSale,
                $alloc,
                $demandChannel,
                $productPricing,
                $dutiesTariffs,
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id,
                $product_id
            ) {

                $region_id = $region->id;

                $sale = $productSale->firstWhere('region_id', $region_id);

                $rawRetail = (int) ($sale->demand_channel_01 ?? 0);
                $rawDirect = (int) ($sale->demand_channel_02 ?? 0);
                $rawTotal = $rawRetail + $rawDirect;

                $allocRow = $alloc['alloc_map'][$region_id] ?? ['allocated' => 0];
                $allocated = (int) $allocRow['allocated'];

                /* --- Channel split (NO ratio drift) --- */
                if ($rawTotal > 0) {
                    $region->salesVolumechannel_01 = (int) round($allocated * ($rawRetail / $rawTotal));
                    $region->salesVolumechannel_02 = $allocated - $region->salesVolumechannel_01;
                } else {
                    $region->salesVolumechannel_01 = 0;
                    $region->salesVolumechannel_02 = 0;
                }

                /* ========================================================
                 | Blade-safe collections
                 ======================================================== */

                // Product Active
                $region->productActive = $demandChannel->map(function ($channel) use ($productPricing, $region) {
                    $ch = clone $channel;
                    $ch->productStatus =
                      $productPricing
                          ->where('region_id', $region->id)
                          ->firstWhere('demand_channel_id', $channel->id)
                          ->status ?? 'N/A';

                    return $ch;
                });

                // Sales Volume
                $region->productSalesVolume = $demandChannel->map(function ($channel) use ($region) {
                    $ch = clone $channel;
                    $ch->salesVolume = $channel->id === 1
                      ? $region->salesVolumechannel_01
                      : $region->salesVolumechannel_02;

                    return $ch;
                });

                // Price
                $region->price = $demandChannel->map(function ($channel) use ($productPricing, $region) {
                    $ch = clone $channel;
                    $ch->price =
                      $productPricing
                          ->where('region_id', $region->id)
                          ->firstWhere('demand_channel_id', $channel->id)
                          ->price ?? 0;

                    return $ch;
                });

                // Revenues
                $region->revenues = $demandChannel->map(function ($channel) use ($productPricing, $region) {
                    $ch = clone $channel;

                    $price = $productPricing
                        ->where('region_id', $region->id)
                        ->firstWhere('demand_channel_id', $channel->id)
                        ->price ?? 0;

                    $qty = $channel->id === 1
                      ? $region->salesVolumechannel_01
                      : $region->salesVolumechannel_02;

                    $ch->totalRevenues = round($qty * $price, 0);

                    return $ch;
                });

                /* ========================================================
                 | Costs
                 ======================================================== */

                $region->productCost =
                  $this->calculateProductCost(
                      $simulation_id,
                      $faculty_id,
                      $group_id,
                      $product_id,
                      $quarter_id,
                      0.5,
                      3,
                      1
                  ) * $allocated;

                $region->orderProcessing =
                  ($region->salesVolumechannel_01 * 4) +
                  ($region->salesVolumechannel_02 * 24);

                $region->dutiesTariffs = $region_id === 1
                  ? 0
                  : ($allocated *
                    $productPricing->where('region_id', $region_id)->avg('price') *
                    ($dutiesTariffs[$region_id - 1] / 100));

                $region->replacementParts = 0; // already included via corporate allocation

                $region->totalExpenses =
                  $region->productCost +
                  $region->orderProcessing +
                  $region->dutiesTariffs;

                $region->grossMargin =
                  $region->revenues->sum('totalRevenues') - $region->totalExpenses;

                $region->totalFixedCosts = 0;
                $region->operatingIncome = $region->grossMargin;

                return $region;
            });

            /* ============================================================
     | 6️⃣ PRODUCT TOTALS
     ============================================================ */
            $product->totalRevenue = $product->marketRegion
                ->sum(fn ($r) => $r->revenues->sum('totalRevenues'));

            $product->grossMargin = $product->marketRegion->sum('grossMargin');
            $product->totalExpenses = $product->marketRegion->sum('totalExpenses');
            $product->operatingIncome = $product->grossMargin;

            // Backward compatibility
            $product->revenue = $product->totalRevenue;
        });

        return $products;
    }

    /**
     * Calculate marketing budget for a product in a specific region and channel.
     *
     * @param  float  $initialBudget  The original marketing spending budget.
     * @param  float  $proposedBudget  The new proposed budget by the user.
     * @return float The adjusted budget within the allowed corporate policy range.
     */
    public function calculateMarketingBudget(
        float $initialBudget,
        float $proposedBudget
    ): float {

        // Define the min and max allowed budget values
        $minBudget = max(0, $initialBudget - 100000);
        $maxBudget = $initialBudget + 100000;

        // Clamp the proposed budget within the allowable range
        if ($proposedBudget < $minBudget) {
            return $minBudget;
        } elseif ($proposedBudget > $maxBudget) {
            return $maxBudget;
        }

        return $proposedBudget;
    }

    /**
     * Calculate total product cost based on configuration.
     *
     * @param  float  $rawMaterialsCost
     * @param  float  $componentsCost
     * @param  float  $laborCost
     * @param  float  $productionCost
     * @param  int  $terahertzLevel  (1-7)
     * @param  int|null  $warrantyMonths  Set to null if no warranty
     * @param  int  $packagingType  1 = Standard, 2 = Premium, 3 = Eco-premium
     */
    public function calculateProductCost(
        int $simulation_id,
        int $faculty_id,
        int $group_number,
        int $product_id,
        int $quarter_id,
        float $terahertzLevel,
        ?int $warrantyMonths,
        int $packagingType
    ): float {

        // Fetch the product along with its associated materials (raw and sub-assembly components)
        $product = Product::with('material')->find($product_id);

        // Separate raw materials and sub-assembly components from the product's materials
        $rawMaterials = optional($product)->material->where('type', 'raw_material');
        $subAssemblyComponents = optional($product)->material->where('type', 'sub_assembly_component');

        // Calculate cost for each raw material as: quantity × unit cost from RawMaterialCost table
        $rawMaterials->each(function ($material) {
            $unitCost = RawMaterialCost::where('raw_material_id', $material->material_id)->first()->cost ?? 0;
            $material->cost = $material->quantity * $unitCost;
        });

        // Calculate cost for each sub-assembly component as: quantity × unit cost from SupplierComponent
        $subAssemblyComponents->each(function ($component) use ($simulation_id, $faculty_id, $group_number, $quarter_id) {

            if ($quarter_id <= 3) {
                // Quarters 1-3: Simulation supplier (ID 4)
                $supplier = Supplier::with('supplierComponent')->find(4);

                $unitCost = optional($supplier)
                    ->supplierComponent
                    ->firstWhere('sub_assembly_component_id', $component->material_id)
                    ->cost ?? 0;

                $component->cost = $component->quantity * $unitCost;
            } elseif ($quarter_id === 4) {
                // Quarter 4: Student suppliers, use supplierComponent (not price table)
                $procurements = ProcurementSubAssembly::with('supplier.supplierComponent')
                    ->where('sub_assembly_component_id', $component->material_id)
                    ->whereHas('procurement', function ($query) use ($simulation_id, $faculty_id, $group_number, $quarter_id) {
                        $query->where([
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_number,
                            'quarter' => $quarter_id,
                        ]);
                    })
                    ->get();

                $totalQuantity = $procurements->sum('quantity');

                $weightedUnitCost = 0;
                foreach ($procurements as $entry) {
                    $supplierComponent = optional($entry->supplier->supplierComponent)
                        ->firstWhere('sub_assembly_component_id', $component->material_id);

                    $cost = $supplierComponent->cost ?? 0;
                    $share = ($totalQuantity > 0) ? $entry->quantity / $totalQuantity : 0;
                    $weightedUnitCost += $cost * $share;
                }

                $component->cost = $component->quantity * $weightedUnitCost;
            } else {
                // Quarter 5 and above: Student suppliers, use supplierComponentPrice (filtered)
                $procurements = ProcurementSubAssembly::with([
                    'supplier' => function ($query) use ($simulation_id, $faculty_id, $quarter_id) {
                        $query->with(['supplierComponentPrice' => function ($q) use ($simulation_id, $faculty_id, $quarter_id) {
                            $q->where([
                                'simulation_id' => $simulation_id,
                                'faculty_id' => $faculty_id,
                                'quarter_id' => $quarter_id,
                            ]);
                        }]);
                    },
                ])
                    ->where('sub_assembly_component_id', $component->material_id)
                    ->whereHas('procurement', function ($query) use ($simulation_id, $faculty_id, $group_number, $quarter_id) {
                        $query->where([
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_number,
                            'quarter' => $quarter_id,
                        ]);
                    })
                    ->get();

                $totalQuantity = $procurements->sum('quantity');

                $weightedUnitCost = 0;
                foreach ($procurements as $entry) {
                    $price = optional($entry->supplier->supplierComponentPrice->first())->price ?? 0;
                    $share = ($totalQuantity > 0) ? $entry->quantity / $totalQuantity : 0;
                    $weightedUnitCost += $price * $share;
                }

                $component->cost = $component->quantity * $weightedUnitCost;
            }
        });

        // Sum total cost of all raw materials
        $totalRawMaterialsCost = $rawMaterials->sum(function ($material) {
            return $material->cost;
        });

        // Sum total cost of all sub-assembly components
        $totalSubAssemblyCost = $subAssemblyComponents->sum(function ($component) {
            return $component->cost;
        });

        $laborCost = (float) $product_id == 1 ? 30 : 36;
        $productionCost = (float) $product_id == 1 ? 20 : 16;

        // Bandwidth Cost: $10 + 0.5 * (T^3)
        $bandwidthCost = 10 + pow($terahertzLevel, 3);

        // Warranty Cost
        $warrantyCost = 0;
        if (! is_null($warrantyMonths) && $warrantyMonths > 0) {
            $warrantyCost = 8 + 3 * pow($warrantyMonths, 2);
        }

        // Packaging Cost
        $packagingCost = ($quarter_id <= 3)
          ? (int) Packaging::find(1)->cost ?? 10
          : (int) Packaging::find($packagingType)->cost ?? 10;

        // Total Product Cost
        return $totalRawMaterialsCost
          + $totalSubAssemblyCost
          + $laborCost
          + $productionCost
          + $bandwidthCost
          + $warrantyCost
          + $packagingCost;
    }

    /**
     * Calculate the fixed cost penalty due to forecast inaccuracy for a specific channel.
     *
     * @param  float  $forecast  The forecasted demand quantity.
     * @param  float  $actual  The actual demand quantity.
     * @param  int  $channel  The sales channel identifier (used to apply channel-specific cost logic).
     * @return float The calculated fixed cost penalty for forecast inaccuracy.
     */
    public function calculateForecastInaccuracyFixedCost(
        float $forecast,
        float $actual,
        int $channel
    ): float {
        if ($actual == 0) {
            return 0.0; // Avoid division by zero
        }

        // Determine base cost based on channel
        if ($channel === 2) {
            $baseCost = 120000;
        } else {
            $baseCost = 80000; // Default or Channel 1
        }

        // Calculate inaccuracy rate (capped at 1.0 or 100%)
        $inaccuracyRate = abs($forecast - $actual) / $actual;
        $inaccuracyRate = min($inaccuracyRate, 1.0);

        // Forecast inaccuracy penalty
        return round($baseCost * $inaccuracyRate, 2);
    }

    /**
     * Calculate final administrative overhead based on forecast accuracy.
     *
     * Overhead increases 1% for every 1% forecast inaccuracy, capped at 100% increase.
     *
     * @param  float  $forecast  Forecasted sales units.
     * @param  float  $actual  Actual sales units.
     * @param  int  $channel  Sales channel (1 or 2).
     * @return float Final administrative overhead including penalty.
     */
    public function calculateAdministrativeOverhead(
        float $forecast,
        float $actual,
        int $channel
    ): float {
        // Set base cost per channel
        $channelCosts = [
            1 => 80000,
            2 => 120000,
        ];

        $baseCost = $channelCosts[$channel] ?? 0;

        // Absolute error percentage
        if ($actual != 0) {
            $errorRate = abs($forecast - $actual) / $actual;
        } else {
            $errorRate = 0; // Or handle differently if needed
        }

        // Cap penalty at 100%
        $errorRate = min($errorRate, 1.0);

        // Final adjusted administrative overhead
        $administrativeOH = $baseCost * (1 + $errorRate);

        return round($administrativeOH, 2);
    }

    /**
     * Calculate the cost incurred due to a product price change in a specific region/channel.
     *
     * Each price change results in:
     * - A fixed administrative cost of $10,000,
     * - A variable cost of $200 per dollar of price change (increase or decrease),
     * - An additional penalty equal to 0.25% of the current month's revenue.
     *
     * These costs are recorded as “Price Changes” in the profit-and-loss statements.
     *
     * @param  float  $oldPrice  The previous price of the product.
     * @param  float  $newPrice  The updated price of the product.
     * @param  float  $revenue  The current month's revenue for that product in the specified region/channel.
     * @return float The total cost associated with the price change.
     */
    public function calculatePriceChangeCost(
        float $oldPrice,
        float $newPrice,
        float $revenue
    ): float {
        $fixedCost = 10000;
        $costPerDollarChange = 200;
        $revenuePenaltyRate = 0.0025;

        $priceChange = abs($newPrice - $oldPrice);

        $cost = $fixedCost + ($costPerDollarChange * $priceChange) + ($revenue * $revenuePenaltyRate);

        return round($cost, 2);
    }

    /**
     * Calculate unfilled for a single product.
     *
     * @param  Collection|array  $dcCenters  // array/collection of DCs (each must have region_id, dc_number)
     * @param  array|object  $manufacturing  // manufacturing info (may contain emergency limits keyed by product id)
     * @param  Collection|array  $actualDemand  // demands preloaded (or you can query inside)
     * @param  array  &$emergencyUsed  // passed by reference, updated with usage per product
     * @param  int  $group_id
     * @return array ['unfilled' => int, 'emergency_produced' => int, 'alloc_map' => [...]]
     */
    private function calculateUnfilledForProduct(
        int $product_id,
        $dcCenters,
        $manufacturing,
        $actualDemand,
        array &$emergencyUsed,
        int $simulation_id,
        int $faculty_id,
        int $group_number,
        int $quarter_id
    ) {
        $logPrefix = "[P&L][prod:$product_id][sim:$simulation_id][grp:$group_number][q:$quarter_id]";

        // -----------------------------------------------------
        // 1) Emergency limit (correct: emergency_production)
        // -----------------------------------------------------
        $emergencyLimit = 0;

        if ($manufacturing instanceof Collection) {
            $m = $manufacturing->firstWhere('product_id', $product_id);
            if ($m) {
                $emergencyLimit = (int) ($m->emergency_production ?? 0);
            }
        } elseif (is_array($manufacturing)) {
            foreach ($manufacturing as $m) {
                $pid = is_array($m) ? ($m['product_id'] ?? null) : ($m->product_id ?? null);
                if ((int) $pid === $product_id) {
                    $emergencyLimit = (int) (
                        is_array($m) ? ($m['emergency_production'] ?? 0) : ($m->emergency_production ?? 0)
                    );
                    break;
                }
            }
        }

        // -----------------------------------------------------
        // 2) Demand by region
        // -----------------------------------------------------
        $demandByRegion = [];
        foreach ($actualDemand as $d) {
            if ((int) $d->product_id !== $product_id) {
                continue;
            }
            $r = (int) $d->region_id;
            $qty = (int) $d->demand_channel_01 + (int) $d->demand_channel_02;
            $demandByRegion[$r] = ($demandByRegion[$r] ?? 0) + $qty;
        }

        // -----------------------------------------------------
        // 3) Beginning inventory (previous quarter ending)
        // -----------------------------------------------------
        $column = [0 => 'product_0', 1 => 'product_01', 2 => 'product_02'][$product_id] ?? null;
        if (! $column) {
            throw new \Exception("Unknown product id: $product_id");
        }

        $prevQ = max(1, $quarter_id - 1);

        $beginRows = InventoryStockFinishedGood::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $prevQ,
        ])->get(['region_id', 'dc_number', $column]);

        $availableByDc = [];
        foreach ($beginRows as $r) {
            $key = "{$r->region_id}::{$r->dc_number}";
            $availableByDc[$key] = ($availableByDc[$key] ?? 0) + (int) $r->{$column};
        }

        // -----------------------------------------------------
        // 4) Add Postponed Production (P0 → P1/P2 inflow)
        // -----------------------------------------------------
        $ppRows = PostpondProduction::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
            'product_id' => $product_id,
        ])->get();

        foreach ($ppRows as $p) {
            $key = "{$p->region_id}::{$p->dc_number}";
            $availableByDc[$key] = ($availableByDc[$key] ?? 0) + (int) $p->quantity;
        }

        // -----------------------------------------------------
        // 5) Add Conversions (Postpond → Finished)
        // -----------------------------------------------------
        $convRows = PostpondConversion::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_number,
            'quarter' => $quarter_id,
        ])->get();

        foreach ($convRows as $c) {
            $key = "{$c->region_id}::{$c->dc_number}";
            $qty = ($product_id == 1) ? (int) $c->converted_p1 : (($product_id == 2) ? (int) $c->converted_p2 : 0);

            $availableByDc[$key] = ($availableByDc[$key] ?? 0) + $qty;
        }

        // -----------------------------------------------------
        // 6) Add Transportation Arrivals (TransportationInfo)
        // -----------------------------------------------------
        try {
            if (Schema::hasTable('transportation_info')) {
                $ship = DB::table('transportation_info')
                    ->where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_number,
                        'quarter' => $quarter_id,
                        'product_id' => $product_id,
                    ])->get();

                foreach ($ship as $s) {
                    $key = "{$s->region_id}::{$s->dc_number}";
                    $arrived = (int) ($s->arrived_quantity ?? $s->arrived_qty ?? $s->delivered_qty ?? 0);
                    if ($arrived > 0) {
                        $availableByDc[$key] = ($availableByDc[$key] ?? 0) + $arrived;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("$logPrefix transportation arrival read error: ".$e->getMessage());
        }

        // -----------------------------------------------------
        // 7) Add Emergency Shipments
        // -----------------------------------------------------
        // -----------------------------------------------------
        // 7) Add Emergency Shipments (Plant → DC1)
        // -----------------------------------------------------
        try {
            if (Schema::hasTable('emergency_shipments')) {

                $es = DB::table('emergency_shipments')
                    ->where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_id' => $group_number,
                        'quarter' => $quarter_id,
                        'product_id' => $product_id,
                    ])->get();

                // 🔍 Find Plant DC (DC1)
                $plantDc = 1;
                $plantRegion = null;

                foreach ($dcCenters as $dc) {
                    if ((int) $dc->dc_number === $plantDc) {
                        $plantRegion = (int) $dc->region_id;
                        break;
                    }
                }

                // 🧠 Inject emergency shipments into plant DC
                if ($plantRegion !== null) {
                    $plantKey = "{$plantRegion}::{$plantDc}";

                    foreach ($es as $e) {
                        $q = (int) ($e->quantity ?? $e->qty ?? 0);

                        if ($q > 0) {
                            $availableByDc[$plantKey] = ($availableByDc[$plantKey] ?? 0) + $q;
                        }
                    }
                } else {
                    Log::warning("$logPrefix plant region not found while applying emergency shipments");
                }
            }
        } catch (\Exception $e) {
            Log::warning("$logPrefix emergency shipments read error: ".$e->getMessage());
        }

        // -----------------------------------------------------
        // 8) Add Plant Production
        // -----------------------------------------------------
        $plantDc = 1;
        $plantRegion = null;

        foreach ($dcCenters as $dc) {
            if ((int) $dc->dc_number === $plantDc) {
                $plantRegion = (int) $dc->region_id;
                break;
            }
        }

        if ($plantRegion !== null) {
            $prodQty = 0;

            if ($manufacturing instanceof Collection) {
                $m = $manufacturing->firstWhere('product_id', $product_id);
                if ($m) {
                    $prodQty = (int) ($m->production_quantity ?? 0);
                }
            } else {
                foreach ($manufacturing as $m) {
                    $pid = is_array($m) ? ($m['product_id'] ?? null) : ($m->product_id ?? null);
                    if ((int) $pid === $product_id) {
                        $prodQty = (int) (
                            is_array($m) ? ($m['production_quantity'] ?? 0) : ($m->production_quantity ?? 0)
                        );
                        break;
                    }
                }
            }

            if ($prodQty > 0) {
                $key = "{$plantRegion}::{$plantDc}";
                $availableByDc[$key] = ($availableByDc[$key] ?? 0) + $prodQty;
            }
        }

        // -----------------------------------------------------
        // 9) Convert DC → Region
        // -----------------------------------------------------
        $availableByRegion = [];
        foreach ($availableByDc as $key => $qty) {
            [$r, $dc] = explode('::', $key);
            $r = (int) $r;
            $availableByRegion[$r] = ($availableByRegion[$r] ?? 0) + $qty;
        }

        // Region list
        $regionList = array_unique(array_merge(
            array_map(fn ($d) => (int) $d->region_id, $dcCenters->all()),
            array_keys($availableByRegion),
            array_keys($demandByRegion)
        ));

        foreach ($regionList as $r) {
            $availableByRegion[$r] = $availableByRegion[$r] ?? 0;
            $demandByRegion[$r] = $demandByRegion[$r] ?? 0;
        }

        // -----------------------------------------------------
        // 10) Allocation Engine
        // -----------------------------------------------------
        $allocMap = [];
        $totalUnfilled = 0;
        $emergencyProduced = 0;

        foreach ($regionList as $r) {

            $need = $demandByRegion[$r];
            $fromFgi = $fromConv = $fromPP = $fromE = 0;

            // FGI
            $take = min($availableByRegion[$r], $need);
            $fromFgi += $take;
            $availableByRegion[$r] -= $take;
            $need -= $take;

            // Conversions
            // (We already added conversions into FGI pool, so this is empty by design)

            // Postpond
            // (Already merged into availableByDc → availableByRegion)

            // Inter-region
            if ($need > 0) {
                foreach ($availableByRegion as $r2 => $qty) {
                    if ($need <= 0 || $r2 == $r) {
                        continue;
                    }
                    $take = min($qty, $need);
                    if ($take > 0) {
                        $fromFgi += $take;
                        $availableByRegion[$r2] -= $take;
                        $need -= $take;
                    }
                }
            }

            // Emergency Production
            if ($need > 0) {
                $used = $emergencyUsed[$product_id] ?? 0;
                $canUse = max(0, $emergencyLimit - $used);

                $take = min($canUse, $need);
                if ($take > 0) {
                    $fromE += $take;
                    $emergencyUsed[$product_id] = $used + $take;
                    $emergencyProduced += $take;
                    $need -= $take;
                }
            }

            $unfilled = max(0, $need);
            $totalUnfilled += $unfilled;

            $allocMap[$r] = [
                'demand' => $demandByRegion[$r],
                'allocated' => $demandByRegion[$r] - $unfilled,
                'from_fgi' => $fromFgi,
                'from_conversion' => 0,
                'from_postpond' => 0,
                'from_emergency' => $fromE,
                'unfilled' => $unfilled,
            ];
        }

        // Debug:
        // Log::debug("$logPrefix Final Available = ".array_sum($availableByRegion)." | Total Unfilled = $totalUnfilled");

        return [
            'unfilled' => $totalUnfilled,
            'emergency_produced' => $emergencyProduced,
            'alloc_map' => $allocMap,
        ];
    }

    /**
     * Run allocation ONCE per product and reuse it.
     */
    protected function getProductAllocation(
        int $product_id,
        int $simulation_id,
        int $faculty_id,
        int $group_id,
        int $quarter_id,
        $dcCenters,
        $manufacturing,
        $productSale
    ): array {

        $cacheKey = implode('|', [
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id,
            $product_id,
        ]);

        if (isset($this->allocationCache[$cacheKey])) {
            return $this->allocationCache[$cacheKey];
        }

        // Emergency usage is tracked per product per quarter.
        // Allocation cache ensures this runs only once per product.
        $emergencyUsed = [];

        $alloc = $this->calculateUnfilledForProduct(
            $product_id,
            $dcCenters,
            $manufacturing,
            $productSale,
            $emergencyUsed,
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id
        );

        return $this->allocationCache[$cacheKey] = $alloc;
    }

    /**
     * Fulfilled (actual sold) units split by product, region, and channel.
     *
     * @return array
     *               [
     *               product_id => [
     *               region_id => [
     *               1 => retail_fulfilled_units,
     *               2 => direct_fulfilled_units,
     *               ]
     *               ]
     *               ]
     */
    public function getFulfilledSalesByRegionChannel(
        int $simulation_id,
        int $faculty_id,
        int $group_id,
        int $quarter_id
    ): array {

        $result = [];

        $products = Product::all();
        $regions = Region::all();

        $actualDemand = ActualDemand::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();

        $manufacturing = Manufacturing::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();

        $dcCenters = Distribution::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
        ])->get();

        foreach ($products as $product) {

            $product_id = $product->id;
            $productSale = $actualDemand->where('product_id', $product_id);

            // ✅ Reused allocation (cached)
            $alloc = $this->getProductAllocation(
                $product_id,
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id,
                $dcCenters,
                $manufacturing,
                $productSale
            );

            foreach ($regions as $region) {

                $region_id = $region->id;
                $sale = $productSale->firstWhere('region_id', $region_id);

                $rawRetail = (int) ($sale->demand_channel_01 ?? 0);
                $rawDirect = (int) ($sale->demand_channel_02 ?? 0);
                $rawTotal = $rawRetail + $rawDirect;

                $allocated = (int) ($alloc['alloc_map'][$region_id]['allocated'] ?? 0);

                if ($rawTotal > 0) {
                    $retail = (int) round($allocated * ($rawRetail / $rawTotal));
                    $direct = $allocated - $retail;
                } else {
                    $retail = 0;
                    $direct = 0;
                }

                $result[$product_id][$region_id] = [
                    1 => $retail,  // Retail
                    2 => $direct,  // Direct
                ];
            }
        }

        return $result;
    }
}
