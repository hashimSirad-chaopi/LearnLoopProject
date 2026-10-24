<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** "Finish this lesson by ..." — set by the coach for one lesson of one exchange (like a module deadline). */
class LessonDeadline extends Model
{
    protected $fillable = ['exchange_id', 'lesson_id', 'exchange_lesson_id', 'set_by', 'due_at'];

    protected $casts = ['due_at' => 'datetime'];

    public function exchange()
    {
        return $this->belongsTo(Exchange::class);
    }
}
