<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A suspended member's request to have their account reactivated. */
class AccountAppeal extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const DECLINED = 'declined';

    /** After a decline the member must wait this long before asking again. */
    public const RETRY_AFTER_HOURS = 24;

    protected $fillable = ['user_id', 'message', 'status', 'admin_note', 'reviewed_by', 'reviewed_at'];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The member was reactivated some other way (Users page, bulk action):
     * close any request still waiting so it does not sit in the queue.
     */
    public static function closeOpenFor(int $userId, string $note = 'An administrator reactivated the account.'): void
    {
        static::where('user_id', $userId)->where('status', self::PENDING)->update([
            'status' => self::APPROVED,
            'admin_note' => $note,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }
}
