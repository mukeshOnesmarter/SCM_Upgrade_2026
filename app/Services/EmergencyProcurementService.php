<?php

namespace App\Services;

use App\Models\Decisions\InventoryStock;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\Procurement;
use App\Models\ProductMaterial;
use App\Models\SubAssemblyVolumeDiscount;
use App\Models\Supplier;
use App\Models\SupplierComponent;
use Illuminate\Support\Facades\Log;

class EmergencyProcurementService
{
    public function handleEmergencyProcurement(
        int $simulationId,
        int $facultyId,
        int $quarterId,
        int $groupNumber
    ) {
        try {
            $supplier = Supplier::find(4);
            if (! $supplier) {
                Log::warning('Emergency supplier not found (ID: 4)');

                return;
            }

            $procurement = Procurement::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
            ])->first();

            if (! $procurement) {
                Log::warning("Procurement record not found for group {$groupNumber}");

                return;
            }

            $manufacturingPlans = Manufacturing::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
            ])->get();

            /** BEGINNING INVENTORY (same logic as ProcurementService) */
            $inventory = InventoryStock::where([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId - 1,
                'region_id' => $procurement->region_id,
            ])->first();

            $beginningInventory = (object) [
                'plastic' => max(0, $inventory->plastic ?? 0),
                'casing' => max(0, $inventory->casing ?? 0),
                'control_interface' => max(0, $inventory->control_interface ?? 0),
                'audio_modal' => max(0, $inventory->audio_modal ?? 0),
                'motherboard' => max(0, $inventory->motherboard ?? 0),
            ];

            $this->handleRawMaterials(
                $procurement,
                $supplier,
                $manufacturingPlans,
                $beginningInventory
            );

            $this->handleSubAssemblies(
                $procurement,
                $supplier,
                $manufacturingPlans,
                $beginningInventory
            );
        } catch (\Throwable $e) {
            Log::error('Emergency Procurement Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /* ================= RAW MATERIALS ================= */
    private function handleRawMaterials(
        Procurement $procurement,
        Supplier $supplier,
        $manufacturingPlans,
        object $beginningInventory
    ) {
        $rawMaterialNeeds = [];

        foreach ($manufacturingPlans as $plan) {
            $materials = ProductMaterial::where('product_id', $plan->product_id)
                ->where('type', 'raw_material')
                ->get();

            foreach ($materials as $material) {
                $rawMaterialNeeds[$material->material_id] =
                    ($rawMaterialNeeds[$material->material_id] ?? 0)
                    + ($plan->production_quantity * $material->quantity);
            }
        }

        foreach ($rawMaterialNeeds as $materialId => $requiredQty) {

            // BEGINNING INVENTORY
            if ($materialId == 1) {
                $inventoryQty = $beginningInventory->plastic;
            } elseif ($materialId == 2) {
                $inventoryQty = $beginningInventory->casing;
            } else {
                $inventoryQty = 0;
            }

            $procuredQty = $procurement->rawMaterial()
                ->where('raw_material_id', $materialId)
                ->sum('quantity');

            $emergencyQty = $procurement->procurementEmergencyRaw()
                ->where('raw_material_id', $materialId)
                ->sum('quantity');

            $shortfall = $requiredQty - (
                $inventoryQty +
                $procuredQty +
                $emergencyQty
            );

            if ($shortfall > 0) {
                $procurement->procurementEmergencyRaw()->create([
                    'raw_material_id' => $materialId,
                    'supplier_id' => $supplier->id,
                    'quantity' => $shortfall,
                    'remarks' => 'Auto Emergency Procurement due to shortfall',
                ]);
            }
        }
    }

    /* ================= SUB ASSEMBLIES ================= */
    private function handleSubAssemblies(
        Procurement $procurement,
        Supplier $supplier,
        $manufacturingPlans,
        object $beginningInventory
    ) {
        $subAssemblyNeeds = [];

        foreach ($manufacturingPlans as $plan) {
            $components = ProductMaterial::where('product_id', $plan->product_id)
                ->where('type', 'sub_assembly_component')
                ->get();

            foreach ($components as $component) {
                $subAssemblyNeeds[$component->material_id] =
                    ($subAssemblyNeeds[$component->material_id] ?? 0)
                    + ($plan->production_quantity * $component->quantity);
            }
        }

        foreach ($subAssemblyNeeds as $componentId => $requiredQty) {

            // BEGINNING INVENTORY
            if ($componentId == 1) {
                $inventoryQty = $beginningInventory->control_interface;
            } elseif ($componentId == 2) {
                $inventoryQty = $beginningInventory->audio_modal;
            } elseif ($componentId == 3) {
                $inventoryQty = $beginningInventory->motherboard;
            } else {
                $inventoryQty = 0;
            }

            $procuredQty = $procurement->subAssembly()
                ->where('sub_assembly_component_id', $componentId)
                ->sum('quantity');

            $emergencyQty = $procurement->procurementEmergencySub()
                ->where('sub_assembly_component_id', $componentId)
                ->sum('quantity');

            $shortfall = $requiredQty - (
                $inventoryQty +
                $procuredQty +
                $emergencyQty
            );

            if ($shortfall <= 0) {
                continue;
            }

            $supplierComponent = SupplierComponent::where([
                'supplier_id' => $supplier->id,
                'sub_assembly_component_id' => $componentId,
            ])->first();

            if (! $supplierComponent) {
                Log::warning("Supplier component config missing for component {$componentId}");

                continue;
            }

            $actualPercentage = $this->calculateActualDeliveryPercentage($supplierComponent);
            $totalShipment = $shortfall;
            $quantityDelivered = (int) round($totalShipment * $actualPercentage);
            $quantityDelay = $totalShipment - $quantityDelivered;

            $finalTotalVolume = $procuredQty + $quantityDelivered;
            $discount = $this->getVolumeDiscount($componentId, $finalTotalVolume);

            $baseCost = $supplierComponent->cost;
            $discountedCost = $baseCost - ($baseCost * $discount / 100);

            $procurement->procurementEmergencySub()->create([
                'sub_assembly_component_id' => $componentId,
                'supplier_id' => $supplier->id,
                'supply_channel_id' => 1,
                'quantity' => $quantityDelivered,
                'total_shipment' => $totalShipment,
                'quantity_delay' => $quantityDelay,
                'discount_applied' => $discount,
                'final_cost' => $discountedCost,
            ]);
        }
    }

    /* ================= HELPERS ================= */
    private function calculateActualDeliveryPercentage($supplierComponent)
    {
        $expected = $supplierComponent->expected_value;
        $variation = $supplierComponent->possible_variation;

        $minFactor = ($expected - $variation) / 100;
        $maxFactor = ($expected + $variation) / 100;

        return rand($minFactor * 100, $maxFactor * 100) / 100;
    }

    private function getVolumeDiscount($componentId, $totalVolume)
    {
        $discount = 0;

        $discounts = SubAssemblyVolumeDiscount::where('sub_assembly_component_id', $componentId)
            ->orderBy('volume', 'asc')
            ->get();

        foreach ($discounts as $entry) {
            if ($totalVolume >= $entry->volume) {
                $discount = $entry->discount;
            }
        }

        return $discount;
    }
}
