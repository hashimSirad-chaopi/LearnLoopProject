<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Settings tab: password, email, blocked members and signing out other phones.
 * Everything that changes the login asks for the current password first.
 */
class AccountController extends Controller
{
    /** POST /api/account/password */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|max:255|confirmed|different:current_password',
        ], [
            'password.different' => 'Your new password must be different from the current one.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->wrongPassword();
        }

        // The `hashed` cast on User hashes this.
        $user->update(['password' => $request->password]);

        // Every other phone / browser signed in as this member has to log in again.
        $this->revokeOtherTokens($request);

        return response()->json(['message' => 'Password changed. Other devices were signed out.']);
    }

    /** PUT /api/account/email */
    public function changeEmail(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->wrongPassword();
        }

        $user->update(['email' => strtolower(trim($request->email))]);

        return response()->json(['message' => 'Email updated', 'user' => $user->fresh()]);
    }

    /** GET /api/blocked-users — the members this member blocked. */
    public function blocked(Request $request)
    {
        $rows = UserBlock::where('blocker_id', $request->user()->id)
            ->with('blockedUser:id,name,avatar_path')
            ->latest()
            ->get()
            ->filter(fn ($block) => $block->blockedUser !== null)
            ->map(fn ($block) => [
                'id' => $block->blockedUser->id,
                'name' => $block->blockedUser->name,
                'avatar_url' => $block->blockedUser->avatar_url,
                'blocked_at' => $block->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json(['blocked' => $rows]);
    }

    /** POST /api/account/logout-others — keep this phone, sign out every other one. */
    public function logoutOthers(Request $request)
    {
        $count = $this->revokeOtherTokens($request);

        return response()->json([
            'message' => $count === 0 ? 'No other devices were signed in.' : "Signed out of {$count} other ".($count === 1 ? 'device.' : 'devices.'),
            'signed_out' => $count,
        ]);
    }

    private function revokeOtherTokens(Request $request): int
    {
        $current = $request->user()->currentAccessToken();

        return $request->user()->tokens()
            ->when($current && isset($current->id), fn ($q) => $q->where('id', '!=', $current->id))
            ->delete();
    }

    /** 422 (not 401) so the app shows "wrong password" instead of treating it as an ended session. */
    private function wrongPassword()
    {
        return response()->json([
            'message' => 'Your current password is not correct.',
            'errors' => ['current_password' => ['Your current password is not correct.']],
        ], 422);
    }
}
