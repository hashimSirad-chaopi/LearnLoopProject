<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class Listing extends Model
{
    protected $fillable = [
    'user_id',
    'title',
    'category_id',
    'description',
    'status',
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
    return $this->belongsTo(Category::class);
    }
}