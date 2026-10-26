<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Listing;
use App\Models\Notification;

/**
 * An admin monitors listings after they are live. Taking one down (disable or delete) needs a
 * reason, like suspending a member, and the owner is told what it was.
 */
class ListingModeration
{
    public const REASONS = [
        'spam' => 'Spam or misleading content',
        'inappropriate' => 'Inappropriate or explicit content',
        'scam' => 'Scam or fraud',
        'offtopic' => 'Not about teaching or learning a skill',
        'contact' => 'Asks for payment or contact outside the app',
        'duplicate' => 'Duplicate listing',
        'rules' => 'Breaking the community rules',
        'other' => 'Other',
    ];

    /** @return list<string> */
    public static function reasonKeys(): array
    {
        return array_keys(self::REASONS);
    }

    public static function label(?string $key): string
    {
        return self::REASONS[(string) $key] ?? self::REASONS['rules'];
    }

    /** "Scam or fraud: asked for a deposit" (for "other", the typed note is the whole reason). */
    public static function describe(?string $key, ?string $note): string
    {
        $note = trim((string) $note);
        if ($key === 'other') {
            return $note !== '' ? $note : self::REASONS['other'];
        }

        return self::label($key).($note !== '' ? ": {$note}" : '');
    }

    /** Take a listing out of the feed. It stays on the owner's My Listings, with the reason. */
    public static function disable(Listing $listing, string $reason, ?string $note, bool $bulk = false): void
    {
        $listing->forceFill([
            'status' => 'inactive',
            'moderation_reason' => $reason,
            'moderation_note' => $note,
            'moderated_at' => now(),
        ])->save();

        $why = self::describe($reason, $note);

        ActivityLog::record('listing.disabled', "Disabled listing \"{$listing->title}\"".($bulk ? ' (bulk)' : '').". Reason: {$why}");
        Notification::notifyMember(
            $listing->user_id,
            'Listing disabled',
            "An admin disabled your listing \"{$listing->title}\". It is no longer visible in the feed. Reason: {$why}.",
            'listing',
            $listing->id
        );
    }

    /** Delete a listing for good. The owner is told why. */
    public static function delete(Listing $listing, string $reason, ?string $note, bool $bulk = false): void
    {
        $why = self::describe($reason, $note);
        $title = $listing->title;
        $owner = $listing->user_id;

        $listing->delete();

        ActivityLog::record('listing.deleted', "Deleted listing \"{$title}\"".($bulk ? ' (bulk)' : '').". Reason: {$why}");
        Notification::notifyMember($owner, 'Listing removed', "An admin removed your listing \"{$title}\". Reason: {$why}.", 'listing', null);
    }

    /** Put a disabled listing back in the feed. */
    public static function restore(Listing $listing, bool $bulk = false): void
    {
        $listing->forceFill([
            'status' => 'active',
            'moderation_reason' => null,
            'moderation_note' => null,
            'moderated_at' => null,
        ])->save();

        ActivityLog::record('listing.enabled', "Restored listing \"{$listing->title}\"".($bulk ? ' (bulk)' : ''));
        Notification::notifyMember($listing->user_id, 'Listing restored', "Your listing \"{$listing->title}\" is visible in the feed again.", 'listing', $listing->id);
    }
}
