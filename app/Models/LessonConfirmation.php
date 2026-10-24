<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A coach confirming that a learner can really do what one lesson teaches. */
class LessonConfirmation extends Model
{
    protected $fillable = ['exchange_id', 'lesson_id', 'exchange_lesson_id', 'learner_id', 'coach_id'];

    public function exchange()
    {
        return $this->belongsTo(Exchange::class);
    }
}
