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
        Schema::create('major_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('cutoff_score')->nullable();
            $table->integer('applicants')->nullable();
            $table->integer('places_offered')->nullable();
            $table->timestamps();
            
            $table->unique(['major_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('major_statistics');
    }
};
