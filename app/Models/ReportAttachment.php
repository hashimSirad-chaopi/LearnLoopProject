<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportAttachment extends Model
{
    public const MAX_IMAGES = 3;
    public const MAX_VIDEOS = 1;
    /** Kilobytes, as Laravel's `max` rule expects: 50 MB per video. */
    public const MAX_VIDEO_KB = 51200;
    /** Kilobytes: 10 MB per photo. */
    public const MAX_IMAGE_KB = 10240;

    protected $fillable = ['report_id', 'type', 'path', 'original_name', 'size'];

    protected $appends = ['url'];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->path);
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }
}
