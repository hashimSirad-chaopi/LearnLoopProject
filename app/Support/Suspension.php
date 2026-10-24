<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;

/**
 * How long an administrator suspends a member for.
 *
 *   1d / 1w / 1y = the account comes back by itself when the time is up
 *   forever      = "totally suspended": only an administrator can reactivate it
 *
 * An administrator can always reactivate early.
 */
class Suspension
{
    public const OPTIONS = [
        '1d' => '1 day',
        '1w' => '1 week',
        '1y' => '1 year',
        'forever' => 'Totally suspended (until you reactivate it)',
    ];

    public const DEFAULT = 'forever';

    /** The reason types an administrator can pick (like Facebook's list). "other" needs a typed message. */
    public const REASONS = [
        'spam' => 'Spam or misleading content',
        'harassment' => 'Harassment or bullying',
        'hate' => 'Hate speech or discrimination',
        'inappropriate' => 'Inappropriate or explicit content',
        'scam' => 'Scam or fraud',
        'fake' => 'Fake account or impersonation',
        'unsafe' => 'Threats or unsafe behavior',
        'rules' => 'Breaking the community rules',
        'other' => 'Other',
    ];

    public const DEFAULT_REASON = 'rules';

    /** Set by the system when too many reports against a member were upheld; not in the admin's list. */
    public const REASON_REPORTS = 'reports';

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::OPTIONS);
    }

    public static function normalize(?string $key): string
    {
        return array_key_exists((string) $key, self::OPTIONS) ? (string) $key : self::DEFAULT;
    }

    /** @return list<string> */
    public static function reasonKeys(): array
    {
        return array_keys(self::REASONS);
    }

    public static function normalizeReason(?string $key): string
    {
        return array_key_exists((string) $key, self::REASONS) || $key === self::REASON_REPORTS
            ? (string) $key
            : self::DEFAULT_REASON;
    }

    public static function reasonLabel(?string $key): string
    {
        return match (self::normalizeReason($key)) {
            self::REASON_REPORTS => 'Several reports against your account were upheld',
            default => self::REASONS[self::normalizeReason($key)],
        };
    }

    /**
     * The reason as one readable line, for messages and the admin screens:
     * "Harassment or bullying: sent rude messages to three members"
     * "Selling things outside the app"  (for "other", the typed message is the reason)
     * Returns null when no reason was recorded.
     */
    public static function describe(?string $key, ?string $note): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $note = trim((string) $note);

        if ($key === 'other') {
            return $note !== '' ? $note : self::REASONS['other'];
        }

        return self::reasonLabel($key).($note !== '' ? ": {$note}" : '');
    }

    /** When the suspension ends, or null for "until an administrator reactivates it". */
    public static function until(string $key): ?CarbonInterface
    {
        return match (self::normalize($key)) {
            '1d' => now()->addDay(),
            '1w' => now()->addWeek(),
            '1y' => now()->addYear(),
            default => null,
        };
    }

    /** "until Oct 25, 2026 3:42 PM UTC" or "until an administrator reactivates it". */
    public static function phrase(?CarbonInterface $until): string
    {
        return $until ? 'until '.$until->format('M j, Y g:i A T') : 'until an administrator reactivates it';
    }

    /** Suspend the member for the chosen time and record why. Returns the phrase for messages and logs. */
    public static function apply(User $user, ?string $key, ?string $reason = null, ?string $note = null): string
    {
        $until = self::until(self::normalize($key));
        $note = trim((string) $note);

        $user->status = 'suspended';
        $user->suspended_until = $until;
        $user->suspension_reason = self::normalizeReason($reason);
        $user->suspension_note = $note !== '' ? $note : null;
        $user->save();

        return self::phrase($until);
    }

    /** Forget the suspension (the account is being reactivated). Does not save. */
    public static function clear(User $user): void
    {
        $user->suspended_until = null;
        $user->suspension_reason = null;
        $user->suspension_note = null;
    }
}
