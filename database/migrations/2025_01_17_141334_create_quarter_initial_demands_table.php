<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuarterInitialDemandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quarter_initial_demands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id'); // FOREIGN KEY REFERENCING SIMULATION MODAL CLASS.
            $table->unsignedBigInteger('product_id'); // FOREIGN KEY REFERENCING PRODUCT MODAL CLASS.
            $table->unsignedBigInteger('region_id'); // FOREIGN KEY REFERENCING REGION MODAL CLASS.
            $table->unsignedBigInteger('firm_id'); // FOREIGN KEY REFERENCING FIRM MODAL CLASS.

            $table->bigInteger('demand_channel_01')->comment('retail');
            $table->bigInteger('demand_channel_02')->comment('direct');

            $table->tinyInteger('quarter')->nullable();
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
        Schema::dropIfExists('quarter_initial_demands');
    }
}
