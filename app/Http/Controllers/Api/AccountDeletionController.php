<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConversationState;
use App\Models\DeviceToken;
use App\Models\Exchange;
use App\Models\Listing;
use App\Models\ListingComment;
use App\Models\ListingLike;
use App\Models\Message;
use App\Models\Notification;
use App\Models\UserBlock;
use App\Models\UserSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * POST /api/account/delete  — a member deletes their own account (Settings > Delete account).
 *
 * Members only: an administrator can never use this route (the member API already refuses
 * admin tokens, and this checks the role again).
 *
 * What happens:
 *  - refused while an exchange is still pending, ongoing or disputed (finish or cancel it first);
 *  - the member's listings, comments, likes, chats, notifications, blocks, skills, phones and
 *    sign-ins are removed;
 *  - the account row itself is kept but emptied ("Deleted member", status "deleted", an unusable
 *    email and password) so finished exchanges and ratings other members rely on stay intact,
 *    and the same email address can be used to register again.
 */
class AccountDeletionController extends Controller
{
    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return response()->json(['message' => 'Administrator accounts cannot be deleted from the app.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'confirm' => 'required|in:DELETE',
        ], [
            'confirm.in' => 'Type DELETE to confirm.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // 422, not 401, so the app shows "wrong password" instead of treating it as an ended session.
        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Your current password is not correct.',
                'errors' => ['current_password' => ['Your current password is not correct.']],
            ], 422);
        }

        $open = Exchange::whereIn('status', ['pending', 'ongoing', 'disputed'])
            ->where(fn ($q) => $q->where('provider_id', $user->id)->orWhere('learner_id', $user->id))
            ->count();

        if ($open > 0) {
            return response()->json([
                'message' => 'You still have '.$open.' open '.Str::plural('exchange', $open).'. Finish or cancel '.($open === 1 ? 'it' : 'them').' first, then delete your account.',
                'code' => 'open_exchanges',
            ], 422);
        }

        $files = [];

        DB::transaction(function () use ($user, &$files) {
            $id = $user->id;

            // Files to remove from disk once the rows are gone.
            foreach (Message::where(fn ($q) => $q->where('sender_id', $id)->orWhere('recipient_id', $id))->whereNotNull('attachment_path')->pluck('attachment_path') as $path) {
                $files[] = $path;
            }
            foreach ([$user->avatar_path, $user->banner_path] as $path) {
                if ($path) {
                    $files[] = $path;
                }
            }

            // Their posts (comments and likes on them go with the listing), then their own likes/comments elsewhere.
            Listing::where('user_id', $id)->get()->each->delete();
            ListingLike::where('user_id', $id)->delete();
            ListingComment::where('user_id', $id)->delete();

            Message::where('sender_id', $id)->orWhere('recipient_id', $id)->delete();
            ConversationState::where('user_id', $id)->orWhere('other_user_id', $id)->delete();
            UserBlock::where('blocker_id', $id)->orWhere('blocked_id', $id)->delete();
            UserSkill::where('user_id', $id)->delete();
            Notification::where('user_id', $id)->delete();
            DeviceToken::where('user_id', $id)->delete();
            $user->tokens()->delete();

            // Empty the account but keep the row (see the class comment).
            $user->forceFill([
                'name' => 'Deleted user',
                'email' => 'deleted-'.$id.'-'.Str::lower(Str::random(6)).'@deleted.invalid',
                'password' => Str::random(48),
                'status' => 'deleted',
                'bio' => null,
                'avatar_path' => null,
                'banner_path' => null,
                'remember_token' => null,
            ])->save();
        });

        foreach (array_unique($files) as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                // A missing file must not undo a finished deletion.
            }
        }

        return response()->json(['message' => 'Your account has been deleted.']);
    }
}
