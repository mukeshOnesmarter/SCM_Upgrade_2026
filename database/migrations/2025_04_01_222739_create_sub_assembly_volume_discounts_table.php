<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubAssemblyVolumeDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_assembly_volume_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_assembly_component_id');
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
        Schema::dropIfExists('sub_assembly_volume_discounts');
    }
}
