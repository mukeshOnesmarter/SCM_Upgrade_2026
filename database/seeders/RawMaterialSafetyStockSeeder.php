<?php

namespace Database\Seeders;

use App\Models\RawMaterialSafetyStock;
use Illuminate\Database\Seeder;

class RawMaterialSafetyStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RawMaterialSafetyStock::create([
            'raw_material_id' => 1,
            'safety_stock' => 2,
        ]);

        RawMaterialSafetyStock::create([
            'raw_material_id' => 2,
            'safety_stock' => 2,
        ]);

        RawMaterialSafetyStock::create([
            'raw_material_id' => 3,
            'safety_stock' => 2,
        ]);

        RawMaterialSafetyStock::create([
            'raw_material_id' => 4,
            'safety_stock' => 15,
        ]);

        RawMaterialSafetyStock::create([
            'raw_material_id' => 5,
            'safety_stock' => 5,
        ]);
    }
}
