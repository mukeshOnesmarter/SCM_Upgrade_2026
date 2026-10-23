<?php

namespace App\Services;

class EmergencyTransportationService
{
    protected float $defaultBaseFreight;

    public function __construct()
    {
        $this->defaultBaseFreight =
            (float) config('simulation.base_freight_per_unit', 1.0);
    }

    public function computeAirFreight(
        ?float $baseFreightPerUnit = null,
        int $units = 0
    ): array {
        $units = max(0, $units);
        if ($units === 0) {
            return [0.0, 0.0, []];
        }

        $base = $baseFreightPerUnit ?? $this->defaultBaseFreight;
        $multiplier = (float) config('simulation.emergency_freight_multiplier', 1.5);

        $unit = round($base * $multiplier, 4);
        $total = round($unit * $units, 2);

        return [
            $unit,
            $total,
            [
                'mode' => 'air',
                'base_per_unit' => $base,
                'multiplier' => $multiplier,
                'unit_freight' => $unit,
                'total_freight' => $total,
            ],
        ];
    }

    public function fetchBaseFreightPerUnit(
        ?int $simulationId = null,
        ?int $productId = null,
        ?int $regionId = null
    ): float {
        return $this->defaultBaseFreight;
    }
}
