<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierComponentPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_component_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id');
            $table->unsignedBigInteger('faculty_id');
            $table->tinyInteger('quarter_id')->nullable();

            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('sub_assembly_component_id');
            $table->integer('adjusted_price');
            $table->integer('market_share');
            $table->integer('has_capacity_constraint');
            $table->json('extra_data')->nullable()->comment('Store additional information');
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
        Schema::dropIfExists('supplier_component_prices');
    }
}
