<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite does not index foreign keys by itself, so the admin pages (users, listings,
 * reports, exchanges, lessons, activity log, dashboard) scanned whole tables.
 * These indexes cover the filters and joins those pages use.
 *
 * Safe to run on a database that already has some of them: an index is only
 * created when the table has the columns and no index with that name exists.
 */
return new class extends Migration
{
    /** table => [index name => columns] */
    private function indexes(): array
    {
        return [
            'listings' => [
                'listings_user_id_idx' => ['user_id'],
                'listings_status_created_idx' => ['status', 'created_at'],
                'listings_wants_skill_idx' => ['wants_skill_id'],
                'listings_offers_skill_idx' => ['offers_skill_id'],
            ],
            'exchanges' => [
                'exchanges_provider_idx' => ['provider_id'],
                'exchanges_learner_idx' => ['learner_id'],
                'exchanges_status_idx' => ['status'],
            ],
            'messages' => [
                'messages_pair_idx' => ['sender_id', 'recipient_id'],
                'messages_recipient_read_idx' => ['recipient_id', 'read_at'],
                'messages_exchange_idx' => ['exchange_id'],
            ],
            'reports' => [
                'reports_status_idx' => ['status'],
                'reports_reported_user_idx' => ['reported_user_id', 'status'],
            ],
            'lessons' => [
                'lessons_level_status_idx' => ['skill_level_id', 'status'],
                'lessons_author_status_idx' => ['author_id', 'status'],
            ],
            'skill_levels' => [
                'skill_levels_skill_idx' => ['skill_id'],
            ],
            'questions' => [
                'questions_lesson_idx' => ['lesson_id'],
            ],
            'question_options' => [
                'question_options_question_idx' => ['question_id'],
            ],
            'user_skills' => [
                'user_skills_skill_type_idx' => ['skill_id', 'type'],
            ],
            'ratings' => [
                'ratings_rated_user_idx' => ['rated_user_id'],
                'ratings_exchange_idx' => ['exchange_id'],
            ],
            'activity_logs' => [
                'activity_logs_created_idx' => ['created_at'],
                'activity_logs_action_idx' => ['action'],
            ],
            'professional_verifications' => [
                'professional_verifications_status_idx' => ['status'],
            ],
            'listing_comments' => [
                'listing_comments_listing_created_idx' => ['listing_id', 'created_at'],
                'listing_comments_parent_idx' => ['parent_id'],
                'listing_comments_user_idx' => ['user_id'],
            ],
            'listing_likes' => [
                'listing_likes_listing_idx' => ['listing_id'],
            ],
            'account_appeals' => [
                'account_appeals_status_idx' => ['status'],
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->indexes() as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (! Schema::hasColumns($table, $columns) || Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, fn ($t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn ($t) => $t->dropIndex($name));
                }
            }
        }
    }
};
