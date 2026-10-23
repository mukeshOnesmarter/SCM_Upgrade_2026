<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawMaterialCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_material_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('raw_material_id'); // FOREIGN KEY REFERENCING RAW-MATERIAL MODAL CLASS.
            $table->decimal('cost', 8, 2)->default('0.00')->nullable();
            $table->decimal('emergency_procurement', 8, 2)->default('0.00')->nullable()->comment('cost + amount	');
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
        Schema::dropIfExists('raw_material_costs');
    }
}
