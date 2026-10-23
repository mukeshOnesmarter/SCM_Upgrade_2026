<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForecastingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forecastings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id'); // FOREIGN KEY REFERENCING SIMULATION MODAL CLASS.
            $table->unsignedBigInteger('faculty_id'); // FOREIGN KEY REFERENCING FACULTY MODAL CLASS.
            $table->tinyInteger('group_number')->nullable();
            $table->tinyInteger('quarter')->nullable();

            $table->unsignedBigInteger('region_id'); // FOREIGN KEY REFERENCING REGION MODAL CLASS.
            $table->unsignedBigInteger('product_id'); // FOREIGN KEY REFERENCING PRODUCT MODAL CLASS.
            $table->unsignedBigInteger('firm_id'); // FOREIGN KEY REFERENCING FIRM MODAL CLASS.

            $table->bigInteger('demand_channel_01')->nullable()->comment('Retail');
            $table->bigInteger('demand_channel_02')->nullable()->comment('Direct');
            $table->decimal('accuracy_channel_01', 5, 1)->nullable();
            $table->decimal('accuracy_channel_02', 5, 1)->nullable();

            $table->string('change_saved_by')->comment('student_id'); // FOREIGN KEY REFERENCING STUDENT MODAL CLASS.
            $table->timestamp('change_saved_timestamp')->nullable();
            $table->string('change_log_history')->nullable();
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
        Schema::dropIfExists('forecastings');
    }
}
