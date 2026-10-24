<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat photos, blocking members, and phone tokens for push notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('messages', 'attachment_path')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('attachment_path')->nullable();
            });
        }

        if (! Schema::hasTable('user_blocks')) {
            Schema::create('user_blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['blocker_id', 'blocked_id']);
            });
        }

        if (! Schema::hasTable('device_tokens')) {
            Schema::create('device_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('token')->unique();
                $table->string('platform', 20)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('user_blocks');

        if (Schema::hasColumn('messages', 'attachment_path')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('attachment_path');
            });
        }
    }
};
