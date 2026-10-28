<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Experience skills (dancing, singing, ...): no lessons, no levels. The only level is
 * Professional, and the only way to get it is a certification an admin approves.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('skills', 'experience_based')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->boolean('experience_based')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('skills', 'experience_based')) {
            Schema::table('skills', function (Blueprint $table) {
                $table->dropColumn('experience_based');
            });
        }
    }
};
