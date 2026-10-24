<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeLessonAttempt extends Model
{
    protected $fillable = ['exchange_lesson_id', 'user_id', 'score', 'total_questions', 'passed'];

    protected $casts = ['passed' => 'boolean'];

    public function exchangeLesson()
    {
        return $this->belongsTo(ExchangeLesson::class);
    }
}
