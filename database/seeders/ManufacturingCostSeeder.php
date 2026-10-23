<?php

namespace Database\Seeders;

use App\Models\ManufacturingCost;
use Illuminate\Database\Seeder;

class ManufacturingCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ManufacturingCost::create([
            'product_id' => 0,
            'fixed_cost' => 20000,
            'labor_cost' => 22,
            'production_cost' => 11,
        ]);

        ManufacturingCost::create([
            'product_id' => 1,
            'fixed_cost' => 22500,
            'labor_cost' => 30,
            'production_cost' => 20,
        ]);

        ManufacturingCost::create([
            'product_id' => 2,
            'fixed_cost' => 24500,
            'labor_cost' => 36,
            'production_cost' => 16,
        ]);
    }
}
