<?php

namespace Database\Seeders;

use App\Models\Decision;
use Illuminate\Database\Seeder;

class DecisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Decision::create([
            'title' => 'Sales Forecasting',
            'slug' => 'forecasting',
        ]);

        Decision::create([
            'title' => 'Procurement',
            'slug' => 'procurement',
        ]);

        Decision::create([
            'title' => 'Manufacturing',
            'slug' => 'manufacturing',
        ]);

        Decision::create([
            'title' => 'Distribution',
            'slug' => 'distribution',
        ]);

        Decision::create([
            'title' => 'Transportation',
            'slug' => 'transportation',
        ]);

        Decision::create([
            'title' => 'Service Management',
            'slug' => 'service-management',
        ]);

        Decision::create([
            'title' => 'Demand Management',
            'slug' => 'demand-planning',
        ]);

        Decision::create([
            'title' => 'IT & Supply Chain Sync',
            'slug' => 'information-technology',
        ]);
    }
}
