<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawMaterialSafetyStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_material_safety_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('raw_material_id'); // FOREIGN KEY REFERENCING RAW-MATERIAL MODAL CLASS.
            $table->string('safety_stock', 70)->comment('%');
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
        Schema::dropIfExists('raw_material_safety_stocks');
    }
}
