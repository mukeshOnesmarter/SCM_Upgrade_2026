<?php

namespace Database\Seeders;

use App\Models\SubAssemblyVolumeDiscount;
use Illuminate\Database\Seeder;

class SubAssemblyVolumeDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SubAssemblyVolumeDiscount::create([
            'sub_assembly_component_id' => 1,
            'volume' => 50000,
            'discount' => 10.4,
        ]);

        SubAssemblyVolumeDiscount::create([
            'sub_assembly_component_id' => 1,
            'volume' => 100000,
            'discount' => 17.5,
        ]);

        SubAssemblyVolumeDiscount::create([
            'sub_assembly_component_id' => 2,
            'volume' => 50000,
            'discount' => 10.4,
        ]);

        SubAssemblyVolumeDiscount::create([
            'sub_assembly_component_id' => 2,
            'volume' => 100000,
            'discount' => 17.5,
        ]);

        SubAssemblyVolumeDiscount::create([
            'sub_assembly_component_id' => 3,
            'volume' => 50000,
            'discount' => 10.4,
        ]);

        SubAssemblyVolumeDiscount::create([
            'sub_assembly_component_id' => 3,
            'volume' => 100000,
            'discount' => 17.5,
        ]);
    }
}
