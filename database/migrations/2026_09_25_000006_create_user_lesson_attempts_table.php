<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_lesson_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score'); // number of correct answers
            $table->unsignedInteger('total_questions');
            $table->boolean('passed')->default(false); // e.g. score/total >= 70%
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();

            // Keep every attempt (for a history/retry feature), but index for
            // fast "did this user pass this lesson" lookups.
            $table->index(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_lesson_attempts');
    }
};
