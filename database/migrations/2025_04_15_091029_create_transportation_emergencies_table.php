<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransportationEmergenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transportation_emergencies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('simulation_id'); // FOREIGN KEY REFERENCING SIMULATION MODAL CLASS.
            $table->unsignedBigInteger('faculty_id'); // FOREIGN KEY REFERENCING FACULTY MODAL CLASS.
            $table->tinyInteger('group_number')->nullable();
            $table->tinyInteger('quarter')->nullable();

            $table->unsignedBigInteger('distribution_center_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('carrier_id');
            $table->unsignedBigInteger('supply_channel_id');
            $table->integer('quantity');
            $table->integer('total_shipment')->nullable();
            $table->integer('quantity_delay')->default(0);

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
        Schema::dropIfExists('transportation_emergencies');
    }
}
