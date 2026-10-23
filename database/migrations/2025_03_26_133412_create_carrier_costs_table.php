<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarrierCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carrier_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carrier_id');
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('supply_channel_id');
            $table->string('cost')->nullable();
            $table->string('expected_value')->nullable()->comment('delivery %');
            $table->string('possible_variation')->nullable()->comment('delivery %');
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
        Schema::dropIfExists('carrier_costs');
    }
}
