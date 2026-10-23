<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawMaterialVolumeDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_material_volume_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('raw_material_id'); // FOREIGN KEY REFERENCING RAW-MATERIAL MODAL CLASS.
            $table->string('volume', 70);
            $table->string('discount', 70)->comment('%');
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
        Schema::dropIfExists('raw_material_volume_discounts');
    }
}
