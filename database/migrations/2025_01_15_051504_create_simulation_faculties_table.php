<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimulationFacultiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simulation_faculties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id');
            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->enum('role', ['faculty', 'co_faculty'])->default('faculty');
            $table->string('is_published')->default('No');
            $table->timestamp('frozen_until')->nullable();
            $table->tinyInteger('status')->default('1');
            $table->tinyInteger('deleted_at')->default('0');
            $table->enum('payment_mode', ['student', 'institute'])->default('student');
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
        Schema::dropIfExists('simulation_faculties');
    }
}
