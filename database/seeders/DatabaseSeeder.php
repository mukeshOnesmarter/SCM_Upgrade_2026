<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    /**
     * The simulation's reference data: industries, regions, products,
     * suppliers, carriers, costs and the rest. Safe on any database.
     *
     * No accounts are seeded here. Create an admin with
     * `php artisan scm:create-admin`. For demo faculty and students on a
     * development database, run `php artisan db:seed --class=DemoSeeder`.
     */
    public function run(): void
    {
        $this->call([
            IndustrySeeder::class,
            FirmSeeder::class,
            RegionSeeder::class,
            DemandChannelSeeder::class,
            PackagingSeeder::class,
            SupplyChannelSeeder::class,
            DecisionSeeder::class,
            IndustryInitialDemandSeeder::class,
            RawMaterialSeeder::class,
            RawMaterialCostSeeder::class,
            RawMaterialVolumeDiscountSeeder::class,
            RawMaterialSafetyStockSeeder::class,
            SubAssemblyComponentSeeder::class,
            SubAssemblyTransportationCostSeeder::class,
            SubAssemblyVolumeDiscountSeeder::class,
            ServiceOutsourcingSeeder::class,
            ServiceOutsourcingCostSeeder::class,
            CarrierSeeder::class,
            CarrierChannelSeeder::class,
            CarrierCrossDockingSeeder::class,
            CarrierCostSeeder::class,
            ManufacturingCostSeeder::class,
            ManufacturingCostAdditionalSeeder::class,
            AiPromptSeeder::class,
            ConsultingFeeSeeder::class,

            SupplierSeeder::class,
            SupplierComponentSeeder::class,

            ProductSeeder::class,
            ProductMaterialSeeder::class,
        ]);
    }
}
