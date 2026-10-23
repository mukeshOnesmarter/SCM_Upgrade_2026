<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubAssemblyTransportationCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_assembly_transportation_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_assembly_component_id');
            $table->decimal('channel_01', 8, 2)->default('0.00')->nullable()->comment('Air');
            $table->decimal('channel_02', 8, 2)->default('0.00')->nullable()->comment('Surface');
            $table->string('emergency_order')->default('50')->nullable()->comment('% higher');
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
        Schema::dropIfExists('sub_assembly_transportation_costs');
    }
}
