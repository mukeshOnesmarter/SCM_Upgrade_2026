<?php

namespace App\Http\Controllers;

use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InformationTechnology;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\PostpondProduction;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\ServiceManagement;
use App\Models\Decisions\Transportation;
use App\Models\Region;
use Illuminate\Support\Facades\Log;

class StudentDecisionCompletedController extends Controller
{
    /**
     * Forcasting Closure
     */
    public function forcastingClosure($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        try {
            $forecastExists = Forecasting::with(['region', 'product', 'firm'])
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                ])->get();

            if ($forecastExists->isEmpty()) {
                return ['message' => 'No Record Saved'];
            }

            $structuredData = $forecastExists->groupBy('region_id')->map(function ($regionGroup) {
                return (object) [
                    'region' => $regionGroup->first()->region, // Get the region
                    'products' => $regionGroup->groupBy('product_id')->map(function ($productGroup) {
                        return (object) [
                            'product' => $productGroup->first()->product, // Get the product
                            'firms' => $productGroup->groupBy('firm_id')->map(function ($firmGroup) {
                                return (object) [
                                    'firm' => $firmGroup->first()->firm, // Get the firm
                                    'demand' => $firmGroup->map(fn ($item) => (object) [
                                        'demand_channel_01' => $item->demand_channel_01,
                                        'demand_channel_02' => $item->demand_channel_02,
                                    ])->values(),
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values();

            return $structuredData;
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Procurement Closure
     */
    public function procurementClosure($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        try {
            $procurementData = Procurement::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])->with(['rawMaterial', 'subAssembly'])->get();

            if ($procurementData->isEmpty()) {
                return ['message' => 'No Record Saved'];
            }

            $structuredData = $procurementData->groupBy('region_id')->map(function ($regionGroup) {
                return [
                    'rawMaterial' => $regionGroup->flatMap->rawMaterial->mapWithKeys(function ($rawMaterial) {
                        return [$rawMaterial->raw_material_id => $rawMaterial->quantity];
                    })->toArray(), // Ensure data is converted to an array

                    'subAssembly' => $regionGroup->flatMap->subAssembly->groupBy('sub_assembly_component_id')
                        ->map(function ($subAssemblyGroup) {
                            return $subAssemblyGroup->groupBy('supplier_id')->map(function ($supplierGroup) {
                                return $supplierGroup->mapWithKeys(function ($supply) {
                                    return [$supply->supply_channel_id => $supply->total_shipment];
                                })->toArray();
                            })->toArray();
                        })->toArray(),
                ];
            })->toArray();

            return $structuredData;
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Manufacturing Closure
     */
    public function manufacturingClosure($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        try {

            $manufacturing = Manufacturing::with('product')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                ])
                ->get();

            // Postponed production records
            $postponed = PostpondProduction::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])
                ->get()
                ->map(function ($item) {
                    return (object) [
                        'product_id' => 0,
                        'product' => null,
                        'production_quantity' => $item->quantity ?? 0,
                        'emergency_production' => $item->emergency_production ?? 0,
                        'adjustable_production' => $item->adjustable_production ?? 0,
                    ];
                });

            $manufacturingRecords = $manufacturing->concat($postponed);

            if ($manufacturingRecords->isEmpty()) {
                return collect(['message' => 'No Record Saved']);
            }

            return $manufacturingRecords;
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Distribution Closure
     */
    public function distributionClosure($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        try {
            $distributionExists = Distribution::with('region', 'emergencyCarrier')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                ])->get();

            if ($distributionExists->isEmpty()) {
                return collect(['message' => 'No Record Saved']);
            }

            return $distributionExists;
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Transportation Closure
     */
    public function transportationClosure($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        try {
            $transportationData = Transportation::with([
                'region',
                'info.product',
                'info.carrier',
                'info.supplyChannel',
            ])->where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])->get(['id', 'distribution_center_id']);

            if ($transportationData->isEmpty()) {
                return collect(['message' => 'No Record Saved']);
            }

            // Structure the data
            $structuredData = $transportationData->groupBy('distribution_center_id')->map(function ($transportations, $distribution_center_id) {
                return [
                    'distribution_center_id' => $distribution_center_id,
                    'region' => $transportations->first()->region, // Fetch region data
                    'products' => $transportations->flatMap->info->groupBy('product_id')->map(function ($transportationInfos, $product_id) {
                        return [
                            'product_id' => $product_id,
                            'product' => $transportationInfos->first()->product, // Fetch product details
                            'carriers' => $transportationInfos->groupBy('carrier_id')->map(function ($carrierInfos, $carrier_id) {
                                return [
                                    'carrier_id' => $carrier_id,
                                    'carrier' => $carrierInfos->first()->carrier, // Fetch carrier details
                                    'supply_channels' => $carrierInfos->map(function ($info) {
                                        return [
                                            'supply_channel_id' => $info->supply_channel_id,
                                            'quantity' => $info->total_shipment,
                                        ];
                                    })->values(),
                                ];
                            })->values(),
                        ];
                    })->values()->sortBy(function ($product) {
                        // Define custom order (1,2,0)
                        $customOrder = [1 => 1, 2 => 2, 0 => 3]; // Any other product IDs will come after these

                        return $customOrder[$product['product_id']] ?? 999;
                    })->values(), // Reset keys after sorting
                ];
            })->values();

            return $structuredData;
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Service Management Closure
     */
    public function serviceManagementClosure($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        try {
            $serviceManagementExists = ServiceManagement::with('region', 'serviceOutsourcing')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                ])->get();

            if ($serviceManagementExists->isEmpty()) {
                return collect(['message' => 'No Record Saved']);
            }

            return $serviceManagementExists;
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Demand Planning Closure
     */
    public function demandPlanningClosure($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        try {
            // Step 1: Fetch all demand planning data at once
            $demandPlanningData = DemandPlanning::with('region', 'product.industry', 'demandChannel')->where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_number,
                'quarter' => $quarter_id,
            ])->get(['region_id', 'product_id', 'demand_channel_id', 'status', 'price', 'marketSpending']);

            // Step 2: Check if data exists
            if ($demandPlanningData->isEmpty()) {
                return ['message' => 'No Record Saved'];
            }

            // Step 3: Group data properly
            $structuredData = $demandPlanningData->groupBy('region_id')->map(function ($regionGroup) {
                return [
                    'region_id' => $regionGroup->first()->region_id,
                    'regionInfo' => $regionGroup->first()->region,
                    'products' => $regionGroup->groupBy('product_id')->map(function ($productGroup) {
                        return [
                            'product_id' => $productGroup->first()->product_id,
                            'productInfo' => $productGroup->first()->product,
                            'demand_channels' => $productGroup->groupBy('demand_channel_id')->map(function ($channelGroup) {
                                // Fetch the first data entry correctly
                                $firstData = $channelGroup->first();

                                return [
                                    'demand_channel_id' => $firstData->demand_channel_id,
                                    'channelInfo' => $firstData->demandChannel,
                                    'data' => [
                                        'status' => $firstData->status,
                                        'price' => $firstData->price,
                                        'marketSpending' => $firstData->marketSpending,
                                    ],
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values();

            return $structuredData;
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }

    /**
     * Information Technology Closure
     */
    public function informationTechnologyClosure($simulation_id, $faculty_id, $group_number, $quarter_id)
    {
        try {
            $informationTechnologyExists = InformationTechnology::with(['carriers.carrier', 'suppliers.supplier.supplierComponent.subAssemblyComponent'])
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_number,
                    'quarter' => $quarter_id,
                ])->first();

            if (! $informationTechnologyExists) {
                return collect(['message' => 'No Record Saved']);
            }

            return $informationTechnologyExists;
        } catch (\Exception $e) {
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing the data.');
        }
    }
}
