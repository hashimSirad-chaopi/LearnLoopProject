<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// LearnLoop pivoted from a peer-to-peer skill-exchange app to a
// Duolingo-style structured course app. These tables belonged to the old
// model and are no longer used anywhere in the codebase.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('exchanges');
        Schema::dropIfExists('listings');
        Schema::dropIfExists('skills'); // old version: just a listing tag, not a course
        Schema::dropIfExists('skill_categories');
    }

    public function down(): void
    {
        // Intentionally not reversible — the old schema is gone for good.
        // If you ever need it back, restore it from git history instead.
    }
};
