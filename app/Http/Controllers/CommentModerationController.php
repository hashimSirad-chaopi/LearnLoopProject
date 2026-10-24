<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ListingComment;
use App\Models\Notification;
use App\Models\User;
use App\Support\Suspension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Admin: read and remove comments members wrote on listings (Feed / Find in the app). */
class CommentModerationController extends Controller
{
    /** Why a comment was removed: the key sent by the form => the wording the member reads. */
    public const REASONS = [
        'spam' => 'Spam or advertising',
        'harassment' => 'Harassment or bullying',
        'inappropriate' => 'Inappropriate or offensive content',
        'off_topic' => 'Not related to the listing',
        'other' => 'Breaking the community rules',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = in_array($request->query('type'), ['comments', 'replies'], true) ? $request->query('type') : 'all';

        $comments = ListingComment::with(['user', 'listing', 'parent.user'])
            ->withCount('replies')
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('body', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('listing', fn ($l) => $l->where('title', 'like', "%{$search}%"));
            }))
            ->when($type === 'comments', fn ($q) => $q->whereNull('parent_id'))
            ->when($type === 'replies', fn ($q) => $q->whereNotNull('parent_id'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => ListingComment::count(),
            'comments' => ListingComment::whereNull('parent_id')->count(),
            'replies' => ListingComment::whereNotNull('parent_id')->count(),
            'today' => ListingComment::where('created_at', '>=', now()->startOfDay())->count(),
        ];

        return view('admin.comments', [
            'comments' => $comments,
            'search' => $search,
            'type' => $type,
            'counts' => $counts,
            'reasons' => self::REASONS,
        ]);
    }

    public function destroy(Request $request, ListingComment $comment)
    {
        $data = $this->validateRemoval($request);

        $replies = $this->remove($comment, $this->reasonText($data));

        return back()->with('success', 'Comment removed and the member was notified.'
            .($replies > 0 ? " {$replies} ".Str::plural('reply', $replies).' under it were removed too.' : ''));
    }

    /**
     * Act on several comments at once.
     *
     *   remove  = delete the selected comments (and replies under them), tell each author why
     *   suspend = suspend the authors of the selected comments, keep the comments
     *   both    = suspend the authors and delete their selected comments
     *
     * Admins, yourself and members who are already suspended are skipped when suspending.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer',
            'action' => 'nullable|in:remove,suspend,both',
            'reason' => 'nullable|in:'.implode(',', array_keys(self::REASONS)),
            'note' => 'nullable|string|max:500',
            'duration' => 'nullable|in:'.implode(',', Suspension::keys()),
            'suspend_reason' => 'nullable|in:'.implode(',', Suspension::reasonKeys()),
        ]);

        $action = $data['action'] ?? 'remove';
        $suspends = in_array($action, ['suspend', 'both'], true);
        $removes = in_array($action, ['remove', 'both'], true);

        $suspendReason = $data['suspend_reason'] ?? Suspension::DEFAULT_REASON;
        if ($suspends && $suspendReason === 'other' && trim((string) ($data['note'] ?? '')) === '') {
            return back()->withErrors(['note' => 'Write the details when the suspension reason is "Other".']);
        }

        $comments = ListingComment::with(['user', 'listing'])->whereIn('id', $data['ids'])->get();

        // Suspend first: the authors are looked up from the comments before any of them are deleted.
        $suspended = 0;
        $skippedMembers = 0;
        if ($suspends) {
            $authors = User::whereIn('id', $comments->pluck('user_id')->filter()->unique())->get();
            foreach ($authors as $author) {
                if ($author->role === 'admin' || $author->id === auth()->id() || $author->status === 'suspended') {
                    $skippedMembers++;
                    continue;
                }
                $when = Suspension::apply($author, $data['duration'] ?? null, $suspendReason, $data['note'] ?? null);
                $why = Suspension::describe($suspendReason, $data['note'] ?? null);
                ActivityLog::record('user.suspended', "Suspended user {$author->name} {$when}. Reason: {$why} (from comments)");
                Notification::notifyMember($author->id, 'Account suspended', "An administrator suspended your account {$when}. Reason: {$why}.", 'user', $author->id);
                $suspended++;
            }
        }

        $removed = 0;
        if ($removes) {
            $reason = $this->reasonText($data);
            foreach ($data['ids'] as $id) {
                // A reply may already be gone because the comment above it was removed in this same batch.
                $comment = ListingComment::with(['user', 'listing'])->find($id);
                if (! $comment) {
                    continue;
                }
                $this->remove($comment, $reason);
                $removed++;
            }
        }

        $parts = [];
        if ($removes) {
            $parts[] = $removed.' '.Str::plural('comment', $removed).' removed';
        }
        if ($suspends) {
            $parts[] = $suspended.' '.Str::plural('member', $suspended).' suspended'
                .($skippedMembers > 0 ? " ({$skippedMembers} skipped: admins, yourself, or already suspended)" : '');
        }

        return back()->with('success', ucfirst(implode(' and ', $parts)).'.');
    }

    private function validateRemoval(Request $request): array
    {
        return $request->validate([
            'reason' => 'nullable|in:'.implode(',', array_keys(self::REASONS)),
            'note' => 'nullable|string|max:300',
        ]);
    }

    private function reasonText(array $data): string
    {
        $text = self::REASONS[$data['reason'] ?? 'other'] ?? self::REASONS['other'];
        $note = trim((string) ($data['note'] ?? ''));

        return $note !== '' ? "{$text}: {$note}" : $text;
    }

    /**
     * Delete one comment and everything under it, and tell the people affected:
     * the author (with the reason) and anyone whose reply went with it.
     *
     * @return int how many replies were removed with it
     */
    private function remove(ListingComment $comment, string $reason): int
    {
        $comment->loadMissing(['listing', 'user']);

        $authorId = $comment->user_id;
        $excerpt = Str::limit($comment->body, 60);
        $listingId = $comment->listing_id;
        $listingTitle = $comment->listing?->title ?? 'a listing';
        $authorName = $comment->user?->name ?? 'a deleted member';

        $replies = $comment->allReplies();
        $replyCount = $replies->count();
        $replyAuthors = $replies->pluck('user_id')->filter()->unique()
            ->reject(fn ($id) => $id === $authorId)->values();

        DB::transaction(fn () => $comment->delete()); // its replies go with it

        if ($authorId) {
            Notification::notifyMember($authorId, 'Comment removed', "An admin removed your comment on \"{$listingTitle}\". Reason: {$reason}.", 'listing', $listingId);
        }

        foreach ($replyAuthors as $memberId) {
            Notification::notifyMember($memberId, 'Reply removed', "Your reply on \"{$listingTitle}\" was removed because the comment above it was removed by an admin.", 'listing', $listingId);
        }

        ActivityLog::record('comment.deleted', "Removed a comment by {$authorName}: \"{$excerpt}\" ({$reason})".($replyCount > 0 ? " with {$replyCount} ".Str::plural('reply', $replyCount) : ''));

        return $replyCount;
    }
}
