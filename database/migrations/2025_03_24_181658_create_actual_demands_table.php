<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ActualDemand
 *
 * ⚠️ IMPORTANT CLARIFICATION
 * ----------------------------------------------------
 * This model currently represents **MARKET DEMAND**, NOT actual firm sales.
 *
 * • Data stored here is the total market demand generated
 *   per simulation / quarter / region / product / channel.
 *
 * • It does NOT indicate how much demand a firm fulfilled.
 *   Fulfillment is calculated later via allocation,
 *   production, inventory, and logistics logic.
 */
class CreateActualDemandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actual_demands', function (Blueprint $table) {
            $table->id();
            // Simulation context
            $table->unsignedBigInteger('simulation_id')
                ->comment('References simulations.id');

            $table->unsignedBigInteger('faculty_id')
                ->comment('References faculties.id');

            $table->tinyInteger('group_number')->nullable()
                ->comment('Team / group number within the simulation');

            $table->tinyInteger('quarter')->nullable()
                ->comment('Simulation quarter');

            // Market dimensions
            $table->unsignedBigInteger('region_id')
                ->comment('Market region (not fulfillment location)');

            $table->unsignedBigInteger('product_id')
                ->comment('Product for which market demand is generated');

            $table->unsignedBigInteger('firm_id')
                ->comment('Firm context used for demand calculation (not sales ownership)');

            /**
             * MARKET DEMAND BY CHANNEL
             * ------------------------------------------------
             * These values represent TOTAL MARKET DEMAND,
             * not fulfilled sales by the firm.
             */
            $table->bigInteger('demand_channel_01')->nullable()
                ->comment('Retail channel market demand');

            $table->bigInteger('demand_channel_02')->nullable()
                ->comment('Direct channel market demand');

            // Demand shift indicator
            $table->boolean('shift_applied')
                ->default(false)
                ->comment('Indicates whether demand shift logic was applied');

            // Audit & tracking
            $table->string('change_saved_by')
                ->comment('Student ID who triggered demand generation');

            $table->timestamp('change_saved_timestamp')
                ->comment('Timestamp when demand was generated/saved');

            $table->string('change_log_history')->nullable()
                ->comment('Change or recalculation history');

            $table->json('extra_data')->nullable()
                ->comment('Additional metadata related to demand calculation');

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
        Schema::dropIfExists('actual_demands');
    }
}
