<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id'); // FOREIGN KEY REFERENCING SUPPLIER MODAL CLASS.
            $table->unsignedBigInteger('sub_assembly_component_id'); // FOREIGN KEY REFERENCING SUB_ASSEMBLY_COMPONENT MODAL CLASS.
            $table->integer('cost');
            $table->integer('expected_value');
            $table->integer('possible_variation');
            $table->decimal('failure_rate', 5, 2);
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
        Schema::dropIfExists('supplier_components');
    }
}
