<?php

namespace App\Services\Reports\Supplementary;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\Forecasting;
use App\Models\Product;
use App\Models\Region;
use App\Services\ActiveUserService;
use App\Services\Reports\ProductPnlService;
use Illuminate\Support\Facades\Log;

class ForecastAccuracyService
{
    protected $activeUserService;

    protected $productPnlService;

    public function __construct(
        ActiveUserService $activeUserService,
        ProductPnlService $productPnlService
    ) {
        $this->activeUserService = $activeUserService;
        $this->productPnlService = $productPnlService;
    }

    /**
     * Build detailed forecast vs actual accuracy per region/product/channel.
     */
    public function forecastAccuracy(int $simulation_id, int $faculty_id, int $quarter_id, int $group_id)
    {
        try {
            $regions = Region::all();
            $products = Product::all();

            // Forecast
            $forecastDemand = Forecasting::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
                'firm_id' => 1,
            ])->get();

            // ✅ Actual Demand (CORRECT SOURCE)
            $actualDemand = ActualDemand::where([
                'simulation_id' => $simulation_id,
                'faculty_id' => $faculty_id,
                'group_number' => $group_id,
                'quarter' => $quarter_id,
            ])->get();

            $regions->each(function ($region) use ($products, $forecastDemand, $actualDemand) {
                $region->products = $products->map(function ($data) use ($region, $forecastDemand, $actualDemand) {

                    $productClone = clone $data;

                    // Forecast
                    $forecastData = $forecastDemand
                        ->where('region_id', $region->id)
                        ->where('product_id', $productClone->id)
                        ->first();

                    $forecast_01 = (float) ($forecastData->demand_channel_01 ?? 0);
                    $forecast_02 = (float) ($forecastData->demand_channel_02 ?? 0);

                    // ✅ Actual Demand (NOT fulfilled)
                    $actualData = $actualDemand
                        ->where('region_id', $region->id)
                        ->where('product_id', $productClone->id)
                        ->first();

                    $actual_01 = (float) ($actualData->demand_channel_01 ?? 0);
                    $actual_02 = (float) ($actualData->demand_channel_02 ?? 0);

                    $productClone->demandData = (object) [
                        'forecastDemand' => (object) [
                            'demandChannel_01' => number_format($forecast_01, 0),
                            'demandChannel_02' => number_format($forecast_02, 0),
                        ],
                        'actualDemand' => (object) [
                            'demandChannel_01' => number_format($actual_01, 0),
                            'demandChannel_02' => number_format($actual_02, 0),
                        ],
                        'demandAccuracy' => (object) [
                            'demandChannel_01' => $this->calculateAccuracy($forecast_01, $actual_01).'%',
                            'demandChannel_02' => $this->calculateAccuracy($forecast_02, $actual_02).'%',
                        ],
                    ];

                    return $productClone;
                });
            });

            return $regions;
        } catch (\Exception $e) {
            Log::error('Error in ForecastAccuracyService: '.$e->getMessage());
        }
    }

    /**
     * Calculate accuracy for a single row.
     */
    public function calculateAccuracy($forecastDemand, $actualDemand, $minActualToCount = 100)
    {
        if ($actualDemand <= $minActualToCount) {
            return 0;
        }

        $accuracy = 1 - abs($forecastDemand - $actualDemand) / $actualDemand;

        return round(max(0, min($accuracy, 1)) * 100, 1);
    }

    /**
     * Build forecast/actual rows for aggregate calculations.
     */
    public function buildForecastActualRows(
        int $simulation_id,
        int $faculty_id,
        int $group_id,
        int $quarter_id
    ): array {

        $rows = [];

        // Forecast
        $forecasts = Forecasting::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
            'firm_id' => 1,
        ])->get();

        // ✅ Actual Demand
        $actualDemand = ActualDemand::where([
            'simulation_id' => $simulation_id,
            'faculty_id' => $faculty_id,
            'group_number' => $group_id,
            'quarter' => $quarter_id,
        ])->get();

        foreach ($forecasts as $f) {

            $productId = $f->product_id;
            $regionId = $f->region_id;

            $actualData = $actualDemand
                ->where('product_id', $productId)
                ->where('region_id', $regionId)
                ->first();

            $actualRetail = (float) ($actualData->demand_channel_01 ?? 0);
            $actualDirect = (float) ($actualData->demand_channel_02 ?? 0);

            // Retail
            $rows[] = [
                'forecast' => (float) ($f->demand_channel_01 ?? 0),
                'actual' => $actualRetail,
                'channel' => 'retail',
            ];

            // Direct
            $rows[] = [
                'forecast' => (float) ($f->demand_channel_02 ?? 0),
                'actual' => $actualDirect,
                'channel' => 'direct',
            ];
        }

        return $rows;
    }

    /**
     * Compute aggregate accuracy.
     */
    public function computeAggregateAccuracy(array $rows, int $minActualToCount = 100): float
    {
        $sumActuals = 0.0;
        $sumAbsError = 0.0;

        foreach ($rows as $r) {
            $forecast = (float) ($r['forecast'] ?? 0);
            $actual = (float) ($r['actual'] ?? 0);

            if ($actual <= $minActualToCount) {
                continue;
            }

            $sumActuals += $actual;
            $sumAbsError += abs($forecast - $actual);
        }

        if ($sumActuals == 0) {
            return 0.0;
        }

        $accuracy = 1 - ($sumAbsError / $sumActuals);
        $accuracy = max(0, min(1, $accuracy));

        return round($accuracy * 100, 1);
    }

    /**
     * Overall forecast accuracy.
     */
    public function getOverallForecastAccuracy(
        int $simulation_id,
        int $faculty_id,
        int $group_id,
        int $quarter_id
    ): float {

        $rows = $this->buildForecastActualRows(
            $simulation_id,
            $faculty_id,
            $group_id,
            $quarter_id
        );

        return $this->computeAggregateAccuracy($rows, 100);
    }
}
