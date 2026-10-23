<?php

namespace Database\Seeders;

use App\Models\Carrier;
use Illuminate\Database\Seeder;

class CarrierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Carrier::create([
            'name' => 'I',
        ]);

        Carrier::create([
            'name' => 'J',
        ]);

        Carrier::create([
            'name' => 'K',
        ]);

        Carrier::create([
            'name' => 'L',
        ]);

        Carrier::create([
            'name' => 'M',
        ]);

        Carrier::create([
            'name' => 'N',
        ]);
    }
}
