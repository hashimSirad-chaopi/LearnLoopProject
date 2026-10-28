<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily quiz attempt limit for the admin's catalog lessons (Continue Learning).
 *
 *  app_settings                    small key/value table the admin edits in Settings > Quizzes
 *  user_lesson_attempts.exchange_id set when the attempt was made inside an exchange. Those
 *                                  attempts are never limited and never use up the daily allowance.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('user_lesson_attempts', 'exchange_id')) {
            Schema::table('user_lesson_attempts', function (Blueprint $table) {
                $table->unsignedBigInteger('exchange_id')->nullable()->after('lesson_id');
                $table->index(['user_id', 'lesson_id', 'attempted_at'], 'ula_user_lesson_when');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_lesson_attempts', 'exchange_id')) {
            Schema::table('user_lesson_attempts', function (Blueprint $table) {
                $table->dropIndex('ula_user_lesson_when');
                $table->dropColumn('exchange_id');
            });
        }

        Schema::dropIfExists('app_settings');
    }
};
