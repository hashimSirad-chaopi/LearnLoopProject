<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $fillable = [
<<<<<<< HEAD
        'user_id',
        'title',
        'category_id',
        'subcategory_id',
        'level',
        'description',
        'status',
    ];
=======
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
>>>>>>> 7128c7a35fdb01d62338eda557a779da41476150

    /**
     * The category is never picked separately in the app: it is the category
     * of the skill being taught, or of the skill wanted for a learner-only
     * listing. Keep it in step whenever a listing is saved with skills.
     */
    protected static function booted(): void
    {
        static::saving(function (Listing $listing) {
            $skillId = $listing->offers_skill_id ?? $listing->wants_skill_id;
            if ($skillId && ($categoryId = Skill::whereKey($skillId)->value('category_id'))) {
                $listing->category_id = $categoryId;
            }
        });
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

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }
}