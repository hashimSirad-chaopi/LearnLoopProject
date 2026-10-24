<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A quiz question can carry a photo, video or audio clip that is shown above its text. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('questions', 'media_type')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->string('media_type')->nullable(); // photo | video | audio
                $table->string('media_path')->nullable(); // public disk
            });
        }
    }

    public function down(): void
    {
        // Columns are left in place on purpose (SQLite cannot drop columns safely).
    }
};
