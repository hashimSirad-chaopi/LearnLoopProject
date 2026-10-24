<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A phone that can receive push notifications (Firebase Cloud Messaging token). */
class DeviceToken extends Model
{
    protected $fillable = ['user_id', 'token', 'platform'];
}
