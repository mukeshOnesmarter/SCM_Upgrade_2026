<?php

namespace App\Http\Middleware;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandElasticity;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\ServiceManagement;
use App\Models\DemandChannel;
use App\Models\Packaging;
use App\Models\Product;
use App\Models\Region;
use App\Models\SimulationQuarter;
use App\Models\SimulationQuarterCustomize;
use App\Models\SimulationTeamMember;
use App\Services\ActiveUserService;
use App\Services\DecisionDataSyncService;
use App\Services\QualityAnalysisService;
use App\Services\SupplierMarketShareService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncDecisionDataMiddleware
{
    protected $activeUserService;

    protected $qualityAnalysisService;

    protected $decisionDataSyncService;

    protected $supplierMarketShareService;

    public function __construct(
        ActiveUserService $activeUserService,
        QualityAnalysisService $qualityAnalysisService,
        DecisionDataSyncService $decisionDataSyncService,
        SupplierMarketShareService $supplierMarketShareService
    ) {
        $this->activeUserService = $activeUserService;
        $this->qualityAnalysisService = $qualityAnalysisService;
        $this->decisionDataSyncService = $decisionDataSyncService;
        $this->supplierMarketShareService = $supplierMarketShareService;
    }

    /**
     * Handle incoming request and trigger decision data sync if prior quarter is closed.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $this->activeUserService->getActiveUser();

        $teamMember = SimulationTeamMember::where([
            'simulation_id' => $request->simulation_id,
            'student_id' => $user->id,
        ])->first();

        if (! $teamMember) {
            return $next($request);
        }

        $simulationId = $request->simulation_id;
        $quarterId = (int) $request->quarter_id;
        $facultyId = $teamMember->faculty_id;
        $groupId = $teamMember->simulation_team_id;

        if (! $facultyId || ! $groupId || ! $quarterId) {
            return $next($request);
        }

        $previousQuarter = $quarterId - 1;

        // Get the quarter record (customized or default)
        $quarterRecord = SimulationQuarterCustomize::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter_name' => $previousQuarter,
        ])->first();

        if (! $quarterRecord) {
            $quarterRecord = SimulationQuarter::where([
                'simulation_id' => $simulationId,
                'quarter_name' => $previousQuarter,
            ])->first();
        }

        // Apply Supplier Market Rules
        if ($quarterId > 4 && $quarterRecord && Carbon::parse($quarterRecord->end_date)->lte(Carbon::today())) {
            $this->supplierMarketShareService->applyMarketRules(
                $simulationId, $facultyId, $previousQuarter);
        }

        // If previous quarter is closed, sync decisions
        if ($quarterId > 3 && $quarterRecord && Carbon::parse($quarterRecord->end_date)->lte(Carbon::today())) {

            $synced = $this->decisionDataSyncService->syncQuarterData($simulationId, $facultyId, $groupId, $previousQuarter);

            if ($synced) {
                try {
                    DB::beginTransaction();

                    $regions = Region::all();
                    $products = Product::with('material.sac.supplierComponent.supplier')->get();
                    $channels = DemandChannel::all();

                    $forecastDemand = Forecasting::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'quarter' => $previousQuarter - 1,
                    ])->get();

                    $actualDemandEntries = ActualDemand::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'quarter' => $previousQuarter - 1,
                    ])->get();

                    $demandPlanningEntries = DemandPlanning::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'quarter' => $previousQuarter,
                    ])->get();

                    $demandPlanningEntriesPastQtr = DemandPlanning::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'quarter' => $previousQuarter - 1,
                    ])->get();

                    $serviceManagementEntries = ServiceManagement::with('serviceOutsourcing.serviceCost')->where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'quarter' => $previousQuarter,
                    ])->get();

                    $performanceIndicators = DemandElasticity::where([
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'quarter' => $previousQuarter - 1,
                    ])->get();

                    $defaultPackagingCost = Packaging::find(1)->cost ?? 10;

                    foreach ($regions as $region) {
                        foreach ($products as $product) {
                            foreach ($channels as $channel) {
                                $group_number = $groupId;

                                $pastforecastDemand = $forecastDemand
                                    ->where('region_id', $region->id)
                                    ->where('product_id', $product->id)
                                    ->firstWhere('group_number', $group_number);

                                $pastDemand = $actualDemandEntries
                                    ->where('region_id', $region->id)
                                    ->where('product_id', $product->id)
                                    ->firstWhere('group_number', $group_number);

                                $pastPlan = $demandPlanningEntriesPastQtr
                                    ->where('region_id', $region->id)
                                    ->where('product_id', $product->id)
                                    ->where('demand_channel_id', $channel->id)
                                    ->firstWhere('group_number', $group_number);

                                $pastPerformanceIndicators = $performanceIndicators
                                    ->where('region_id', $region->id)
                                    ->where('product_id', $product->id)
                                    ->where('demand_channel_id', $channel->id)
                                    ->firstWhere('group_number', $group_number);

                                $currentPlan = $demandPlanningEntries
                                    ->where('region_id', $region->id)
                                    ->where('product_id', $product->id)
                                    ->where('demand_channel_id', $channel->id)
                                    ->firstWhere('group_number', $group_number);

                                // Skip if necessary data is missing
                                if (! $pastPlan || ! $pastDemand) {
                                    continue;
                                }

                                $forecastVolume = 0;
                                if ($pastforecastDemand) {
                                    $forecastVolume = $channel->id == 1
                                        ? $pastforecastDemand->demand_channel_01 ?? 0
                                        : $pastforecastDemand->demand_channel_02 ?? 0;
                                }

                                $actualSale = 0;
                                if ($pastDemand) {
                                    $actualSale = $channel->id == 1
                                        ? $pastDemand->demand_channel_01 ?? 0
                                        : $pastDemand->demand_channel_02 ?? 0;
                                }

                                // Calculate PQ, SQ, AQ
                                $pq = $this->qualityAnalysisService->calculatePQ($product, $simulationId, $previousQuarter, $group_number);
                                $sq = $this->qualityAnalysisService->calculateSQ($group_number, $region->id, $serviceManagementEntries);
                                $aq = $this->qualityAnalysisService->calculateAQ($forecastVolume, $actualSale);

                                // Raw values for marketing inputs
                                $rawMarketing = $plannedDemand->marketSpending ?? 0;
                                $rawPackaging = $defaultPackagingCost;
                                $rawWarranty = 8 + 3 * 9;

                                $preferences = $this->qualityAnalysisService->getPreferences($product->sort_name, $region->id);

                                $rawValues = [
                                    'Marketing' => $rawMarketing,
                                    'Packaging' => $rawPackaging,
                                    'Warranty' => $rawWarranty,
                                ];

                                // Calculate Composite Attractiveness Index (CAI)
                                $cai = $this->qualityAnalysisService->calculateCAI(
                                    $aq,
                                    $sq,
                                    $pq,
                                    $preferences,
                                    $rawValues
                                );

                                // Calculate New Demand:
                                $newDemand = $this->qualityAnalysisService->calculateAdjustedDemand(
                                    $actualSale,
                                    $cai,
                                    $pastPerformanceIndicators->cai,
                                    $currentPlan->price,
                                    $pastPlan->price
                                );

                                // Save Performance Indicators entry
                                $exists = DemandElasticity::where([
                                    'simulation_id' => $simulationId,
                                    'faculty_id' => $facultyId,
                                    'group_number' => $group_number,
                                    'quarter' => $previousQuarter,
                                    'region_id' => $region->id,
                                    'product_id' => $product->id,
                                    'demand_channel_id' => $channel->id,
                                ])->exists();

                                if (! $exists) {
                                    DemandElasticity::create([
                                        'simulation_id' => $simulationId,
                                        'faculty_id' => $facultyId,
                                        'group_number' => $group_number,
                                        'quarter' => $previousQuarter,
                                        'region_id' => $region->id,
                                        'product_id' => $product->id,
                                        'demand_channel_id' => $channel->id,
                                        'pq' => $pq,
                                        'sq' => $sq,
                                        'aq' => $aq,
                                        'cai' => $cai,
                                    ]);
                                }

                                // Save or update Actual Demand entry
                                $fieldName = $channel->id == 1 ? 'demand_channel_01' : 'demand_channel_02';
                                ActualDemand::updateOrCreate(
                                    [
                                        'simulation_id' => $simulationId,
                                        'faculty_id' => $facultyId,
                                        'group_number' => $group_number,
                                        'quarter' => $previousQuarter,
                                        'region_id' => $region->id,
                                        'product_id' => $product->id,
                                    ],
                                    [
                                        'firm_id' => 1,
                                        $fieldName => round($newDemand),
                                    ]
                                );
                            }

                            // Apply Demand Shift Rule between Channels
                            $currentPricing_01 = $demandPlanningEntries
                                ->where('region_id', $region->id)
                                ->where('product_id', $product->id)
                                ->where('group_number', $group_number)
                                ->firstWhere('demand_channel_id', 1);

                            $currentPricing_02 = $demandPlanningEntries
                                ->where('region_id', $region->id)
                                ->where('product_id', $product->id)
                                ->where('group_number', $group_number)
                                ->firstWhere('demand_channel_id', 2);

                            // Proceed only if both channels have pricing data
                            if ($currentPricing_01 && $currentPricing_02) {

                                $price1 = $currentPricing_01->price ?? 0;
                                $price2 = $currentPricing_02->price ?? 0;

                                // Avoid divide-by-zero
                                if ($price1 > 0 && $price2 > 0) {
                                    $priceDifferencePercent = abs($price1 - $price2) / min($price1, $price2) * 100;

                                    // Only proceed if price difference is significant
                                    if ($priceDifferencePercent > 33) {

                                        $demands = ActualDemand::where([
                                            'simulation_id' => $simulationId,
                                            'faculty_id' => $facultyId,
                                            'group_number' => $group_number,
                                            'quarter' => $previousQuarter,
                                            'region_id' => $region->id,
                                            'product_id' => $product->id,
                                        ])->first();

                                        if ($demands && ! $demands->shift_applied) {
                                            $demand1 = $demands->demand_channel_01 ?? 0;
                                            $demand2 = $demands->demand_channel_02 ?? 0;

                                            // Avoid applying shift if already shifted (optional: based on exact match or flag)
                                            $originalDemand1 = $demand1;
                                            $originalDemand2 = $demand2;

                                            $shiftPercent = 0.03;
                                            $shiftAmount = 0;

                                            if ($price1 > $price2 && $demand1 > 0) {
                                                $shiftAmount = round($demand1 * $shiftPercent);
                                                $demand1 -= $shiftAmount;
                                                $demand2 += $shiftAmount;
                                            } elseif ($price2 > $price1 && $demand2 > 0) {
                                                $shiftAmount = round($demand2 * $shiftPercent);
                                                $demand2 -= $shiftAmount;
                                                $demand1 += $shiftAmount;
                                            }

                                            // Apply update only if actual changes occurred
                                            if ($originalDemand1 !== $demand1 || $originalDemand2 !== $demand2) {
                                                $demands->update([
                                                    'demand_channel_01' => max(0, $demand1),
                                                    'demand_channel_02' => max(0, $demand2),
                                                    'shift_applied' => true,
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    DB::commit();
                } catch (Throwable $e) {
                    DB::rollBack();
                    Log::error('SyncDecisionDataMiddleware error: '.$e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'simulation_id' => $simulationId,
                        'faculty_id' => $facultyId,
                        'group_id' => $groupId,
                    ]);
                }
            }
        }

        return $next($request);
    }
}
