<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostpondConversionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('postpond_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id')->index();
            $table->unsignedBigInteger('faculty_id')->index();
            $table->unsignedBigInteger('group_number')->index();
            $table->unsignedInteger('quarter')->index();

            $table->unsignedInteger('region_id')->nullable()->index();
            $table->unsignedInteger('dc_number')->nullable()->index();

            $table->integer('converted_p1')->default(0);
            $table->integer('converted_p2')->default(0);
            $table->integer('total_converted')->default(0);

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
        Schema::dropIfExists('postpond_conversions');
    }
}
