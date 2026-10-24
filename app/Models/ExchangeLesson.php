<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A lesson a coach wrote for ONE exchange. Private to the two partners and
 * never reviewed; the admin can read it when an exchange is disputed.
 */
class ExchangeLesson extends Model
{
    protected $fillable = ['exchange_id', 'author_id', 'title', 'content', 'questions', 'content_type', 'attachment_path', 'attachment_name'];

    protected $casts = ['questions' => 'array'];

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? asset('storage/'.$this->attachment_path) : null;
    }

    public function exchange()
    {
        return $this->belongsTo(Exchange::class);
    }

    public function attempts()
    {
        return $this->hasMany(ExchangeLessonAttempt::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
