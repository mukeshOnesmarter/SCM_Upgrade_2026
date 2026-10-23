<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id'); // FOREIGN KEY REFERENCING STUDENT MODAL CLASS.
            $table->string('profile_picture')->nullable();
            $table->text('address')->nullable();
            $table->string('nationality')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->text('bio')->nullable();
            $table->json('social_links')->nullable(); // STORE AS JSON (e.g., { "LinkedIn": "...", "Twitter": "..." })
            $table->text('academic_interests')->nullable();
            $table->text('skills')->nullable();
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
        Schema::dropIfExists('student_profiles');
    }
}
