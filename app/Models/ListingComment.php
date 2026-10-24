<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingComment extends Model
{
    protected $fillable = ['listing_id', 'user_id', 'parent_id', 'body'];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(ListingComment::class, 'parent_id');
    }

    /** Direct replies. Deleting a comment removes all of them (database cascade). */
    public function replies()
    {
        return $this->hasMany(ListingComment::class, 'parent_id');
    }

    /** Every reply under this comment, at any depth. */
    public function allReplies(): \Illuminate\Support\Collection
    {
        $found = collect();
        $parentIds = [$this->id];

        while ($parentIds !== []) {
            $level = static::with('user:id,name')->whereIn('parent_id', $parentIds)->get();
            $found = $found->merge($level);
            $parentIds = $level->pluck('id')->all();
        }

        return $found;
    }
}
