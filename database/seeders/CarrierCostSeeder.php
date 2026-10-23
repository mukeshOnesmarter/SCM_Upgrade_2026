<?php

namespace Database\Seeders;

use App\Models\CarrierCost;
use Illuminate\Database\Seeder;

class CarrierCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CarrierCost::create([
            'carrier_id' => 1,
            'region_id' => 2,
            'supply_channel_id' => 1,
            'cost' => 10,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 1,
            'region_id' => 2,
            'supply_channel_id' => 2,
            'cost' => 7.50,
            'expected_value' => 70,
            'possible_variation' => 4,
        ]);

        CarrierCost::create([
            'carrier_id' => 1,
            'region_id' => 3,
            'supply_channel_id' => 1,
            'cost' => 18,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 1,
            'region_id' => 3,
            'supply_channel_id' => 2,
            'cost' => 12.50,
            'expected_value' => 70,
            'possible_variation' => 4,
        ]);

        CarrierCost::create([
            'carrier_id' => 2,
            'region_id' => 2,
            'supply_channel_id' => 1,
            'cost' => 12.50,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 2,
            'region_id' => 2,
            'supply_channel_id' => 2,
            'cost' => 5,
            'expected_value' => 40,
            'possible_variation' => 8,
        ]);

        CarrierCost::create([
            'carrier_id' => 2,
            'region_id' => 3,
            'supply_channel_id' => 1,
            'cost' => 18,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 2,
            'region_id' => 3,
            'supply_channel_id' => 2,
            'cost' => 5,
            'expected_value' => 30,
            'possible_variation' => 8,
        ]);

        CarrierCost::create([
            'carrier_id' => 3,
            'region_id' => 2,
            'supply_channel_id' => 1,
            'cost' => 10.00,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 3,
            'region_id' => 2,
            'supply_channel_id' => 2,
            'cost' => 7.50,
            'expected_value' => 70,
            'possible_variation' => 12,
        ]);

        CarrierCost::create([
            'carrier_id' => 3,
            'region_id' => 3,
            'supply_channel_id' => 1,
            'cost' => 18,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 3,
            'region_id' => 3,
            'supply_channel_id' => 2,
            'cost' => 7.50,
            'expected_value' => 60,
            'possible_variation' => 12,
        ]);

        CarrierCost::create([
            'carrier_id' => 4,
            'region_id' => 2,
            'supply_channel_id' => 1,
            'cost' => 12.50,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 4,
            'region_id' => 2,
            'supply_channel_id' => 2,
            'cost' => 10,
            'expected_value' => 75,
            'possible_variation' => 4,
        ]);

        CarrierCost::create([
            'carrier_id' => 4,
            'region_id' => 3,
            'supply_channel_id' => 1,
            'cost' => 18,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 4,
            'region_id' => 3,
            'supply_channel_id' => 2,
            'cost' => 7.50,
            'expected_value' => 60,
            'possible_variation' => 4,
        ]);

        CarrierCost::create([
            'carrier_id' => 5,
            'region_id' => 2,
            'supply_channel_id' => 1,
            'cost' => 10,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 5,
            'region_id' => 2,
            'supply_channel_id' => 2,
            'cost' => 7.50,
            'expected_value' => 65,
            'possible_variation' => 8,
        ]);

        CarrierCost::create([
            'carrier_id' => 5,
            'region_id' => 3,
            'supply_channel_id' => 1,
            'cost' => 20,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 5,
            'region_id' => 3,
            'supply_channel_id' => 2,
            'cost' => 10,
            'expected_value' => 75,
            'possible_variation' => 8,
        ]);

        CarrierCost::create([
            'carrier_id' => 6,
            'region_id' => 2,
            'supply_channel_id' => 1,
            'cost' => 15,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 6,
            'region_id' => 2,
            'supply_channel_id' => 2,
            'cost' => 12.50,
            'expected_value' => 82,
            'possible_variation' => 12,
        ]);

        CarrierCost::create([
            'carrier_id' => 6,
            'region_id' => 3,
            'supply_channel_id' => 1,
            'cost' => 22.50,
            'expected_value' => 100,
            'possible_variation' => 0,
        ]);

        CarrierCost::create([
            'carrier_id' => 6,
            'region_id' => 3,
            'supply_channel_id' => 2,
            'cost' => 15,
            'expected_value' => 78,
            'possible_variation' => 12,
        ]);
    }
}
