<?php

namespace App\Services;

use App\Jobs\GenerateScorecardJob;
use App\Jobs\QuarterClosing\ProcessGroupJob;
use App\Jobs\QuarterClosing\StoreFinancialSnapshotJob;
use App\Jobs\QuarterClosing\SyncQuarterDataJob;
use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandElasticity;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\ServiceManagement;
use App\Models\DemandChannel;
use App\Models\Packaging;
use App\Models\Product;
use App\Models\Region;
use App\Models\SimulationTeamMember;
use App\Services\Reports\BalanceSheetService;
use App\Services\Reports\FlexScorecardService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuarterClosingService
{
    protected $qualityAnalysisService;

    protected $decisionDataSyncService;

    protected $supplierMarketShareService;

    protected $generateDemandService;

    protected $balanceSheetService;

    protected $flexScorecardService;

    protected $exportReportService;

    protected $aiDecisionSaveService;

    public function __construct(
        QualityAnalysisService $qualityAnalysisService,
        DecisionDataSyncService $decisionDataSyncService,
        SupplierMarketShareService $supplierMarketShareService,
        GenerateDemandService $generateDemandService,
        BalanceSheetService $balanceSheetService,
        FlexScorecardService $flexScorecardService,
        ExportReportService $exportReportService,
        AiDecisionSaveService $aiDecisionSaveService
    ) {
        $this->qualityAnalysisService = $qualityAnalysisService;
        $this->decisionDataSyncService = $decisionDataSyncService;
        $this->supplierMarketShareService = $supplierMarketShareService;
        $this->generateDemandService = $generateDemandService;
        $this->balanceSheetService = $balanceSheetService;
        $this->flexScorecardService = $flexScorecardService;
        $this->exportReportService = $exportReportService;
        $this->aiDecisionSaveService = $aiDecisionSaveService;
    }

    public function quarterClosure($simulation_id, $faculty_id, $quarter_id)
    {
        // 🔒 Align lock with job middleware
        $lockKey = "quarter-closure:{$simulation_id}:{$quarter_id}";
        $lock = Cache::lock($lockKey, 600);

        if (! $lock->get()) {
            Log::warning('quarterClosure already running — skipping', [
                'lock_key' => $lockKey,
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
            ]);

            return;
        }

        try {
            // 🧠 Idempotency (service is the single source of truth)
            $idempotentKey = "flexee:quarterClosure:done:{$simulation_id}:{$faculty_id}:{$quarter_id}";
            if (Cache::has($idempotentKey)) {
                Log::info('quarterClosure skipped — already processed', [
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $quarter_id,
                ]);

                return;
            }

            if ($quarter_id > 4) {
                $this->supplierMarketShareService
                    ->applyMarketRules($simulation_id, $faculty_id, $quarter_id);
            }

            $simulationGroup = SimulationTeamMember::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
            ])->whereNotNull('student_joined')
                ->pluck('simulation_team_id')
                ->unique()
                ->values();

            if ($simulationGroup->isEmpty()) {
                Log::warning('No teams found for quarterClosure', [
                    'simulation_id' => $simulation_id,
                    'faculty_id' => $faculty_id,
                    'quarter' => $quarter_id,
                ]);

                return;
            }

            foreach ($simulationGroup as $group) {
                if ($quarter_id <= 3) {
                    continue;
                }

                try {
                    $syncJob = new SyncQuarterDataJob($simulation_id, $faculty_id, $group, $quarter_id);
                    $processJob = new ProcessGroupJob($simulation_id, $faculty_id, $group, $quarter_id);
                    $snapshotJob = new StoreFinancialSnapshotJob($simulation_id, $faculty_id, $group, $quarter_id);
                    $scorecardJob = new GenerateScorecardJob($simulation_id, $faculty_id, $group, $quarter_id);

                    $syncJob->onQueue('quarter-closure');
                    $processJob->onQueue('quarter-closure');
                    $snapshotJob->onQueue('financial-reports');
                    $scorecardJob->onQueue('scorecards');

                    Bus::chain([
                        $syncJob,
                        $processJob,
                        $snapshotJob,
                        $scorecardJob,
                    ])->onQueue('quarter-closure')->dispatch();

                } catch (\Throwable $e) {
                    Log::error('Failed to dispatch Quarter closure chain', [
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group' => $group,
                        'quarter' => $quarter_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ✅ Mark quarter processed ONLY after dispatch
            Cache::put($idempotentKey, true, now()->addHours(24));
        } catch (\Throwable $e) {
            Log::error('Quarter Closing update failed', [
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            try {
                $lock->release();
            } catch (\Throwable $releaseEx) {
                Log::warning('Failed to release quarterClosure lock', [
                    'lock_key' => $lockKey,
                    'error' => $releaseEx->getMessage(),
                ]);
            }
        }
    }

    public function processGroup($simulation_id, $faculty_id, $group, $quarter_id)
    {

        $group_number = (int) $group;
        $previousQuarter = $quarter_id - 1;

        $regions = Region::all();
        $products = Product::with('material.sac.supplierComponent.supplier')->get();
        $channels = DemandChannel::all();
        $defaultPackagingCost = Packaging::find(1)->cost ?? 10;
        $timestamp = now();

        $forecastDemand = Forecasting::where(compact('simulation_id', 'faculty_id'))
            ->where('quarter', $previousQuarter)->get();

        $actualDemandEntries = ActualDemand::where(compact('simulation_id', 'faculty_id'))
            ->where('quarter', $previousQuarter)->get();

        $demandPlanningEntries = DemandPlanning::where(compact('simulation_id', 'faculty_id'))
            ->where('quarter', $quarter_id)->get();

        $pastPlans = DemandPlanning::where(compact('simulation_id', 'faculty_id'))
            ->where('quarter', $previousQuarter)->get();

        $performanceIndicators = DemandElasticity::where(compact('simulation_id', 'faculty_id'))
            ->where('quarter', $previousQuarter)->get();

        $services = ServiceManagement::with('serviceOutsourcing.serviceCost')
            ->where(compact('simulation_id', 'faculty_id'))
            ->where('quarter', $quarter_id)->get();

        $currentPlans = DemandPlanning::where(compact('simulation_id', 'faculty_id'))
            ->where('quarter', $quarter_id)->get();

        foreach ($regions as $region) {
            foreach ($products as $product) {
                foreach ($channels as $channel) {

                    $pastForecast = $forecastDemand
                        ->where('region_id', $region->id)
                        ->where('product_id', $product->id)
                        ->firstWhere('group_number', $group_number);

                    $pastActual = $actualDemandEntries
                        ->where('region_id', $region->id)
                        ->where('product_id', $product->id)
                        ->firstWhere('group_number', $group_number);

                    $pastPlan = $pastPlans
                        ->where('region_id', $region->id)
                        ->where('product_id', $product->id)
                        ->where('demand_channel_id', $channel->id)
                        ->firstWhere('group_number', $group_number);

                    $currentPlan = $demandPlanningEntries
                        ->where('region_id', $region->id)
                        ->where('product_id', $product->id)
                        ->where('demand_channel_id', $channel->id)
                        ->firstWhere('group_number', $group_number);

                    $pastIndicator = $performanceIndicators
                        ->where('region_id', $region->id)
                        ->where('product_id', $product->id)
                        ->where('demand_channel_id', $channel->id)
                        ->firstWhere('group_number', $group_number);

                    if (! $pastPlan || ! $pastActual || ! $currentPlan) {
                        continue;
                    }

                    $forecastVolume = $pastForecast ? (
                        $channel->id == 1 ? ($pastForecast->demand_channel_01 ?? 0) : ($pastForecast->demand_channel_02 ?? 0)
                    ) : 0;

                    $actualSale = $channel->id == 1
                      ? ($pastActual->demand_channel_01 ?? 0)
                      : ($pastActual->demand_channel_02 ?? 0);

                    $pq = $this->qualityAnalysisService->calculatePQ($product, $simulation_id, $previousQuarter, $group_number);
                    $sq = $this->qualityAnalysisService->calculateSQ($group_number, $region->id, $services);
                    $aq = $this->qualityAnalysisService->calculateAQ($forecastVolume, $actualSale);

                    $rawValues = [
                        'Marketing' => $pastPlan->marketSpending ?? 0,
                        'Packaging' => $defaultPackagingCost,
                        'Warranty' => 8 + 3 * 9,
                    ];

                    $preferences = $this->qualityAnalysisService->getPreferences($product->sort_name, $region->id);

                    $cai = $this->qualityAnalysisService->calculateCAI($aq, $sq, $pq, $preferences, $rawValues);
                    $baseCai = $quarter_id == 1 ? 0.25 : ($pastIndicator->cai ?? 0.25);

                    // 1. Base demand
                    $baseDemand = $this->qualityAnalysisService->calculateAdjustedDemand(
                        $actualSale,
                        $cai,
                        $baseCai,
                        $currentPlan->price,
                        $pastPlan->price
                    );

                    // 2. Quarter growth factor (2.5% per quarter, compound)
                    $quarterGrowth = pow(1.025, max(0, $quarter_id - 1));

                    // 3. Apply growth
                    $rawDemand = $baseDemand * $quarterGrowth;

                    // 4. Final demand (round safely, never collapse unintentionally)
                    $newDemand = max(1, (int) ceil($rawDemand));

                    DemandElasticity::updateOrCreate(
                        [
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_number,
                            'quarter' => $quarter_id,
                            'region_id' => $region->id,
                            'product_id' => $product->id,
                            'demand_channel_id' => $channel->id,
                        ],
                        [
                            'pq' => $pq,
                            'sq' => $sq,
                            'aq' => $aq,
                            'cai' => $cai,
                        ]
                    );

                    $field = $channel->id == 1 ? 'demand_channel_01' : 'demand_channel_02';

                    ActualDemand::updateOrCreate(
                        [
                            'simulation_id' => $simulation_id,
                            'faculty_id' => $faculty_id,
                            'group_number' => $group_number,
                            'quarter' => $quarter_id,
                            'region_id' => $region->id,
                            'product_id' => $product->id,
                        ],
                        [
                            'firm_id' => 1,
                            $field => $newDemand,
                            'change_saved_timestamp' => $timestamp,
                        ]
                    );

                    // Update forecast accuracy
                    $forecastRecord = Forecasting::where([
                        'simulation_id' => $simulation_id,
                        'faculty_id' => $faculty_id,
                        'group_number' => $group_number,
                        'quarter' => $quarter_id,
                        'region_id' => $region->id,
                        'product_id' => $product->id,
                    ])->first();

                    if ($forecastRecord) {
                        if ($channel->id == 1) {
                            $forecastRecord->update([
                                'accuracy_channel_01' => $this->generateDemandService->forecastAccuracy(
                                    $forecastRecord->demand_channel_01,
                                    round($newDemand)
                                ),
                            ]);
                        } else {
                            $forecastRecord->update([
                                'accuracy_channel_02' => $this->generateDemandService->forecastAccuracy(
                                    $forecastRecord->demand_channel_02,
                                    round($newDemand)
                                ),
                            ]);
                        }
                    }
                }
            }
        }
    }
}
