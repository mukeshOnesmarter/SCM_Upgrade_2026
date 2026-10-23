<?php

namespace Database\Seeders;

use App\Models\RawMaterial;
use Illuminate\Database\Seeder;

class RawMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RawMaterial::create([
            'name' => 'Plastics',
            'type' => 'Raw-Materials',
        ]);

        RawMaterial::create([
            'name' => 'Casings',
            'type' => 'Raw-Materials',
        ]);
    }
}
