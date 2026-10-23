<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManufacturingCostAdditionalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manufacturing_cost_additionals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manufacturing_cost_id');
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('distribution_center');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('fixed_cost')->comment('per order');
            $table->unsignedInteger('labor_cost')->comment('per unit');
            $table->unsignedInteger('production_cost')->comment('per unit');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manufacturing_cost_additionals');
    }
}
