<?php

namespace Database\Seeders;

use App\Models\ServiceOutsourcing;
use Illuminate\Database\Seeder;

class ServiceOutsourcingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ServiceOutsourcing::create([
            'level_title' => 'Minimum',
            'description' => 'Basic call center service with 10% service quality (SQ) guarantee.',
        ]);

        ServiceOutsourcing::create([
            'level_title' => 'Standard',
            'description' => 'Balanced support and cost. 20% SQ guarantee.',
        ]);

        ServiceOutsourcing::create([
            'level_title' => 'Enhanced',
            'description' => 'Faster response times. 30% SQ guarantee.',
        ]);

        ServiceOutsourcing::create([
            'level_title' => 'Premium',
            'description' => 'Best-in-class service. 40% SQ guarantee.',
        ]);
    }
}
