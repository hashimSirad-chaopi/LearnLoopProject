<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Rating extends Model
{
    protected $fillable = ['exchange_id', 'rater_id', 'rated_user_id', 'rating', 'comment'];

    /**
     * Ratings that follow the rule: in a two-way swap both members rate each other; in a
     * learner-only exchange (nothing taught back) only the learner rates.
     */
    public function scopeValid($query)
    {
        return $query->whereHas('exchange', function ($exchange) {
            $exchange->where(function ($w) {
                $w->whereColumn('ratings.rater_id', 'exchanges.learner_id')
                    ->orWhere(function ($twoWay) {
                        $twoWay->whereNotNull('exchanges.skill_offered')->where('exchanges.skill_offered', '!=', '')
                            ->whereNotNull('exchanges.skill_wanted')->where('exchanges.skill_wanted', '!=', '');
                    });
            });
        });
    }

    protected static function booted(): void
    {
        // The database removes the attachment rows with the rating; this
        // removes their files from disk too.
        static::deleting(function (Rating $rating) {
            foreach ($rating->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->path);
            }
        });
    }

    /** Photos / videos the rater attached as proof. */
    public function attachments()
    {
        return $this->hasMany(RatingAttachment::class);
    }

    public function exchange()
    {
        return $this->belongsTo(Exchange::class);
    }

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratedUser()
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }
}
