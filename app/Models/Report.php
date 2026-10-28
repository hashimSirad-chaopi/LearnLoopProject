<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'title', 'reported_by', 'reported_user_id', 'listing_id', 'reason', 'status',
        'action_taken', 'admin_note', 'resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    public function reporter() { return $this->belongsTo(User::class, 'reported_by'); }
    public function reportedUser() { return $this->belongsTo(User::class, 'reported_user_id'); }
    public function listing() { return $this->belongsTo(Listing::class); }
    public function attachments() { return $this->hasMany(ReportAttachment::class); }

    /** Reports against this member that an admin upheld (resolved, not dismissed). */
    public static function upheldAgainst(int $userId): int
    {
        return static::where('reported_user_id', $userId)->where('status', 'resolved')->count();
    }

    public static function warningsAgainst(int $userId): int
    {
        return static::where('reported_user_id', $userId)->where('action_taken', 'warn')->count();
    }
}
