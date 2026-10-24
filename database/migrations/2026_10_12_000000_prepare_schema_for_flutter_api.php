<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the schema in line with what the Flutter API (routes/api.php) needs:
 *
 *  1. exchanges: the old enum/CHECK allowed only pending|ongoing|completed|disputed,
 *     so "decline" (cancelled) failed. Rebuilt with a plain string status plus
 *     admin_notes. Existing rows are copied across.
 *  2. schedules, ratings, messages: previously only existed in
 *     database.sqlite.backup, never in a migration.
 *
 * Every step is guarded, so it is safe to run on any copy of your database.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- 1. rebuild exchanges without the status CHECK constraint ----
        if (Schema::hasTable('exchanges') && ! Schema::hasColumn('exchanges', 'admin_notes')) {
            Schema::create('exchanges_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('learner_id')->constrained('users')->onDelete('cascade');
                $table->string('skill_offered');
                $table->string('skill_wanted');
                $table->string('status')->default('pending');
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });

            DB::statement(
                'INSERT INTO exchanges_new (id, provider_id, learner_id, skill_offered, skill_wanted, status, created_at, updated_at)
                 SELECT id, provider_id, learner_id, skill_offered, skill_wanted, status, created_at, updated_at FROM exchanges'
            );

            Schema::drop('exchanges');
            Schema::rename('exchanges_new', 'exchanges');
        }

        // ---- 2. API tables ----
        if (! Schema::hasTable('schedules')) {
            Schema::create('schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exchange_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->time('time_start');
                $table->time('time_end');
                $table->string('location')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('pending'); // pending|confirmed|completed|cancelled
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ratings')) {
            Schema::create('ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exchange_id')->constrained()->onDelete('cascade');
                $table->foreignId('rater_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('rated_user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->unique(['exchange_id', 'rater_id']);
            });
        }

        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('exchange_id')->nullable()->constrained()->onDelete('cascade');
                $table->text('message_text');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('schedules');
        // exchanges is intentionally left in its rebuilt shape.
    }
};
