<?php

namespace App\Services;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandFulfillment;
use App\Models\Decisions\EmergencyProduction;
use App\Models\Decisions\EmergencyShipment;
use App\Models\Decisions\InventoryStockFinishedGood;
use App\Models\Decisions\PostpondProduction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemandFulfillmentService
{
    /**
     * Generate FINAL demand fulfillment snapshot
     * for a given simulation / faculty / group / quarter.
     *
     * This method is:
     * - READ ONLY on demand & inventory
     * - WRITE ONLY on demand_fulfillments
     * - SAFE for re-runs
     */
    public function generateForGroupQuarter(
        int $simulationId,
        int $facultyId,
        int $groupNumber,
        int $quarter
    ): void {
        DB::beginTransaction();

        try {
            /*
             |--------------------------------------------------------------------------
             | 1️⃣ Clear previous snapshot (idempotency)
             |--------------------------------------------------------------------------
             */
            DemandFulfillment::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarter,
            ])->delete();

            /*
             |--------------------------------------------------------------------------
             | 2️⃣ Identify DC slices (fallback to region-level if needed)
             |--------------------------------------------------------------------------
             */
            $dcRows = InventoryStockFinishedGood::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarter,
            ])->get();

            if ($dcRows->isEmpty()) {
                // fallback: create logical DC slices from ActualDemand regions
                $regions = ActualDemand::where([
                    'simulation_id' => $simulationId,
                    'faculty_id' => $facultyId,
                    'group_number' => $groupNumber,
                    'quarter' => $quarter,
                ])->distinct()->pluck('region_id')->filter()->values();

                $dcRows = collect();
                foreach ($regions as $regionId) {
                    $row = new InventoryStockFinishedGood;
                    $row->region_id = $regionId;
                    $row->dc_number = 0;
                    $dcRows->push($row);
                }
            }

            /*
             |--------------------------------------------------------------------------
             | 3️⃣ Products in scope
             |--------------------------------------------------------------------------
             */
            $productIds = [0, 1, 2]; // extend if more products exist

            /*
             |--------------------------------------------------------------------------
             | 4️⃣ Build fulfillment rows
             |--------------------------------------------------------------------------
             */
            foreach ($dcRows as $dc) {
                $regionId = $dc->region_id ?? null;
                $dcNumber = $dc->dc_number ?? 0;

                foreach ($productIds as $productId) {

                    /*
                     |--------------------------------------------------------------------------
                     | Demand (READ ONLY)
                     |--------------------------------------------------------------------------
                     */
                    $demand = (int) ActualDemand::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupNumber,
                        'quarter' => $quarter,
                        'product_id' => $productId,
                    ])
                        ->when($regionId !== null, fn ($q) => $q->where('region_id', $regionId))
                        ->select(DB::raw('SUM(COALESCE(demand_channel_01,0) + COALESCE(demand_channel_02,0)) as total'))
                        ->value('total');

                    if ($demand <= 0) {
                        continue;
                    }

                    /*
                     |--------------------------------------------------------------------------
                     | Supply sources (RAW)
                     |--------------------------------------------------------------------------
                     */
                    // Postponed
                    $rawPostpond = (int) PostpondProduction::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupNumber,
                        'quarter' => $quarter,
                        'product_id' => $productId,
                        'region_id' => $regionId,
                    ])->sum('quantity');

                    // Finished goods (local DC)
                    $rawFg = 0;
                    if ($dc instanceof InventoryStockFinishedGood) {
                        $col = sprintf('product_%02d', $productId);
                        if (isset($dc->$col)) {
                            $rawFg = (int) $dc->$col;
                        }
                    }

                    // Emergency shipment (plant → DC)
                    $rawPlant = (int) EmergencyShipment::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_id' => $groupNumber,
                        'quarter' => $quarter,
                        'product_id' => $productId,
                        'mode' => 'air',
                    ])->sum('quantity');

                    // Emergency production (in-house)
                    $rawEmergency = (int) EmergencyProduction::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_id' => $groupNumber,
                        'quarter' => $quarter,
                        'product_id' => $productId,
                    ])->sum('quantity');

                    /*
                     |--------------------------------------------------------------------------
                     | 5️⃣ CLAMP SUPPLY → DEMAND (CRITICAL FIX)
                     |--------------------------------------------------------------------------
                     */
                    $takenFromFg = min($rawFg, $demand);

                    $takenFromPostpond = min(
                        $rawPostpond,
                        max(0, $demand - $takenFromFg)
                    );

                    $takenFromPlant = min(
                        $rawPlant,
                        max(0, $demand - $takenFromFg - $takenFromPostpond)
                    );

                    $producedEmergency = min(
                        $rawEmergency,
                        max(0, $demand - $takenFromFg - $takenFromPostpond - $takenFromPlant)
                    );

                    $unfilled = max(0, $demand - (
                        $takenFromFg +
                        $takenFromPostpond +
                        $takenFromPlant +
                        $producedEmergency
                    ));

                    /*
                     |--------------------------------------------------------------------------
                     | 6️⃣ Cost snapshots
                     |--------------------------------------------------------------------------
                     */
                    $productionUnitCost = null;
                    if ($producedEmergency > 0) {
                        $agg = EmergencyProduction::where([
                            'simulation_id' => $simulationId,
                            'faculty_id' => $facultyId,
                            'group_id' => $groupNumber,
                            'quarter' => $quarter,
                            'product_id' => $productId,
                        ])
                            ->select(
                                DB::raw('SUM(estimated_unit_cost * quantity) as cost'),
                                DB::raw('SUM(quantity) as qty')
                            )->first();

                        if ($agg && $agg->qty > 0) {
                            $productionUnitCost = round($agg->cost / $agg->qty, 4);
                        }
                    }

                    $airUnitCost = null;
                    if ($takenFromPlant > 0) {
                        $aggShip = EmergencyShipment::where([
                            'simulation_id' => $simulationId,
                            'faculty_id' => $facultyId,
                            'group_id' => $groupNumber,
                            'quarter' => $quarter,
                            'product_id' => $productId,
                            'mode' => 'air',
                        ])
                            ->select(
                                DB::raw('SUM(unit_freight * quantity) as cost'),
                                DB::raw('SUM(quantity) as qty')
                            )->first();

                        if ($aggShip && $aggShip->qty > 0) {
                            $airUnitCost = round($aggShip->cost / $aggShip->qty, 4);
                        }
                    }

                    /*
                     |--------------------------------------------------------------------------
                     | 7️⃣ Persist fulfillment row (FINAL TRUTH)
                     |--------------------------------------------------------------------------
                     */
                    DemandFulfillment::create([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupNumber,
                        'quarter' => $quarter,
                        'region_id' => $regionId,
                        'dc_number' => $dcNumber,
                        'product_id' => $productId,
                        'demand' => $demand,
                        'taken_from_fg' => $takenFromFg,
                        'taken_from_postpond' => $takenFromPostpond,
                        'taken_from_plant' => $takenFromPlant,
                        'produced_emergency' => $producedEmergency,
                        'unfilled' => $unfilled,
                        'unit_price' => null,
                        'production_unit_cost' => $productionUnitCost,
                        'air_unit_cost' => $airUnitCost,
                        'postpond_conversion_id' => null,
                        'meta' => [
                            'raw' => [
                                'fg' => $rawFg,
                                'postpond' => $rawPostpond,
                                'plant' => $rawPlant,
                                'emergency' => $rawEmergency,
                            ],
                        ],
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('DemandFulfillmentService failed', [
                'error' => $e->getMessage(),
                'sim' => $simulationId,
                'group' => $groupNumber,
                'qtr' => $quarter,
            ]);

            throw $e;
        }
    }
}
