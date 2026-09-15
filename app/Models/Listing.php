<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $fillable = ['user_id', 'title', 'category', 'description', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}