<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Member skill levels now use the same names as a listing's level:
 * easy | moderate | hard | expert | professional (was beginner | intermediate |
 * professional). Existing rows are renamed; "professional" stays as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_skills')->where('level', 'beginner')->update(['level' => 'easy']);
        DB::table('user_skills')->where('level', 'intermediate')->update(['level' => 'moderate']);
    }

    public function down(): void
    {
        DB::table('user_skills')->where('level', 'easy')->update(['level' => 'beginner']);
        DB::table('user_skills')->where('level', 'moderate')->update(['level' => 'intermediate']);
        // hard / expert have no old equivalent.
        DB::table('user_skills')->whereIn('level', ['hard', 'expert'])->update(['level' => 'professional']);
    }
};
