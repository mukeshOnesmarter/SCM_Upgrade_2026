<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Product::create([
            'industry_id' => 1,
            'sort_name' => 'SHA',
            'name' => 'Smart Home Assistant',
            'distribution' => '60',
        ]);

        Product::create([
            'industry_id' => 1,
            'sort_name' => 'ST',
            'name' => 'Smart Thermostat',
            'distribution' => '40',
        ]);
    }
}
