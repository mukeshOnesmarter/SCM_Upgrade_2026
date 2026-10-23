<?php

namespace App\Services;

use App\Models\Decisions\ProcurementSubAssembly;
use App\Models\SupplierComponent;
use App\Models\SupplierComponentPrice;
use Illuminate\Support\Facades\Log;

class SupplierMarketShareService
{
    /**
     * Apply market rules based on supplier's market share for a given quarter.
     */
    public function applyMarketRules($simulationId, $facultyId, $quarter)
    {
        try {
            // Step 1: Fetch all procurement records for the given simulation, faculty, and quarter
            $procurements = ProcurementSubAssembly::whereHas('procurement', function ($q) use ($simulationId, $facultyId, $quarter) {
                $q->where('simulation_id', $simulationId)
                    ->where('faculty_id', $facultyId)
                    ->where('quarter', $quarter);
            })->get();

            // Step 2: Calculate total quantity ordered per component
            $totalByComponent = $procurements->groupBy('sub_assembly_component_id')->map(function ($rows) {
                return $rows->sum('quantity');
            });

            // Step 3: Load all supplier-component relationships
            $supplierComponents = SupplierComponent::with('supplier')->get();

            // Step 4: Loop through each supplier-component combination
            foreach ($supplierComponents as $supplierComponent) {
                $supplierId = $supplierComponent->supplier_id;
                $componentId = $supplierComponent->sub_assembly_component_id;

                // Get total quantity ordered for this component across all suppliers
                $totalForComponent = $totalByComponent[$componentId] ?? 0;
                if ($totalForComponent == 0) {
                    continue; // Skip components that were not ordered
                }

                // Get total quantity ordered from this supplier for this component
                $supplierQuantity = $procurements
                    ->where('supplier_id', $supplierId)
                    ->where('sub_assembly_component_id', $componentId)
                    ->sum('quantity');

                // Step 5: Calculate market share percentage
                $marketShare = ($supplierQuantity / $totalForComponent) * 100;

                // Step 6: Determine price increase and capacity constraint based on market share
                $basePrice = $this->getBasePrice($simulationId, $facultyId, $quarter, $supplierComponent);
                $priceIncrease = 0;
                $capacityConstraint = 0;

                if ($marketShare > 60) {
                    $priceIncrease = 0.05;
                    $capacityConstraint = 1;
                } elseif ($marketShare > 50) {
                    $priceIncrease = 0.05;
                } elseif ($marketShare >= 40) {
                    $priceIncrease = 0.02;
                }

                // Calculate adjusted price
                $adjustedPrice = round($basePrice * (1 + $priceIncrease));

                // Prepare extra data for logging/debugging
                $extraData = [
                    'price_adjustment_reason' => 'Market share: '.round($marketShare, 2).'%',
                    'price_increased_by' => $priceIncrease > 0 ? ($priceIncrease * 100).'%' : 'No increase',
                    'base_price' => $basePrice,
                    'final_price' => $adjustedPrice,
                ];

                // Step 7: Store updated price and market share into SupplierComponentPrice for next quarter
                SupplierComponentPrice::updateOrCreate(
                    [
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'quarter_id' => $quarter + 1,
                        'supplier_id' => $supplierId,
                        'sub_assembly_component_id' => $componentId,
                    ],
                    [
                        'adjusted_price' => $adjustedPrice,
                        'market_share' => round($marketShare, 2),
                        'has_capacity_constraint' => $capacityConstraint,
                        'extra_data' => json_encode($extraData),
                    ]
                );
            }
        } catch (\Throwable $e) {
            // Log error and rethrow
            Log::error('Supplier market share update failed', [
                'error' => $e->getMessage(),
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'quarter' => $quarter,
            ]);
            throw $e;
        }
    }

    /**
     * Get the base price for a supplier-component.
     */
    public function getBasePrice($simulationId, $facultyId, $quarter, $supplierComponent)
    {
        $previousPrice = SupplierComponentPrice::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter_id' => $quarter,
            'supplier_id' => $supplierComponent->supplier_id,
            'sub_assembly_component_id' => $supplierComponent->sub_assembly_component_id,
        ])->value('adjusted_price');

        return $previousPrice !== null ? $previousPrice : $supplierComponent->cost;
    }
}
