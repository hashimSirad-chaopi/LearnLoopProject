<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\Rating;
use App\Models\RatingAttachment;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    /**
     * GET /api/ratings — the ratings OTHER members left for me (Profile -> Rating).
     * The app filters by star and sorts them; the full list is small enough to send at once.
     */
    public function mine(Request $request)
    {
        return response()->json($this->ratingsPayload($request->user()));
    }

    /**
     * GET /api/users/{user}/ratings — what other members said about THIS member, so you can
     * judge whether they are trusted before you swap. Same shape as mine(). Only real, valid
     * ratings count (the same rule as the profile average), never across a block, and only for
     * active members.
     */
    public function forUser(Request $request, User $user)
    {
        if ($user->status !== 'active' || UserBlock::between($request->user()->id, $user->id)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($this->ratingsPayload($user));
    }

    private function ratingsPayload(User $member): array
    {
        $rows = Rating::with(['rater', 'exchange', 'attachments'])
            ->valid()
            ->where('rated_user_id', $member->id)
            ->latest()
            ->get();

        return [
            'average' => round((float) $rows->avg('rating'), 1),
            'count' => $rows->count(),
            'ratings' => $rows->map(fn (Rating $r) => [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'comment' => $r->comment,
                'created_at' => $r->created_at?->toIso8601String(),
                'rater' => [
                    'id' => $r->rater?->id,
                    'name' => $r->rater?->name ?? 'User',
                    'avatar_url' => $r->rater?->avatar_url,
                ],
                'skill' => $r->exchange?->skillPair(),
                'attachments' => $this->attachmentsJson($r),
            ])->values(),
        ];
    }

    /** "Feedback & Rating" — leave a rating after an exchange completes. */
    public function store(Request $request, Exchange $exchange)
    {
        $userId = $request->user()->id;
        abort_unless($exchange->provider_id === $userId || $exchange->learner_id === $userId, 403);

        // Learner-only exchange (nothing taught back): only the learner rates. In a two-way swap
        // both members rate each other.
        if ($exchange->isLearnerOnly() && $exchange->learner_id !== $userId) {
            return response()->json(['message' => 'Only the learner can rate a learner-only exchange.'], 403);
        }

        if ($exchange->status !== 'completed') {
            return response()->json([
                'message' => $exchange->is_professional
                    ? 'You can rate once the professional confirms the exchange is finished.'
                    : 'You can only rate a completed exchange',
            ], 422);
        }

        // Paid exchange: the professional teaches and confirms; only the learner rates.
        if ($exchange->is_professional && $exchange->provider_id === $userId) {
            return response()->json(['message' => 'Only the learner can rate a paid professional exchange.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            // Proof: up to 3 photos and 2 videos (50 MB each). Sent as multipart
            // fields images[] and videos[].
            'images' => 'sometimes|array|max:'.RatingAttachment::MAX_IMAGES,
            'images.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:'.RatingAttachment::MAX_IMAGE_KB,
            'videos' => 'sometimes|array|max:'.RatingAttachment::MAX_VIDEOS,
            'videos.*' => 'file|mimetypes:video/mp4,video/quicktime,video/webm,video/3gpp,video/x-matroska,video/x-msvideo|max:'.RatingAttachment::MAX_VIDEO_KB,
        ], [
            'images.max' => 'You can add up to '.RatingAttachment::MAX_IMAGES.' photos.',
            'videos.max' => 'You can add up to '.RatingAttachment::MAX_VIDEOS.' videos.',
            'videos.*.max' => 'Each video must be 50 MB or smaller.',
            'videos.*.mimetypes' => 'Videos must be MP4, MOV, WebM, 3GP, MKV or AVI.',
            'videos.*.uploaded' => 'A video could not be uploaded. It may be larger than the server allows (50 MB each).',
            'images.*.max' => 'Each photo must be 10 MB or smaller.',
            'images.*.uploaded' => 'A photo could not be uploaded.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $ratedUserId = $exchange->provider_id === $userId ? $exchange->learner_id : $exchange->provider_id;

        // unique(exchange_id, rater_id) in the migration means this is
        // safe to call again — it just updates the same row instead of
        // creating a duplicate.
        $rating = Rating::updateOrCreate(
            ['exchange_id' => $exchange->id, 'rater_id' => $userId],
            ['rated_user_id' => $ratedUserId, 'rating' => $request->rating, 'comment' => $request->comment]
        );

        // New proof replaces the old one, so sending the form again never
        // stacks up files.
        if ($request->hasFile('images') || $request->hasFile('videos')) {
            foreach ($rating->attachments as $old) {
                Storage::disk('public')->delete($old->path);
                $old->delete();
            }

            foreach ((array) $request->file('images', []) as $file) {
                $this->saveAttachment($rating, $file, 'image');
            }
            foreach ((array) $request->file('videos', []) as $file) {
                $this->saveAttachment($rating, $file, 'video');
            }
        }

        return response()->json(['rating' => $rating->load('attachments')], 201);
    }

    private function saveAttachment(Rating $rating, $file, string $type): void
    {
        $rating->attachments()->create([
            'type' => $type,
            'path' => $file->store('rating-proofs', 'public'),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }

    private function attachmentsJson(Rating $rating): array
    {
        return $rating->attachments->map(fn (RatingAttachment $a) => [
            'id' => $a->id,
            'type' => $a->type,
            'url' => $a->url,
            'name' => $a->original_name,
        ])->values()->all();
    }
}
