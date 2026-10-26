<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class Listing extends Model
{
    protected $fillable = [
    'user_id',
    'title',
    'category_id',
    'description',
    'status',
    'offers_skill_id',
    'wants_skill_id',
    'wants_level',
    'offers_level',
    'budget',
];

    /** Shown to the owner in the app: was it taken down by an admin, and why. */
    protected $appends = ['disabled_by_admin', 'disabled_reason'];

    protected function casts(): array
    {
        return ['moderated_at' => 'datetime'];
    }

    /**
     * The category is never picked separately in the app: it is the category
     * of the skill being taught, or of the skill wanted for a learner-only
     * listing. Keep it in step whenever a listing is saved with skills.
     */
    protected static function booted(): void
    {
        static::saving(function (Listing $listing) {
            // "Disabled by an admin" only exists while the listing is inactive.
            if ($listing->status !== 'inactive') {
                $listing->moderation_reason = null;
                $listing->moderation_note = null;
                $listing->moderated_at = null;
            }

            $skillId = $listing->offers_skill_id ?? $listing->wants_skill_id;
            if ($skillId && ($categoryId = Skill::whereKey($skillId)->value('category_id'))) {
                $listing->category_id = $categoryId;
            }
        });
    }

    /** Taken down by an admin (as opposed to closed by its owner). */
    public function isDisabledByAdmin(): bool
    {
        return $this->status === 'inactive' && $this->moderated_at !== null;
    }

    public function getDisabledByAdminAttribute(): bool
    {
        return $this->isDisabledByAdmin();
    }

    public function getDisabledReasonAttribute(): ?string
    {
        return $this->isDisabledByAdmin()
            ? \App\Support\ListingModeration::describe($this->moderation_reason, $this->moderation_note)
            : null;
    }

    public function offersSkill()
    {
        return $this->belongsTo(Skill::class, 'offers_skill_id');
    }

    public function wantsSkill()
    {
        return $this->belongsTo(Skill::class, 'wants_skill_id');
    }

    /** "I want to learn X" with nothing offered in return. */
    public function isLearnerOnly(): bool
    {
        return $this->offers_skill_id === null && $this->wants_skill_id !== null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(ListingComment::class);
    }

    public function likes()
    {
        return $this->hasMany(ListingLike::class);
    }

    public function category()
    {
    return $this->belongsTo(Category::class);
    }
}