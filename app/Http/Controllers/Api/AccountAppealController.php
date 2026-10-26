<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountAppeal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * "Recover my account", sent from the login screen by a SUSPENDED member.
 *
 * It has to work without a login token (the member cannot get one), so the
 * email and password are checked instead. That also stops anyone from filing
 * requests for somebody else's account. The route is throttled.
 */
class AccountAppealController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'message' => 'required|string|min:10|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Administrator accounts cannot use the app.',
                'code' => 'admin_not_allowed',
            ], 403);
        }

        $user->liftSuspensionIfOver();

        if ($user->status === 'active') {
            return response()->json(['message' => 'Your account is not suspended. You can log in normally.'], 422);
        }

        if (AccountAppeal::where('user_id', $user->id)->where('status', AccountAppeal::PENDING)->exists()) {
            return response()->json(['message' => 'Your request is already waiting for an admin to review it.'], 409);
        }

        $recentlyDeclined = AccountAppeal::where('user_id', $user->id)
            ->where('status', AccountAppeal::DECLINED)
            ->where('reviewed_at', '>', now()->subHours(AccountAppeal::RETRY_AFTER_HOURS))
            ->exists();

        if ($recentlyDeclined) {
            return response()->json([
                'message' => 'Your last request was declined less than '.AccountAppeal::RETRY_AFTER_HOURS.' hours ago. Please try again later.',
            ], 429);
        }

        $appeal = AccountAppeal::create([
            'user_id' => $user->id,
            'message' => trim($request->message),
            'status' => AccountAppeal::PENDING,
        ]);

        \App\Support\AdminMail::newAppeal($appeal);

        return response()->json([
            'message' => 'Your request was sent. An admin will review it.',
            'appeal' => ['status' => $appeal->status],
        ], 201);
    }
}
