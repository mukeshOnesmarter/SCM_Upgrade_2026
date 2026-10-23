<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcurementSubAssembliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('procurement_sub_assemblies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_id');
            $table->unsignedBigInteger('sub_assembly_component_id')->comment('sub assemblie');
            $table->unsignedBigInteger('supplier_id')->comment('supplier');
            $table->unsignedBigInteger('supply_channel_id')->comment('supply_channel');
            $table->integer('quantity')->nullable();
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
        Schema::dropIfExists('procurement_sub_assemblies');
    }
}
