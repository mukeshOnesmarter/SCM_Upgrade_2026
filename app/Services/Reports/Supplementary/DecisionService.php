<?php

namespace App\Services\Reports\Supplementary;

use App\Models\Carrier;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\Transportation;
use App\Models\RawMaterial;
use App\Models\Region;
use App\Models\SubAssemblyComponent;
use App\Models\Supplier;
use App\Services\ActiveUserService;
use Illuminate\Support\Facades\Log;

class DecisionService
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    public function decisionReport(int $simulation_id, int $faculty_id, int $quarter_id, int $group_id)
    {
        try {

            $dcCenters = Distribution::with('region')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                ])->get(['region_id', 'dc_number']);

            $procurement = Procurement::with('subAssembly', 'rawMaterial')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                ])->get();

            // Raw Material Procurement for Region 1 (Global Summary)
            $region1Procurement = $procurement->where('region_id', 1)->first();

            $procurementRawMaterial = RawMaterial::all()
                ->map(function ($component) use ($region1Procurement) {
                    $component->quantity = $region1Procurement && $region1Procurement->rawMaterial
                      ? $region1Procurement->rawMaterial->where('raw_material_id', $component->id)->sum('quantity')
                      : null;

                    return $component;
                });

            // Sub-Assembly Procurement per DC respecting Region
            $procurementSubAssembly = $dcCenters->map(function ($dc) use ($procurement) {
                $procurementData = $procurement->where('region_id', $dc->region_id)->first();

                $dc->subAssembly = SubAssemblyComponent::all()->map(function ($component) use ($procurementData) {
                    $component->suppliers = Supplier::all()->map(function ($supplier) use ($procurementData, $component) {
                        $componentQuantity = $procurementData && $procurementData->subAssembly
                          ? $procurementData->subAssembly->where('sub_assembly_component_id', $component->id)
                              ->where('supplier_id', $supplier->id)
                              ->sum('quantity')
                          : null;
                        $supplier->procurement = $componentQuantity;

                        return $supplier;
                    });

                    return $component;
                });

                return $dc;
            });

            // Manufacturing Data
            $productManufacturing = Manufacturing::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
            ])->get();

            $manufacturing = (object) [
                'Production' => (object) [
                    'product_0' => optional($productManufacturing->firstWhere('product_id', 0))->production_quantity,
                    'product_1' => optional($productManufacturing->firstWhere('product_id', 1))->production_quantity,
                    'product_2' => optional($productManufacturing->firstWhere('product_id', 2))->production_quantity,
                ],
                'emergency_production' => (object) [
                    'product_0' => optional($productManufacturing->firstWhere('product_id', 0))->emergency_production,
                    'product_1' => optional($productManufacturing->firstWhere('product_id', 1))->emergency_production,
                    'product_2' => optional($productManufacturing->firstWhere('product_id', 2))->emergency_production,
                ],
                'production_flexibility' => (object) [
                    'product_0' => optional($productManufacturing->firstWhere('product_id', 0))->adjustable_production,
                    'product_1' => optional($productManufacturing->firstWhere('product_id', 1))->adjustable_production,
                    'product_2' => optional($productManufacturing->firstWhere('product_id', 2))->adjustable_production,
                ],
            ];

            // Shipments Data
            $shipments = Transportation::with('transportationInfo')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                ])->get();

            $shipments->map(function ($shipment) {
                $shipment->carriers = Carrier::all()->map(function ($carrier) use ($shipment) {
                    $carrier->products = (object) [
                        'product_0' => $shipment->transportationInfo
                          ? $shipment->transportationInfo->where('carrier_id', $carrier->id)->where('product_id', 0)->sum('quantity')
                          : null,
                        'product_1' => $shipment->transportationInfo
                          ? $shipment->transportationInfo->where('carrier_id', $carrier->id)->where('product_id', 1)->sum('quantity')
                          : null,
                        'product_2' => $shipment->transportationInfo
                          ? $shipment->transportationInfo->where('carrier_id', $carrier->id)->where('product_id', 2)->sum('quantity')
                          : null,
                    ];

                    return $carrier;
                });
            });

            return (object) [
                'procurementRawMaterial' => $procurementRawMaterial,
                'procurementSubAssembly' => $procurementSubAssembly,
                'manufacturing' => $manufacturing,
                'shipments' => $shipments,
            ];
        } catch (\Exception $e) {
            Log::error('Error Decision Service: '.$e->getMessage());

            return null;
        }
    }
}
