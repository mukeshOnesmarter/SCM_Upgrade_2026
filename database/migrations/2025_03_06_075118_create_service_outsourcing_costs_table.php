<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceOutsourcingCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_outsourcing_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_outsourcing_id');
            $table->unsignedBigInteger('region_id');
            $table->decimal('cost_call', 8, 2)->unsigned()->comment('Cost per call in $');
            $table->decimal('sq_guarantee', 5, 2)->default(0.10);
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
        Schema::dropIfExists('service_outsourcing_costs');
    }
}
