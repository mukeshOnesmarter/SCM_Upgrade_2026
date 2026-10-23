<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmergencyShipmentsTable extends Migration
{
    public function up()
    {
        Schema::create('emergency_shipments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('simulation_id')->index();
            $table->unsignedBigInteger('faculty_id')->index();
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->unsignedInteger('quarter')->index();

            // 🔑 link to emergency_productions
            $table->unsignedBigInteger('emergency_production_id')->index();

            $table->unsignedBigInteger('product_id')->index();
            $table->integer('quantity')->default(0);

            $table->decimal('unit_freight', 12, 4)->default(0);
            $table->decimal('total_freight', 16, 2)->default(0);

            $table->string('mode')->default('air');
            $table->json('meta')->nullable();

            $table->timestamps();

            // 🔒 Strong idempotency guarantee
            $table->unique('emergency_production_id');

            // Optional but highly recommended
            $table->foreign('emergency_production_id')
                ->references('id')
                ->on('emergency_productions')
                ->onDelete('cascade');

            $table->index(['simulation_id', 'quarter', 'group_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('emergency_shipments');
    }
}
