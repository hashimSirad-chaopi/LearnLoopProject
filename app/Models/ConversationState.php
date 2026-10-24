<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationState extends Model
{
    protected $fillable = ['user_id', 'other_user_id', 'archived_at', 'cleared_at'];

    protected $casts = ['archived_at' => 'datetime', 'cleared_at' => 'datetime'];

    public static function forPair(int $userId, int $otherId): self
    {
        return static::firstOrCreate(['user_id' => $userId, 'other_user_id' => $otherId]);
    }
}
