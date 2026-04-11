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
        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->nullable();
            
            // Refleja la división académica (ej: Area 1, CBI, IyCFM)
            $table->string('division_name'); 
            
            // Datos del último año para vista rápida
            $table->integer('min_score');
            $table->integer('applicants')->nullable();
            $table->integer('places')->nullable();
            
            // Código Holland para el Test Vocacional (ej: RIA, SAE)
            $table->string('holland_code', 10)->nullable();
            
            $table->text('description')->nullable();
            $table->json('extra_requirements')->nullable(); // Para carreras con pre-requisitos
            $table->timestamps();
            
            $table->index(['name', 'division_name']);
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
