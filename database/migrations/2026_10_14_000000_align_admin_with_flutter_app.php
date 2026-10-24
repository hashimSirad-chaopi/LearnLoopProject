<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the admin database in line with what the Flutter app (lib/) already
 * does. Every step is guarded, so it is safe to run on any copy of the DB.
 *
 *  skills            + category_id        Flutter Skill.category (Language, Design...)
 *  lessons           + author/status...   member-written lessons + the admin review queue
 *  lesson_ratings    (new)                learner star ratings -> author trust
 *  exchange_lessons  (new)                private lessons a coach writes inside ONE exchange
 *  user_skills       (new)                skills a member teaches / wants + Beginner..Professional level
 *  users             + bio, availability, avatar_path, banner_path
 *  listings          + offers_skill_id, wants_skill_id   (offers is NULL = learner-only listing)
 *  exchanges         skill_offered / skill_wanted become nullable (learner-only exchange)
 *                    + requested_by (who proposed it, so the OTHER member accepts)
 *                    + dispute_reason, disputed_by, disputed_at, resolved_at
 *
 * Meaning of the exchange columns (the same as in the app):
 *   provider_id   = the member who TEACHES `skill_offered`
 *   learner_id    = the member who LEARNS it
 *   skill_wanted  = what the provider wants back, i.e. what the learner teaches.
 *                   NULL means nothing is swapped back (learner-only).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- skills: category ----
        if (! Schema::hasColumn('skills', 'category_id')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            });
        }

        // ---- lessons: authorship + review ----
        if (! Schema::hasColumn('lessons', 'status')) {
            Schema::table('lessons', function (Blueprint $table) {
                // Lessons already in the catalog were written by an admin, so they are published.
                $table->string('status')->default('published'); // pending | published | rejected
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                // Set ONLY when an admin approves by hand. Only these count toward the author's trust.
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
            });
        }

        if (! Schema::hasTable('lesson_ratings')) {
            Schema::create('lesson_ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('stars'); // 1-5
                $table->timestamps();
                $table->unique(['lesson_id', 'user_id']);
            });
        }

        // ---- lessons a coach writes for ONE exchange (no admin approval; admin reads them in a dispute) ----
        if (! Schema::hasTable('exchange_lessons')) {
            Schema::create('exchange_lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exchange_id')->constrained()->cascadeOnDelete();
                $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('content')->nullable();
                // [{"text": "...", "options": ["a","b","c","d"], "correct": 1}, ...]
                $table->json('questions')->nullable();
                $table->timestamps();
            });
        }

        // ---- member skills + proficiency ----
        if (! Schema::hasTable('user_skills')) {
            Schema::create('user_skills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
                $table->string('type'); // offered | wanted
                $table->string('level')->nullable(); // beginner | intermediate | professional (offered only)
                $table->timestamps();
                $table->unique(['user_id', 'skill_id', 'type']);
            });
        }

        // ---- users: profile fields ----
        if (! Schema::hasColumn('users', 'bio')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('bio')->nullable();
                $table->string('availability')->nullable();
                $table->string('avatar_path')->nullable();
                $table->string('banner_path')->nullable();
            });
        }

        // ---- listings: which skills are offered / wanted ----
        if (! Schema::hasColumn('listings', 'wants_skill_id')) {
            Schema::table('listings', function (Blueprint $table) {
                $table->foreignId('offers_skill_id')->nullable()->constrained('skills')->nullOnDelete();
                $table->foreignId('wants_skill_id')->nullable()->constrained('skills')->nullOnDelete();
            });
        }

        // ---- exchanges: learner-only + disputes ----
        if (! Schema::hasColumn('exchanges', 'dispute_reason')) {
            Schema::table('exchanges', function (Blueprint $table) {
                $table->string('skill_offered')->nullable()->change();
                $table->string('skill_wanted')->nullable()->change();
            });

            Schema::table('exchanges', function (Blueprint $table) {
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('dispute_reason')->nullable();
                $table->foreignId('disputed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('disputed_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('exchange_lessons');
        Schema::dropIfExists('lesson_ratings');
        // Column additions are left in place on purpose (SQLite cannot drop
        // foreign-key columns safely). Restore the database backup if you
        // need the old shape back.
    }
};
