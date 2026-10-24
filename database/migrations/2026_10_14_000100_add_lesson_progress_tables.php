<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Learning progress inside an exchange (the app's LessonProgress):
 *
 *  user_lesson_attempts        (already exists) every catalog quiz attempt; passed = score >= 70%
 *  exchange_lesson_attempts    (new) quiz attempts on a private lesson a coach wrote for ONE exchange
 *  lesson_confirmations        (new) "the coach has seen the learner do it for real", per exchange.
 *                              Exactly one of lesson_id / exchange_lesson_id is set.
 *
 * A lesson is done for a learner when the quiz is passed AND the coach confirmed it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exchange_lesson_attempts')) {
            Schema::create('exchange_lesson_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exchange_lesson_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('score');
                $table->unsignedInteger('total_questions');
                $table->boolean('passed')->default(false);
                $table->timestamps();
                $table->index(['exchange_lesson_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('lesson_confirmations')) {
            Schema::create('lesson_confirmations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exchange_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('exchange_lesson_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('learner_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['exchange_id', 'lesson_id', 'learner_id']);
                $table->unique(['exchange_id', 'exchange_lesson_id', 'learner_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_confirmations');
        Schema::dropIfExists('exchange_lesson_attempts');
    }
};
