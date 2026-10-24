<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presence: when a member closed the app or logged out. It lets the server say
 * "Offline right now" while still remembering the real last-seen time, so the
 * other side shows "Active 1 second ago" instead of just "Offline".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'offline_since')) {
                $table->timestamp('offline_since')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'offline_since')) {
                $table->dropColumn('offline_since');
            }
        });
    }
};
