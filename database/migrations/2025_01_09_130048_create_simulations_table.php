<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimulationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->string('simulation_code')->unique();
            $table->string('share_code')->unique();
            $table->string('title');
            $table->string('organization')->nullable();
            $table->integer('quarters');
            $table->integer('firms');
            $table->integer('teams');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('timezone', 100)->default('America/New_York');
            $table->string('is_published')->default('No');
            $table->string('simulation_status')->default('In Progress');
            $table->decimal('fee', 8, 2)->default(100.00);
            $table->integer('licence_count')->default(0);
            $table->string('payment_status')->default('pending');
            $table->tinyInteger('status')->default('1');
            $table->boolean('is_syncing')->default(false);
            $table->boolean('is_deploying')->default(false);
            $table->boolean('is_analysis_enabled')->default(false);
            $table->tinyInteger('deleted_at')->default('0');
            $table->unsignedBigInteger('created_by')->comment('Admin ID');
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
        Schema::dropIfExists('simulations');
    }
}
