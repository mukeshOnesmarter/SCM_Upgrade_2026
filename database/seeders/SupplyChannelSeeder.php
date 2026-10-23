<?php

namespace Database\Seeders;

use App\Models\SupplyChannel;
use Illuminate\Database\Seeder;

class SupplyChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SupplyChannel::create([
            'name' => 'Air',
        ]);

        SupplyChannel::create([
            'name' => 'Surface',
        ]);
    }
}
