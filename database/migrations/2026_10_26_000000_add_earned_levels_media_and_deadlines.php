<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Earn the right to teach" (Needs_Improvement.docx).
 *
 *  lessons               + content_type, attachment_*   an admin can add a lesson as text, file, audio, video or photo
 *  exchange_lessons      + content_type, attachment_*   so can a coach, inside one exchange
 *  lesson_deadlines      (new)  a coach puts a deadline on any lesson of an exchange (like a module)
 *  listings              + offers_level                 the level the poster teaches at (never above what they earned)
 *  professional_verifications (new)  proof + hard quiz + admin review for the "Professional" level
 *
 * Every step is guarded, so it is safe to run on any copy of the database.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['lessons', 'exchange_lessons'] as $table) {
            if (! Schema::hasColumn($table, 'content_type')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('content_type')->default('text'); // text | file | audio | video | photo
                    $t->string('attachment_path')->nullable();   // public disk
                    $t->string('attachment_name')->nullable();   // the name the uploader gave it
                });
            }
        }

        if (! Schema::hasColumn('listings', 'offers_level')) {
            Schema::table('listings', function (Blueprint $table) {
                $table->string('offers_level')->nullable(); // easy | moderate | hard | expert | professional
            });
        }

        if (! Schema::hasTable('lesson_deadlines')) {
            Schema::create('lesson_deadlines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exchange_id')->constrained()->cascadeOnDelete();
                // Exactly one of these two is set: a catalog lesson, or a private lesson of this exchange.
                $table->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('exchange_lesson_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('due_at');
                $table->timestamps();

                $table->unique(['exchange_id', 'lesson_id']);
                $table->unique(['exchange_id', 'exchange_lesson_id']);
            });
        }

        if (! Schema::hasTable('professional_verifications')) {
            Schema::create('professional_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
                // quiz      proof sent, hard quiz still to take
                // failed    quiz failed, retry after retry_after
                // in_review quiz passed, an admin is looking at the proof
                // approved  the member is Professional in this skill
                // rejected  an admin turned the proof down (reason given); they may send new proof
                $table->string('status')->default('quiz');
                $table->string('proof_path')->nullable();
                $table->string('proof_name')->nullable();
                $table->text('proof_note')->nullable();
                $table->json('question_ids')->nullable(); // the hard-quiz questions drawn for this attempt, in order
                $table->unsignedInteger('quiz_score')->nullable();
                $table->unsignedInteger('quiz_total')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->timestamp('retry_after')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'skill_id']);
                $table->index('status');
            });
        }

        // From now on a member's teaching level is EARNED, not typed in. Bring every stored level
        // in line with the lessons they have really passed (levels they only claimed are removed).
        try {
            foreach (DB::table('user_skills')->where('type', 'offered')->distinct()->pluck('user_id') as $userId) {
                \App\Support\TeachingLevels::syncOffered((int) $userId);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_verifications');
        Schema::dropIfExists('lesson_deadlines');
        // Column additions are left in place on purpose (SQLite cannot drop columns safely).
    }
};
