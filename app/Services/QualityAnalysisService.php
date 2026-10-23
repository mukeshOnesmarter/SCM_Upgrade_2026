<?php

namespace App\Services;

use App\Models\Decisions\Demand\ActualDemand;
use App\Models\Decisions\DemandPlanning;
use App\Models\Decisions\ProcurementSubAssembly;
use App\Models\Decisions\ServiceManagement;
use App\Models\Packaging;
use App\Models\Product;
use Illuminate\Support\Collection;

class QualityAnalysisService
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

    public function getPreferences(string $productSortName, int $regionId): array
    {
        return $this->productPreferences[$productSortName][$regionId] ?? [
            'AQ' => 1,
            'PQ' => 1,
            'SQ' => 1,
            'Marketing' => 1,
            'Packaging' => 1,
            'Warranty' => 1,
        ];
    }

    /**
     * Calculate Product Quality (PQ)
     * Formula: 1 - (Total Failed Quantity / Total Procured Quantity)
     */
    public function calculatePQ($product, $simulationId, $quarter, $groupNumber): float
    {
        $totalFailed = 0;
        $totalProcured = 0;

        foreach ($product->material as $material) {
            $sac = $material->sac;
            if (! $sac) {
                continue;
            }

            foreach ($sac->supplierComponent as $component) {
                $supplier = $component->supplier;
                if (! $supplier) {
                    continue;
                }

                $failRate = ($component->failure_rate ?? 0) / 100;

                $qty = ProcurementSubAssembly::where('sub_assembly_component_id', $sac->id)
                    ->where('supplier_id', $supplier->id)
                    ->whereHas('procurement', function ($q) use ($simulationId, $quarter, $groupNumber) {
                        $q->where('simulation_id', $simulationId)
                            ->where('quarter', $quarter)
                            ->where('group_number', $groupNumber);
                    })->sum('quantity');

                $totalFailed += $qty * $failRate;
                $totalProcured += $qty;
            }
        }

        return $totalProcured > 0 ? round(1 - ($totalFailed / $totalProcured), 3) : 1.0;
    }

    /**
     * Calculate Service Quality (SQ)
     */
    public function calculateSQ($groupNumber, $regionId, $serviceManagementEntry): float
    {
        $baseMap = [
            1 => 0.92,
            2 => 0.91,
            3 => 0.90,
            4 => 0.89,
            5 => 0.88,
            6 => 0.87,
            7 => 0.86,
            8 => 0.85,
            9 => 0.84,
            10 => 0.83,
        ];

        $baseSQ = $baseMap[$groupNumber] ?? 0.80;
        // $variation = mt_rand(-3, 3) / 100; hide for testing purpost use this later instead of 0.02
        $variation = 0.02;
        $adjusted = round($baseSQ + $variation, 2);

        $entry = $serviceManagementEntry->firstWhere('region_id', $regionId);

        $sqGuarantee = optional(optional(optional($entry)->serviceOutsourcing)
            ->serviceCost)->firstWhere('region_id', $regionId)->sq_guarantee ?? 0.1;

        return max($sqGuarantee, $adjusted);
    }

    /**
     * Calculate Availability Quality (AQ)
     */
    public function calculateAQ($plannedVolume, $actualVolume): float
    {
        $unfilled = max($plannedVolume - $actualVolume, 0);

        return $plannedVolume > 0 ? round(1 - ($unfilled / $plannedVolume), 3) : 1.0;
    }

    /**
     * Calculate Customer Acceptance Index (CAI)
     */
    public function calculateCAI(float $aq, float $sq, float $pq, array $preferences, array $rawValues): float
    {
        $weights = [
            'AQ' => $preferences['AQ'] ?? 1,
            'SQ' => $preferences['SQ'] ?? 1,
            'PQ' => $preferences['PQ'] ?? 1,
            'Marketing' => $preferences['Marketing'] ?? 1,
            'Packaging' => $preferences['Packaging'] ?? 1,
            'Warranty' => $preferences['Warranty'] ?? 1,
        ];

        $totalWeight = array_sum($weights);
        $normalizedWeights = array_map(fn ($w) => $w / $totalWeight, $weights);

        $normalizedMarketing = min($rawValues['Marketing'] / 100000, 1);
        $normalizedPackaging = min($rawValues['Packaging'] / 28, 1);
        $normalizedWarranty = min($rawValues['Warranty'] / 50, 1);

        $cai = (
            $aq * $normalizedWeights['AQ'] +
            $sq * $normalizedWeights['SQ'] +
            $pq * $normalizedWeights['PQ'] +
            $normalizedMarketing * $normalizedWeights['Marketing'] +
            $normalizedPackaging * $normalizedWeights['Packaging'] +
            $normalizedWarranty * $normalizedWeights['Warranty']
        );

        return round($cai, 4);
    }

    /**
     * Estimate past CAI for the previous quarter
     */
    public function calculatePastCAI(
        Product $product,
        int $simulationId,
        int $facultyId,
        int $groupNumber,
        int $quarter,
        int $regionId,
        int $channelId,
        float $defaultPackagingCost = 10,
        ?Collection $serviceEntries = null // Optional for optimization
    ): ?float {
        $pastQuarter = $quarter - 1;
        if ($pastQuarter < 0) {
            return null; // No past data to compute
        }

        // Fetch past quarter planned demand and actual demand
        $planned = DemandPlanning::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $pastQuarter,
            'region_id' => $regionId,
            'product_id' => $product->id,
            'demand_channel_id' => $channelId,
            'group_number' => $groupNumber,
        ])->first();

        $actual = ActualDemand::where([
            'simulation_id' => $simulationId,
            'faculty_id' => $facultyId,
            'quarter' => $pastQuarter,
            'region_id' => $regionId,
            'product_id' => $product->id,
            'group_number' => $groupNumber,
        ])->first();

        $actualSale = 0;
        if ($actual) {
            $actualSale = $channelId == 1
              ? $actual->demand_channel_01
              : $actual->demand_channel_02;
        }

        if (! $planned || ! $actual) {
            return null;
        }

        // Load services if not passed in (optimization for loops)
        if (is_null($serviceEntries)) {
            $serviceEntries = ServiceManagement::with('serviceOutsourcing.serviceCost')
                ->where([
                    'simulation_id' => $simulationId,
                    'faculty_id' => $facultyId,
                    'quarter' => $pastQuarter,
                ])->get();
        }

        // Component Quality Metrics
        $pq = $this->calculatePQ($product, $simulationId, $pastQuarter, $groupNumber);
        $sq = $this->calculateSQ($groupNumber, $regionId, $serviceEntries);
        $aq = $this->calculateAQ($planned->volume ?? 0, $actualSale ?? 0);

        // Preferences and cost drivers
        $preferences = $this->getPreferences($product->sort_name, $regionId);
        $rawValues = [
            'Marketing' => $planned->marketSpending ?? 0,
            'Packaging' => Packaging::find(1)->cost ?? 10,
            'Warranty' => 8 + 3 * pow(3, 2), // Hardcoded warranty cost logic
        ];

        $cai = $this->calculateCAI($aq, $sq, $pq, $preferences, $rawValues);

        return round($cai, 4);
    }

    /**
     * Calculate adjusted demand volume based on CI and price changes.
     */
    public function calculateAdjustedDemand(
        float $actualSale,
        ?float $currentCI,
        ?float $pastCI,
        ?float $currentPrice,
        ?float $pastPrice,
        int $channelId = 1 // 1 = Retail, 2 = Direct
    ): float {

        // Normalize inputs
        $currentCI = $currentCI ?? 0;
        $pastCI = $pastCI ?? 0;
        $currentPrice = $currentPrice ?? 0;
        $pastPrice = $pastPrice ?? 0;

        // If no base demand → nothing to adjust
        if ($actualSale <= 0) {
            return 0;
        }

        /**
         * CHANNEL RULE:
         * Direct channel has price = 0 → ignore price impact
         */
        $ignorePriceImpact = ($channelId == 2 || $pastPrice == 0);

        // CI impact (safe)
        $ciChangePercent = $pastCI > 0
          ? ($currentCI - $pastCI) / $pastCI
          : 0;

        $volumeChangeDueToCI = $ciChangePercent * 2;

        // Price impact (safe)
        $volumeChangeDueToPrice = 0;

        if (! $ignorePriceImpact && $pastPrice > 0) {
            $priceChangePercent = ($currentPrice - $pastPrice) / $pastPrice;
            $volumeChangeDueToPrice = -5 * $priceChangePercent;
        }

        // Final impact
        $totalVolumeChangePercent = $volumeChangeDueToCI + $volumeChangeDueToPrice;

        $adjustedDemand = $actualSale * (1 + $totalVolumeChangePercent);

        return max(0, round($adjustedDemand));
    }
}
