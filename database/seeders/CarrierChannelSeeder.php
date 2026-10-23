<?php

namespace Database\Seeders;

use App\Models\CarrierChannel;
use Illuminate\Database\Seeder;

class CarrierChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CarrierChannel::create([
            'carrier_id' => 1,
            'supply_channel_id' => 1,
        ]);

        CarrierChannel::create([
            'carrier_id' => 1,
            'supply_channel_id' => 2,
        ]);

        CarrierChannel::create([
            'carrier_id' => 2,
            'supply_channel_id' => 1,
        ]);

        CarrierChannel::create([
            'carrier_id' => 2,
            'supply_channel_id' => 2,
        ]);

        CarrierChannel::create([
            'carrier_id' => 3,
            'supply_channel_id' => 1,
        ]);

        CarrierChannel::create([
            'carrier_id' => 3,
            'supply_channel_id' => 2,
        ]);

        CarrierChannel::create([
            'carrier_id' => 4,
            'supply_channel_id' => 1,
        ]);

        CarrierChannel::create([
            'carrier_id' => 4,
            'supply_channel_id' => 2,
        ]);

        CarrierChannel::create([
            'carrier_id' => 5,
            'supply_channel_id' => 1,
        ]);

        CarrierChannel::create([
            'carrier_id' => 5,
            'supply_channel_id' => 2,
        ]);

        CarrierChannel::create([
            'carrier_id' => 6,
            'supply_channel_id' => 1,
        ]);

        CarrierChannel::create([
            'carrier_id' => 6,
            'supply_channel_id' => 2,
        ]);
    }
}
