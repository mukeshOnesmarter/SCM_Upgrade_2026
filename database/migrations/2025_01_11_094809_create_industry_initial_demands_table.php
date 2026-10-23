<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndustryInitialDemandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('industry_initial_demands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('industry_id'); // FOREIGN KEY INDUSTRY CLASS
            $table->unsignedBigInteger('product_id'); // FOREIGN KEY PRODUCT CLASS
            $table->unsignedBigInteger('region_id'); // FOREIGN KEY REGION CLASS
            $table->string('demand_channel_01')->comment('retail');
            $table->string('demand_channel_02')->comment('direct');
            $table->tinyInteger('quarters')->default(0);
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
        Schema::dropIfExists('industry_initial_demands');
    }
}
