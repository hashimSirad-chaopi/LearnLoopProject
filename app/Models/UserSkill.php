<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSkill extends Model
{
    /** Same names as a listing's level, plus Professional. */
    public const LEVELS = ['easy', 'moderate', 'hard', 'expert', 'professional'];

    /** Names used before the rename, still accepted from older app builds. */
    public const LEGACY = ['beginner' => 'easy', 'intermediate' => 'moderate'];

    protected $fillable = ['user_id', 'skill_id', 'type', 'level'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
