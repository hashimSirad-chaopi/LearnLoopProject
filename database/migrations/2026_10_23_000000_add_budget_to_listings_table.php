<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Professional (paid) listings: what the learner is willing to pay per lesson,
 * in pesos. Nullable = "negotiable". Only used when wants_level = 'professional'.
 * The app does not process payments; members agree on it themselves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'budget')) {
                $table->unsignedInteger('budget')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'budget')) {
                $table->dropColumn('budget');
            }
        });
    }
};
