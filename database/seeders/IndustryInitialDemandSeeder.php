<?php

namespace Database\Seeders;

use App\Models\IndustryInitialDemand;
use Illuminate\Database\Seeder;

class IndustryInitialDemandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        IndustryInitialDemand::create([
            'industry_id' => 1,
            'product_id' => 1,
            'region_id' => 1,
            'demand_channel_01' => 20000,
            'demand_channel_02' => 10000,
        ]);

        IndustryInitialDemand::create([
            'industry_id' => 1,
            'product_id' => 1,
            'region_id' => 2,
            'demand_channel_01' => 20000,
            'demand_channel_02' => 20000,
        ]);

        IndustryInitialDemand::create([
            'industry_id' => 1,
            'product_id' => 1,
            'region_id' => 3,
            'demand_channel_01' => 35000,
            'demand_channel_02' => 25000,
        ]);

        IndustryInitialDemand::create([
            'industry_id' => 1,
            'product_id' => 2,
            'region_id' => 1,
            'demand_channel_01' => 16000,
            'demand_channel_02' => 11000,
        ]);

        IndustryInitialDemand::create([
            'industry_id' => 1,
            'product_id' => 2,
            'region_id' => 2,
            'demand_channel_01' => 11000,
            'demand_channel_02' => 7000,
        ]);

        IndustryInitialDemand::create([
            'industry_id' => 1,
            'product_id' => 2,
            'region_id' => 3,
            'demand_channel_01' => 10000,
            'demand_channel_02' => 6000,
        ]);
    }
}
