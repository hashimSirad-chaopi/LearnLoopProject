<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separate "from an admin" notifications from "member activity" ones.
 *
 * Until now both were stored with type 'admin_notice', so a coach adding a
 * lesson to your exchange showed up in the app as "FROM ADMIN". The new
 * `source` column says who a notification really comes from:
 *
 *   admin = something an administrator did or sent
 *   user  = something that happened through other members / the app itself
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('notifications', 'source')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('source', 10)->default('user');
                $table->index(['user_id', 'source', 'read_at'], 'notifications_user_source_read_index');
            });
        }

        // Everything an admin sent or triggered.
        DB::table('notifications')->where('type', 'admin_notice')->update(['source' => 'admin']);

        // The two notices a *member* triggers were wrongly saved as admin notices.
        DB::table('notifications')
            ->where('type', 'admin_notice')
            ->where('title', 'New lesson from your coach')
            ->update(['source' => 'user', 'type' => 'lesson_added']);

        DB::table('notifications')
            ->where('type', 'admin_notice')
            ->where('title', 'Lesson confirmed')
            ->update(['source' => 'user', 'type' => 'lesson_confirmed']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'source')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('notifications_user_source_read_index');
                $table->dropColumn('source');
            });
        }
    }
};
