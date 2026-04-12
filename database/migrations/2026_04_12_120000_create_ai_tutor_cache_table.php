<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tutor_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->index();
            $table->string('respuesta_incorrecta')->index();
            $table->text('explicacion_ia');
            $table->timestamps();

            $table->unique(['question_id', 'respuesta_incorrecta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tutor_cache');
    }
};
