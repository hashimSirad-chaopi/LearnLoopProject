<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Listings go live straight away; an admin only monitors them. When an admin takes one
 * down, the listing stays in the database as status "inactive" with these columns filled
 * in (that is how "disabled by an admin" differs from "closed by the member").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'moderation_reason')) {
                $table->string('moderation_reason', 32)->nullable();
            }
            if (! Schema::hasColumn('listings', 'moderation_note')) {
                $table->text('moderation_note')->nullable();
            }
            if (! Schema::hasColumn('listings', 'moderated_at')) {
                $table->timestamp('moderated_at')->nullable();
            }
        });

        // Nothing waits for approval any more.
        DB::table('listings')->where('status', 'pending')->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['moderation_reason', 'moderation_note', 'moderated_at']);
        });
    }
};
