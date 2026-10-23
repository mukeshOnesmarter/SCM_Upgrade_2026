<?php

namespace App\Services;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Distribution;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\InformationTechnology;
use App\Models\Decisions\InformationTechnologyCarrier;
use App\Models\Decisions\InformationTechnologySupplier;
use App\Models\Decisions\Manufacturing;
use App\Models\Decisions\PostpondProduction;
use App\Models\Decisions\Procurement;
use App\Models\Decisions\ProcurementRawMaterial;
use App\Models\Decisions\ProcurementSubAssembly;
use App\Models\Decisions\ServiceManagement;
use App\Models\Decisions\Transportation;
use App\Models\Decisions\TransportationInfo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiDecisionSaveService
{
    protected int $firmId = 1;

    protected int $changeSavedBy = 1;

    protected GenerateDemandService $generateDemandService;

    protected array $moduleConfigKeys = [
        'forecasting' => 'forecasting',
        'demand_planning' => 'demand_planning',
        'distribution' => 'distribution',
        'procurement' => 'procurement',
        'manufacturing' => 'manufacturing',
        'transportation' => 'transportation',
        'service_management' => 'service_management',
        'information_technology' => 'information_technology',
    ];

    public function __construct(GenerateDemandService $generateDemandService)
    {
        $this->generateDemandService = $generateDemandService;
    }

    private function getModuleConfigKeysForQuarter(int $quarterId): array
    {
        // Q1..Q5 (quarterId < 6)
        if ($quarterId < 6) {
            return [
                'forecasting' => 'forecasting',
                'procurement' => 'procurement',
                'manufacturing' => 'manufacturing',
            ];
        }

        // Q6 (quarterId < 7)
        if ($quarterId < 7) {
            return [
                'forecasting' => 'forecasting',
                'distribution' => 'distribution',
                'procurement' => 'procurement',
                'manufacturing' => 'manufacturing',
                'transportation' => 'transportation',
            ];
        }

        // Q7+ (full modules)
        return [
            'forecasting' => 'forecasting',
            'demand_planning' => 'demand_planning',
            'distribution' => 'distribution',
            'procurement' => 'procurement',
            'manufacturing' => 'manufacturing',
            'transportation' => 'transportation',
            'service_management' => 'service_management',
            'information_technology' => 'information_technology',
        ];
    }

    /**
     * Fetch all configured AI endpoints and persist results.
     */
    public function fetchAndSaveAllRecommendations(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $extraPayload = []
    ): array {
        $payload = array_merge([
            'simulation_id' => $simulationId,
            'group_number' => $groupNumber,
            'faculty_id' => $facultyId,
            'quarter' => $quarterId,
        ], $extraPayload);

        $decisions = [];

        $moduleConfigKeys = $this->getModuleConfigKeysForQuarter($quarterId);

        foreach ($moduleConfigKeys as $decisionKey => $configKey) {
            try {
                $response = $this->fetchModule($configKey, $payload);
                if ($response === null) {
                    Log::info("No response from AI endpoint for {$configKey}");

                    continue;
                }

                // Unwrap the module and ignore _meta, meta, etc.
                if (is_array($response) && array_key_exists($decisionKey, $response)) {
                    $module = $response[$decisionKey];
                    $decisions[$decisionKey] = isset($module['rows']) ? $module : ['rows' => []];
                } else {
                    $decisions[$decisionKey] = $response;
                }
            } catch (\Throwable $e) {
                Log::error("Error fetching AI module {$configKey}: ".$e->getMessage(), [
                    'config_key' => $configKey,
                    'simulation_id' => $simulationId,
                    'faculty_id' => $facultyId,
                    'group_number' => $groupNumber,
                    'quarter' => $quarterId,
                ]);
            }
        }

        return $this->saveAIDecisions($simulationId, $facultyId, $groupNumber, $quarterId, $decisions);
    }

    /**
     * Fetch single AI module endpoint.
     * Returns array or null on failure.
     */
    public function fetchModule(string $configKey, array $payload, int $defaultTimeout = 300): ?array
    {
        $endpoints = config('ai_decision.endpoints', []);
        $url = $endpoints[$configKey] ?? null;

        if (empty($url)) {
            Log::warning("AI endpoint not configured for key '{$configKey}'");

            return null;
        }

        $moduleTimeouts = [
            'forecasting' => 300,
            'manufacturing' => 300,
            'procurement' => 300,
            'distribution' => 300,
            'transportation' => 300,
            'demand_planning' => 300,
            'service_management' => 300,
            'information_technology' => 300,
        ];

        $timeoutSeconds = $moduleTimeouts[$configKey] ?? $defaultTimeout;
        $retries = 3;
        $backoffMs = 1000;

        try {

            if ($configKey === 'transportation') {

                // Extract required parameters from payload
                $simulationId = $payload['simulation_id'] ?? null;
                $groupNumber = $payload['group_number'] ?? null;
                $facultyId = $payload['faculty_id'] ?? null;
                $quarterId = $payload['quarter'] ?? null;

                // Step 1: Fetch matching Distribution records
                $distributionsPlan = Distribution::where([
                    'simulation_id' => $simulationId,
                    'group_number' => $groupNumber,
                    'faculty_id' => $facultyId,
                    'quarter' => $quarterId,
                    'is_operational' => 'yes',
                ])->get();

                // Step 2: Transform them into the required format
                $rows = $distributionsPlan->map(function ($dist) {
                    return [
                        'region_id' => $dist->region_id,
                        'dc_number' => $dist->dc_number,
                        'dc_type' => $dist->dc_type,
                        'rfid_application' => $dist->rfid_application,
                        'sac_shipping' => $dist->sac_shipping,
                        'emergency_carrier_id' => $dist->emergency_carrier_id,
                        'is_operational' => $dist->is_operational,
                    ];
                })->toArray();

                // Step 3: Optional fallback if no data found
                if (empty($rows)) {
                    $rows[] = [
                        'region_id' => 2,
                        'dc_number' => 2,
                        'dc_type' => 'outsourced',
                        'rfid_application' => 'yes',
                        'sac_shipping' => 'economy',
                        'emergency_carrier_id' => 5,
                        'is_operational' => 'yes',
                    ];
                }

                // Step 4: Attach to payload
                $payload['distributions_plan'] = [
                    'rows' => $rows,
                ];
            }

            $response = Http::retry($retries, $backoffMs)
                ->timeout($timeoutSeconds)
                ->post($url, $payload);

            Log::info("AI module {$configKey} HTTP response", [
                'url' => $url,
                'status' => $response->status(),
                'timeout_used' => $timeoutSeconds,
                'body' => $response->body(),
            ]);

            if (! $response->successful()) {
                Log::warning("AI endpoint returned non-success for {$configKey}", [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $json = $response->json();

            return is_array($json) ? $json : (array) $json;
        } catch (\Throwable $e) {
            Log::error("HTTP fetch error for AI endpoint {$configKey}: ".$e->getMessage(), ['url' => $url]);

            return null;
        }
    }

    /**
     * Orchestrator: persist decisions for all domains inside a DB transaction.
     */
    public function saveAIDecisions(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $decisions
    ): array {
        $summary = ['success' => false, 'inserted' => [], 'errors' => []];

        DB::beginTransaction();
        try {
            $timestamp = Carbon::now();

            $forecastRows = $this->normalizeRows($decisions['forecasting'] ?? $decisions['forecast'] ?? []);
            if (! empty($forecastRows)) {
                $summary['inserted']['forecasting'] = $this->saveForecasting($simulationId, $facultyId, $groupNumber, $quarterId, $forecastRows, $timestamp);
            }

            $procurementRows = $this->normalizeRows($decisions['procurement'] ?? []);
            if (! empty($procurementRows)) {
                $summary['inserted']['procurement'] = $this->saveProcurement($simulationId, $facultyId, $groupNumber, $quarterId, $procurementRows, $timestamp);
            }

            $manufacturingRows = $this->normalizeRows($decisions['manufacturing'] ?? $decisions['manufacture'] ?? []);
            if (! empty($manufacturingRows)) {
                $summary['inserted']['manufacturing'] = $this->saveManufacturing($simulationId, $facultyId, $groupNumber, $quarterId, $manufacturingRows, $timestamp);
            }

            $distributionRows = $this->normalizeRows($decisions['distribution'] ?? $decisions['distributions'] ?? []);
            if (! empty($distributionRows)) {
                $summary['inserted']['distributions'] = $this->saveDistribution($simulationId, $facultyId, $groupNumber, $quarterId, $distributionRows, $timestamp);
            }

            // $transport = $decisions['transportation'] ?? $decisions['transport'] ?? [];
            // $transportRows = $this->normalizeTransportation($transport) ?: $this->normalizeRows($transport);
            // if (!empty($transportRows)) {
            //     $summary['inserted']['transportation'] = $this->saveTransportation($simulationId, $facultyId, $groupNumber, $quarterId, $transportRows, $timestamp);
            // }

            $transportRows = $this->normalizeRows($decisions['transportation'] ?? $decisions['transportation'] ?? []);
            if (! empty($transportRows)) {
                $summary['inserted']['transportation'] = $this->saveTransportation($simulationId, $facultyId, $groupNumber, $quarterId, $transportRows, $timestamp);
            }

            $demandPlanningRows = $this->normalizeRows($decisions['demand_planning'] ?? $decisions['demand_planning'] ?? []);
            if (! empty($demandPlanningRows)) {
                $summary['inserted']['demand_planning'] = $this->saveDemandPlanning($simulationId, $facultyId, $groupNumber, $quarterId, $demandPlanningRows, $timestamp);
            }

            $serviceRows = $this->normalizeRows($decisions['service_management'] ?? $decisions['service_management'] ?? []);
            if (! empty($serviceRows)) {
                $summary['inserted']['service_management'] = $this->saveServiceManagement($simulationId, $facultyId, $groupNumber, $quarterId, $serviceRows, $timestamp);
            }

            $it = $decisions['information_technology'] ?? $decisions['information_technology'] ?? [];
            $itRows = $this->normalizeIT($it) ?: $this->normalizeRows($it);
            if (! empty($itRows)) {
                $summary['inserted']['information_technology'] = $this->saveInformationTechnology($simulationId, $facultyId, $groupNumber, $quarterId, $itRows, $timestamp);
            }

            DB::commit();
            $summary['success'] = true;

            return $summary;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AiDecisionSaveService orchestration error: '.$e->getMessage(), [
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
            ]);
            $summary['errors'][] = $e->getMessage();

            return $summary;
        }
    }

    /* Normalizers */
    protected function normalizeRows($payload): array
    {
        if (empty($payload)) {
            return [];
        }

        // 1️⃣ Directly use rows if present
        if (is_array($payload) && isset($payload['rows']) && is_array($payload['rows'])) {
            return $payload['rows'];
        }

        // 2️⃣ If wrapper like { "module": { "rows": [...] } }
        if (is_array($payload) && count($payload) === 1) {
            $first = reset($payload);
            if (is_array($first) && isset($first['rows']) && is_array($first['rows'])) {
                return $first['rows'];
            }
        }

        // 3️⃣ If already a sequential array (no "rows" key)
        if (is_array($payload) && $this->isSequentialArray($payload)) {
            return $payload;
        }

        // 4️⃣ Single record → wrap it
        if (is_array($payload)) {
            return [$payload];
        }

        return [];
    }

    protected function normalizeProcurement($payload): array
    {
        if (empty($payload)) {
            return [];
        }
        if (isset($payload['region_id']) || isset($payload['raw_materials']) || isset($payload['sub_assemblies'])) {
            return [$payload];
        }

        return $this->normalizeRows($payload);
    }

    protected function normalizeTransportation($payload): array
    {
        if (empty($payload)) {
            return [];
        }
        if (isset($payload['transportation']) && isset($payload['transportation']['shipments'])) {
            return $payload['transportation']['shipments'];
        }
        if (isset($payload['shipments'])) {
            return $payload['shipments'];
        }
        if ($this->isSequentialArray($payload)) {
            return $payload;
        }
        if (isset($payload['transportation']) && isset($payload['transportation']['rows'])) {
            return $payload['transportation']['rows'];
        }
        if (isset($payload['product_id']) && isset($payload['carrier_id'])) {
            return [$payload];
        }

        return [];
    }

    protected function normalizeIT($payload): array
    {
        if (isset($payload['information_technology']) && is_array($payload['information_technology'])) {
            return [$payload['information_technology']];
        }
        if ($this->isSequentialArray($payload)) {
            return $payload;
        }
        if (isset($payload['ai_integration']) || isset($payload['carriers']) || isset($payload['suppliers'])) {
            return [$payload];
        }

        return [];
    }

    protected function isSequentialArray(array $arr): bool
    {
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /* Domain savers */

    protected function saveForecasting(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $rows,
        \DateTimeInterface $timestamp
    ): int {
        $count = 0;
        foreach ($rows as $item) {
            $common = [
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
                'firm_id' => $this->firmId,
            ];
            if (isset($item['region_id'])) {
                $common['region_id'] = $item['region_id'];
            }
            if (isset($item['product_id'])) {
                $common['product_id'] = $item['product_id'];
            }

            $update = [
                'demand_channel_01' => $item['demand_channel_01'] ?? 0,
                'demand_channel_02' => $item['demand_channel_02'] ?? 0,
                'accuracy_channel_01' => $item['accuracy_channel_01'] ?? null,
                'accuracy_channel_02' => $item['accuracy_channel_02'] ?? null,
                'change_saved_by' => $this->changeSavedBy,
                'change_saved_timestamp' => $timestamp,
            ];

            Forecasting::updateOrCreate($common, $update);

            if (empty($update['accuracy_channel_01']) || empty($update['accuracy_channel_02'])) {
                try {
                    if (class_exists(ActualDemand::class)) {
                        $actual = ActualDemand::where($common)->first();
                        if ($actual) {
                            $forecastModel = Forecasting::where($common)->first();
                            if ($forecastModel) {
                                $forecastModel->update([
                                    'accuracy_channel_01' => $this->generateDemandService->forecastAccuracy($forecastModel->demand_channel_01 ?? 0, $actual->demand_channel_01 ?? 0),
                                    'accuracy_channel_02' => $this->generateDemandService->forecastAccuracy($forecastModel->demand_channel_02 ?? 0, $actual->demand_channel_02 ?? 0),
                                ]);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Forecast accuracy calc failed: '.$e->getMessage(), ['simulation_id' => $simulationId, 'quarter' => $quarterId]);
                }
            }

            $count++;
        }

        return $count;
    }

    protected function saveProcurement(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $blocks,
        \DateTimeInterface $timestamp
    ): int {
        $count = 0;
        foreach ($blocks as $block) {
            $regionId = $block['region_id'] ?? null;
            if ($regionId === null) {
                Log::warning('Skipping procurement block missing region_id', ['block' => $block]);

                continue;
            }

            $procTypes = $block['procurement_type'] ?? '';
            if (is_array($procTypes)) {
                $procTypes = implode(',', $procTypes);
            } else {
                $procTypes = trim((string) $procTypes);
            }

            $common = [
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
                'region_id' => $regionId,
            ];

            $procUpdate = [
                'procurement_type' => $procTypes,
                'change_saved_by' => $this->changeSavedBy,
                'change_saved_timestamp' => $timestamp,
            ];

            $procurement = Procurement::updateOrCreate($common, $procUpdate);
            $count++;

            if ((int) $regionId === 1 && ! empty($block['raw_materials']) && is_array($block['raw_materials'])) {
                foreach ($block['raw_materials'] as $rm) {
                    if (empty($rm['raw_material_id'])) {
                        Log::warning('Skipping raw_material without id', ['block' => $block, 'rm' => $rm]);

                        continue;
                    }
                    ProcurementRawMaterial::updateOrCreate(
                        [
                            'procurement_id' => $procurement->id,
                            'raw_material_id' => (int) $rm['raw_material_id'],
                        ],
                        [
                            'quantity' => (int) ($rm['quantity'] ?? 0),
                        ]
                    );
                }
            }

            if (! empty($block['sub_assemblies']) && is_array($block['sub_assemblies'])) {
                foreach ($block['sub_assemblies'] as $sa) {
                    if (empty($sa['sub_assembly_component_id']) || empty($sa['supplier_id']) || ! isset($sa['supply_channel_id'])) {
                        Log::warning('Skipping invalid sub_assembly entry', ['sa' => $sa, 'procurement_id' => $procurement->id]);

                        continue;
                    }

                    ProcurementSubAssembly::updateOrCreate(
                        [
                            'procurement_id' => $procurement->id,
                            'sub_assembly_component_id' => (int) $sa['sub_assembly_component_id'],
                            'supplier_id' => (int) $sa['supplier_id'],
                            'supply_channel_id' => (int) $sa['supply_channel_id'],
                        ],
                        [
                            'quantity' => (int) ($sa['quantity'] ?? ($sa['total_shipment'] ?? 0)),
                            'total_shipment' => (int) ($sa['total_shipment'] ?? 0),
                            'quantity_delay' => (int) ($sa['quantity_delay'] ?? 0),
                        ]
                    );
                }
            }
        }

        return $count;
    }

    protected function saveManufacturing(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $rows,
        \DateTimeInterface $timestamp
    ): int {

        $count = 0;
        $totalPostponedZero = 0;   // we will accumulate total postponed product_0
        $hasProductZero = false;   // track if product_id 0 exists in rows

        foreach ($rows as $item) {

            if (! array_key_exists('product_id', $item)) {
                Log::warning('Skipping manufacturing row without product_id', ['row' => $item]);

                continue;
            }

            $productId = (int) $item['product_id'];

            // Track postponed production of Product Zero
            if ($productId === 0) {
                $hasProductZero = true;

                $prod = (int) ($item['production_quantity'] ?? 0);
                $adj = (int) ($item['adjustable_production'] ?? 0);

                // postponed = production + adjustable
                $totalPostponedZero = $prod + $adj;
            }

            $common = [
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
                'product_id' => $productId,
            ];

            $update = [
                'production_quantity' => $item['production_quantity'] ?? null,
                'emergency_production' => $item['emergency_production'] ?? null,
                'adjustable_production' => $item['adjustable_production'] ?? null,
                'change_saved_by' => $this->changeSavedBy,
                'change_saved_timestamp' => $timestamp,
            ];

            Manufacturing::updateOrCreate($common, $update);
            $count++;
        }

        // -----------------------------------------------------
        // 🔥 NEW: Handle PostpondProduction for Product Zero
        // -----------------------------------------------------
        // Only if product 0 exists in the CSV/rows
        if ($hasProductZero) {

            if ($totalPostponedZero <= 0) {
                PostpondProduction::where([
                    'simulation_id' => $simulationId,
                    'faculty_id' => $facultyId,
                    'group_number' => $groupNumber,
                    'quarter' => $quarterId,
                    'product_id' => 0,
                ])->delete();
            } else {
                PostpondProduction::updateOrCreate(
                    [
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_number' => $groupNumber,
                        'quarter' => $quarterId,
                        'region_id' => 1,
                        'product_id' => 0,
                    ],
                    [
                        'quantity' => $totalPostponedZero,
                    ]
                );
            }
        }

        return $count;
    }

    protected function saveDistribution(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $rows,
        \DateTimeInterface $timestamp
    ): int {
        $count = 0;
        foreach ($rows as $item) {
            if (empty($item['region_id']) || ! isset($item['dc_number'])) {
                Log::warning('Skipping distribution row without region_id/dc_number', ['row' => $item]);

                continue;
            }

            $common = [
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
                'region_id' => $item['region_id'],
                'dc_number' => $item['dc_number'],
            ];

            $update = array_merge($item, [
                'change_saved_by' => $this->changeSavedBy,
                'change_saved_timestamp' => $timestamp,
            ]);

            Distribution::updateOrCreate($common, $update);
            $count++;
        }

        return $count;
    }

    protected function saveTransportation(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $rows,
        \DateTimeInterface $timestamp
    ): int {
        $count = 0;

        foreach ($rows as $item) {
            if (empty($item['distribution_center_id'])) {
                Log::warning('Skipping transportation row without distribution_center_id', ['row' => $item]);

                continue;
            }

            $distributionCenterId = (int) $item['distribution_center_id'];

            if (empty($item['shipments']) || ! is_array($item['shipments'])) {
                Log::warning('Skipping transportation row without shipments array', ['distribution_center_id' => $distributionCenterId, 'row' => $item]);

                continue;
            }

            $transport = Transportation::firstOrCreate(
                [
                    'simulation_id' => $simulationId,
                    'quarter' => $quarterId,
                    'faculty_id' => $facultyId,
                    'group_number' => $groupNumber,
                    'distribution_center_id' => $distributionCenterId,
                ],
                [
                    'change_saved_by' => $this->changeSavedBy,
                ]
            );

            $infoRows = [];
            $now = $timestamp instanceof \DateTimeInterface ? $timestamp : now();

            foreach ($item['shipments'] as $s) {
                if (empty($s['product_id']) || empty($s['carrier_id'])) {
                    Log::warning('Skipping transportation shipment missing keys', ['shipment' => $s, 'distribution_center_id' => $distributionCenterId]);

                    continue;
                }

                $productId = (int) $s['product_id'];
                $carrierId = (int) $s['carrier_id'];
                $supplyChannelId = isset($s['supply_channel_id']) ? (int) $s['supply_channel_id'] : null;
                $totalShipment = (int) ($s['total_shipment'] ?? 0);
                $quantity = (int) ($s['quantity'] ?? ($s['total_shipment'] ?? 0));
                $quantityDelay = (int) ($s['quantity_delay'] ?? 0);

                $infoRows[] = [
                    'transportation_id' => $transport->id,
                    'product_id' => $productId,
                    'carrier_id' => $carrierId,
                    'supply_channel_id' => $supplyChannelId,
                    'total_shipment' => $totalShipment,
                    'quantity' => $quantity,
                    'quantity_delay' => $quantityDelay,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            try {
                TransportationInfo::where('transportation_id', $transport->id)->delete();

                if (! empty($infoRows)) {
                    TransportationInfo::insert($infoRows);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed replacing transportation info rows: '.$e->getMessage(), [
                    'transportation_id' => $transport->id,
                    'simulation_id' => $simulationId,
                ]);
            }

            $count++;
        }

        return $count;
    }

    protected function saveDemandPlanning(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $rows,
        \DateTimeInterface $timestamp
    ): int {
        $count = 0;
        foreach ($rows as $item) {
            if (empty($item['region_id']) || ! isset($item['product_id']) || ! isset($item['demand_channel_id'])) {
                Log::warning('Skipping demand planning row missing keys', ['row' => $item]);

                continue;
            }

            $common = [
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
                'region_id' => $item['region_id'],
                'product_id' => $item['product_id'],
                'demand_channel_id' => $item['demand_channel_id'],
            ];

            $update = [
                'status' => $item['status'] ?? null,
                'price' => $item['price'] ?? null,
                'market_price' => $item['market_price'] ?? null,
                'marketSpending' => $item['marketSpending'] ?? null,
                'change_saved_by' => $this->changeSavedBy,
                'change_saved_timestamp' => $timestamp,
            ];

            DemandPlanning::updateOrCreate($common, $update);
            $count++;
        }

        return $count;
    }

    protected function saveServiceManagement(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $rows,
        \DateTimeInterface $timestamp
    ): int {
        $count = 0;
        foreach ($rows as $item) {
            if (empty($item['region_id']) || ! isset($item['service_id'])) {
                Log::warning('Skipping service management row missing keys', ['row' => $item]);

                continue;
            }

            $common = [
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
                'region_id' => $item['region_id'],
            ];

            $update = [
                'service_id' => $item['service_id'],
                'change_saved_by' => $this->changeSavedBy,
                'change_saved_timestamp' => $timestamp,
            ];

            ServiceManagement::updateOrCreate($common, $update);
            $count++;
        }

        return $count;
    }

    protected function saveInformationTechnology(
        int $simulationId,
        ?int $facultyId,
        ?int $groupNumber,
        int $quarterId,
        array $rows,
        \DateTimeInterface $timestamp
    ): int {
        $count = 0;
        foreach ($rows as $item) {
            $it = InformationTechnology::create([
                'simulation_id' => $simulationId,
                'faculty_id' => $facultyId,
                'group_number' => $groupNumber,
                'quarter' => $quarterId,
                'change_saved_by' => $this->changeSavedBy,
                'change_saved_timestamp' => $timestamp,
                'ai_integration' => 1,
            ]);

            if (! empty($item['carrier_sync']) && is_array($item['carrier_sync'])) {
                foreach ($item['carrier_sync'] as $carrier) {
                    if (empty($carrier['carrier_id'])) {
                        Log::warning('Skipping IT carrier without carrier_id', ['carrier' => $carrier]);

                        continue;
                    }
                    InformationTechnologyCarrier::create([
                        'information_technology_id' => $it->id,
                        'carrier_id' => (int) $carrier['carrier_id'],
                        'status' => 'active',
                    ]);
                }
            }

            if (! empty($item['supplier_sync']) && is_array($item['supplier_sync'])) {
                foreach ($item['supplier_sync'] as $supplier) {
                    if (empty($supplier['supplier_id'])) {
                        Log::warning('Skipping IT supplier without supplier_id', ['supplier' => $supplier]);

                        continue;
                    }
                    InformationTechnologySupplier::create([
                        'information_technology_id' => $it->id,
                        'supplier_id' => (int) $supplier['supplier_id'],
                        'status' => 'active',
                    ]);
                }
            }

            $count++;
        }

        return $count;
    }
}
