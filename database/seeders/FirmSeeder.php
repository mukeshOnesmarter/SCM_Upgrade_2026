<?php

namespace Database\Seeders;

use App\Models\Firm;
use Illuminate\Database\Seeder;

class FirmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Firm::create([
            'industry_id' => 1,
            'name' => 'A',
            'custome_name' => 'Acme Corporation',
            'demand_increment' => 100,
        ]);

        Firm::create([
            'industry_id' => 1,
            'name' => 'B',
            'custome_name' => 'Globex Corporation',
            'demand_increment' => 200,
        ]);

        Firm::create([
            'industry_id' => 1,
            'name' => 'C',
            'custome_name' => 'Soylent Corp',
            'demand_increment' => 300,
        ]);

        Firm::create([
            'industry_id' => 1,
            'name' => 'D',
            'custome_name' => 'Umbrella Corporation',
            'demand_increment' => 400,
        ]);

        Firm::create([
            'industry_id' => 1,
            'name' => 'E',
            'custome_name' => 'Vehement Capital',
            'demand_increment' => 500,
        ]);

        Firm::create([
            'industry_id' => 1,
            'name' => 'F',
            'custome_name' => 'Apex Global Industries',
            'demand_increment' => 550,
        ]);

        Firm::create([
            'industry_id' => 1,
            'name' => 'G',
            'custome_name' => 'Prime Logistics',
            'demand_increment' => 600,
        ]);

        Firm::create([
            'industry_id' => 1,
            'name' => 'H',
            'custome_name' => 'FutureTech Innovations',
            'demand_increment' => 650,
        ]);

        Firm::create([
            'industry_id' => 1,
            'name' => 'I',
            'custome_name' => 'EcoTech Industries',
            'demand_increment' => 700,
        ]);

        Firm::create([
            'industry_id' => 1,
            'name' => 'J',
            'custome_name' => 'Rapid Ventures',
            'demand_increment' => 750,
        ]);
    }
}
