<?php

namespace App\Services\Reports\Supplementary;

use App\Models\Decisions\Distribution;
use App\Models\Decisions\InventoryStock;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\PostpondProduction;
use App\Models\Decisions\Procurement;
use App\Models\PostpondConversion;
use App\Models\RawMaterial;
use App\Models\ReplacementRawMaterial;
use App\Models\ReplacementSubAssembly;
use App\Models\SubAssemblyComponent;
use App\Services\ActiveUserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcurementService
{
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    /**
     * Calculates finished goods inventory for each distribution center in a given quarter.
     */
    public function procurement(int $simulation_id, int $faculty_id, int $quarter_id, int $group_id, bool $saveToDb = false)
    {
        try {

            // Fetch distribution centers for this team and quarter
            $dcCenters = Distribution::with('region')->where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
            ])->get(['region_id', 'dc_number']);

            // Fetch raw material and sub-assembly components for procurement calculation
            $rawMaterial = RawMaterial::all();
            $subAssembly = SubAssemblyComponent::all();
            $procurementInventory = $rawMaterial->concat($subAssembly);

            // Loop over each distribution center to calculate inventory values
            $dcCenters->each(function ($item) use ($simulation_id, $faculty_id, $group_id, $quarter_id, $saveToDb) {
                try {

                    $item->dc_number = (int) $item->dc_number;
                    $item->region_id = (int) $item->region_id;

                    $procurements = Procurement::with(
                        'rawMaterial',
                        'subAssembly',
                        'procurementEmergencyRaw',
                        'procurementEmergencySub'
                    )
                        ->where([
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_id,
                            'quarter' => $quarter_id,
                            'region_id' => $item->region_id,
                        ])
                        ->first();

                    if (! $procurements) {
                        $procurements = (object) [
                            'rawMaterial' => collect(),
                            'subAssembly' => collect(),
                            'procurementEmergencyRaw' => collect(),
                            'procurementEmergencySub' => collect(),
                        ];
                    }

                    // --- NEW: fetch previous quarter procurements to compute arrivals this quarter for motherboard (Epsilon)
                    // NOTE: Custom behavior requested: ALL motherboard orders (normal OR emergency)
                    // are delivered next quarter. Therefore, arrivals this quarter come from previous-quarter
                    // normal + previous-quarter emergency motherboard orders.
                    $prevMotherboardArrival = 0;
                    $prevProcurements = null;
                    if ($quarter_id > 0) {
                        $prevProcurements = Procurement::with('subAssembly', 'procurementEmergencySub')
                            ->where([
                                'simulation_id' => $simulation_id,
                                'faculty_id' => $faculty_id,
                                'group_number' => $group_id,
                                'quarter' => $quarter_id - 1,
                                'region_id' => $item->region_id,
                            ])->first();

                        if ($prevProcurements) {
                            // Include both normal and emergency previous-quarter motherboard orders as arrivals now
                            $prevNormal = optional($prevProcurements->subAssembly)
                                ->where('sub_assembly_component_id', 3)
                                ->sum('quantity') ?? 0;

                            $prevEmergency = optional($prevProcurements->procurementEmergencySub)
                                ->where('sub_assembly_component_id', 3)
                                ->sum('quantity') ?? 0;

                            $prevMotherboardArrival = $prevNormal + $prevEmergency;
                        }
                    }

                    $replacementRawMaterial = ReplacementRawMaterial::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => $item->region_id,
                    ])->get();

                    $replacementSubAssembly = ReplacementSubAssembly::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => $item->region_id,
                    ])->get();

                    $manufacturing = Manufacturing::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                    ])->get();

                    $postpondProduction = PostpondProduction::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => $item->region_id,
                    ])->get();

                    $postpondProduction_all = $postpondProduction->sum('quantity') ?? 0;
                    $postpondProduction_product_01 = $postpondProduction->where('product_id', 1)->sum('quantity') ?? 0;
                    $postpondProduction_product_02 = $postpondProduction->where('product_id', 2)->sum('quantity') ?? 0;

                    $beginningInventory = InventoryStock::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id - 1,
                        'region_id' => $item->region_id,
                    ])->first();

                    $item->beginning_inventory = (object) [
                        'plastic' => max(0, $beginningInventory->plastic ?? 0),
                        'casing' => max(0, $beginningInventory->casing ?? 0),
                        'control_interface' => max(0, $beginningInventory->control_interface ?? 0),
                        'audio_modal' => max(0, $beginningInventory->audio_modal ?? 0),
                        'motherboard' => max(0, $beginningInventory->motherboard ?? 0),
                    ];

                    // NOTE: CHANGED BEHAVIOR: All motherboard (Epsilon) orders (normal or emergency)
                    // are now treated as arriving next quarter. That means:
                    // - Current-quarter normal motherboard orders are on-order (arrive next quarter).
                    // - Current-quarter emergency motherboard orders are ALSO on-order (arrive next quarter).
                    // - Arrivals this quarter come from previous-quarter normal + previous-quarter emergency motherboard orders.

                    // Current-quarter surface purchases (normal). Set motherboard to 0 (arrives next quarter).
                    $prevSub = $prevProcurements ? $prevProcurements->subAssembly : collect();
                    $prevEmgSub = $prevProcurements ? $prevProcurements->procurementEmergencySub : collect();

                    $item->purchases_surface = (object) [
                        'plastic' => optional(optional($procurements)->rawMaterial)->firstWhere('raw_material_id', 1)->quantity ?? 0,
                        'casing' => optional(optional($procurements)->rawMaterial)->firstWhere('raw_material_id', 2)->quantity ?? 0,
                        'control_interface' => $procurements->subAssembly->filter(function ($sa) {
                            return $sa->sub_assembly_component_id == 1 && $sa->supply_channel_id == 2;
                        })->sum('quantity') ?? 0,
                        'audio_modal' => $procurements->subAssembly->filter(function ($sa) {
                            return $sa->sub_assembly_component_id == 2 && $sa->supply_channel_id == 2;
                        })->sum('quantity') ?? 0,
                        // NOTE: Motherboard surface shipments from the previous quarter are arriving this quarter.
                        'motherboard' => $prevSub->where('sub_assembly_component_id', 3)
                            ->where('supply_channel_id', 2)
                            ->sum('quantity') ?? 0,
                    ];

                    // Current-quarter air purchases (normal). Set motherboard to 0 (arrives next quarter even if air).
                    $item->purchases_air = (object) [
                        'plastic' => 0,
                        'casing' => 0,
                        'control_interface' => $procurements->subAssembly->filter(function ($sa) {
                            return $sa->sub_assembly_component_id == 1 && $sa->supply_channel_id == 1;
                        })->sum('quantity') ?? 0,
                        'audio_modal' => $procurements->subAssembly->filter(function ($sa) {
                            return $sa->sub_assembly_component_id == 2 && $sa->supply_channel_id == 1;
                        })->sum('quantity') ?? 0,
                        // NOTE: Motherboard air shipments from the previous quarter are arriving this quarter.
                        'motherboard' => $prevSub->where('sub_assembly_component_id', 3)
                            ->where('supply_channel_id', 1)
                            ->sum('quantity') ?? 0,
                    ];

                    // Emergency procurements: keep recording emergency raw/sub orders, but for motherboard
                    // we will NOT add emergency motherboard to available inventory in current quarter.
                    // Instead emergency motherboard is recorded and appears as on-order (arrive next quarter).
                    $item->purchases_emergency = (object) [
                        'plastic' => optional($procurements->procurementEmergencyRaw->firstWhere('raw_material_id', 1))->quantity ?? 0,
                        'casing' => optional($procurements->procurementEmergencyRaw->firstWhere('raw_material_id', 2))->quantity ?? 0,
                        'control_interface' => $procurements->procurementEmergencySub->where('sub_assembly_component_id', 1)->sum('quantity') ?? 0,
                        'audio_modal' => $procurements->procurementEmergencySub->where('sub_assembly_component_id', 2)->sum('quantity') ?? 0,
                        // NOTE: emergency Motherboard air shipments from the previous quarter are arriving this quarter.
                        'motherboard' => $prevEmgSub->where('sub_assembly_component_id', 3)
                            ->where('supply_channel_id', 1)
                            ->sum('quantity') ?? 0,
                    ];

                    // Build available inventory. For motherboard, include:
                    // - beginning inventory
                    // - arrivals from previous quarter normal + previous quarter emergency orders
                    // (we do NOT include current-quarter emergency motherboard here)
                    $item->available_inventory = (object) [
                        'plastic' => $item->beginning_inventory->plastic +
                          $item->purchases_surface->plastic +
                          $item->purchases_air->plastic +
                          $item->purchases_emergency->plastic,

                        'casing' => $item->beginning_inventory->casing +
                          $item->purchases_surface->casing +
                          $item->purchases_air->casing +
                          $item->purchases_emergency->casing,

                        'control_interface' => $item->beginning_inventory->control_interface +
                          $item->purchases_surface->control_interface +
                          $item->purchases_air->control_interface +
                          $item->purchases_emergency->control_interface,

                        'audio_modal' => $item->beginning_inventory->audio_modal +
                          $item->purchases_surface->audio_modal +
                          $item->purchases_air->audio_modal +
                          $item->purchases_emergency->audio_modal,
                        'motherboard' => $item->beginning_inventory->motherboard +
                          $item->purchases_surface->motherboard +
                          $item->purchases_air->motherboard +
                          $item->purchases_emergency->motherboard,
                    ];

                    $item->production = [
                        (object) [
                            'product_id' => 1,
                            'product_name' => 'Smart Home Assistant',
                            'plastic' => ($manufacturing->firstWhere('product_id', 1)->production_quantity ?? 0) * 5,
                            'casing' => ($manufacturing->firstWhere('product_id', 1)->production_quantity ?? 0) * 5,
                            'control_interface' => 0,
                            'audio_modal' => ($manufacturing->firstWhere('product_id', 1)->production_quantity ?? 0) * 1,
                            'motherboard' => ($manufacturing->firstWhere('product_id', 1)->production_quantity ?? 0) * 1,
                        ],
                        (object) [
                            'product_id' => 2,
                            'product_name' => 'Smart Thermostat',
                            'plastic' => ($manufacturing->firstWhere('product_id', 2)->production_quantity ?? 0) * 5,
                            'casing' => ($manufacturing->firstWhere('product_id', 2)->production_quantity ?? 0) * 5,
                            'control_interface' => ($manufacturing->firstWhere('product_id', 2)->production_quantity ?? 0) * 1,
                            'audio_modal' => 0,
                            'motherboard' => ($manufacturing->firstWhere('product_id', 2)->production_quantity ?? 0) * 1,
                        ],
                    ];

                    $item->emergency_production = [
                        (object) [
                            'product_id' => 1,
                            'product_name' => 'Smart Home Assistant',
                            'plastic' => ($manufacturing->firstWhere('product_id', 1)->emergency_production ?? 0) * 5,
                            'casing' => ($manufacturing->firstWhere('product_id', 1)->emergency_production ?? 0) * 5,
                            'control_interface' => 0,
                            'audio_modal' => ($manufacturing->firstWhere('product_id', 1)->emergency_production ?? 0) * 1,
                            'motherboard' => ($manufacturing->firstWhere('product_id', 1)->emergency_production ?? 0) * 1,
                        ],
                        (object) [
                            'product_id' => 2,
                            'product_name' => 'Smart Thermostat',
                            'plastic' => ($manufacturing->firstWhere('product_id', 2)->emergency_production ?? 0) * 5,
                            'casing' => ($manufacturing->firstWhere('product_id', 2)->emergency_production ?? 0) * 5,
                            'control_interface' => ($manufacturing->firstWhere('product_id', 2)->emergency_production ?? 0) * 1,
                            'audio_modal' => 0,
                            'motherboard' => ($manufacturing->firstWhere('product_id', 2)->emergency_production ?? 0) * 1,
                        ],
                    ];

                    $item->postponed_production = (object) [
                        'plastic' => $postpondProduction_all * 9,
                        'casing' => $postpondProduction_all * 9,
                        'control_interface' => 0,
                        'audio_modal' => 0,
                        'motherboard' => $postpondProduction_all * 1,
                    ];

                    // Postpond Conversion Row For This DC
                    $postpondConversion = PostpondConversion::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => $item->region_id,
                    ])->first();

                    // safe extracted converted counts
                    $convertedP1 = (int) ($postpondConversion->converted_p1 ?? 0);
                    $convertedP2 = (int) ($postpondConversion->converted_p2 ?? 0);

                    $audioModalPerP1 = 1;
                    $controlInterfacePerP2 = 1;
                    $conversionUsedAudioModal = $convertedP1 * $audioModalPerP1;
                    $conversionUsedControlInterface = $convertedP2 * $controlInterfacePerP2;

                    $item->postpondConversion = [
                        (object) [
                            'product_id' => 1,
                            'product_name' => 'Smart Home Assistant',
                            'plastic' => 0,
                            'casing' => 0,
                            'control_interface' => $conversionUsedControlInterface,
                            'audio_modal' => 0,
                            'motherboard' => 0,
                        ],
                        (object) [
                            'product_id' => 2,
                            'product_name' => 'Smart Thermostat',
                            'plastic' => 0,
                            'casing' => 0,
                            'control_interface' => 0,
                            'audio_modal' => $conversionUsedAudioModal,
                            'motherboard' => 0,
                        ],
                    ];

                    $item->replacement_parts = (object) [
                        'plastic' => $replacementRawMaterial->where('raw_material_id', 1)->sum('quantity') ?? 0,
                        // FIX: casing should use raw_material_id == 2
                        'casing' => $replacementRawMaterial->where('raw_material_id', 2)->sum('quantity') ?? 0,
                        'control_interface' => $replacementSubAssembly->where('sub_assembly_component_id', 1)->sum('quantity') ?? 0,
                        'audio_modal' => $replacementSubAssembly->where('sub_assembly_component_id', 2)->sum('quantity') ?? 0,
                        'motherboard' => $replacementSubAssembly->where('sub_assembly_component_id', 3)->sum('quantity') ?? 0,
                    ];

                    $item->purchases_delayed = (object) [
                        'plastic' => 0,
                        'casing' => 0,
                        'control_interface' => $procurements->subAssembly->filter(function ($sa) {
                            return $sa->sub_assembly_component_id == 1;
                        })->sum('quantity_delay') ?? 0,
                        'audio_modal' => $procurements->subAssembly->filter(function ($sa) {
                            return $sa->sub_assembly_component_id == 2;
                        })->sum('quantity_delay') ?? 0,
                        'motherboard' => $prevSub->where('sub_assembly_component_id', 3)
                            ->sum('quantity_delay') ?? 0,
                    ];

                    // Production Sum
                    $productionPlasticSum = array_sum(array_map(fn ($p) => $p->plastic, $item->production));
                    $productionCasingSum = array_sum(array_map(fn ($p) => $p->casing, $item->production));
                    $productionControlInterfaceSum = array_sum(array_map(fn ($p) => $p->control_interface, $item->production));
                    $productionAudioModalSum = array_sum(array_map(fn ($p) => $p->audio_modal, $item->production));
                    $productionMotherboardSum = array_sum(array_map(fn ($p) => $p->motherboard, $item->production));
                    // Emergency Production Sum
                    $emgProductionPlasticSum = array_sum(array_map(fn ($p) => $p->plastic, $item->emergency_production));
                    $emgProductionCasingSum = array_sum(array_map(fn ($p) => $p->casing, $item->emergency_production));
                    $emgProductionControlInterfaceSum = array_sum(array_map(fn ($p) => $p->control_interface, $item->emergency_production));
                    $emgProductionAudioModalSum = array_sum(array_map(fn ($p) => $p->audio_modal, $item->emergency_production));
                    $emgProductionMotherboardSum = array_sum(array_map(fn ($p) => $p->motherboard, $item->emergency_production));
                    // Postpond Conversion
                    $postpondConversionPlasticSum = array_sum(array_map(fn ($p) => $p->plastic, $item->postpondConversion));
                    $postpondConversionCasingSum = array_sum(array_map(fn ($p) => $p->casing, $item->postpondConversion));
                    $postpondConversionControlInterfaceSum = array_sum(array_map(fn ($p) => $p->control_interface, $item->postpondConversion));
                    $postpondConversionAudioModalSum = array_sum(array_map(fn ($p) => $p->audio_modal, $item->postpondConversion));
                    $postpondConversionMotherboardSum = array_sum(array_map(fn ($p) => $p->motherboard, $item->postpondConversion));

                    if ($item->dc_number === 1 && $item->region_id === 1) {

                        $item->ending_inventory = (object) [
                            'plastic' => $item->available_inventory->plastic -
                              $productionPlasticSum -
                              $emgProductionPlasticSum -
                              $postpondConversionPlasticSum -
                              $item->postponed_production->plastic -
                              $item->replacement_parts->plastic +
                              $item->purchases_delayed->plastic,
                            'casing' => $item->available_inventory->casing -
                              $productionCasingSum -
                              $emgProductionCasingSum -
                              $postpondConversionCasingSum -
                              $item->postponed_production->casing -
                              $item->replacement_parts->casing +
                              $item->purchases_delayed->casing,
                            'control_interface' => $item->available_inventory->control_interface -
                              $productionControlInterfaceSum -
                              $emgProductionControlInterfaceSum -
                              $postpondConversionControlInterfaceSum -
                              $item->postponed_production->control_interface -
                              $item->replacement_parts->control_interface +
                              $item->purchases_delayed->control_interface,
                            'audio_modal' => $item->available_inventory->audio_modal -
                              $productionAudioModalSum -
                              $emgProductionAudioModalSum -
                              $postpondConversionAudioModalSum -
                              $item->postponed_production->audio_modal -
                              $item->replacement_parts->audio_modal +
                              $item->purchases_delayed->audio_modal,
                            'motherboard' => $item->available_inventory->motherboard -
                              $productionMotherboardSum -
                              $emgProductionMotherboardSum -
                              $postpondConversionMotherboardSum -
                              $item->postponed_production->motherboard -
                              $item->replacement_parts->motherboard,
                        ];
                    } else {
                        $item->ending_inventory = (object) [
                            'plastic' => $item->available_inventory->plastic -
                              $item->postponed_production->plastic -
                              $item->replacement_parts->plastic +
                              $item->purchases_delayed->plastic,
                            'casing' => $item->available_inventory->casing -
                              $item->postponed_production->casing -
                              $item->replacement_parts->casing +
                              $item->purchases_delayed->casing,
                            'control_interface' => $item->available_inventory->control_interface -
                              $item->postponed_production->control_interface -
                              $item->replacement_parts->control_interface +
                              $item->purchases_delayed->control_interface,
                            'audio_modal' => $item->available_inventory->audio_modal -
                              $item->postponed_production->audio_modal -
                              $item->replacement_parts->audio_modal +
                              $item->purchases_delayed->audio_modal,
                            'motherboard' => $item->available_inventory->motherboard -
                              $item->postponed_production->motherboard -
                              $item->replacement_parts->motherboard +
                              $item->purchases_delayed->motherboard,
                        ];
                    }

                    if ($saveToDb) {
                        // Save motherboard_on_order = current-quarter normal + emergency motherboard orders
                        $motherboardOnOrderNormal = $procurements->subAssembly
                            ->where('sub_assembly_component_id', 3)
                            ->sum('quantity') ?? 0;

                        $motherboardOnOrderEmergency = $procurements->procurementEmergencySub
                            ->where('sub_assembly_component_id', 3)
                            ->sum('quantity') ?? 0;

                        $motherboardOnOrder = $motherboardOnOrderNormal + $motherboardOnOrderEmergency;

                        if ((int) $quarter_id === 0) {
                            $savedMotherboard = 0;
                        } else {
                            $savedMotherboard = max(0, $item->ending_inventory->motherboard ?? 0);
                        }

                        InventoryStock::updateOrCreate(
                            [
                                'simulation_id' => $simulation_id,
                                'faculty_id' => $faculty_id,
                                'group_number' => $group_id,
                                'quarter' => $quarter_id,
                                'region_id' => $item->region_id,
                                'dc_number' => $item->dc_number,
                            ],
                            [
                                'plastic' => max(0, $item->ending_inventory->plastic ?? 0),
                                'casing' => max(0, $item->ending_inventory->casing ?? 0),
                                'control_interface' => max(0, $item->ending_inventory->control_interface ?? 0),
                                'audio_modal' => max(0, $item->ending_inventory->audio_modal ?? 0),
                                'motherboard' => $savedMotherboard,
                                'motherboard_on_order' => max(0, $motherboardOnOrder),
                            ]
                        );
                    }

                } catch (\Exception $e) {
                    Log::error("Error in processing distribution center: {$item->dc_number}, Region: {$item->region_id}. Message: ".$e->getMessage());
                }
            });

            return (object) [
                'dcCenters' => $dcCenters,
                'procurementInventory' => $procurementInventory,
            ];
        } catch (\Exception $e) {
            Log::error('Error Procurement Service: '.$e->getMessage());

            return collect(); // Return an empty collection in case of failure
        }
    }
}
