<?php

namespace Database\Seeders;

use App\Models\Packaging;
use Illuminate\Database\Seeder;

class PackagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Packaging::create([
            'type' => 'Standard',
            'cost' => '10',
        ]);

        Packaging::create([
            'type' => 'Premium ',
            'cost' => '14',
        ]);

        Packaging::create([
            'type' => 'Environmentally Sensitive Premium ',
            'cost' => '28',
            'description' => 'Premium packaging may increase demand and offers better protection during shipping, potentially reducing failure rates in the field.',
        ]);
    }
}
