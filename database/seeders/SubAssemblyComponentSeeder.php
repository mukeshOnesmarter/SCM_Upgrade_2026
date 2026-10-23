<?php

namespace Database\Seeders;

use App\Models\SubAssemblyComponent;
use Illuminate\Database\Seeder;

class SubAssemblyComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SubAssemblyComponent::create([
            'name' => 'Control Interface',
            'type' => 'Sub-Assembly-Component',
        ]);

        SubAssemblyComponent::create([
            'name' => 'Audio Modal',
            'type' => 'Sub-Assembly-Component',
        ]);

        SubAssemblyComponent::create([
            'name' => 'Motherboard',
            'type' => 'Sub-Assembly-Component',
        ]);
    }
}
