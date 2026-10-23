<?php

namespace Database\Seeders;

use App\Models\CarrierCrossDocking;
use Illuminate\Database\Seeder;

class CarrierCrossDockingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CarrierCrossDocking::create([
            'carrier_id' => 3,
        ]);

        CarrierCrossDocking::create([
            'carrier_id' => 4,
        ]);

        CarrierCrossDocking::create([
            'carrier_id' => 5,
        ]);

        CarrierCrossDocking::create([
            'carrier_id' => 6,
        ]);
    }
}
