<?php

namespace Database\Seeders;

use App\Models\RawMaterialCost;
use Illuminate\Database\Seeder;

class RawMaterialCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RawMaterialCost::create([
            'raw_material_id' => 1,
            'cost' => 4.00,
            'emergency_procurement' => 5.00,
        ]);

        RawMaterialCost::create([
            'raw_material_id' => 2,
            'cost' => 5.00,
            'emergency_procurement' => 6.00,
        ]);
    }
}
