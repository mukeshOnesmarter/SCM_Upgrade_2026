<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcurementEmergencyRawsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('procurement_emergency_raws', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_id');
            $table->unsignedBigInteger('raw_material_id')->comment('raw_material');
            $table->integer('quantity')->nullable();
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
        Schema::dropIfExists('procurement_emergency_raws');
    }
}
