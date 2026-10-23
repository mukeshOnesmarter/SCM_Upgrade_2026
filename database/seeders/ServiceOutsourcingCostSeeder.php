<?php

namespace Database\Seeders;

use App\Models\ServiceOutsourcingCost;
use Illuminate\Database\Seeder;

class ServiceOutsourcingCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 1,
            'region_id' => 1,
            'cost_call' => 7.50,
            'sq_guarantee' => 0.90,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 1,
            'region_id' => 2,
            'cost_call' => 9.00,
            'sq_guarantee' => 0.90,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 1,
            'region_id' => 3,
            'cost_call' => 10.00,
            'sq_guarantee' => 0.90,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 2,
            'region_id' => 1,
            'cost_call' => 12.50,
            'sq_guarantee' => 0.95,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 2,
            'region_id' => 2,
            'cost_call' => 15.00,
            'sq_guarantee' => 0.95,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 2,
            'region_id' => 3,
            'cost_call' => 16.50,
            'sq_guarantee' => 0.95,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 3,
            'region_id' => 1,
            'cost_call' => 20.00,
            'sq_guarantee' => 0.98,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 3,
            'region_id' => 2,
            'cost_call' => 22.50,
            'sq_guarantee' => 0.98,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 3,
            'region_id' => 3,
            'cost_call' => 26.00,
            'sq_guarantee' => 0.98,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 4,
            'region_id' => 1,
            'cost_call' => 30.00,
            'sq_guarantee' => 1.00,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 4,
            'region_id' => 2,
            'cost_call' => 33.75,
            'sq_guarantee' => 1.00,
        ]);

        ServiceOutsourcingCost::create([
            'service_outsourcing_id' => 4,
            'region_id' => 3,
            'cost_call' => 40.00,
            'sq_guarantee' => 1.00,
        ]);
    }
}
