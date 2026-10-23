<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimulationOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('simulation_orders', function (Blueprint $table) {
            $table->id();

            $table->string('course_title', 120);
            $table->string('organization', 120)->nullable();

            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->string('faculty_name', 120)->nullable();
            $table->string('faculty_email', 120)->nullable();

            $table->date('start_date')->nullable();
            $table->unsignedTinyInteger('quarters')->default(1);
            $table->unsignedInteger('teams')->default(1);
            $table->unsignedInteger('learners')->default(1);

            $table->decimal('fee_per_learner', 10, 2)->default(0.00);
            $table->enum('payment_responsibility', ['learner', 'institution'])->default('institution');

            $table->string('time_zone', 64)->default('UTC');
            $table->text('special_instructions')->nullable();
            $table->json('settings')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'running', 'completed', 'cancelled'])->default('draft');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('extra_data')->nullable()->comment('Store additional information');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('simulation_orders');
    }
}
