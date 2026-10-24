<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    /** Same three values as the Flutter LessonReviewStatus enum. */
    public const STATUSES = ['pending', 'published', 'rejected'];

    protected $fillable = [
        'skill_level_id', 'title', 'content', 'order',
        'status', 'author_id', 'rejection_reason', 'approved_by', 'reviewed_at',
        'content_type', 'attachment_path', 'attachment_name',
    ];

    /** How a lesson is delivered. 'text' is the written explanation; the others carry an uploaded file. */
    public const CONTENT_TYPES = ['text', 'file', 'audio', 'video', 'photo'];

    protected $casts = ['reviewed_at' => 'datetime'];

    /** The member who wrote it. NULL = written by an admin in the catalog. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(LessonRating::class);
    }

    /** Public URL of the uploaded file/audio/video/photo, or null for a text lesson. */
    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? asset('storage/'.$this->attachment_path) : null;
    }

    public function isMemberWritten(): bool
    {
        return $this->author_id !== null;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function skillLevel(): BelongsTo
    {
        return $this->belongsTo(SkillLevel::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UserLessonAttempt::class);
    }
}
