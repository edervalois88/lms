<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add columns only if they don't exist
            if (!Schema::hasColumn('users', 'streak_days')) {
                $table->integer('streak_days')->default(0);
            }
            if (!Schema::hasColumn('users', 'last_study_at')) {
                $table->timestamp('last_study_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'streak_days')) {
                $table->dropColumn('streak_days');
            }
            if (Schema::hasColumn('users', 'last_study_at')) {
                $table->dropColumn('last_study_at');
            }
        });
    }
};
