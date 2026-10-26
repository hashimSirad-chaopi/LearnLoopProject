<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'bio', 'availability', 'avatar_path', 'banner_path'])]
#[Hidden(['password', 'remember_token', 'last_seen_at', 'offline_since', 'suspended_until', 'suspension_reason', 'suspension_note', 'admin_preferences'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_until' => 'datetime',
            'last_seen_at' => 'datetime',
            'offline_since' => 'datetime',
            'admin_preferences' => 'array',
            'password' => 'hashed',
        ];
    }

    /** One of this admin's panel preferences (Settings > Notifications), or $default when never set. */
    public function adminPref(string $key, mixed $default = null): mixed
    {
        $prefs = $this->getAttribute('admin_preferences');

        return is_array($prefs) && array_key_exists($key, $prefs) ? $prefs[$key] : $default;
    }

    /** Added to every API/JSON user so the app can draw the pictures (Flutter avatarUrl / bannerUrl). */
    protected $appends = ['avatar_url', 'banner_url', 'presence'];

    /** Seen within this many seconds = "Active" (the app checks in about every 20 seconds). */
    public const ONLINE_WINDOW_SECONDS = 75;

    /**
     * The three states the app and the admin panel show:
     *   suspended = an admin suspended the account
     *   active    = signed in and using the app / panel right now
     *   offline   = not around (closed the app, logged out, or never signed in)
     * The exact last-seen time is hidden from the API; only this word is sent.
     */
    public function getPresenceAttribute(): string
    {
        if ($this->status === 'suspended') {
            return 'suspended';
        }

        return $this->isOnline() ? 'active' : 'offline';
    }

    public function isOnline(): bool
    {
        if ($this->last_seen_at === null
            || ! $this->last_seen_at->gt(now()->subSeconds(self::ONLINE_WINDOW_SECONDS))) {
            return false;
        }

        // Closed the app / logged out: offline now, even though the last-seen
        // time is only seconds old. Any later request clears this again.
        return $this->offline_since === null || $this->last_seen_at->gt($this->offline_since);
    }

    /** Not suspended and seen recently. */
    public function scopeOnline($query)
    {
        return $query->where('status', 'active')
            ->where('last_seen_at', '>', now()->subSeconds(self::ONLINE_WINDOW_SECONDS))
            ->whereNull('offline_since');
    }

    /** Not suspended, but not around right now. */
    public function scopeOffline($query)
    {
        return $query->where('status', 'active')->where(function ($q) {
            $q->whereNull('last_seen_at')
                ->orWhere('last_seen_at', '<=', now()->subSeconds(self::ONLINE_WINDOW_SECONDS))
                ->orWhereNotNull('offline_since');
        });
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner_path ? asset('storage/'.$this->banner_path) : null;
    }

    /** True when a timed suspension has run out (a "totally suspended" member never expires). */
    public function suspensionHasEnded(): bool
    {
        return $this->status === 'suspended'
            && $this->suspended_until !== null
            && $this->suspended_until->isPast();
    }

    /**
     * Reactivate this member if their timed suspension is over.
     * Checked whenever the member logs in or calls the API, and when an admin
     * opens the Users page, so no scheduled job is needed.
     */
    public function liftSuspensionIfOver(): bool
    {
        if (! $this->suspensionHasEnded()) {
            return false;
        }

        $this->status = 'active';
        \App\Support\Suspension::clear($this);
        $this->save();

        AccountAppeal::closeOpenFor($this->id, 'The suspension ended.');
        ActivityLog::create([
            'user_id' => null,
            'action' => 'user.suspension_ended',
            'description' => "Suspension ended for {$this->name}",
        ]);
        Notification::notifyMember($this->id, 'Suspension ended', 'Your suspension has ended. You can log in and use LearnLoop again.', 'user', $this->id);

        return true;
    }

    /** Reactivate every member whose timed suspension is over. */
    public static function liftExpiredSuspensions(): int
    {
        $lifted = 0;

        foreach (static::where('status', 'suspended')->whereNotNull('suspended_until')->where('suspended_until', '<=', now())->get() as $member) {
            if ($member->liftSuspensionIfOver()) {
                $lifted++;
            }
        }

        return $lifted;
    }

    /** Ratings other users left for this user. */
    /**
     * The notifications table is our own (user_id, source, ...), not Laravel's
     * polymorphic one, so this overrides the Notifiable trait's relation.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'rated_user_id')->valid();
    }

    /** Skills this member teaches or wants, with their Beginner..Professional level. */
    public function userSkills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function skillsOffered(): HasMany
    {
        return $this->userSkills()->where('type', 'offered');
    }

    public function skillsWanted(): HasMany
    {
        return $this->userSkills()->where('type', 'wanted');
    }

    /** Lessons this member wrote for the public catalog. */
    public function authoredLessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'author_id');
    }

    /** Exchanges this member finished, as either side. */
    public function completedExchangesCount(): int
    {
        return Exchange::where('status', 'completed')
            ->where(fn ($q) => $q->where('provider_id', $this->id)->orWhere('learner_id', $this->id))
            ->count();
    }

    /** $user->average_rating — 0 when the user has no ratings yet. */
    public function getAverageRatingAttribute(): float
    {
        return (float) ($this->ratingsReceived()->avg('rating') ?? 0);
    }
}
