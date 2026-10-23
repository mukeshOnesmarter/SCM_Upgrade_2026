<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportExportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id');
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedInteger('quarter');

            $table->string('report_type'); // e.g. 'corporatePnl', 'balanceSheet', etc.
            $table->string('file_path');   // Relative or CDN path to the saved XLSX
            $table->json('filters')->nullable();
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
        Schema::dropIfExists('report_exports');
    }
}
