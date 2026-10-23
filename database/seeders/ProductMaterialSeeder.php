<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('product_materials')->insert([
            [
                'product_id' => 1,
                'material_id' => 1,
                'type' => 'raw_material',
                'quantity' => '5',
            ],
            [
                'product_id' => 1,
                'material_id' => 2,
                'type' => 'raw_material',
                'quantity' => '5',
            ],
            [
                'product_id' => 1,
                'material_id' => 2,
                'type' => 'sub_assembly_component',
                'quantity' => 1,
            ],
            [
                'product_id' => 1,
                'material_id' => 3,
                'type' => 'sub_assembly_component',
                'quantity' => 1,
            ],
        ]);

        DB::table('product_materials')->insert([
            [
                'product_id' => 2,
                'material_id' => 1,
                'type' => 'raw_material',
                'quantity' => '5',
            ],
            [
                'product_id' => 2,
                'material_id' => 2,
                'type' => 'raw_material',
                'quantity' => '5',
            ],
            [
                'product_id' => 2,
                'material_id' => 1,
                'type' => 'sub_assembly_component',
                'quantity' => 1,
            ],
            [
                'product_id' => 2,
                'material_id' => 3,
                'type' => 'sub_assembly_component',
                'quantity' => 1,
            ],
        ]);

    }
}
