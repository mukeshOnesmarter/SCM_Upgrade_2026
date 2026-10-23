<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcurementEmergencySubsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('procurement_emergency_subs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_id');
            $table->unsignedBigInteger('sub_assembly_component_id')->comment('sub assemblie');
            $table->unsignedBigInteger('supplier_id')->comment('supplier');
            $table->unsignedBigInteger('supply_channel_id')->comment('supply_channel');
            $table->integer('quantity')->nullable();
            $table->integer('total_shipment')->nullable();
            $table->integer('quantity_delay')->default(0);
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('procurement_emergency_subs');
    }
}
