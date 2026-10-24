<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id', 'question_text', 'order', 'media_type', 'media_path'];

    /** media_type + media_url, ready for an API payload. */
    public function mediaPayload(): array
    {
        return \App\Support\QuestionMedia::payload($this->media_type, $this->media_path);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }
}
