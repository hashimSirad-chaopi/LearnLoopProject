<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    protected $fillable = ['provider_id', 'learner_id', 'skill_offered', 'skill_wanted', 'status'];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function learner()
    {
        return $this->belongsTo(User::class, 'learner_id');
    }
}