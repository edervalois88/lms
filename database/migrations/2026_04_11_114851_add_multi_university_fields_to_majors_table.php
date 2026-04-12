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
        if (!Schema::hasTable('majors')) {
            return;
        }

        Schema::table('majors', function (Blueprint $table) {
            if (!Schema::hasColumn('majors', 'campus_id')) {
                $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('majors', 'division_name')) {
                $table->string('division_name')->nullable();
            }

            if (!Schema::hasColumn('majors', 'holland_code')) {
                $table->string('holland_code', 10)->nullable();
            }

            if (!Schema::hasColumn('majors', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('majors', 'extra_requirements')) {
                $table->json('extra_requirements')->nullable();
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Esta migración es aditiva para proteger datos existentes.
    }
};
