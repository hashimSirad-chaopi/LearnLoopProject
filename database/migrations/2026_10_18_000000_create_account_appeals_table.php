<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Recover my account": a suspended member cannot log in, so they send the
 * admins a short request from the login screen. An administrator answers it
 * (reactivate, or decline with a reason).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('account_appeals')) {
            return;
        }

        Schema::create('account_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('status', 20)->default('pending');   // pending | approved | declined
            $table->text('admin_note')->nullable();             // shown to the member
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_appeals');
    }
};
