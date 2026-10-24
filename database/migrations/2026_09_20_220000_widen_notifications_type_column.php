<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This migration has failed partway through more than once on
     * SQLite (see the class-level history in this project's chat log),
     * leaving one of several possible intermediate states behind:
     *
     *   - 'notifications'        still the original table, untouched
     *   - '__temp__notifications' the original data, renamed aside by
     *                             a previous failed attempt
     *   - 'notifications_new'    a half-finished replacement table
     *                             from a previous failed attempt,
     *                             which may or may not already hold
     *                             the copied data
     *
     * Every step below checks what actually exists before acting, so
     * this is safe to re-run from any of those states without losing
     * data or double-creating a table.
     */
    public function up(): void
    {
        // If a previous attempt already got as far as building
        // notifications_new, decide whether to trust it or discard it.
        if (Schema::hasTable('notifications_new')) {
            if (Schema::hasTable('notifications') || Schema::hasTable('__temp__notifications')) {
                // The real data lives elsewhere, so this is a stale,
                // incomplete leftover — safe to drop and rebuild.
                Schema::drop('notifications_new');
            } else {
                // Nothing else exists, so this must already be the
                // finished table from an attempt that got interrupted
                // right before the final rename. Just finish that step.
                Schema::rename('notifications_new', 'notifications');

                return;
            }
        }

        $source = match (true) {
            Schema::hasTable('notifications') => 'notifications',
            Schema::hasTable('__temp__notifications') => '__temp__notifications',
            default => null,
        };

        Schema::create('notifications_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type')->default('admin_notice');
            $table->string('title');
            $table->text('message');
            $table->string('related_id')->nullable();
            $table->string('related_type')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['created_at']);
        });

        if ($source !== null) {
            DB::statement("
                INSERT INTO notifications_new
                    (id, user_id, type, title, message, related_id, related_type, read_at, created_at, updated_at)
                SELECT
                    id, user_id, type, title, message, related_id, related_type, read_at, created_at, updated_at
                FROM {$source}
            ");

            Schema::drop($source);
        }

        if (Schema::hasTable('notifications')) {
            Schema::drop('notifications');
        }

        Schema::rename('notifications_new', 'notifications');
    }

    /**
     * Reverse the migration by rebuilding the original enum-based table.
     * Note: if any rows were created with a type outside the original
     * nine values (e.g. 'listing_approved'), this copy will fail —
     * that's an expected trade-off of rolling back a widened column.
     */
    public function down(): void
    {
        Schema::create('notifications_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', [
                'new_match',
                'proposal_received',
                'proposal_accepted',
                'proposal_declined',
                'new_message',
                'schedule_reminder',
                'exchange_completed',
                'rating_received',
                'admin_notice',
            ])->default('admin_notice');
            $table->string('title');
            $table->text('message');
            $table->string('related_id')->nullable();
            $table->string('related_type')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['created_at']);
        });

        DB::statement('
            INSERT INTO notifications_old
                (id, user_id, type, title, message, related_id, related_type, read_at, created_at, updated_at)
            SELECT
                id, user_id, type, title, message, related_id, related_type, read_at, created_at, updated_at
            FROM notifications
        ');

        Schema::drop('notifications');
        Schema::rename('notifications_old', 'notifications');
    }
};
