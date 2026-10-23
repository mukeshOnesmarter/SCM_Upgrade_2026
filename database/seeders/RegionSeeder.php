<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Region::create([
            'name' => 'A',
            'custome_name' => 'North America',
        ]);

        Region::create([
            'name' => 'B',
            'custome_name' => 'South America',
        ]);

        Region::create([
            'name' => 'C',
            'custome_name' => 'Central America',
        ]);
    }
}
