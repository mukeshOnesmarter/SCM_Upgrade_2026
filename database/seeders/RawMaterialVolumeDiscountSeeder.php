<?php

namespace Database\Seeders;

use App\Models\RawMaterialVolumeDiscount;
use Illuminate\Database\Seeder;

class RawMaterialVolumeDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RawMaterialVolumeDiscount::create([
            'raw_material_id' => 1,
            'volume' => 250000,
            'discount' => 8,
        ]);

        RawMaterialVolumeDiscount::create([
            'raw_material_id' => 1,
            'volume' => 500000,
            'discount' => 14,
        ]);

        RawMaterialVolumeDiscount::create([
            'raw_material_id' => 1,
            'volume' => 1000000,
            'discount' => 19,
        ]);

        RawMaterialVolumeDiscount::create([
            'raw_material_id' => 2,
            'volume' => 250000,
            'discount' => 8,
        ]);

        RawMaterialVolumeDiscount::create([
            'raw_material_id' => 2,
            'volume' => 500000,
            'discount' => 14,
        ]);

        RawMaterialVolumeDiscount::create([
            'raw_material_id' => 2,
            'volume' => 1000000,
            'discount' => 19,
        ]);
    }
}
