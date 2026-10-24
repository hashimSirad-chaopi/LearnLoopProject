<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the admin decided on a report: no action, a warning, or a suspension,
 * plus a short note (shown to the member in their warning).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'action_taken')) {
                $table->string('action_taken', 20)->nullable();   // none | warn | suspend
            }
            if (! Schema::hasColumn('reports', 'admin_note')) {
                $table->text('admin_note')->nullable();
            }
            if (! Schema::hasColumn('reports', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['action_taken', 'admin_note', 'resolved_at']);
        });
    }
};
