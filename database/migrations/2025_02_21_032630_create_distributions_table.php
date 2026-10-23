<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id'); // FOREIGN KEY REFERENCING SIMULATION MODAL CLASS.
            $table->unsignedBigInteger('faculty_id'); // FOREIGN KEY REFERENCING FACULTY MODAL CLASS.
            $table->tinyInteger('group_number')->nullable();
            $table->tinyInteger('quarter')->nullable();
            $table->string('region_id')->nullable();
            $table->string('dc_number')->nullable();

            $table->string('dc_type')->nullable()->comment('/ Insourced / Outsourced');
            $table->string('is_home_region')->default('no')->comment('yes / no');
            $table->string('rfid_application')->nullable()->comment('yes / no');
            $table->string('ffgi_shipping')->nullable()->comment('economy, standard, expedited');
            $table->string('sac_shipping')->nullable()->comment('economy, standard, expedited');
            $table->string('cross_docking_k')->nullable();
            $table->string('cross_docking_l')->nullable();
            $table->string('cross_docking_m')->nullable();
            $table->string('cross_docking_n')->nullable();
            $table->unsignedBigInteger('emergency_carrier_id'); // FOREIGN KEY REFERENCING SHIPMENT MODAL CLASS.;

            $table->string('change_saved_by')->comment('student_id'); // FOREIGN KEY REFERENCING STUDENT MODAL CLASS.
            $table->timestamp('change_saved_timestamp')->nullable();
            $table->string('change_log_history')->nullable();
            $table->string('is_operational')->default('yes')->comment('yes / no');
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
        Schema::dropIfExists('distributions');
    }
}
