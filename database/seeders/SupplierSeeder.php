<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Supplier::create([
            'name' => 'A',
            'custome_name' => 'Global Prime Suppliers',
            'region_id' => 1,
        ]);

        Supplier::create([
            'name' => 'B',
            'custome_name' => 'Federal Suppliers',
            'region_id' => 1,
        ]);

        Supplier::create([
            'name' => 'C',
            'custome_name' => 'Precision Line Suppliers',
            'region_id' => 1,
        ]);

        Supplier::create([
            'name' => 'D',
            'custome_name' => 'Elite Tech Suppliers',
            'region_id' => 1,
        ]);

        Supplier::create([
            'name' => 'E',
            'custome_name' => 'Summit Edge Suppliers',
            'region_id' => 1,
        ]);

        Supplier::create([
            'name' => 'F',
            'custome_name' => 'Reliable Trade Suppliers',
            'region_id' => 1,
        ]);

        Supplier::create([
            'name' => 'G',
            'custome_name' => 'Visionary Suppliers',
            'region_id' => 1,
        ]);
    }
}
