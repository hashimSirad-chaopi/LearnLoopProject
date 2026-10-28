<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\Listing;
use App\Models\ListingComment;
use App\Models\ListingLike;
use App\Models\Rating;
use App\Models\Report;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserLessonAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * GET /api/activity[?type=listings|likes|comments|exchanges|learning|safety]
 *
 * The member's own timeline, newest first (at most 150 entries): what they posted, liked,
 * commented on, requested and finished, passed, rated, reported and blocked.
 * Only the member's own actions are listed.
 */
class ActivityHistoryController extends Controller
{
    private const MAX = 150;
    private const PER_SOURCE = 60;

    public function index(Request $request)
    {
        $me = $request->user();
        $type = $request->query('type');
        $want = fn (string $t) => ! $type || $type === 'all' || $type === $t;
        $items = [];

        $add = function (string $kind, string $icon, string $title, ?string $detail, $at, ?int $listingId = null) use (&$items) {
            if (! $at) {
                return;
            }
            $items[] = [
                'kind' => $kind,
                'icon' => $icon,
                'title' => $title,
                'detail' => $detail,
                'at' => $at->toIso8601String(),
                'listing_id' => $listingId,
                '_ts' => $at->getTimestamp(),
            ];
        };

        if ($want('listings')) {
            foreach (Listing::where('user_id', $me->id)->latest()->limit(self::PER_SOURCE)->get() as $l) {
                $add('listings', 'post', 'Posted a listing', $l->title, $l->created_at, $l->id);
            }
        }

        if ($want('likes')) {
            foreach (ListingLike::with('listing:id,title,user_id')->where('user_id', $me->id)->latest()->limit(self::PER_SOURCE)->get() as $like) {
                if ($like->listing) {
                    $add('likes', 'like', 'Liked a listing', $like->listing->title, $like->created_at, $like->listing->id);
                }
            }
        }

        if ($want('comments')) {
            foreach (ListingComment::with('listing:id,title')->where('user_id', $me->id)->latest()->limit(self::PER_SOURCE)->get() as $c) {
                if ($c->listing) {
                    $add('comments', 'comment', $c->parent_id ? 'Replied on a listing' : 'Commented on a listing',
                        '"'.Str::limit($c->body, 80).'" — '.$c->listing->title, $c->created_at, $c->listing->id);
                }
            }
        }

        if ($want('exchanges')) {
            $exchanges = Exchange::with(['provider:id,name', 'learner:id,name'])
                ->where(fn ($q) => $q->where('provider_id', $me->id)->orWhere('learner_id', $me->id))
                ->latest()->limit(self::PER_SOURCE)->get();

            foreach ($exchanges as $e) {
                $other = $e->provider_id === $me->id ? $e->learner : $e->provider;
                $name = $other?->name ?? 'a member';
                $pair = $e->skillPair();
                $sent = $e->requested_by === $me->id;

                $add('exchanges', 'exchange', $sent ? "Sent a request to {$name}" : "Got a request from {$name}", $pair, $e->created_at);

                $end = match ($e->status) {
                    'ongoing' => ['Started an exchange with '.$name, $e->updated_at],
                    'completed' => ['Completed an exchange with '.$name, $e->updated_at],
                    'cancelled' => ['Exchange with '.$name.' was cancelled', $e->updated_at],
                    'disputed' => ['Exchange with '.$name.' went to review', $e->disputed_at ?? $e->updated_at],
                    default => null,
                };
                if ($end && $e->updated_at && $e->created_at && $e->updated_at->gt($e->created_at)) {
                    $add('exchanges', 'exchange', $end[0], $pair, $end[1]);
                }
            }
        }

        if ($want('learning')) {
            foreach (UserLessonAttempt::with('lesson:id,title')->where('user_id', $me->id)->where('passed', true)->latest('attempted_at')->limit(self::PER_SOURCE)->get() as $a) {
                if ($a->lesson) {
                    $add('learning', 'lesson', 'Passed a lesson', $a->lesson->title.' ('.$a->score.'/'.$a->total_questions.')', $a->attempted_at ?? $a->created_at);
                }
            }
            foreach (Rating::with('ratedUser:id,name')->where('rater_id', $me->id)->latest()->limit(self::PER_SOURCE)->get() as $r) {
                $add('learning', 'rating', 'Rated '.($r->ratedUser?->name ?? 'a member'), str_repeat('★', (int) $r->rating).str_repeat('☆', max(0, 5 - (int) $r->rating)), $r->created_at);
            }
        }

        if ($want('safety')) {
            foreach (Report::where('reported_by', $me->id)->latest()->limit(self::PER_SOURCE)->get() as $r) {
                $add('safety', 'report', 'Sent a report', $r->title ?: $r->reason, $r->created_at);
            }
            foreach (UserBlock::with('blockedUser:id,name')->where('blocker_id', $me->id)->latest()->limit(self::PER_SOURCE)->get() as $b) {
                $add('safety', 'block', 'Blocked '.($b->blockedUser?->name ?? 'a member'), null, $b->created_at);
            }
        }

        usort($items, fn ($a, $b) => $b['_ts'] <=> $a['_ts']);
        $items = array_slice($items, 0, self::MAX);
        foreach ($items as &$item) {
            unset($item['_ts']);
        }

        return response()->json(['activity' => $items]);
    }
}
