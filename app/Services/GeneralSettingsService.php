<?php

namespace App\Services;

class GeneralSettingsService
{
    public function generalSettings()
    {
        $growthParameters = [
            'gdp' => 2,
            'drift' => 0.5,
            'actual_demand' => 500,
        ];

        $initialDemand_product_01 = [
            'region_01_retail' => 20000,
            'region_01_direct' => 16000,
            'region_02_retail' => 20000,
            'region_02_direct' => 11000,
            'region_03_retail' => 35000,
            'region_03_direct' => 10000,
        ];

        $initialDemand_product_02 = [
            'region_01_retail' => 5200,
            'region_01_direct' => 1000,
            'region_02_retail' => 20002,
            'region_02_direct' => 11004,
            'region_03_retail' => 35060,
            'region_03_direct' => 10000,
        ];

        return (object) [
            'growthParameters' => $growthParameters,
            'initialDemand_product_01' => $initialDemand_product_01,
            'initialDemand_product_02' => $initialDemand_product_02,
        ];
    }
}
