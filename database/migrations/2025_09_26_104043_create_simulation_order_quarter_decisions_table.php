<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimulationOrderQuarterDecisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simulation_order_quarter_decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_order_quarter_id');
            $table->unsignedBigInteger('decision_id');
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
        Schema::dropIfExists('simulation_order_quarter_decisions');
    }
}
