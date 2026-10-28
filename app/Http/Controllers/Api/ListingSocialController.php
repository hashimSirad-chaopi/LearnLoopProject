<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingComment;
use App\Models\ListingLike;
use App\Models\Notification;
use App\Models\UserBlock;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** Feed / Find: comments (with replies) and likes on a listing. */
class ListingSocialController extends Controller
{
    /** A member can see a listing that is live, or one of their own. */
    private function visible(Request $request, Listing $listing): bool
    {
        return $listing->status === 'active' || $listing->user_id === $request->user()->id;
    }

    /** GET /api/listings/{listing}/comments — flat, oldest first; the app builds the reply tree from parent_id. */
    public function index(Request $request, Listing $listing)
    {
        if (! $this->visible($request, $listing)) {
            return response()->json(['message' => 'Listing not found'], 404);
        }

        $me = $request->user()->id;
        $blocked = UserBlock::where('blocker_id', $me)->pluck('blocked_id')
            ->merge(UserBlock::where('blocked_id', $me)->pluck('blocker_id'))->all();

        $all = $listing->comments()->with('user:id,name,avatar_path')->orderBy('created_at')->get();

        // Drop comments by blocked members, and the replies hanging under them (they would have no parent in the app).
        $hidden = $all->whereIn('user_id', $blocked)->pluck('id')->all();
        do {
            $before = count($hidden);
            $hidden = array_values(array_unique(array_merge($hidden, $all->whereIn('parent_id', $hidden)->pluck('id')->all())));
        } while (count($hidden) > $before);

        $comments = $all->reject(fn (ListingComment $c) => in_array($c->id, $hidden, true))
            ->map(fn (ListingComment $c) => [
                'id' => $c->id,
                'parent_id' => $c->parent_id,
                'body' => $c->body,
                'created_at' => $c->created_at,
                'user' => ['id' => $c->user?->id, 'name' => $c->user?->name ?? 'User', 'avatar_url' => $c->user?->avatar_url, 'presence' => $c->user?->presence],
            ])->values();

        return response()->json(['comments' => $comments]);
    }

    /** POST /api/listings/{listing}/comments { body, parent_id? } */
    public function store(Request $request, Listing $listing)
    {
        if (! $this->visible($request, $listing)) {
            return response()->json(['message' => 'Listing not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:50000',
            'parent_id' => 'nullable|integer|exists:listing_comments,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($request->parent_id && ! $listing->comments()->whereKey($request->parent_id)->exists()) {
            return response()->json(['message' => 'That comment is not on this listing'], 422);
        }

        $body = trim($request->body);

        // The same text from the same member on the same listing within a minute is a double tap or spam.
        $duplicate = $listing->comments()
            ->where('user_id', $request->user()->id)
            ->where('body', $body)
            ->where('created_at', '>=', now()->subMinute())
            ->exists();
        if ($duplicate) {
            return response()->json(['message' => 'You just posted that comment.'], 422);
        }

        $comment = $listing->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
            'body' => $body,
        ]);

        $this->notifyAbout($request->user(), $listing, $comment);

        return response()->json(['comment' => [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'body' => $comment->body,
            'created_at' => $comment->created_at,
            'user' => ['id' => $request->user()->id, 'name' => $request->user()->name, 'avatar_url' => $request->user()->avatar_url],
        ]], 201);
    }

    /**
     * Tell the member being replied to, and the listing's owner, that someone wrote.
     * Nobody is told about their own comment, or by someone they blocked (or who blocked them),
     * and nobody gets two notices for one comment.
     */
    private function notifyAbout($author, Listing $listing, ListingComment $comment): void
    {
        $excerpt = Str::limit($comment->body, 80);
        $told = [$author->id];

        $parentAuthor = $comment->parent_id ? ListingComment::whereKey($comment->parent_id)->value('user_id') : null;

        if ($parentAuthor && ! in_array($parentAuthor, $told, true) && ! UserBlock::between($author->id, $parentAuthor)) {
            Notification::notifyFromMember($parentAuthor, 'comment_reply', 'New reply', "{$author->name} replied to your comment on \"{$listing->title}\": {$excerpt}", 'listing', $listing->id);
            $told[] = $parentAuthor;
        }

        if ($listing->user_id && ! in_array($listing->user_id, $told, true) && ! UserBlock::between($author->id, $listing->user_id)) {
            Notification::notifyFromMember($listing->user_id, 'comment', 'New comment on your listing', "{$author->name} commented on \"{$listing->title}\": {$excerpt}", 'listing', $listing->id);
        }
    }

    /** DELETE /api/comments/{comment} — your own comment (its replies go with it). */
    public function destroy(Request $request, ListingComment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only delete your own comments'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }

    /** POST /api/listings/{listing}/like — toggles your like, returns the new state and count. */
    public function toggleLike(Request $request, Listing $listing)
    {
        if (! $this->visible($request, $listing)) {
            return response()->json(['message' => 'Listing not found'], 404);
        }

        $existing = ListingLike::where('listing_id', $listing->id)->where('user_id', $request->user()->id)->first();
        if ($existing) {
            $existing->delete();
        } else {
            ListingLike::create(['listing_id' => $listing->id, 'user_id' => $request->user()->id]);
        }

        return response()->json([
            'liked' => ! $existing,
            'likes' => ListingLike::where('listing_id', $listing->id)->count(),
        ]);
    }
}
