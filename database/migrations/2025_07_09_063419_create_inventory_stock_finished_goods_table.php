<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryStockFinishedGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_stock_finished_goods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id');
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('group_number');
            $table->unsignedInteger('quarter');
            $table->unsignedInteger('region_id');
            $table->unsignedInteger('dc_number');

            $table->integer('product_0')->default(0);
            $table->integer('product_01')->default(0);
            $table->integer('product_02')->default(0);

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
        Schema::dropIfExists('inventory_stock_finished_goods');
    }
}
