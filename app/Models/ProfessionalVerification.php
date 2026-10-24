<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A member's request to be "Professional" in one skill: proof (certificate, portfolio, ID badge...),
 * a hard quiz, then an admin's decision. See App\Support\TeachingLevels.
 */
class ProfessionalVerification extends Model
{
    public const STATUSES = ['quiz', 'failed', 'in_review', 'approved', 'rejected'];

    /** Share of the hard quiz that must be right (higher than a lesson quiz). */
    public const PASS_RATIO = 0.8;
    public const QUIZ_SIZE = 10;
    public const MIN_QUESTIONS = 5;
    public const RETRY_HOURS = 24;

    protected $fillable = [
        'user_id', 'skill_id', 'status', 'proof_path', 'proof_name', 'proof_note', 'question_ids',
        'quiz_score', 'quiz_total', 'attempts', 'retry_after', 'reviewed_by', 'reviewed_at', 'rejection_reason',
    ];

    protected $casts = [
        'question_ids' => 'array',
        'retry_after' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function passMark(int $total): int
    {
        return (int) ceil($total * self::PASS_RATIO);
    }

    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_path ? asset('storage/'.$this->proof_path) : null;
    }

    public function proofIsImage(): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|webp)$/i', (string) $this->proof_path);
    }
}
