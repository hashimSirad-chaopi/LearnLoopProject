<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonRating extends Model
{
    protected $fillable = ['lesson_id', 'user_id', 'stars'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
