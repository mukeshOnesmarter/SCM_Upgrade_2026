<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\CarrierCost;
use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\Demand\InitialDemand;
use App\Models\Decisions\Demand\ProbabilityDemand;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InformationTechnology;
use App\Models\Decisions\InformationTechnologyCarrier;
use App\Models\Decisions\InformationTechnologySupplier;
use App\Models\Decisions\InventoryStock;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\PostpondProduction;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\ProcurementRawMaterial;
use App\Models\Decisions\ProcurementSubAssembly;
use App\Models\Decisions\ServiceManagement;
use App\Models\Decisions\Transportation;
use App\Models\Decisions\TransportationInfo;
use App\Models\IndustryInitialDemand;
use App\Models\Product;
use App\Models\Region;
use App\Models\SimulationFirm;
use App\Models\SimulationTeamMember;
use App\Models\SupplierComponent;
use App\Services\ActiveUserService;
use App\Services\GenerateDemandService;
use App\Services\InitialSimulationSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentDecisionManageController extends Controller
{
    /**
     * Call Active User Service.
     */
    protected $activeUserService;

    protected $generateDemandService;

    protected $initialSimulationSetupService;

    public function __construct(
        ActiveUserService $activeUserService,
        GenerateDemandService $generateDemandService,
        InitialSimulationSetupService $initialSimulationSetupService
    ) {
        $this->activeUserService = $activeUserService;
        $this->generateDemandService = $generateDemandService;
        $this->initialSimulationSetupService = $initialSimulationSetupService;
    }

    /**
     * SAVE FORECASTING
     */
    public function saveForecasting(Request $request)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            $facultyId = $teamMember->faculty_id ?? null;
            $groupId = $teamMember->simulation_team_id ?? null;

            $request->validate([
                'increment_min' => [
                    'required_if:additional_increment,on',
                    'integer',
                    'between:100,500',
                ],
                'increment_max' => [
                    'required_if:additional_increment,on',
                    'integer',
                    'between:100,1000',
                    'gt:increment_min',
                ],
            ], [
                'increment_min.required_if' => 'The minimum increment is required when additional increment is selected.',
                'increment_min.between' => 'The minimum increment must be between 100 and 500.',
                'increment_max.required_if' => 'The maximum increment is required when additional increment is selected.',
                'increment_max.between' => 'The maximum increment must be between 100 and 1000.',
                'increment_max.gt' => 'The maximum increment must be greater than the minimum increment.',
            ]);

            foreach ($request->all() as $key => $value) {
                if (preg_match('/region_(\d+)_product_(\d+)_demand_(\d+)/', $key)) {

                    $val = $value[0] ?? null;

                    // Reject if contains comma or non-numeric format
                    if (! preg_match('/^\d+$/', $val)) {
                        throw ValidationException::withMessages([
                            $key => 'Use numbers only without commas.',
                        ]);
                    }
                }
            }

            $randomIncrement = 0;
            if ($request->has('additional_increment')) {
                $randomIncrement = rand((int) $request->increment_min, (int) $request->increment_max);
            }

            $groupedData = [];
            foreach ($request->all() as $key => $value) {
                if (preg_match('/region_(\d+)_product_(\d+)_demand_(\d+)/', $key, $matches)) {
                    $regionId = $matches[1];
                    $productId = $matches[2];
                    $demandId = $matches[3];

                    $groupKey = "{$regionId}_{$productId}";
                    if (! isset($groupedData[$groupKey])) {
                        $groupedData[$groupKey] = [
                            'region_id' => $regionId,
                            'product_id' => $productId,
                            'demand_channel_01' => 0,
                            'demand_channel_02' => 0,
                        ];
                    }

                    if ($demandId == 1) {
                        $groupedData[$groupKey]['demand_channel_01'] = ($value[0] ?? 0) + $randomIncrement;
                    } elseif ($demandId == 2) {
                        $groupedData[$groupKey]['demand_channel_02'] = ($value[0] ?? 0) + $randomIncrement;
                    }
                }
            }

            $firms = SimulationFirm::where('simulation_id', $request->simulation_id)->get();

            foreach ($firms as $firm) {
                $firm_id = $firm->firm_id;

                foreach ($groupedData as $group) {
                    $regionId = $group['region_id'];
                    $productId = $group['product_id'];
                    $demand_channel_01 = $group['demand_channel_01'];
                    $demand_channel_02 = $group['demand_channel_02'];

                    $commonData = [
                        'simulation_id' => $request->simulation_id,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupId,
                        'quarter' => $request->quarter_id,
                        'region_id' => $regionId,
                        'product_id' => $productId,
                        'firm_id' => $firm_id,
                    ];

                    $timestampData = [
                        'change_saved_by' => $user->id,
                        'change_saved_timestamp' => now(),
                    ];

                    // Initial Demand
                    $initialDemandCreate = InitialDemand::updateOrCreate(
                        $commonData,
                        array_merge([
                            'demand_channel_01' => $demand_channel_01,
                            'demand_channel_02' => $demand_channel_02,
                        ], $timestampData)
                    );

                    $probabilityDemandCreate = ProbabilityDemand::updateOrCreate(
                        $commonData,
                        array_merge($commonData, [
                            'demand_channel_01' => $this->generateDemandService->probabilityDemand($initialDemandCreate->demand_channel_01, $this->generateDemandService->randomNumber()),
                            'demand_channel_02' => $this->generateDemandService->probabilityDemand($initialDemandCreate->demand_channel_02, $this->generateDemandService->randomNumber()),
                        ], $timestampData)
                    );

                    $actualDemandCreate = ActualDemand::updateOrCreate(
                        $commonData,
                        array_merge($commonData, [
                            'demand_channel_01' => 0,
                            'demand_channel_02' => 0,
                        ], $timestampData)
                    );

                    $forecastDemandCreate = Forecasting::updateOrCreate(
                        $commonData,
                        array_merge($commonData, [
                            'demand_channel_01' => $demand_channel_01,
                            'demand_channel_02' => $demand_channel_02,
                        ], $timestampData)
                    );

                    // Forecast Accuracy Update
                    $forecastDemandCreate->update([
                        'accuracy_channel_01' => $this->generateDemandService->forecastAccuracy($forecastDemandCreate->demand_channel_01, $actualDemandCreate->demand_channel_01),
                        'accuracy_channel_02' => $this->generateDemandService->forecastAccuracy($forecastDemandCreate->demand_channel_02, $actualDemandCreate->demand_channel_02),
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Forecasting data saved or updated successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Forecasting Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * SAVE FORECASTING DEFAULT
     */
    public function saveDefaultForecasting(Request $request)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            // STEP 01: GET ALL AVAILABLE REGIONS.
            $regions = Region::all();
            foreach ($regions as $region) {
                // STEP 02: GET ALL PRODUCTS.
                $products = Product::all();
                foreach ($products as $product) {
                    // STEP 03: GET ALL FIRMS.
                    $firms = SimulationFirm::where('simulation_id', $request->simulation_id)->get();
                    foreach ($firms as $firm) {
                        // STEP 04: COLLECT HIERARCHY SUMMARY (IDs)
                        $region_id = $region->id;
                        $product_id = $product->id;
                        $firm_id = $firm->firm_id;
                        // STEP 05: COLLECT LAST QUARTER FORCAST DEMAND INITILIZE DEMAND FOR CURRENT QUARTER.
                        $collectDemand = IndustryInitialDemand::where([
                            'region_id' => $region_id,
                            'product_id' => $product_id,
                        ])->select('demand_channel_01', 'demand_channel_02')->first();

                        //  STEP 06: SET DEMAND FOR CURRENT QUARTER ON THE BASIS OF DEMAND COLLECTED.
                        $demand_channel_01 = $collectDemand->demand_channel_01 ?? 0;
                        $demand_channel_02 = $collectDemand->demand_channel_02 ?? 0;

                        $commonData = [
                            'simulation_id' => $request->simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_id,
                            'quarter' => $request->quarter_id,
                            'region_id' => $region_id,
                            'product_id' => $product_id,
                            'firm_id' => $firm_id,
                        ];

                        $timestampData = [
                            'change_saved_by' => $user->id,
                            'change_saved_timestamp' => now(),
                        ];

                        //  STEP 07: CHECK IF THE RECORD EXISTS OR NOT
                        $initialDemandCreate = InitialDemand::updateOrCreate(
                            $commonData,
                            array_merge([
                                'demand_channel_01' => $demand_channel_01,
                                'demand_channel_02' => $demand_channel_02,
                            ], $timestampData)
                        );

                        $probabilityDemandCreate = ProbabilityDemand::updateOrCreate(
                            $commonData,
                            array_merge($commonData, [
                                'demand_channel_01' => $this->generateDemandService->probabilityDemand($initialDemandCreate->demand_channel_01, $this->generateDemandService->randomNumber()),
                                'demand_channel_02' => $this->generateDemandService->probabilityDemand($initialDemandCreate->demand_channel_02, $this->generateDemandService->randomNumber()),
                            ], $timestampData)
                        );

                        $actualDemandCreate = ActualDemand::updateOrCreate(
                            $commonData,
                            array_merge($commonData, [
                                'demand_channel_01' => $this->generateDemandService->actualDemand($probabilityDemandCreate->demand_channel_01),
                                'demand_channel_02' => $this->generateDemandService->actualDemand($probabilityDemandCreate->demand_channel_02),
                            ], $timestampData)
                        );

                        $forecastDemandCreate = Forecasting::updateOrCreate(
                            $commonData,
                            array_merge($commonData, [
                                'demand_channel_01' => $this->generateDemandService->forecastDemand($actualDemandCreate->demand_channel_01, $initialDemandCreate->demand_channel_01),
                                'demand_channel_02' => $this->generateDemandService->forecastDemand($actualDemandCreate->demand_channel_02, $initialDemandCreate->demand_channel_01),
                            ], $timestampData)
                        );

                        $forecastDemandCreate->update([
                            'accuracy_channel_01' => $this->generateDemandService->forecastAccuracy($forecastDemandCreate->demand_channel_01, $actualDemandCreate->demand_channel_01),
                            'accuracy_channel_02' => $this->generateDemandService->forecastAccuracy($forecastDemandCreate->demand_channel_02, $actualDemandCreate->demand_channel_02),
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Forecasting data saved successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /***
     * SAVE PROCUREMENT
     */
    public function saveProcurement(Request $request)
    {
        DB::beginTransaction();

        try {
            // Active user
            $user = $this->activeUserService->getActiveUser();

            // Team member
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->firstOrFail();

            // Identifiers
            $simulation_id = (int) $request->simulation_id;
            $faculty_id = (int) $teamMember->faculty_id;
            $group_id = (int) $teamMember->simulation_team_id;
            $quarter_id = (int) $request->quarter_id;
            $region_id = (int) $request->region_id;
            $student_id = (int) $user->id;

            /**
             * ------------------------------------------------
             * STEP 1: SINGLE PROCUREMENT PER QUARTER + REGION
             * ------------------------------------------------
             */
            $procurement = Procurement::updateOrCreate(
                [
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'region_id' => $region_id,
                ],
                [
                    'procurement_type' => implode(',', $request->procurement_type ?? []),
                    'change_saved_by' => $student_id,
                    'change_saved_timestamp' => now(),
                ]
            );

            /**
             * ------------------------------------------------
             * STEP 2: RAW MATERIALS
             * ------------------------------------------------
             */
            if (is_array($request->raw_material)) {
                foreach ($request->raw_material as $raw_material_id => $quantity) {

                    // STRICT CHECK AGAIN
                    if ($quantity !== null && $quantity !== '' && ! preg_match('/^\d+$/', $quantity)) {
                        throw ValidationException::withMessages([
                            "raw_material.$raw_material_id" => 'Use digits only (no commas).',
                        ]);
                    }

                    ProcurementRawMaterial::updateOrCreate(
                        [
                            'procurement_id' => $procurement->id,
                            'raw_material_id' => (int) $raw_material_id,
                        ],
                        [
                            'quantity' => (int) ($quantity ?? 0),
                        ]
                    );
                }
            }

            /**
             * ------------------------------------------------
             * STEP 3: SUB-ASSEMBLY COMPONENTS (ONLY SELECTED)
             * ------------------------------------------------
             */
            foreach ($request->all() as $key => $value) {

                if (! preg_match(
                    '/sub_assembly_component_(\d+)_supplier_(\d+)_supply_channel_(\d+)/',
                    $key,
                    $matches
                )) {
                    continue;
                }

                // STRICT CHECK AGAIN (DEFENSIVE)
                if ($value !== null && $value !== '' && ! preg_match('/^\d+$/', $value)) {
                    throw ValidationException::withMessages([
                        $key => 'Use digits only (no commas).',
                    ]);
                }

                $subAssemblyComponentId = (int) $matches[1];
                $supplierId = (int) $matches[2];
                $supplyChannelId = (int) $matches[3];
                $totalShipment = (int) ($value ?? 0);

                /**
                 * RULE:
                 * - If user entered nothing AND no existing row → SKIP
                 * - If existing row exists → allow update
                 */
                $existing = ProcurementSubAssembly::where([
                    'procurement_id' => $procurement->id,
                    'sub_assembly_component_id' => $subAssemblyComponentId,
                    'supplier_id' => $supplierId,
                    'supply_channel_id' => $supplyChannelId,
                ])->first();

                if ($totalShipment <= 0 && ! $existing) {
                    continue;
                }

                // Defaults
                $quantity = $totalShipment;
                $quantityDelay = 0;

                // Surface shipment logic
                if ($supplyChannelId === 2 && $totalShipment > 0) {
                    $supplierInfo = SupplierComponent::where([
                        'supplier_id' => $supplierId,
                        'sub_assembly_component_id' => $subAssemblyComponentId,
                    ])->first();

                    if ($supplierInfo) {
                        $min = ($supplierInfo->expected_value - $supplierInfo->possible_variation) / 100;
                        $max = ($supplierInfo->expected_value + $supplierInfo->possible_variation) / 100;

                        $factor = rand($min * 100, $max * 100) / 100;

                        $quantity = (int) round($totalShipment * $factor);
                        $quantityDelay = $totalShipment - $quantity;
                    }
                }

                ProcurementSubAssembly::updateOrCreate(
                    [
                        'procurement_id' => $procurement->id,
                        'sub_assembly_component_id' => $subAssemblyComponentId,
                        'supplier_id' => $supplierId,
                        'supply_channel_id' => $supplyChannelId,
                    ],
                    [
                        'total_shipment' => $totalShipment,
                        'quantity' => $quantity,
                        'quantity_delay' => $quantityDelay,
                    ]
                );
            }

            DB::commit();

            return redirect()->back()->with('success', 'Procurement data saved successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Procurement Save Failed]', [
                'simulation_id' => $request->simulation_id ?? null,
                'quarter_id' => $request->quarter_id ?? null,
                'region_id' => $request->region_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'An unexpected error occurred. Please try again.')
                ->withInput();
        }
    }

    /**
     * SAVE SAVE MANUFACTURING:
     */
    public function saveManufacturing(Request $request)
    {
        DB::beginTransaction();

        try {
            // Active user and team info
            $user = $this->activeUserService->getActiveUser();
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            $simulation_id = $request->simulation_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $quarter_id = (int) $request->quarter_id;
            $student_id = $user->id;

            // ------------------------------------------------
            // STRICT NUMERIC VALIDATION (Manufacturing)
            // ------------------------------------------------
            if (is_array($request->production_quantity)) {
                foreach ($request->production_quantity as $productId => $val) {
                    if ($val !== null && $val !== '' && ! preg_match('/^\d+$/', $val)) {
                        throw ValidationException::withMessages([
                            "production_quantity.$productId" => 'Enter whole numbers only without commas.',
                        ]);
                    }
                }
            }

            // emergency_production[]
            if (is_array($request->emergency_production)) {
                foreach ($request->emergency_production as $productId => $val) {
                    if ($val !== null && $val !== '' && ! preg_match('/^\d+$/', $val)) {
                        throw ValidationException::withMessages([
                            "emergency_production.$productId" => 'Enter whole numbers only without commas.',
                        ]);
                    }
                }
            }

            // adjustable_production[]
            if (is_array($request->adjustable_production)) {
                foreach ($request->adjustable_production as $productId => $val) {
                    if ($val !== null && $val !== '' && ! preg_match('/^\d+$/', $val)) {
                        throw ValidationException::withMessages([
                            "adjustable_production.$productId" => 'Enter whole numbers only without commas.',
                        ]);
                    }
                }
            }

            // product zero
            if ($request->has('production_quantity_zero')) {
                $val = $request->production_quantity_zero;
                if ($val !== null && $val !== '' && ! preg_match('/^\d+$/', $val)) {
                    throw ValidationException::withMessages([
                        'production_quantity_zero' => 'Enter whole numbers only without commas.',
                    ]);
                }
            }

            // ---------- compute requested motherboards ----------
            $requestedMotherboards = 0;

            if (is_array($request->product_id)) {
                foreach ($request->product_id as $product_id) {
                    $prod = (int) ($request->production_quantity[$product_id] ?? 0);
                    $requestedMotherboards += $prod;
                }
            }

            // include Product Zero
            $requestedMotherboards += (int) ($request->production_quantity_zero ?? 0);

            // ---------- compute available motherboards using helper (same controller) ----------
            $availableMotherboards = $this->availableMotherboards(
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id
            );

            // Validate
            if ($requestedMotherboards > $availableMotherboards) {
                return redirect()->back()->with('error', "Insufficient motherboards stock — you can produce up to {$availableMotherboards} unit(s).")
                    ->withInput();
            }

            // ---------- Save manufacturing rows (unchanged) ----------
            if (is_array($request->product_id)) {
                foreach ($request->product_id as $product_id) {
                    Manufacturing::updateOrCreate(
                        [
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_id,
                            'quarter' => $quarter_id,
                            'product_id' => $product_id,
                        ],
                        [
                            'change_saved_by' => $student_id,
                            'production_quantity' => $request->production_quantity[$product_id] ?? 0,
                            'emergency_production' => $request->emergency_production[$product_id] ?? 0,
                            'adjustable_production' => $request->adjustable_production[$product_id] ?? 0,
                            'change_saved_timestamp' => now(),
                        ]
                    );
                }
            }

            // create/update PostpondProduction entries for product_0
            $totalZero = $request->production_quantity_zero ?? 0;

            if ($totalZero <= 0) {
                PostpondProduction::where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'product_id' => 0,
                ])->delete();
            } else {
                PostpondProduction::updateOrCreate(
                    [
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_id,
                        'quarter' => $quarter_id,
                        'region_id' => 1,
                        'product_id' => 0,
                    ],
                    [
                        'quantity' => $totalZero,
                    ]
                );
            }

            DB::commit();

            return redirect()->back()->with('success', 'Manufacturing data saved successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * SAVE DISTRIBUTION
     */
    public function saveDistribution(Request $request)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            $rules = [
                'distribution_center' => 'required',
                'ffgi_surface_shipping' => 'required',
                'sac_surface_shipping' => 'required',
                'rfid_application' => ['required_if:distribution_center,insourced'],
                'emergency_carrier' => 'required|not_in:null',
            ];

            $messages = [
                'rfid_application.required_if' => 'The RFID application field is required when distribution center is insourced.',
                'emergency_carrier.required' => 'The Emergency Carrier field is required.',
                'emergency_carrier.not_in' => 'Please select a valid Emergency Carrier.',
            ];

            $validatedData = $request->validate($rules, $messages);

            // Prepare Data for Saving
            $data = [
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $request->quarter_id,
                'region_id' => $request->region_id,
                'dc_number' => $request->dc_number,

                'dc_type' => $request->distribution_center,
                'is_home_region' => $request->is_home_region,
                'is_operational' => $request->is_operational,
                'rfid_application' => $request->rfid_application,
                'ffgi_shipping' => $request->ffgi_surface_shipping,
                'sac_shipping' => $request->sac_surface_shipping,
                'cross_docking_k' => $request->cross_docking_K ?? null,
                'cross_docking_l' => $request->cross_docking_L ?? null,
                'cross_docking_m' => $request->cross_docking_M ?? null,
                'cross_docking_n' => $request->cross_docking_N ?? null,
                'emergency_carrier_id' => $request->emergency_carrier,
                'change_saved_by' => $student_id,
                'change_saved_timestamp' => now(),
            ];

            // CREATE OR UPDATE RECORD
            Distribution::updateOrCreate(
                [
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $request->quarter_id,
                    'region_id' => $request->region_id,
                    'dc_number' => $request->dc_number,
                ],
                $data
            );

            DB::commit();

            return redirect()->back()->with('success', 'Distribution data saved successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * CLOSE DISTRIBUTION
     */
    public function closeDistribution(Request $request)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;
            $region_id = $request->region_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;

            $updateDc = Distribution::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
                'region_id' => $region_id,
            ])->first();

            if (! $updateDc) {
                return redirect()->back()->with('error', 'Distribution Center not found for the given region.');
            }

            $updateDc->is_operational = $request->requesttype === 'close' ? 'no' : 'yes';
            $updateDc->updated_at = now();
            $updateDc->save();

            DB::commit();

            return redirect()->back()->with('success', 'Distribution Center status updated successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error closing Distribution Center: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * SAVE TRANSPORTATION
     */
    public function saveTransportation(Request $request)
    {
        DB::beginTransaction();

        try {
            // Active user
            $user = $this->activeUserService->getActiveUser();

            // Team member
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->firstOrFail();

            // Identifiers
            $simulation_id = (int) $request->simulation_id;
            $faculty_id = (int) $teamMember->faculty_id;
            $group_id = (int) $teamMember->simulation_team_id;
            $quarter_id = (int) $request->quarter_id;
            $distribution_center_id = (int) $request->distribution_center_id;
            $student_id = (int) $user->id;

            /**
             * Ensure manufacturing is saved and transport qty does not exceed production
             */
            $this->validateTransportationAgainstManufacturing(
                $request,
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id,
                $distribution_center_id
            );

            /**
             * ------------------------------------------------
             * STEP 1: SINGLE TRANSPORTATION PER QTR + DC
             * ------------------------------------------------
             */
            $transportation = Transportation::updateOrCreate(
                [
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'distribution_center_id' => $distribution_center_id,
                ],
                [
                    'change_saved_by' => $student_id,
                    'change_saved_timestamp' => now(),
                ]
            );

            // STRICT VALIDATION HERE
            foreach ($request->all() as $key => $value) {

                if (preg_match(
                    '/dc_(\d+)_product_(\d+)_carrier_(\d+)_supply_channel_(\d+)/',
                    $key
                )) {
                    if ($value !== null && $value !== '' && ! preg_match('/^\d+$/', $value)) {
                        throw ValidationException::withMessages([
                            $key => 'Use digits only (no commas).',
                        ]);
                    }
                }
            }

            /**
             * ------------------------------------------------
             * STEP 2: TRANSPORTATION INFO (ONLY SELECTED)
             * ------------------------------------------------
             * Field pattern:
             * dc_{dc}_product_{product}_carrier_{carrier}_supply_channel_{channel}
             */
            foreach ($request->all() as $key => $value) {

                if (! preg_match(
                    '/dc_(\d+)_product_(\d+)_carrier_(\d+)_supply_channel_(\d+)/',
                    $key,
                    $matches
                )) {
                    continue;
                }

                // DEFENSIVE CHECK
                if ($value !== null && $value !== '' && ! preg_match('/^\d+$/', $value)) {
                    throw ValidationException::withMessages([
                        $key => 'Use digits only (no commas).',
                    ]);
                }

                $dcId = (int) $matches[1];
                $productId = (int) $matches[2];
                $carrierId = (int) $matches[3];
                $channelId = (int) $matches[4];
                $totalShipment = (int) ($value ?? 0);

                // Safety: only current DC
                if ($dcId !== $distribution_center_id) {
                    continue;
                }

                // Check if row already exists
                $existing = TransportationInfo::where([
                    'transportation_id' => $transportation->id,
                    'product_id' => $productId,
                    'carrier_id' => $carrierId,
                    'supply_channel_id' => $channelId,
                ])->first();

                /**
                 * APPLY BUSINESS RULES
                 */

                // 1️⃣ New entry + value = 0 → DO NOTHING
                if (! $existing && $totalShipment <= 0) {
                    continue;
                }

                // Defaults
                $quantity = $totalShipment;
                $quantityDelay = 0;

                // Surface shipment variation
                if ($channelId === 2 && $totalShipment > 0) {
                    $carrierInfo = CarrierCost::where([
                        'carrier_id' => $carrierId,
                        'region_id' => $distribution_center_id,
                    ])->firstWhere('supply_channel_id', $channelId);

                    if ($carrierInfo) {
                        $min = ($carrierInfo->expected_value - $carrierInfo->possible_variation) / 100;
                        $max = ($carrierInfo->expected_value + $carrierInfo->possible_variation) / 100;

                        $factor = rand($min * 100, $max * 100) / 100;

                        $quantity = (int) round($totalShipment * $factor);
                        $quantityDelay = $totalShipment - $quantity;
                    }
                }

                // 2️⃣ Existing entry OR New entry with value > 0 → UPSERT
                TransportationInfo::updateOrCreate(
                    [
                        'transportation_id' => $transportation->id,
                        'product_id' => $productId,
                        'carrier_id' => $carrierId,
                        'supply_channel_id' => $channelId,
                    ],
                    [
                        'total_shipment' => $totalShipment,
                        'quantity' => $quantity,
                        'quantity_delay' => $quantityDelay,
                    ]
                );
            }

            // --------------------------------------------
            // VALIDATION: CROSS DOCKING PER CARRIER
            // --------------------------------------------

            // Step 1: Collect shipment per carrier
            $carrierShipments = [];

            foreach ($request->all() as $key => $value) {

                if (preg_match(
                    '/dc_(\d+)_product_(\d+)_carrier_(\d+)_supply_channel_(\d+)/',
                    $key,
                    $matches
                )) {
                    $carrierId = (int) $matches[3];

                    if ((int) $value > 0) {
                        $carrierShipments[$carrierId] = true;
                    }
                }
            }

            // Step 2: Map carrier name → ID
            $carrierMap = Carrier::whereIn('name', ['K', 'L', 'M', 'N'])
                ->pluck('id', 'name'); // ['K' => 1, 'L' => 2...]

            // Step 3: Validate each cross docking selection
            $errors = [];

            foreach (['k', 'l', 'm', 'n'] as $carrierKey) {

                if ($request->has('cross_docking_'.$carrierKey)) {

                    $carrierName = strtoupper($carrierKey);
                    $carrierId = $carrierMap[$carrierName] ?? null;

                    if (! $carrierId || ! isset($carrierShipments[$carrierId])) {

                        $errors[] = "Cross Docking for Carrier {$carrierName} requires shipment for the same carrier.";
                    }
                }
            }

            // Step 4: Throw error if any
            if (! empty($errors)) {
                throw ValidationException::withMessages([
                    'cross_docking' => implode(' ', $errors),
                ]);
            }

            // Save Cross Docking
            $distribution = Distribution::updateOrCreate(
                [
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                    'region_id' => $distribution_center_id,
                ],
                [
                    'cross_docking_k' => $request->has('cross_docking_k') ? 'yes' : 'no',
                    'cross_docking_l' => $request->has('cross_docking_l') ? 'yes' : 'no',
                    'cross_docking_m' => $request->has('cross_docking_m') ? 'yes' : 'no',
                    'cross_docking_n' => $request->has('cross_docking_n') ? 'yes' : 'no',
                ]
            );

            DB::commit();

            return redirect()->back()->with('success', 'Transportation data saved successfully.');
        } catch (ValidationException $e) {

            DB::rollBack();

            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('[Transportation Save Failed]', [
                'simulation_id' => $request->simulation_id ?? null,
                'quarter_id' => $request->quarter_id ?? null,
                'dc_id' => $request->distribution_center_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     *  SAVE DEMAND PLANNING
     */
    public function saveDemandPlanning(Request $request)
    {

        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            // Loop through regions
            foreach ($request->input('region', []) as $region_id) {
                // Loop through products under each region
                foreach ($request->input('product', []) as $product_id) {
                    // Loop through demand channels under each product
                    foreach ($request->input('demand', []) as $demand_id) {
                        // Construct dynamic field keys based on naming convention
                        $statusKey = "status_region_{$region_id}_product_{$product_id}_demand_{$demand_id}";
                        $priceKey = "price_region_{$region_id}_product_{$product_id}_demand_{$demand_id}";
                        $marketSpendingKey = "marketSpending_region_{$region_id}_product_{$product_id}_demand_{$demand_id}";

                        // Retrieve values from request, default to null if not set
                        $status = $request->input($statusKey, null);
                        $price = $request->filled($priceKey) ? $request->input($priceKey) : 0;
                        $marketSpending = $request->filled($marketSpendingKey) ? $request->input($marketSpendingKey) : 0;

                        // STRICT NUMERIC VALIDATION (Demand Planning)
                        if ($price !== null && $price !== '' && ! preg_match('/^\d+$/', $price)) {
                            throw ValidationException::withMessages([
                                $priceKey => 'Use digits only for price (no commas, no decimals).',
                            ]);
                        }

                        // MARKETSPENDING MUST BE INTEGER
                        if ($marketSpending !== null && $marketSpending !== '' && ! preg_match('/^\d+$/', $marketSpending)) {
                            throw ValidationException::withMessages([
                                $marketSpendingKey => 'Use digits only for market spending (no commas, no decimals).',
                            ]);
                        }

                        if ($demand_id == 1) {
                            $markupPercentage = rand(20, 35);
                            $markupMultiplier = 1 + ($markupPercentage / 100);
                            $marketPrice = round(round($price * $markupMultiplier) / 10) * 10; // Double round to nearest 10
                        } else {
                            $marketPrice = $price;
                        }

                        // Insert into database only if necessary fields are provided
                        if ($status !== null) {
                            DemandPlanning::updateOrCreate(
                                [
                                    'simulation_id' => $simulation_id,
                                    'faculty_id' => $faculty_id,
                                    'group_number' => $group_id,
                                    'quarter' => $quarter_id,
                                    'region_id' => $region_id,
                                    'product_id' => $product_id,
                                    'demand_channel_id' => $demand_id,
                                ],
                                [
                                    'status' => $status,
                                    'price' => $price,
                                    'market_price' => $marketPrice,
                                    'marketSpending' => $marketSpending,
                                    'change_saved_by' => $student_id,
                                    'change_saved_timestamp' => now(),
                                ]
                            );
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Distribution data saved successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     *  SAVE SERVICE MANAGEMENT
     */
    public function saveServiceManagement(Request $request)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            // Loop through regions
            foreach ($request->input('region', []) as $region_id) {

                // Get the field name dynamically
                $fieldName = "service_region_{$region_id}";

                if ($request->filled($fieldName)) {

                    ServiceManagement::updateOrCreate(
                        [
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_id,
                            'quarter' => $quarter_id,
                            'change_saved_by' => $student_id,
                            'region_id' => $region_id,
                        ],
                        [
                            'service_id' => $request->$fieldName,
                            'change_saved_timestamp' => now(),
                        ]
                    );
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Service data saved successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * SAVE INFORMATION TECHNOLOGY
     */
    public function saveInformationTechnology(Request $request)
    {
        DB::beginTransaction();

        try {
            // Get the active user
            $user = $this->activeUserService->getActiveUser();

            // Retrieve associated faculty and group ID
            $teamMember = SimulationTeamMember::where([
                'simulation_id' => $request->simulation_id,
                'student_id' => $user->id,
            ])->first();

            // Extract necessary details
            $simulation_id = $request->simulation_id;
            $quarter_id = $request->quarter_id;
            $faculty_id = $teamMember->faculty_id ?? null;
            $group_id = $teamMember->simulation_team_id ?? null;
            $student_id = $user->id;

            // Step 1: Create or update InformationTechnology record
            $infoTech = InformationTechnology::updateOrCreate(
                [
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $quarter_id,
                    'group_number' => $group_id,
                ],
                [

                    'change_saved_by' => $student_id,
                    'change_saved_timestamp' => now(),
                ]
            );

            // Step 2: Handle Suppliers
            if ($request->has('suppliers')) {
                foreach ($request->suppliers as $supplierId) {
                    if ($request->input("supplier_{$supplierId}") === 'yes') {
                        // Insert or update supplier record
                        InformationTechnologySupplier::updateOrCreate(
                            [
                                'information_technology_id' => $infoTech->id,
                                'supplier_id' => $supplierId,
                            ],
                            ['status' => 'active']
                        );
                    } else {
                        // Delete supplier record if value is "no"
                        InformationTechnologySupplier::where([
                            'information_technology_id' => $infoTech->id,
                            'supplier_id' => $supplierId,
                        ])->delete();
                    }
                }
            }

            // Step 3: Handle Carriers
            if ($request->has('carrier')) {
                foreach ($request->carrier as $carrierId) {
                    if ($request->input("carrier_{$carrierId}") === 'yes') {
                        // Insert or update carrier record
                        InformationTechnologyCarrier::updateOrCreate(
                            [
                                'information_technology_id' => $infoTech->id,
                                'carrier_id' => $carrierId,
                            ],
                            ['status' => 'active']
                        );
                    } else {
                        // Delete carrier record if value is "no"
                        InformationTechnologyCarrier::where([
                            'information_technology_id' => $infoTech->id,
                            'carrier_id' => $carrierId,
                        ])->delete();
                    }
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Distribution data saved successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    /**
     * Compute available motherboards for a quarter (beginning inventory + arrivals from previous quarter procurements).
     *
     * Kept inside the same controller as a private helper per your request.
     */
    private function availableMotherboards(int $simulation_id, ?int $faculty_id, ?int $group_id, int $quarter_id): int
    {
        // 1) beginning inventory = InventoryStock where quarter = quarter_id - 1
        $beginningInventory = InventoryStock::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => max(0, $quarter_id - 1),
        ])->first();

        $beginMotherboards = max(0, $beginningInventory->motherboard ?? 0);

        // 2) arrivals = previous-quarter procurements (normal + emergency)
        $prevMotherboardArrival = 0;
        if ($quarter_id > 0) {
            $prevProcurements = Procurement::with('subAssembly', 'procurementEmergencySub')
                ->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id - 1,
                ])->first();

            if ($prevProcurements) {
                $prevNormal = optional($prevProcurements->subAssembly)
                    ->where('sub_assembly_component_id', 3)
                    ->sum('quantity') ?? 0;

                $prevEmergency = optional($prevProcurements->procurementEmergencySub)
                    ->where('sub_assembly_component_id', 3)
                    ->sum('quantity') ?? 0;

                $prevMotherboardArrival = $prevNormal + $prevEmergency;
            }
        }

        return $beginMotherboards + $prevMotherboardArrival;
    }

    /**
     * Validate Transportation against Manufacturing output.
     *
     * Rules:
     * 1. Manufacturing must be saved for the quarter
     * 2. Transported quantity per product ≤ Manufactured quantity
     * 3. Product 0 (SHELL) cannot be transported
     *
     * @throws \Exception
     */
    protected function validateTransportationAgainstManufacturing(
        Request $request,
        int $simulation_id,
        int $faculty_id,
        int $group_id,
        int $quarter_id,
        int $distribution_center_id
    ) {
        /**
         * -----------------------------------------
         * STEP 1: Manufacturing must exist
         * -----------------------------------------
         */
        $manufacturingRows = Manufacturing::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();

        if ($manufacturingRows->isEmpty()) {
            throw new \Exception(
                'Please save Manufacturing decisions before Transportation.'
            );
        }

        /**
         * -----------------------------------------
         * STEP 2: Compute manufactured qty per product
         * (production + emergency only)
         * -----------------------------------------
         */
        $manufacturedQtyByProduct = [];

        foreach ($manufacturingRows as $row) {
            $manufacturedQtyByProduct[$row->product_id] =
              ($manufacturedQtyByProduct[$row->product_id] ?? 0)
              + (int) $row->production_quantity
              + (int) $row->emergency_production;
        }

        /**
         * -----------------------------------------
         * STEP 2A: Include SHELL (Product 0)
         * from PostpondProduction
         * -----------------------------------------
         */
        $shellQty = PostpondProduction::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
            'product_id' => 0,
        ])->sum('quantity');

        if ($shellQty > 0) {
            $manufacturedQtyByProduct[0] = (int) $shellQty;
        }

        /**
         * -----------------------------------------
         * STEP 3: Requested transportation
         * (current DC only)
         * -----------------------------------------
         */
        $transportRequestedByProduct = [];

        foreach ($request->all() as $key => $value) {

            if (! preg_match(
                '/dc_(\d+)_product_(\d+)_carrier_(\d+)_supply_channel_(\d+)/',
                $key,
                $matches
            )) {
                continue;
            }

            $dcId = (int) $matches[1];
            $productId = (int) $matches[2];
            $qty = (int) ($value ?? 0);

            if ($dcId !== $distribution_center_id) {
                continue;
            }

            $transportRequestedByProduct[$productId] =
              ($transportRequestedByProduct[$productId] ?? 0) + $qty;
        }

        /**
         * -----------------------------------------
         * STEP 4: Already shipped from OTHER DCs
         * -----------------------------------------
         */
        $alreadyTransportedByProduct = TransportationInfo::whereHas(
            'transportation',
            function ($q) use (
                $simulation_id,
                $faculty_id,
                $group_id,
                $quarter_id,
                $distribution_center_id
            ) {
                $q->where([
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'group_number' => $group_id,
                    'quarter' => $quarter_id,
                ])
                    ->where('distribution_center_id', '!=', $distribution_center_id);
            }
        )
            ->selectRaw('product_id, SUM(total_shipment) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id')
            ->toArray();

        /**
         * -----------------------------------------
         * STEP 5: GLOBAL VALIDATION (FINAL STATE)
         * -----------------------------------------
         */
        $allProductIds = array_unique(array_merge(
            array_keys($manufacturedQtyByProduct),
            array_keys($alreadyTransportedByProduct),
            array_keys($transportRequestedByProduct)
        ));

        foreach ($allProductIds as $productId) {

            $manufacturedQty =
              $manufacturedQtyByProduct[$productId] ?? 0;

            $alreadyOtherDCs =
              $alreadyTransportedByProduct[$productId] ?? 0;

            // Requested value is FINAL value for this DC
            $requestedThisDC =
              $transportRequestedByProduct[$productId] ?? 0;

            $totalAfterSave =
              $alreadyOtherDCs + $requestedThisDC;

            if ($totalAfterSave > $manufacturedQty) {
                throw new \Exception(
                    "Transportation exceeds available manufactured units for Product {$productId}."
                );
            }
        }

        return true;
    }
}
