<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    public const STATUSES = ['pending', 'ongoing', 'completed', 'disputed', 'cancelled'];

    protected $fillable = [
        'provider_id', 'learner_id', 'requested_by', 'skill_offered', 'skill_wanted', 'status', 'admin_notes',
        'dispute_reason', 'disputed_by', 'disputed_at', 'resolved_at',
        'cancel_requested_by', 'cancel_reason', 'cancel_requested_at', 'cancel_declined_at', 'cancel_decline_note',
    ];

    protected $casts = [
        'disputed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'cancel_requested_at' => 'datetime',
        'cancel_declined_at' => 'datetime',
    ];

    /** A cancel request is waiting for the other member's answer. */
    public function hasOpenCancelRequest(): bool
    {
        return $this->cancel_requested_by !== null && $this->cancel_declined_at === null;
    }

    public function cancelRequestedBy()
    {
        return $this->belongsTo(User::class, 'cancel_requested_by');
    }

    /** One person learns and the other helps, with nothing swapped back. */
    public function isLearnerOnly(): bool
    {
        return empty($this->skill_wanted) || empty($this->skill_offered);
    }

    /** "Python ↔ Guitar", or "Python (learner only)". */
    public function skillPair(): string
    {
        if ($this->skill_offered && $this->skill_wanted) {
            return "{$this->skill_offered} ↔ {$this->skill_wanted}";
        }
        $skill = $this->skill_offered ?: $this->skill_wanted;

        return $skill ? "{$skill} (learner only)" : 'No skill recorded';
    }

    /**
     * The member who has to accept or decline a pending request: the one who
     * did NOT propose it. Older rows without requested_by fall back to the
     * provider, which is how the API worked before learner-only listings.
     */
    public function responderId(): int
    {
        if ($this->requested_by === $this->provider_id) {
            return $this->learner_id;
        }

        return $this->provider_id;
    }

    public function disputedBy()
    {
        return $this->belongsTo(User::class, 'disputed_by');
    }

    public function exchangeLessons()
    {
        return $this->hasMany(ExchangeLesson::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function learner()
    {
        return $this->belongsTo(User::class, 'learner_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
