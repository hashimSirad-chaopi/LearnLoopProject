<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['exchange_id', 'date', 'time_start', 'time_end', 'location', 'notes', 'status'];

    public function exchange()
    {
        return $this->belongsTo(Exchange::class);
    }
}
