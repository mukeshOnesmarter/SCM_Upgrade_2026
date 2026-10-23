<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimulationTeamMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simulation_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('simulation_id'); // FOREIGN KEY SIMULATION CLASS.
            $table->unsignedBigInteger('simulation_team_id'); // FOREIGN KEY SIMULATION-TEAM CLASS.
            $table->unsignedBigInteger('faculty_id'); // FOREIGN KEY FACULTY CLASS.
            $table->unsignedBigInteger('student_id'); // FOREIGN KEY STUDENT CLASS
            $table->string('student_allowed')->default('No'); // FACULTY ALLOWED TO PERFORM SIMULATION.
            $table->boolean('initial_setup_done')->default(false);
            $table->timestamp('initial_setup_at')->nullable();
            $table->date('student_joined')->nullable(); // STUDENT JOIN THE SIMULATION IF YES JOIN DATE.

            $table->boolean('is_blocked')->default(false);
            $table->timestamp('blocked_at')->nullable();
            $table->boolean('is_removed')->default(false);
            $table->timestamp('removed_at')->nullable();
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, partial
            $table->date('payment_due_date')->nullable();
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
        Schema::dropIfExists('simulation_team_members');
    }
}
