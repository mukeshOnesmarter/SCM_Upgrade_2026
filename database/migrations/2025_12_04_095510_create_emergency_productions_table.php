<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmergencyProductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emergency_productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id')->index();
            $table->unsignedBigInteger('faculty_id')->index();
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->unsignedInteger('quarter')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->integer('quantity')->default(0);
            $table->decimal('estimated_unit_cost', 14, 4)->nullable();
            $table->decimal('estimated_total_cost', 16, 2)->nullable();
            $table->string('source')->default('in_house'); // in_house | plant-shipped | external
            $table->json('meta')->nullable();
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
        Schema::dropIfExists('emergency_productions');
    }
}
