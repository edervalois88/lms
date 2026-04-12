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
        Schema::create('reward_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category', 40);
            $table->string('slot', 40);
            $table->string('rarity', 30)->default('common');
            $table->unsignedInteger('cost_xp')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('limited_from')->nullable();
            $table->timestamp('limited_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['category', 'slot']);
            $table->index(['is_active', 'limited_from', 'limited_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_items');
    }
};
