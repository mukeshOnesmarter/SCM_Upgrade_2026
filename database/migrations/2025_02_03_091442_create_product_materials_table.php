<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // FOREIGN KEY REFERENCING PRODUCT MODAL CLASS.
            $table->unsignedBigInteger('material_id'); // FOREIGN KEY RAW_MATERIAL / SUB_ASSEMBLY_COMPONENT.
            $table->string('type', 70)->comment('RAW/SAC');
            $table->string('quantity', 70)->nullable();
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
        Schema::dropIfExists('product_materials');
    }
}
