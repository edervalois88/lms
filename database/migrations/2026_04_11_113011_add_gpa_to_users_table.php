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
            $table->decimal('gpa', 4, 2)->nullable()->after('email'); // Promedio de bachillerato (ej: 8.50)
            $table->string('target_university')->nullable()->after('gpa'); // Atajo para vistas rápidas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gpa', 'target_university']);
        });
    }
};
