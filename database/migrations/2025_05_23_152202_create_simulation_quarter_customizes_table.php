<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimulationQuarterCustomizesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simulation_quarter_customizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faculty_id'); // FOREIGN KEY REFERENCING FACULTY CLASS.
            $table->unsignedBigInteger('simulation_id'); // FOREIGN KEY REFERENCING SIMULATION MODAL CLASS.
            $table->string('quarter_name', 70)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('is_schedule')->default('Yes');
            $table->json('decisions')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->tinyInteger('deleted_at')->default('0');
            $table->string('close_manualy')->nullable();
            $table->string('closing_sync')->nullable();
            $table->dateTime('sync_date')->nullable();
            $table->json('extra_data')->nullable()->comment('Store additional information');
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
        Schema::dropIfExists('simulation_quarter_customizes');
    }
}
