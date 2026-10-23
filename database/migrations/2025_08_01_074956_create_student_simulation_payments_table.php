<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentSimulationPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_simulation_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('simulation_id');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable(); // e.g., 'manual', 'razorpay', 'stripe'
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->timestamp('paid_at')->nullable(); // When payment was made
            $table->text('transaction_reference')->nullable(); // Transaction ID or manual note
            $table->unsignedBigInteger('confirmed_by')->nullable(); // faculty/admin ID (optional)
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
        Schema::dropIfExists('student_simulation_payments');
    }
}
