<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Cancel exchange" negotiation: one member asks to cancel an ONGOING exchange and
 * gives a reason; the other member accepts (the exchange becomes cancelled) or
 * declines. If they cannot agree, the member files a dispute for the admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('exchanges', 'cancel_requested_by')) {
            Schema::table('exchanges', function (Blueprint $table) {
                $table->foreignId('cancel_requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('cancel_reason')->nullable();
                $table->timestamp('cancel_requested_at')->nullable();
                $table->timestamp('cancel_declined_at')->nullable();
                $table->text('cancel_decline_note')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancel_requested_by');
            $table->dropColumn(['cancel_reason', 'cancel_requested_at', 'cancel_declined_at', 'cancel_decline_note']);
        });
    }
};
