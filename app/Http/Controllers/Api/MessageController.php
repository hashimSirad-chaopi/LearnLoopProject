<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConversationState;
use App\Models\Message;
use App\Models\User;
use App\Models\UserBlock;
use App\Support\PushNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    /**
     * "In-App Messaging" — the Messages tab's conversation list. Groups
     * this user's messages by whichever other user is on the other end.
     */
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;
        $wantArchived = $request->boolean('archived');

        $states = ConversationState::where('user_id', $userId)->get()->keyBy('other_user_id');

        $otherOf = fn ($m) => $m->sender_id === $userId ? $m->recipient_id : $m->sender_id;

        $groups = Message::with(['sender', 'recipient'])
            ->where('sender_id', $userId)
            ->orWhere('recipient_id', $userId)
            ->latest()
            ->get()
            // "Delete chat" hides everything up to the moment it was cleared (for this member only).
            ->filter(function ($m) use ($states, $otherOf) {
                $cleared = $states->get($otherOf($m))?->cleared_at;

                return ! $cleared || $m->created_at->gt($cleared);
            })
            ->groupBy($otherOf);

        $archivedCount = $groups->filter(fn ($g, $otherId) => $states->get($otherId)?->archived_at !== null)->count();

        $conversations = $groups
            ->filter(fn ($g, $otherId) => ($states->get($otherId)?->archived_at !== null) === $wantArchived)
            ->map(function ($group) use ($userId) {
                $last = $group->first(); // already newest-first from the query above
                $other = $last->sender_id === $userId ? $last->recipient : $last->sender;

                // "Active now" / "Active 5 min ago" under the name in the chat list. Hidden when
                // either member blocked the other or the account is suspended (same rule as the thread).
                $showActivity = $other->status === 'active' && ! UserBlock::between($userId, $other->id);

                return [
                    'user_id' => $other->id,
                    'name' => $other->name,
                    'avatar_url' => $other->avatar_url,
                    'presence' => $other->presence,
                    'activity_visible' => $showActivity,
                    'last_seen_at' => $showActivity ? $other->last_seen_at?->toIso8601String() : null,
                    'last_message' => $last->message_text !== '' ? $last->message_text : match ($last->attachment_type) {
                        'video' => 'Video',
                        'audio' => 'Voice message',
                        default => 'Photo',
                    },
                    'last_message_at' => $last->created_at,
                    'unread_count' => $group->where('recipient_id', $userId)->whereNull('read_at')->count(),
                ];
            })
            ->values();

        return response()->json(['conversations' => $conversations, 'archived_count' => $archivedCount]);
    }

    /**
     * Full thread with one specific user — marks their messages to me as read.
     * Also says whether they are typing right now and whether either of us blocked the other.
     */
    public function thread(Request $request, User $user)
    {
        $userId = $request->user()->id;

        $cleared = ConversationState::where('user_id', $userId)->where('other_user_id', $user->id)->value('cleared_at');

        $messages = Message::where(function ($outer) use ($userId, $user) {
            $outer->where(function ($q) use ($userId, $user) {
                $q->where('sender_id', $userId)->where('recipient_id', $user->id);
            })->orWhere(function ($q) use ($userId, $user) {
                $q->where('sender_id', $user->id)->where('recipient_id', $userId);
            });
        })->when($cleared, fn ($q) => $q->where('created_at', '>', $cleared))
            ->orderBy('created_at')->get();

        Message::where('sender_id', $user->id)
            ->where('recipient_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $blockedByMe = UserBlock::blocked($userId, $user->id);
        $blockedMe = UserBlock::blocked($user->id, $userId);

        // "Active now" / "Last active today at 3:42 PM" in the chat header. Only the person you are
        // chatting with, and nothing at all if either of you blocked the other or the account is suspended.
        $showActivity = ! $blockedByMe && ! $blockedMe && $user->status === 'active';

        return response()->json([
            'messages' => $messages,
            'partner_typing' => Cache::has("typing:{$user->id}:{$userId}"),
            'blocked_by_me' => $blockedByMe,
            'blocked_me' => $blockedMe,
            'partner' => [
                'presence' => $showActivity ? $user->presence : null,
                'last_seen_at' => $showActivity ? $user->last_seen_at?->toIso8601String() : null,
            ],
        ]);
    }

    /**
     * Text, plus at most one attachment (multipart): a photo (`image`), a video (`video`)
     * or a voice message / audio clip (`audio`). A caption is optional.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => ['required', 'exists:users,id', Rule::notIn([$request->user()->id])],
            'message_text' => 'nullable|string|max:20000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120', // 5 MB
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/webm,video/3gpp,video/x-matroska,video/x-m4v|max:51200', // 50 MB
            // An .m4a recording is often reported as video/mp4, so that type is allowed here too.
            'audio' => 'nullable|file|mimetypes:audio/mp4,audio/x-m4a,audio/m4a,audio/aac,audio/mpeg,audio/ogg,audio/wav,audio/x-wav,audio/webm,audio/3gpp,audio/amr,video/mp4,video/3gpp,video/webm|max:20480', // 20 MB
            'exchange_id' => 'nullable|exists:exchanges,id',
        ], [
            'video.max' => 'A video must be 50 MB or smaller.',
            'video.mimetypes' => 'Videos must be MP4, MOV, WebM, 3GP or MKV.',
            'audio.max' => 'A voice message must be 20 MB or smaller.',
            'audio.mimetypes' => 'That audio format is not supported.',
        ]);

        $validator->after(function ($v) use ($request) {
            $files = collect(['image', 'video', 'audio'])->filter(fn ($field) => $request->hasFile($field));

            if ($files->count() > 1) {
                $v->errors()->add('message_text', 'Send one photo, video or voice message at a time.');
            } elseif (trim((string) $request->message_text) === '' && $files->isEmpty()) {
                $v->errors()->add('message_text', 'Write a message or attach a photo, video or voice message.');
            }
        });

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first() ?: 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $sender = $request->user();

        if (UserBlock::between($sender->id, (int) $request->recipient_id)) {
            return response()->json(['message' => "You can't message this user."], 403);
        }

        $attachmentType = collect(['image', 'video', 'audio'])->first(fn ($field) => $request->hasFile($field));

        $message = Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $request->recipient_id,
            'exchange_id' => $request->exchange_id,
            'message_text' => trim((string) $request->message_text),
            'attachment_path' => $attachmentType ? $request->file($attachmentType)->store('chat', 'public') : null,
            'attachment_type' => $attachmentType,
        ]);

        Cache::forget("typing:{$sender->id}:{$request->recipient_id}");

        // A new message brings an archived chat back to both members' main lists.
        ConversationState::where('user_id', $request->recipient_id)->where('other_user_id', $sender->id)->update(['archived_at' => null]);
        ConversationState::where('user_id', $sender->id)->where('other_user_id', $request->recipient_id)->update(['archived_at' => null]);

        PushNotifier::toUser(
            (int) $request->recipient_id,
            $sender->name,
            $message->message_text !== '' ? $message->message_text : match ($attachmentType) {
                'video' => 'Sent you a video',
                'audio' => 'Sent you a voice message',
                default => 'Sent you a photo',
            },
            ['kind' => 'message', 'sender_id' => (string) $sender->id, 'sender_name' => $sender->name]
        );

        return response()->json(['message_data' => $message->load('sender', 'recipient')], 201);
    }

    /** POST /messages/typing — "I am typing to this member" (lasts a few seconds). */
    public function typing(Request $request)
    {
        $validated = $request->validate(['recipient_id' => 'required|exists:users,id']);

        Cache::put("typing:{$request->user()->id}:{$validated['recipient_id']}", true, 8);

        return response()->json(['ok' => true]);
    }

    /** DELETE /messages/{message} — you can remove a message you sent. */
    public function destroy(Request $request, Message $message)
    {
        abort_unless($message->sender_id === $request->user()->id, 403, 'You can only delete your own messages');

        if ($message->attachment_path) {
            Storage::disk('public')->delete($message->attachment_path);
        }
        $message->delete();

        return response()->json(['message' => 'Message deleted']);
    }

    /** POST /messages/conversations/{user}/archive — hide the chat from the main list. */
    public function archive(Request $request, User $user)
    {
        ConversationState::forPair($request->user()->id, $user->id)->update(['archived_at' => now()]);

        return response()->json(['message' => 'Chat archived']);
    }

    /** DELETE /messages/conversations/{user}/archive — move it back. */
    public function unarchive(Request $request, User $user)
    {
        ConversationState::forPair($request->user()->id, $user->id)->update(['archived_at' => null]);

        return response()->json(['message' => 'Chat restored']);
    }

    /**
     * DELETE /messages/conversations/{user} — "delete chat" for ME only.
     * The other member keeps their copy; nothing is removed from the database.
     * The chat reappears, with only newer messages, if either of you writes again.
     */
    public function clear(Request $request, User $user)
    {
        ConversationState::forPair($request->user()->id, $user->id)->update(['cleared_at' => now(), 'archived_at' => null]);

        return response()->json(['message' => 'Chat deleted']);
    }
}
