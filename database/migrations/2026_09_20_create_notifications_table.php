<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guarded: the table may already physically exist if this
        // migration's 'Ran' record was ever lost (e.g. a database file
        // swap) while the schema itself survived. Skipping avoids a
        // false 'table already exists' failure in that situation.
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
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
                'admin_notice'
            ])->default('admin_notice');
            $table->string('title');
            $table->text('message');
            $table->string('related_id')->nullable(); // ID of related resource (exchange_id, user_id, etc)
            $table->string('related_type')->nullable(); // Type of related resource (exchange, user, etc)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Index for faster queries
            $table->index(['user_id', 'read_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
