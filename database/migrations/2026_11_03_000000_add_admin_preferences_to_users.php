<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Per-admin panel preferences (Settings > Notifications), stored as JSON on the user. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'admin_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('admin_preferences')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'admin_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('admin_preferences');
            });
        }
    }
};
