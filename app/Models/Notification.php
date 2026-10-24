<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /** Sent or triggered by an administrator. */
    public const SOURCE_ADMIN = 'admin';

    /** Came from other members or the app itself (coach lessons, requests, ...). */
    public const SOURCE_USER = 'user';

    protected $fillable = [
        'user_id',
        'type',
        'source',
        'title',
        'message',
        'related_id',
        'related_type',
        'read_at',
    ];

    /** Every notification we create also goes to the member's phone as a push. */
    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            \App\Support\PushNotifier::toUser(
                (int) $notification->user_id,
                $notification->source === self::SOURCE_ADMIN ? 'LearnLoop admin: '.$notification->title : $notification->title,
                $notification->message,
                ['kind' => 'notification', 'source' => (string) $notification->source, 'notification_id' => (string) $notification->id]
            );
        });
    }

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Tell a member about something an ADMINISTRATOR did or sent. The Flutter
     * app lists these under its "Admin" tab. Only call this from admin code.
     */
    public static function notifyMember(int $userId, string $title, string $message, ?string $relatedType = null, $relatedId = null): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'admin_notice',
            'source' => self::SOURCE_ADMIN,
            'title' => $title,
            'message' => $message,
            'related_type' => $relatedType,
            'related_id' => $relatedId === null ? null : (string) $relatedId,
        ]);
    }

    /**
     * Tell a member about something ANOTHER MEMBER did (a coach added a lesson,
     * a lesson was confirmed, ...). The Flutter app lists these under its
     * "Activity" tab, never under "Admin".
     */
    public static function notifyFromMember(int $userId, string $type, string $title, string $message, ?string $relatedType = null, $relatedId = null): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'source' => self::SOURCE_USER,
            'title' => $title,
            'message' => $message,
            'related_type' => $relatedType,
            'related_id' => $relatedId === null ? null : (string) $relatedId,
        ]);
    }

    public function scopeFromAdmin($query)
    {
        return $query->where('source', self::SOURCE_ADMIN);
    }

    public function scopeFromMembers($query)
    {
        return $query->where('source', self::SOURCE_USER);
    }

    /**
     * Get the user this notification belongs to
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if notification is unread
     */
    public function isUnread()
    {
        return is_null($this->read_at);
    }

    /**
     * Get all unread notifications for a user
     */
    public static function unreadForUser($userId)
    {
        return self::where('user_id', $userId)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }

    /**
     * Get count of unread notifications for a user
     */
    public static function unreadCountForUser($userId)
    {
        return self::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
