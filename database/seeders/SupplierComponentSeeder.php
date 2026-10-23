<?php

namespace Database\Seeders;

use App\Models\SupplierComponent;
use Illuminate\Database\Seeder;

class SupplierComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        SupplierComponent::create([
            'supplier_id' => 1,
            'sub_assembly_component_id' => 2,
            'cost' => '16',
            'expected_value' => 87,
            'possible_variation' => 5,
            'failure_rate' => 0.5,
        ]);
        SupplierComponent::create([
            'supplier_id' => 2,
            'sub_assembly_component_id' => 2,
            'cost' => '13',
            'expected_value' => 85,
            'possible_variation' => 10,
            'failure_rate' => 2.1,
        ]);
        SupplierComponent::create([
            'supplier_id' => 3,
            'sub_assembly_component_id' => 2,
            'cost' => '11',
            'expected_value' => 82,
            'possible_variation' => 10,
            'failure_rate' => 2.7,
        ]);
        SupplierComponent::create([
            'supplier_id' => 4,
            'sub_assembly_component_id' => 2,
            'cost' => '22',
            'expected_value' => 90,
            'possible_variation' => 8,
            'failure_rate' => 1.2,
        ]);
        SupplierComponent::create([
            'supplier_id' => 2,
            'sub_assembly_component_id' => 1,
            'cost' => '15',
            'expected_value' => 75,
            'possible_variation' => 4,
            'failure_rate' => 2.6,
        ]);
        SupplierComponent::create([
            'supplier_id' => 3,
            'sub_assembly_component_id' => 1,
            'cost' => '16',
            'expected_value' => 78,
            'possible_variation' => 6,
            'failure_rate' => 2.5,
        ]);
        SupplierComponent::create([
            'supplier_id' => 4,
            'sub_assembly_component_id' => 1,
            'cost' => '24',
            'expected_value' => 80,
            'possible_variation' => 8,
            'failure_rate' => 1.8,
        ]);
        SupplierComponent::create([
            'supplier_id' => 5,
            'sub_assembly_component_id' => 1,
            'cost' => '14',
            'expected_value' => 70,
            'possible_variation' => 10,
            'failure_rate' => 2.7,
        ]);
        SupplierComponent::create([
            'supplier_id' => 6,
            'sub_assembly_component_id' => 1,
            'cost' => '13',
            'expected_value' => 70,
            'possible_variation' => 12,
            'failure_rate' => 2.8,
        ]);

        SupplierComponent::create([
            'supplier_id' => 4,
            'sub_assembly_component_id' => 3,
            'cost' => '29',
            'expected_value' => 80,
            'possible_variation' => 5,
            'failure_rate' => 0.9,
        ]);
        SupplierComponent::create([
            'supplier_id' => 5,
            'sub_assembly_component_id' => 3,
            'cost' => '20',
            'expected_value' => 75,
            'possible_variation' => 10,
            'failure_rate' => 1.7,
        ]);
        SupplierComponent::create([
            'supplier_id' => 6,
            'sub_assembly_component_id' => 3,
            'cost' => '19',
            'expected_value' => 77,
            'possible_variation' => 10,
            'failure_rate' => 2.2,
        ]);
        SupplierComponent::create([
            'supplier_id' => 7,
            'sub_assembly_component_id' => 3,
            'cost' => '21',
            'expected_value' => 78,
            'possible_variation' => 10,
            'failure_rate' => 2.1,
        ]);
    }
}
