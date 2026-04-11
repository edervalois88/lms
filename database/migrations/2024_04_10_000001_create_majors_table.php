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
        Schema::create('majors', function (Blueprint $user_rule) {
            $user_rule->id();
            $user_rule->string('name');
            $user_rule->integer('area_id');
            $user_rule->string('school_name');
            $user_rule->integer('min_score');
            $user_rule->integer('min_score_prev')->nullable();
            $user_rule->string('demand_level')->nullable();
            $user_rule->text('description')->nullable();
            $user_rule->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('majors');
    }
};
