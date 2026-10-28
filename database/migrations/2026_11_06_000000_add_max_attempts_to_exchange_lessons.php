<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The coach who writes a lesson inside an exchange can limit how many times the learner may take its
 * quiz. NULL = no limit. The learner's tries are already counted in exchange_lesson_attempts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('exchange_lessons', 'max_attempts')) {
            Schema::table('exchange_lessons', function (Blueprint $table) {
                $table->unsignedSmallInteger('max_attempts')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exchange_lessons', 'max_attempts')) {
            Schema::table('exchange_lessons', function (Blueprint $table) {
                $table->dropColumn('max_attempts');
            });
        }
    }
};
