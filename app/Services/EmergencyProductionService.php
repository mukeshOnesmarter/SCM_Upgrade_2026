<?php

namespace App\Services;

use App\Models\Decisions\EmergencyProduction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyProductionService
{
    protected int $defaultEmergencyLimit;

    protected int $premiumThreshold;

    protected int $shiftCapacityPerMonth;

    public function __construct()
    {
        $this->defaultEmergencyLimit =
            (int) config('simulation.emergency_limit_per_product', 10000);

        $this->premiumThreshold =
            (int) config('simulation.emergency_premium_threshold', 5000);

        $this->shiftCapacityPerMonth =
            (int) config('simulation.shift_capacity_per_month', 50000);
    }

    /**
     * Tiered cost:
     * - First tier: +50%
     * - Second tier: +100%
     */
    public function computeEmergencyCostTiered(float $baseUnitCost, int $units): array
    {
        if ($units <= 0) {
            return [0.0, 0.0];
        }

        $tier1 = min($units, $this->premiumThreshold);
        $tier2 = max(0, $units - $this->premiumThreshold);

        $total =
            ($tier1 * $baseUnitCost * 1.5) +
            ($tier2 * $baseUnitCost * 2.0);

        return [
            round($total / $units, 4),
            round($total, 2),
        ];
    }

    public function alreadyEmergencyProducedThisQuarter(
        int $simulationId,
        int $productId,
        int $quarter
    ): int {
        return (int) EmergencyProduction::where([
            'simulation_id' => $simulationId,
            'product_id' => $productId,
            'quarter' => $quarter,
        ])->sum('quantity');
    }

    /**
     * Idempotent creation
     */
    public function createOrGetProduction(array $attrs)
    {
        $query = EmergencyProduction::where([
            'simulation_id' => $attrs['simulation_id'],
            'faculty_id' => $attrs['faculty_id'],
            'product_id' => $attrs['product_id'],
            'quarter' => $attrs['quarter'],
        ]);

        if (array_key_exists('group_id', $attrs)) {
            $query->where('group_id', $attrs['group_id']);
        } else {
            $query->whereNull('group_id');
        }

        $existing = $query->first();
        if ($existing) {
            return $existing;
        }

        return EmergencyProduction::create($attrs);
    }

    /**
     * Base unit cost heuristic
     */
    public function fetchBaseUnitProductionCost(
        int $simulationId,
        int $productId
    ): float {
        try {
            $agg = EmergencyProduction::where([
                'simulation_id' => $simulationId,
                'product_id' => $productId,
            ])
                ->whereNotNull('estimated_unit_cost')
                ->select(
                    DB::raw('SUM(estimated_unit_cost * quantity) as total_cost'),
                    DB::raw('SUM(quantity) as total_qty')
                )
                ->first();

            if ($agg && $agg->total_qty > 0) {
                return round($agg->total_cost / $agg->total_qty, 4);
            }
        } catch (\Throwable $e) {
            Log::warning('EmergencyProductionService cost fallback', [
                'product' => $productId,
                'error' => $e->getMessage(),
            ]);
        }

        return (float) config('simulation.default_unit_cost', 10.0);
    }

    /**
     * Available plant capacity
     */
    public function computeAvailablePlantCapacity(int $plannedThisQuarter): int
    {
        return max(0, $this->shiftCapacityPerMonth - $plannedThisQuarter);
    }
}
