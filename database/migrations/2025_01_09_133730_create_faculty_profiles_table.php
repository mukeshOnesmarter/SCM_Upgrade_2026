<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacultyProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faculty_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faculty_id'); // FOREIGN KEY REFERENCING FACULTY MODAL CLASS.
            $table->string('profile_picture')->nullable();
            $table->text('address')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 10)->nullable();
            $table->text('qualifications')->nullable();
            $table->text('research_interests')->nullable();
            $table->integer('experience')->default(0); // YEARS OF EXPERIENCE
            $table->text('bio')->nullable();
            $table->json('social_links')->nullable(); // STORE AS JSON (e.g., { "LinkedIn": "...", "Twitter": "..." })
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
        Schema::dropIfExists('faculty_profiles');
    }
}
