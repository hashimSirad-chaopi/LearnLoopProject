<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['sender_id', 'recipient_id', 'exchange_id', 'message_text', 'attachment_path', 'attachment_type', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        // Voice messages and videos need "Range" requests to play on a phone, which the plain
        // static-file server of `php artisan serve` does not support. This route does.
        if (in_array($this->attachment_type, ['audio', 'video'], true)) {
            return url('/media/chat/'.basename($this->attachment_path));
        }

        return asset('storage/'.$this->attachment_path);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function exchange()
    {
        return $this->belongsTo(Exchange::class);
    }
}
