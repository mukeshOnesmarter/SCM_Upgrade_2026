<?php

namespace Database\Seeders;

use App\Models\ManufacturingCostAdditional;
use Illuminate\Database\Seeder;

class ManufacturingCostAdditionalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ManufacturingCostAdditional::create([
            'manufacturing_cost_id' => 1,
            'region_id' => 1,
            'distribution_center' => 1,
            'product_id' => 1,
            'fixed_cost' => 5000,
            'labor_cost' => 14,
            'production_cost' => 12,
        ]);

        ManufacturingCostAdditional::create([
            'manufacturing_cost_id' => 1,
            'region_id' => 2,
            'distribution_center' => 2,
            'product_id' => 1,
            'fixed_cost' => 5000,
            'labor_cost' => 15,
            'production_cost' => 14,
        ]);

        ManufacturingCostAdditional::create([
            'manufacturing_cost_id' => 1,
            'region_id' => 3,
            'distribution_center' => 3,
            'product_id' => 1,
            'fixed_cost' => 4000,
            'labor_cost' => 12,
            'production_cost' => 11,
        ]);

        ManufacturingCostAdditional::create([
            'manufacturing_cost_id' => 1,
            'region_id' => 1,
            'distribution_center' => 1,
            'product_id' => 2,
            'fixed_cost' => 6000,
            'labor_cost' => 16,
            'production_cost' => 10,
        ]);

        ManufacturingCostAdditional::create([
            'manufacturing_cost_id' => 1,
            'region_id' => 2,
            'distribution_center' => 2,
            'product_id' => 2,
            'fixed_cost' => 8000,
            'labor_cost' => 20,
            'production_cost' => 12,
        ]);

        ManufacturingCostAdditional::create([
            'manufacturing_cost_id' => 1,
            'region_id' => 3,
            'distribution_center' => 3,
            'product_id' => 2,
            'fixed_cost' => 5000,
            'labor_cost' => 15,
            'production_cost' => 10,
        ]);
    }
}
