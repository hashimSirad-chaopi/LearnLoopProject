<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBlock extends Model
{
    protected $fillable = ['blocker_id', 'blocked_id'];

    /** The member who was blocked. */
    public function blockedUser()
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }

    /** Has [$a] blocked [$b]? */
    public static function blocked(int $a, int $b): bool
    {
        return static::where('blocker_id', $a)->where('blocked_id', $b)->exists();
    }

    /** Is there a block in EITHER direction between the two members? */
    public static function between(int $a, int $b): bool
    {
        return static::blocked($a, $b) || static::blocked($b, $a);
    }
}
