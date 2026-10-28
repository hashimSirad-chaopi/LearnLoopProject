<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Exchange;
use App\Models\Lesson;
use App\Models\Listing;
use App\Models\ListingComment;
use App\Models\Rating;
use App\Models\Report;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserLessonAttempt;
use App\Models\UserSkill;
use Carbon\Carbon;

/**
 * Everything the admin Dashboard shows, gathered in one place so the page
 * itself stays plain Blade. Counts only: no member's private messages.
 */
class DashboardStats
{
    /** @param int $days the date range shown on the dashboard: 7, 30 or 90 */
    public static function build(int $days = 30): array
    {
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $members = fn () => User::where('role', '!=', 'admin')->where('status', '!=', 'deleted');

        $byStatus = Exchange::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $exchangeStatuses = [];
        foreach (Exchange::STATUSES as $status) {
            $exchangeStatuses[$status] = (int) ($byStatus[$status] ?? 0);
        }

        $avgRating = Rating::valid()->avg('rating');

        return [
            'totalMembers' => $members()->count(),
            'newMembersWeek' => $members()->where('created_at', '>=', now()->subDays(7))->count(),
            'days' => $days,
            'newMembersRange' => $members()->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())->count(),
            'totalListings' => Listing::count(),
            'activeListings' => Listing::where('status', 'active')->count(),
            'exchangeStatuses' => $exchangeStatuses,
            'totalExchanges' => array_sum($exchangeStatuses),
            'avgRating' => $avgRating ? round((float) $avgRating, 1) : null,
            'ratingsCount' => Rating::valid()->count(),
            'skillCount' => Skill::count(),
            'categoryCount' => Category::count(),
            'publishedLessons' => Lesson::where('status', 'published')->whereNull('author_id')->count(), // catalog only
            'pendingReports' => Report::where('status', 'pending')->count(),
            'onlineNow' => $members()->online()->count(),
            'suspendedMembers' => $members()->where('status', 'suspended')->count(),
            'commentsRange' => ListingComment::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())->count(),
            'commentsTotal' => ListingComment::count(),
            'newListingsRange' => Listing::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())->count(),
            'newMembersPrevious' => $members()
                ->where('created_at', '>=', now()->subDays(2 * $days - 1)->startOfDay())
                ->where('created_at', '<', now()->subDays($days - 1)->startOfDay())
                ->count(),

            'signups' => self::signups($days),
            'engagement' => self::engagement($days),
            'topWanted' => self::topSkills('wanted'),
            'topOffered' => self::topSkills('offered'),
            'topCategories' => self::topCategories(),

            'recentMembers' => $members()->latest()->take(5)->get(['id', 'name', 'email', 'status', 'avatar_path', 'created_at']),
            'activities' => ActivityLog::latest()->take(8)->get(),
        ];
    }

    /**
     * New members over the range. Up to 31 days it is one bar per day; for 90
     * days it is one bar per week, so the chart stays readable.
     */
    private static function signups(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $created = User::where('role', '!=', 'admin')->where('status', '!=', 'deleted')->where('created_at', '>=', $from)->pluck('created_at');

        $step = $days > 31 ? 7 : 1;
        $buckets = [];
        for ($start = $from->copy(); $start->lte(now()); $start->addDays($step)) {
            $end = $start->copy()->addDays($step)->subSecond();
            $count = $created->filter(fn ($at) => Carbon::parse($at)->between($start, $end))->count();
            $buckets[] = [
                'label' => $step === 1 ? $start->format('M j') : $start->format('M j').' week',
                'day' => $step === 1 ? $start->format('j') : $start->format('M j'),
                'count' => $count,
            ];
        }

        return $buckets;
    }

    /**
     * Weekly engagement: catalog lessons passed and exchanges completed, in
     * rolling 7-day windows ending today. About one window per week of the range.
     */
    private static function engagement(int $days): array
    {
        $weeks = max(4, min(13, (int) ceil($days / 7)));
        $from = now()->subDays($weeks * 7 - 1)->startOfDay();

        $passed = UserLessonAttempt::where('passed', true)->where('attempted_at', '>=', $from)->pluck('attempted_at');
        $completed = Exchange::where('status', 'completed')->where('updated_at', '>=', $from)->pluck('updated_at');

        $rows = [];
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $end = now()->subDays($i * 7)->endOfDay();
            $start = $end->copy()->subDays(6)->startOfDay();
            $rows[] = [
                'label' => $start->format('M j'),
                'lessons' => $passed->filter(fn ($at) => Carbon::parse($at)->between($start, $end))->count(),
                'exchanges' => $completed->filter(fn ($at) => Carbon::parse($at)->between($start, $end))->count(),
            ];
        }

        return [
            'weeks' => $rows,
            'lessonsTotal' => array_sum(array_column($rows, 'lessons')),
            'exchangesTotal' => array_sum(array_column($rows, 'exchanges')),
        ];
    }

    /** The 5 skills most members want to learn / offer to teach. */
    private static function topSkills(string $type): array
    {
        return UserSkill::with('skill:id,title')
            ->where('type', $type)
            ->selectRaw('skill_id, COUNT(*) as total')
            ->groupBy('skill_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->filter(fn ($row) => $row->skill)
            ->map(fn ($row) => ['name' => $row->skill->title, 'count' => (int) $row->total])
            ->values()
            ->all();
    }

    /** The 5 categories with the most listings. */
    private static function topCategories(): array
    {
        $rows = Listing::selectRaw('category_id, COUNT(*) as total')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $names = Category::whereIn('id', $rows->pluck('category_id'))->pluck('name', 'id');

        return $rows
            ->filter(fn ($row) => $names->has($row->category_id))
            ->map(fn ($row) => ['name' => $names[$row->category_id], 'count' => (int) $row->total])
            ->values()
            ->all();
    }
}
