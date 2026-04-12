<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freemium_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 50);
            $table->date('usage_date');
            $table->unsignedInteger('hits')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'feature', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freemium_usages');
    }
};
