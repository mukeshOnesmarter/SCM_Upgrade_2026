<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostpondProductionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('postpond_productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id'); // FOREIGN KEY REFERENCING SIMULATION MODAL CLASS.
            $table->unsignedBigInteger('faculty_id'); // FOREIGN KEY REFERENCING FACULTY MODAL CLASS.
            $table->tinyInteger('group_number')->nullable();
            $table->tinyInteger('quarter')->nullable();
            $table->unsignedBigInteger('region_id')->nullable(); // FOREIGN KEY REFERENCING REGION MODAL CLASS.
            $table->unsignedBigInteger('product_id')->nullable(); // FOREIGN KEY REFERENCING REGION MODAL CLASS.
            $table->integer('quantity')->nullable();
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
        Schema::dropIfExists('postpond_productions');
    }
}
