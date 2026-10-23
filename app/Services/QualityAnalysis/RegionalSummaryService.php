<?php

namespace App\Services\QualityAnalysis;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\Forecasting;
use App\Models\Decisions\ServiceManagement;
use App\Models\DemandChannel;
use App\Models\Packaging;
use App\Models\Product;
use App\Models\Region;
use App\Models\SimulationTeamMember;
use App\Services\QualityAnalysisService;
use Illuminate\Support\Facades\DB;

class RegionalSummaryService
{
    protected $productPreferences = [
        'SHA' => [
            1 => [ // Region 1
                'Advertising' => 3.26,
                'Alpha' => 3.14,
                'AQ' => 3.19,
                'Beta' => 3.15,
                'Bandwidth' => 3.99,
                'Marketing' => 3.33,
                'Packaging' => 4.81,
                'Price' => 4.52,
                'PQ' => 3.41,
                'Promotion' => 3.02,
                'SalesForce' => 3.56,
                'SQ' => 2.98,
                'Warranty' => 3.57,
            ],
            2 => [ // Region 2
                'Advertising' => 3.34,
                'Alpha' => 3.52,
                'AQ' => 3.56,
                'Beta' => 3.11,
                'Bandwidth' => 3.65,
                'Marketing' => 3.33,
                'Packaging' => 4.88,
                'Price' => 4.31,
                'PQ' => 3.45,
                'Promotion' => 3.14,
                'SalesForce' => 3.32,
                'SQ' => 2.74,
                'Warranty' => 3.55,
            ],
            3 => [ // Region 3
                'Advertising' => 3.57,
                'Alpha' => 3.09,
                'AQ' => 3.82,
                'Beta' => 3.35,
                'Bandwidth' => 3.72,
                'Marketing' => 3.61,
                'Packaging' => 4.11,
                'Price' => 4.88,
                'PQ' => 3.69,
                'Promotion' => 3.27,
                'SalesForce' => 3.62,
                'SQ' => 2.75,
                'Warranty' => 4.05,
            ],
        ],
        'ST' => [
            1 => [
                'Advertising' => 3.52,
                'Alpha' => 3.13,
                'AQ' => 3.49,
                'Beta' => 3.70,
                'Bandwidth' => 3.80,
                'Marketing' => 3.30,
                'Packaging' => 4.33,
                'Price' => 4.56,
                'PQ' => 3.40,
                'Promotion' => 3.30,
                'SalesForce' => 3.45,
                'SQ' => 2.92,
                'Warranty' => 3.82,
            ],
            2 => [
                'Advertising' => 4.19,
                'Alpha' => 3.44,
                'AQ' => 4.10,
                'Beta' => 3.70,
                'Bandwidth' => 3.59,
                'Marketing' => 3.78,
                'Packaging' => 5.35,
                'Price' => 4.51,
                'PQ' => 3.38,
                'Promotion' => 3.72,
                'SalesForce' => 3.64,
                'SQ' => 2.83,
                'Warranty' => 3.99,
            ],
            3 => [
                'Advertising' => 3.55,
                'Alpha' => 3.50,
                'AQ' => 3.91,
                'Beta' => 3.41,
                'Bandwidth' => 3.91,
                'Marketing' => 3.56,
                'Packaging' => 3.67,
                'Price' => 4.71,
                'PQ' => 3.92,
                'Promotion' => 3.17,
                'SalesForce' => 3.51,
                'SQ' => 2.85,
                'Warranty' => 3.98,
            ],
        ],
    ];

    protected $serviceTiers = [
        'Minimum' => [
            1 => ['cost' => 6, 'guarantee' => 0.10],
            2 => ['cost' => 7, 'guarantee' => 0.10],
            3 => ['cost' => 8, 'guarantee' => 0.10],
        ],
        'Standard' => [
            1 => ['cost' => 10, 'guarantee' => 0.20],
            2 => ['cost' => 12, 'guarantee' => 0.20],
            3 => ['cost' => 13, 'guarantee' => 0.20],
        ],
        'Enhanced' => [
            1 => ['cost' => 16, 'guarantee' => 0.30],
            2 => ['cost' => 18, 'guarantee' => 0.30],
            3 => ['cost' => 21, 'guarantee' => 0.30],
        ],
        'Premium' => [
            1 => ['cost' => 24, 'guarantee' => 0.40],
            2 => ['cost' => 27, 'guarantee' => 0.40],
            3 => ['cost' => 32, 'guarantee' => 0.40],
        ],
    ];

    protected QualityAnalysisService $qualityAnalysis;

    public function __construct()
    {
        $this->qualityAnalysis = new QualityAnalysisService;
    }

    public function regionalSummary($simulation_id, $faculty_id, $quarter)
    {
        try {
            // Load master data
            $regions = Region::all();
            $products = Product::with('material.sac.supplierComponent.supplier')->get();
            $channels = DemandChannel::all();

            // Get unique firm/group numbers participating in this simulation
            $firms = SimulationTeamMember::where('simulation_id', $simulation_id)
                ->whereNotNull('student_joined')
                ->select(DB::raw('simulation_team_id as group_number'))
                ->distinct()
                ->get();

            // Load actual demand data and demand planning for the quarter
            $forecastingEntries = Forecasting::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter,
            ])->get();

            $actualDemandEntries = ActualDemand::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter - 1,
            ])->get();

            $demandPlanningEntries = DemandPlanning::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter,
            ])->get();

            $demandPlanningEntriesPastQtr = DemandPlanning::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter - 1,
            ])->get();

            // Load service outsourcing data with cost details
            $serviceManagementEntries = ServiceManagement::with('serviceOutsourcing.serviceCost')->where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'quarter' => $quarter,
            ])->get();

            // Generate the full summary
            $summary = $regions->map(function ($region) use (
                $simulation_id,
                $quarter,
                $products,
                $channels,
                $firms,
                $forecastingEntries,
                $actualDemandEntries,
                $demandPlanningEntries,
                $demandPlanningEntriesPastQtr,
                $serviceManagementEntries,
                $faculty_id
            ) {
                return [
                    'region' => $region,
                    'products' => $products->map(function ($product) use (
                        $simulation_id,
                        $quarter,
                        $channels,
                        $firms,
                        $region,
                        $forecastingEntries,
                        $actualDemandEntries,
                        $demandPlanningEntries,
                        $demandPlanningEntriesPastQtr,
                        $serviceManagementEntries,
                        $faculty_id
                    ) {
                        return [
                            'product' => $product,
                            'channels' => $channels->map(function ($channel) use (
                                $simulation_id,
                                $quarter,
                                $firms,
                                $region,
                                $product,
                                $forecastingEntries,
                                $actualDemandEntries,
                                $demandPlanningEntries,
                                $demandPlanningEntriesPastQtr,
                                $serviceManagementEntries,
                                $faculty_id
                            ) {
                                return [
                                    'channel' => $channel,
                                    'firms' => $firms->map(function ($firm) use (
                                        $simulation_id,
                                        $quarter,
                                        $region,
                                        $product,
                                        $channel,
                                        $forecastingEntries,
                                        $actualDemandEntries,
                                        $demandPlanningEntries,
                                        $demandPlanningEntriesPastQtr,
                                        $serviceManagementEntries,
                                        $faculty_id
                                    ) {

                                        $pastforecastDemand = $forecastingEntries
                                            ->where('region_id', $region->id)
                                            ->where('product_id', $product->id)
                                            ->firstWhere('group_number', $firm->group_number);

                                        // Get actual sales volume for the channel
                                        $actualDemandEntry = $actualDemandEntries
                                            ->where('region_id', $region->id)
                                            ->where('product_id', $product->id)
                                            ->firstWhere('group_number', $firm->group_number);

                                        $forecastVolume = 0;
                                        if ($pastforecastDemand) {
                                            $forecastVolume = $channel->id == 1
                                              ? $pastforecastDemand->demand_channel_01 ?? 0
                                              : $pastforecastDemand->demand_channel_02 ?? 0;
                                        }

                                        $actualSale = 0;
                                        if ($actualDemandEntry) {
                                            $actualSale = $channel->id == 1
                                              ? $actualDemandEntry->demand_channel_01
                                              : $actualDemandEntry->demand_channel_02;
                                        }

                                        // Current QTR planned price from demand planning
                                        $plannedEntries = $demandPlanningEntries
                                            ->where('region_id', $region->id)
                                            ->where('product_id', $product->id)
                                            ->where('group_number', $firm->group_number);

                                        $price = 0;
                                        $marketPrice = 0;
                                        if ($plannedEntries->isNotEmpty()) {
                                            $entry = $plannedEntries->firstWhere('demand_channel_id', $channel->id);
                                            if ($entry) {
                                                $price = $entry->price;
                                                $marketPrice = $entry->market_price;
                                            }
                                        }

                                        // Past Qtr planned price from demand planning
                                        $plannedEntriesPastQtr = $demandPlanningEntriesPastQtr
                                            ->where('region_id', $region->id)
                                            ->where('product_id', $product->id)
                                            ->where('group_number', $firm->group_number);
                                        $pastPrice = 0;
                                        $pastMarketPrice = 0;
                                        if ($plannedEntriesPastQtr->isNotEmpty()) {
                                            $pastEntry = $plannedEntriesPastQtr->firstWhere('demand_channel_id', $channel->id);
                                            if ($pastEntry) {
                                                $pastPrice = $pastEntry->price;
                                                $pastMarketPrice = $pastEntry->market_price;
                                            }
                                        }

                                        $plannedDemand = $demandPlanningEntries
                                            ->where('region_id', $region->id)
                                            ->where('product_id', $product->id)
                                            ->where('demand_channel_id', $channel->id)
                                            ->firstWhere('group_number', $firm->group_number);

                                        // Get Preference
                                        $aqPreference = $this->productPreferences[$product->sort_name][$region->id]['AQ'] ?? 1;
                                        $pqPreference = $this->productPreferences[$product->sort_name][$region->id]['PQ'] ?? 1;
                                        $sqPreference = $this->productPreferences[$product->sort_name][$region->id]['SQ'] ?? 1;

                                        // Calling Method From Service.
                                        $pq = $this->qualityAnalysis->calculatePQ(
                                            $product,
                                            $simulation_id,
                                            $quarter,
                                            $firm->group_number
                                        );

                                        $sq = $this->qualityAnalysis->calculateSQ(
                                            $firm->group_number,
                                            $region->id,
                                            $serviceManagementEntries
                                        );

                                        $aq = $this->qualityAnalysis->calculateAQ(
                                            $forecastVolume,
                                            $actualSale
                                        );

                                        $cai = $this->qualityAnalysis->calculateCAI(
                                            $aq,
                                            $sq,
                                            $pq,
                                            $this->qualityAnalysis->getPreferences($product->sort_name, $region->id),
                                            [
                                                'Marketing' => $plannedDemand->marketSpending ?? 0,
                                                'Packaging' => Packaging::find(1)->cost ?? 10,
                                                'Warranty' => 8 + 3 * pow(3, 2),
                                            ]
                                        );

                                        $pastCai = $this->qualityAnalysis->calculatePastCAI(
                                            $product,
                                            $simulation_id,
                                            $faculty_id,
                                            $firm->group_number,
                                            $quarter,
                                            $region->id,
                                            $channel->id
                                        );

                                        $adjustedDemand = $this->qualityAnalysis->calculateAdjustedDemand(
                                            $actualSale,
                                            $cai,
                                            $pastCai,
                                            $marketPrice,
                                            $pastMarketPrice
                                        );

                                        $netVolumeChangePercent = round((($adjustedDemand - $actualSale) / ($actualSale ?: 1)) * 100, 2);

                                        // Market share and bar representation
                                        $marketShare = $this->calculateMarketShare(
                                            $simulation_id,
                                            $faculty_id,
                                            $quarter,
                                            $firm->group_number,
                                            $region->id,
                                            $channel->id,
                                            $product->id
                                        );

                                        $marketSharePercent = $marketShare * 100;
                                        $barLength = floor($marketSharePercent / 5);
                                        $marketBar = str_repeat('█', $barLength);

                                        return [
                                            'firm' => $firm,
                                            'volume' => number_format($actualSale),
                                            'market_share' => round($marketSharePercent, 0),
                                            'market_bar' => $marketBar,
                                            'price' => $price,
                                            'market_price' => $marketPrice,
                                            'pq' => round($pq * $pqPreference, 2),
                                            'sq' => round($sq * $sqPreference, 2),
                                            'aq' => round($aq * $aqPreference, 2),
                                            'cai' => round($cai * 100, 2),
                                            'pastCI' => round($pastCai * 100, 2),
                                            'pastPrice' => $pastPrice,
                                            'past_market_price' => $pastMarketPrice,
                                            'adjusted_demand' => number_format(round($adjustedDemand)),

                                            'ci_change' => $cai < $pastCai
                                              ? '↓ ('.round($cai * 100, 2).' < '.round($pastCai * 100, 2).") → <small class='negative'>Negative impact</small>"
                                              : ($cai > $pastCai
                                                ? '↑ ('.round($cai * 100, 2).' > '.round($pastCai * 100, 2).") → <small class='positive'>Positive impact</small>"
                                                : '↔ ('.round($cai * 100, 2).' = '.round($pastCai * 100, 2).") → <small class='neutral'>No change</small>"
                                              ),

                                            'price_change' => $price > $pastPrice
                                              ? '↑ ('.$price.' > '.$pastPrice.") → <small class='negative'>Negative impact</small>"
                                              : ($price < $pastPrice
                                                ? '↓ ('.$price.' < '.$pastPrice.") → <small class='positive'>Positive impact</small>"
                                                : '↔ ('.$price.' = '.$pastPrice.") → <small class='neutral'>No change</small>"
                                              ),

                                            'net_volume_change' => ($adjustedDemand > $actualSale)
                                              ? '('.$netVolumeChangePercent.'%)'."<small class='positive'>Net Positive</small>"
                                              : (($adjustedDemand < $actualSale)
                                                ? '('.$netVolumeChangePercent.'%)'."<small class='negative'>Net Negative</small>"
                                                : 'No Change (0%)'),
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    }),
                ];
            });

            return $summary;
        } catch (\Throwable $th) {
            report($th);

            return redirect()->back()->withErrors('Error: '.$th->getMessage());
        }
    }

    public function calculateMarketShare(int $simulationId, int $facultyId, int $quarter, int $groupNumber, int $regionId, int $channelId, int $productId): float
    {
        // Firm's volume (your group's sales in the region/channel)
        $firmVolume = ActualDemand::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $quarter,
            'group_number' => $groupNumber,
            'region_id' => $regionId,
            'product_id' => $productId,
        ])->selectRaw('SUM(demand_channel_01 + demand_channel_02) as total_volume')
            ->value('total_volume');

        // Total market volume (all groups in same region/channel/product)
        $totalVolume = ActualDemand::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $quarter,
            'region_id' => $regionId,
            'product_id' => $productId,
        ])->selectRaw('SUM(demand_channel_01 + demand_channel_02) as total_market_volume')
            ->value('total_market_volume');

        if (empty($totalVolume)) {
            return 0.0;
        }

        return round($firmVolume / $totalVolume, 4); // e.g., 0.6725 = 67.25%
    }
}
