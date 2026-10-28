<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1. "Finish exchange" agreement. Member <-> member: one member asks to finish and the OTHER has
 *    to agree (or say "not yet"). Nobody can complete an exchange alone. Same shape as the
 *    cancel request columns.
 * 2. exchanges.is_professional: a paid exchange with a verified professional. Only the
 *    professional can confirm it is finished; the learner rates after that.
 * 3. report_attachments: proof for a report (up to 3 photos and 1 video, 50 MB each).
 *
 * listings.wants_skill_id is already nullable, so "Teach only" listings need no column change.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('exchanges', 'finish_requested_by')) {
            Schema::table('exchanges', function (Blueprint $table) {
                $table->foreignId('finish_requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('finish_requested_at')->nullable();
                $table->timestamp('finish_declined_at')->nullable();
                $table->text('finish_decline_note')->nullable();
            });
        }

        if (! Schema::hasColumn('exchanges', 'is_professional')) {
            Schema::table('exchanges', function (Blueprint $table) {
                $table->boolean('is_professional')->default(false);
            });
        }

        if (! Schema::hasTable('report_attachments')) {
            Schema::create('report_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained()->cascadeOnDelete();
                $table->string('type'); // image | video
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->unsignedBigInteger('size')->nullable(); // bytes
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('report_attachments');

        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finish_requested_by');
            $table->dropColumn(['finish_requested_at', 'finish_declined_at', 'finish_decline_note', 'is_professional']);
        });
    }
};
