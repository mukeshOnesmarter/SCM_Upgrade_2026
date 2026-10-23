<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id'); // FOREIGN KEY REFERENCING SIMULATION MODAL CLASS.
            $table->unsignedBigInteger('faculty_id'); // FOREIGN KEY REFERENCING FACULTY MODAL CLASS.
            $table->tinyInteger('group_number')->nullable();
            $table->tinyInteger('quarter')->nullable();
            $table->tinyInteger('region_id')->nullable();
            $table->tinyInteger('dc_number')->nullable();
            $table->integer('plastic')->default(0);
            $table->integer('casing')->default(0);
            $table->integer('control_interface')->default(0);
            $table->integer('audio_modal')->default(0);
            $table->integer('motherboard')->default(0);
            $table->integer('motherboard_on_order')->default(0);
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
        Schema::dropIfExists('inventory_stocks');
    }
}
