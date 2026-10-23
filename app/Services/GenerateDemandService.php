<?php

namespace App\Services;

class GenerateDemandService
{
    /**
     * Generate a normalized random number using normal distribution
     */
    public function randomNumber($mean = 0.02, $stdDev = 0.0025)
    {
        // Generate two uniform random numbers
        $u = mt_rand() / mt_getrandmax();
        $v = mt_rand() / mt_getrandmax();

        // Box-Muller transform to generate standard normal value
        $z = sqrt(-2.0 * log($u)) * cos(2.0 * M_PI * $v);

        // Apply mean and standard deviation
        $randomNumber = $mean + $stdDev * $z;

        // Return growth factor centered around 1
        return 1 + $randomNumber;
    }

    /**
     * Calculate a percentage of a given value
     */
    public function calculatePercentage($value, $percentage)
    {
        $totalValue = ($value * $percentage) / 100;

        return round($totalValue);
    }

    /**
     * Initial demand (just returns the given figure)
     */
    public function initialDemand($figure)
    {
        return $figure;
    }

    /**
     * Multiply initial demand by random growth factor
     */
    public function probabilityDemand($initialdemand, $randomNumber)
    {
        return round($initialdemand * $randomNumber);
    }

    /**
     * Apply normal distribution to simulate actual demand
     */
    public function actualDemand($probabilityDemand, $stdDev = 75)
    {
        // Box-Muller transform to generate a normally distributed value
        $u = mt_rand() / mt_getrandmax();
        $v = mt_rand() / mt_getrandmax();
        $z = sqrt(-2.0 * log($u)) * cos(2.0 * M_PI * $v); // Standard normal

        $randomNumber = $probabilityDemand + $stdDev * $z;

        return max(round($randomNumber), 0); // Ensure it's not negative
    }

    /**
     * Forecast demand using exponential smoothing
     */
    public function forecastDemand($actualDemand, $initialdemand)
    {
        $alpha = 0.8;
        $forecastDemand = $alpha * $actualDemand + ((1 - $alpha) * $initialdemand);

        return ceil($forecastDemand / 1000) * 1000;
    }

    /**
     * Calculate forecast accuracy
     */
    public function forecastAccuracy($forecastDemand, $actualDemand)
    {
        if ($actualDemand == 0) {
            return 0;
        }

        $accuracy = 1 - abs($forecastDemand - $actualDemand) / $actualDemand;

        return round(max(0, min($accuracy, 1)) * 100, 1);
    }

    /**
     * Generate forecast and accuracy metrics
     */
    public function generateDemand($initialDemand)
    {
        $randomNumber = $this->randomNumber();
        $initialDemand = $this->initialDemand($initialDemand);
        $probabilityDemand = $this->probabilityDemand($initialDemand, $randomNumber);
        $actualDemand = $this->actualDemand($probabilityDemand);
        $forecastDemand = $this->forecastDemand($actualDemand, $initialDemand);
        $forecastAccuracy = $this->forecastAccuracy($forecastDemand, $actualDemand);

        return (object) [
            'forecastDemand' => $forecastDemand,
            'forecastAccuracy' => $forecastAccuracy,
        ];
    }

    /**
     * Calculate percentage growth
     */
    public function growthPercentage($value, $percentage)
    {
        return ($value * $percentage) / 100;
    }

    /**
     * Default forecasting for student portal
     */
    public function defaultForecasting($initialDemand)
    {
        $randomNumber = $this->randomNumber();
        $initialDemand = $this->initialDemand($initialDemand);
        $probabilityDemand = $this->probabilityDemand($initialDemand, $randomNumber);
        $actualDemand = $this->actualDemand($probabilityDemand);
        $forecastDemand = $this->forecastDemand($actualDemand, $initialDemand);

        return $forecastDemand + rand(20, 100);
    }

    /**
     * Error function approximation
     */
    private function erf($x)
    {
        $sign = ($x < 0) ? -1 : 1;
        $x = abs($x);

        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        $p = 0.3275911;

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return $sign * $y;
    }

    /**
     * Normal Cumulative Distribution Function
     */
    private function normalCDF($x, $mean = 0, $stdDev = 1)
    {
        $z = ($x - $mean) / ($stdDev * sqrt(2));

        return 0.5 * (1 + $this->erf($z));
    }
}
