<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which lesson level the poster wants to start learning at
 * (easy | moderate | hard | expert, the same levels as the admin's learning materials).
 * Nullable so listings made before this update keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'wants_level')) {
                $table->string('wants_level')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'wants_level')) {
                $table->dropColumn('wants_level');
            }
        });
    }
};
