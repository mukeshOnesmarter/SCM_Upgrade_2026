<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScorecardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scorecards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id');
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedInteger('quarter');

            $table->string('category', 32); // financial, operational, etc.
            $table->string('sub_measure', 64); // e.g., Net Profit Margin

            $table->decimal('your_firm', 14, 2)->nullable();
            $table->decimal('industry_worst', 14, 2)->nullable();
            $table->decimal('industry_avg', 14, 2)->nullable();
            $table->decimal('industry_best', 14, 2)->nullable();

            $table->string('unit', 8)->nullable();
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
        Schema::dropIfExists('scorecards');
    }
}
