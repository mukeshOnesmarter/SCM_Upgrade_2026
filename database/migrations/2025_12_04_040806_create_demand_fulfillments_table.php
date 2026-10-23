<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemandFulfillmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demand_fulfillments', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Context
            $table->unsignedBigInteger('simulation_id')->index();
            $table->unsignedBigInteger('faculty_id')->index();
            $table->unsignedInteger('group_number')->index();
            $table->unsignedInteger('quarter')->index();

            // DC / product
            $table->unsignedInteger('region_id')->nullable()->index();
            $table->unsignedInteger('dc_number')->nullable()->index();
            $table->unsignedSmallInteger('product_id')->index();

            // demand & allocation breakdown
            $table->unsignedInteger('demand')->default(0);

            $table->unsignedInteger('taken_from_fg')->default(0);
            $table->unsignedInteger('taken_from_postpond')->default(0);
            $table->unsignedInteger('taken_from_plant')->default(0);
            $table->unsignedInteger('produced_emergency')->default(0);

            $table->unsignedInteger('unfilled')->default(0);

            // optional cost fields (snapshots for audit)
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->decimal('production_unit_cost', 12, 4)->nullable();
            $table->decimal('air_unit_cost', 12, 4)->nullable();

            // bookkeeping
            $table->unsignedBigInteger('postpond_conversion_id')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Useful constraints (optional)
            $table->unique(
                [
                    'simulation_id',
                    'faculty_id',
                    'group_number',
                    'quarter',
                    'region_id',
                    'dc_number',
                    'product_id',
                ],
                'uniq_demand_fulfillment_slice'
            );

            $table->index(
                ['simulation_id', 'quarter', 'group_number'],
                'idx_sim_q_group'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('demand_fulfillments');
    }
}
