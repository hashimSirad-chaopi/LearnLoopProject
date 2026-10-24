<?php

namespace App\Support;

use App\Models\AccountAppeal;
use App\Models\Exchange;
use App\Models\Lesson;
use App\Models\Listing;
use App\Models\Report;

/**
 * The ADMIN's own notifications: work waiting for an administrator.
 *
 * These are counted live from the tables, so they never go stale and nothing
 * is stored. They are separate from the `notifications` table, which only
 * holds what members receive in the app.
 */
class AdminAlerts
{
    /** Counted once per request: the sidebar and the page both ask for it. */
    private static ?array $items = null;

    /** @return array<int, array{label:string, hint:string, count:int, url:string}> */
    public static function items(): array
    {
        return self::$items ??= [
            [
                'label' => 'Lessons waiting for review',
                'hint' => 'Member-written lessons from authors who are not trusted in that skill yet.',
                'count' => Lesson::whereNotNull('author_id')->where('status', 'pending')->count(),
                'url' => route('admin.lessons', ['status' => 'pending']),
            ],
            [
                'label' => 'Listings waiting for approval',
                'hint' => 'New listings stay hidden from the feed until you enable them.',
                'count' => Listing::where('status', 'pending')->count(),
                'url' => route('admin.listings'),
            ],
            [
                'label' => 'Reports to review',
                'hint' => 'Members reported a listing or another member.',
                'count' => Report::where('status', 'pending')->count(),
                'url' => route('admin.reports'),
            ],
            [
                'label' => 'Professional proofs to check',
                'hint' => 'Members who passed the hard quiz and sent proof to be verified as Professional.',
                'count' => \App\Models\ProfessionalVerification::where('status', 'in_review')->count(),
                'url' => route('admin.professional'),
            ],
            [
                'label' => 'Account recovery requests',
                'hint' => 'Suspended members asking you to reactivate their account.',
                'count' => AccountAppeal::where('status', 'pending')->count(),
                'url' => route('admin.appeals'),
            ],
            [
                'label' => 'Disputed exchanges',
                'hint' => 'A member disputed an exchange and needs a decision.',
                'count' => Exchange::where('status', 'disputed')->count(),
                'url' => route('admin.exchanges'),
            ],
        ];
    }

    /** Everything waiting for an admin, for the sidebar badge. */
    public static function total(): int
    {
        return array_sum(array_column(self::items(), 'count'));
    }
}
