<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialQuartersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('financial_quarters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id');
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('group_number');
            $table->unsignedBigInteger('quarter_id');

            $table->decimal('cash', 15, 2)->default(0);
            $table->decimal('loans', 15, 2)->default(0);
            $table->decimal('marketable_securities', 15, 2)->default(0);
            $table->decimal('non_operating_income', 15, 2)->default(0);
            $table->decimal('dividends', 15, 2)->default(0);
            $table->decimal('net_income', 15, 2)->default(0);
            $table->decimal('retained_earnings_current', 15, 2)->default(0);
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
        Schema::dropIfExists('financial_quarters');
    }
}
