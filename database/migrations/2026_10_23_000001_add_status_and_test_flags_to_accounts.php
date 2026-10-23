<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admins get a status like faculty and students, so an account can be
     * switched off without deleting it. Every account gets an is_test mark:
     * test accounts are kept to rehearsal courses and left out of reports.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('status')->default('Active')->after('role');
            $table->boolean('is_test')->default(false)->after('status');
        });

        Schema::table('faculties', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('status');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('is_test'));
        Schema::table('faculties', fn (Blueprint $table) => $table->dropColumn('is_test'));
        Schema::table('admins', fn (Blueprint $table) => $table->dropColumn(['status', 'is_test']));
    }
};
