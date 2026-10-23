<?php

namespace Database\Seeders;

use App\Models\SubAssemblyTransportationCost;
use Illuminate\Database\Seeder;

class SubAssemblyTransportationCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SubAssemblyTransportationCost::create([
            'sub_assembly_component_id' => 1,
            'channel_01' => 4,
            'channel_02' => 3,
        ]);

        SubAssemblyTransportationCost::create([
            'sub_assembly_component_id' => 2,
            'channel_01' => 4,
            'channel_02' => 3,
        ]);

        SubAssemblyTransportationCost::create([
            'sub_assembly_component_id' => 3,
            'channel_01' => 6,
            'channel_02' => 4,
        ]);
    }
}
