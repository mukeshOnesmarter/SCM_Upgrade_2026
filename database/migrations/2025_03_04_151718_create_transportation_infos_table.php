<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransportationInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transportation_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transportation_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('carrier_id');
            $table->unsignedBigInteger('supply_channel_id');
            $table->integer('quantity');
            $table->integer('total_shipment')->nullable();
            $table->integer('quantity_delay')->default(0);
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
        Schema::dropIfExists('transportation_infos');
    }
}
