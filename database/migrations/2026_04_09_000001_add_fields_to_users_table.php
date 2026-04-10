<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('email');
            $table->json('preferences')->nullable()->after('role');
            $table->timestamp('onboarded_at')->nullable()->after('preferences');
            $table->integer('streak_days')->default(0)->after('onboarded_at');
            $table->timestamp('last_study_at')->nullable()->after('streak_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'preferences',
                'onboarded_at',
                'streak_days',
                'last_study_at',
            ]);
        });
    }
};
