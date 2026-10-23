<?php

namespace Database\Seeders;

use App\Models\ConsultingFee;
use Illuminate\Database\Seeder;

class ConsultingFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fees = [
            [
                'slug' => 'earnings',
                'title' => 'Benchmark - Earnings',
                'cost' => 1000,
                'limit' => null,
            ],

            [
                'slug' => 'balancesheet',
                'title' => 'Benchmark - Balance Sheets',
                'cost' => '2000 per firm',
                'limit' => null,
            ],

            [
                'slug' => 'procurement',
                'title' => 'Benchmark - Procurement',
                'cost' => 3500,
                'limit' => null,
            ],

            [
                'slug' => 'manufacturing',
                'title' => 'Benchmark - Manufacturing',
                'cost' => 6000,
                'limit' => null,
            ],

            [
                'slug' => 'distribution',
                'title' => 'Benchmark - Distribution',
                'cost' => 6000,
                'limit' => null,
            ],

            [
                'slug' => 'transportation',
                'title' => 'Benchmark - Transportation',
                'cost' => 6500,
                'limit' => null,
            ],

            [
                'slug' => 'generatedemand',
                'title' => 'Benchmark - Generate Demand',
                'cost' => 7000,
                'limit' => null,
            ],

            [
                'slug' => 'it',
                'title' => 'Benchmark - Info Tech & Research Studies',
                'cost' => 2500,
                'limit' => null,
            ],

            [
                'slug' => 'operating',
                'title' => 'Benchmark - Operating Statistics',
                'cost' => 3000,
                'limit' => null,
            ],

            [
                'slug' => 'market',
                'title' => 'Market Statistics',
                'cost' => 3000,
                'limit' => null,
            ],

            [
                'slug' => 'region',
                'title' => 'Regional Summary Analysis',
                'cost' => '6000 per region',
                'limit' => null,
            ],

            [
                'slug' => 'customersatisfaction',
                'title' => 'Customer Satisfaction',
                'cost' => 12000,
                'limit' => null,
            ],

            [
                'slug' => 'pricesensitivity',
                'title' => 'Price Sensitivity Analysis',
                'cost' => '25000 per product per region per channel',
                'limit' => 4,
            ],
        ];

        foreach ($fees as $fee) {
            ConsultingFee::updateOrCreate(
                ['slug' => $fee['slug']],
                $fee
            );
        }
    }
}
