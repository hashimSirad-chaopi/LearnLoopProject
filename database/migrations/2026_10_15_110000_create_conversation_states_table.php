<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-member view of a chat: archived, or cleared ("deleted for me").
 * Nothing is removed from `messages`, so the other member keeps their copy
 * and admins can still see messages if there is a report.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conversation_states')) {
            return;
        }

        Schema::create('conversation_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('other_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'other_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_states');
    }
};
