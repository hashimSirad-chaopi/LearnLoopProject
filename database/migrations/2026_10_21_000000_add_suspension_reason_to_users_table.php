<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why an account was suspended: a reason type chosen by the admin (spam,
 * harassment, ... or "other") and an optional / required note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'suspension_reason')) {
                $table->string('suspension_reason', 30)->nullable();
            }
            if (! Schema::hasColumn('users', 'suspension_note')) {
                $table->text('suspension_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['suspension_reason', 'suspension_note'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
