<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSkill;
use App\Support\TeachingLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Matches the Flutter "Create Account" screen: username, email,
     * password, confirm password. `username` is stored as `name`.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status' => 'active',
        ]);

        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        // The administrator signs in to the web panel only, never to the member app.
        // (Checked after the password, so only someone who knows it learns this.)
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'Administrator accounts cannot sign in to the app. Please use the admin panel on the website.',
                'code' => 'admin_not_allowed',
            ], 403);
        }

        // A timed suspension that has run out ends right here, at the next login.
        $user->liftSuspensionIfOver();

        if ($user->status !== 'active') {
            // So the app can show "request sent / waiting" or "declined: reason" and offer to ask again.
            $appeal = \App\Models\AccountAppeal::where('user_id', $user->id)->latest()->first();

            return response()->json([
                'message' => 'This account is suspended.',
                'code' => 'account_suspended',
                'suspended_until' => $user->suspended_until?->toIso8601String(),   // null = until an admin reactivates it
                'suspension_reason' => \App\Support\Suspension::describe($user->suspension_reason, $user->suspension_note),
                'appeal' => $appeal ? ['status' => $appeal->status, 'admin_note' => $appeal->admin_note] : null,
            ], 403);
        }

        $token = $user->createToken('flutter-app')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    /** The app was sent to the background: show the member as offline right away. */
    public function goOffline(Request $request)
    {
        $now = now();
        \Illuminate\Support\Facades\DB::table('users')->where('id', $request->user()->id)
            ->update(['last_seen_at' => $now, 'offline_since' => $now]);

        return response()->json(['message' => 'Offline']);
    }

    public function logout(Request $request)
    {
        $userId = $request->user()->id;
        $request->user()->currentAccessToken()->delete();

        // Logged out = offline right away, not "active" for two more minutes.
        $now = now();
        \Illuminate\Support\Facades\DB::table('users')->where('id', $userId)
            ->update(['last_seen_at' => $now, 'offline_since' => $now]);

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            // `level` here is the level this member has EARNED, never one they typed in.
            'skills_offered' => TeachingLevels::offeredPayload($user),
            'skills_wanted' => $user->skillsWanted()->with('skill')->get(),
            'completed_exchanges' => $user->completedExchangesCount(),
            'average_rating' => round($user->average_rating, 1),
            'ratings_count' => $user->ratingsReceived()->count(),
        ]);
    }

    /**
     * Edit Profile: profile picture and banner. Multipart upload, field `image`.
     * Needs `php artisan storage:link` once so /storage/... is public.
     * POST /api/profile/avatar   POST /api/profile/banner
     */
    public function uploadAvatar(Request $request)
    {
        return $this->storePicture($request, 'avatar_path', 'avatars');
    }

    public function uploadBanner(Request $request)
    {
        return $this->storePicture($request, 'banner_path', 'banners');
    }

    private function storePicture(Request $request, string $column, string $folder)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // 5 MB
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if ($user->{$column}) {
            Storage::disk('public')->delete($user->{$column});
        }

        $user->update([$column => $request->file('image')->store($folder, 'public')]);

        return response()->json(['user' => $user->fresh()]);
    }

    /** Remove the picture again (the app falls back to initials / a plain banner). */
    public function deleteAvatar(Request $request)
    {
        return $this->removePicture($request, 'avatar_path');
    }

    public function deleteBanner(Request $request)
    {
        return $this->removePicture($request, 'banner_path');
    }

    private function removePicture(Request $request, string $column)
    {
        $user = $request->user();

        if ($user->{$column}) {
            Storage::disk('public')->delete($user->{$column});
            $user->update([$column => null]);
        }

        return response()->json(['user' => $user->fresh()]);
    }

    public function updateProfile(Request $request)
    {
        // Older app builds still send beginner / intermediate; map them first.
        if (is_array($request->input('skills_offered'))) {
            $request->merge(['skills_offered' => array_map(function ($row) {
                if (is_array($row) && isset($row['level'])) {
                    $row['level'] = UserSkill::LEGACY[$row['level']] ?? $row['level'];
                }
                return $row;
            }, $request->input('skills_offered'))]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'bio' => 'sometimes|nullable|string|max:1000',
            'availability' => 'sometimes|nullable|string|max:255',
            // Edit Profile: the skills you teach. The level is NOT chosen here: it is the level you
            // earned by passing lessons (or Professional after proof + hard quiz). Older app builds
            // still send a `level`; it is ignored.
            'skills_offered' => 'sometimes|array',
            'skills_offered.*.skill_id' => 'required|exists:skills,id',
            'skills_offered.*.level' => 'nullable|string',
            // ...and skills you want to learn (no level, you do not have them yet).
            'skills_wanted' => 'sometimes|array',
            'skills_wanted.*' => 'exists:skills,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // You may only ADD a skill to teach once you have earned at least its Easy level. Skills that
        // are already on your profile can stay (they simply cannot be taught until a level is earned).
        if ($request->has('skills_offered')) {
            $already = $user->skillsOffered()->pluck('skill_id')->all();
            $blocked = [];
            foreach ($request->skills_offered as $i => $row) {
                $skillId = (int) $row['skill_id'];
                if (! in_array($skillId, $already, true) && TeachingLevels::earned($user->id, $skillId) === null) {
                    $title = \App\Models\Skill::whereKey($skillId)->value('title');
                    $blocked["skills_offered.$i"] = ["Finish the Easy lessons of {$title} before you can teach it."];
                }
            }
            if ($blocked) {
                return response()->json(['message' => reset($blocked)[0], 'errors' => $blocked], 422);
            }
        }

        $user->update($request->only('name', 'bio', 'availability'));

        // Each list is sent whole, so replace what is stored.
        if ($request->has('skills_offered')) {
            $user->skillsOffered()->delete();
            foreach (collect($request->skills_offered)->pluck('skill_id')->unique() as $skillId) {
                $user->userSkills()->updateOrCreate(
                    ['skill_id' => $skillId, 'type' => 'offered'],
                    ['level' => TeachingLevels::earned($user->id, (int) $skillId)]
                );
            }
        }
        if ($request->has('skills_wanted')) {
            $user->skillsWanted()->delete();
            foreach (array_unique($request->skills_wanted) as $skillId) {
                $user->userSkills()->updateOrCreate(['skill_id' => $skillId, 'type' => 'wanted'], ['level' => null]);
            }
        }

        return response()->json([
            'user' => $user->fresh(),
            'skills_offered' => TeachingLevels::offeredPayload($user),
            'skills_wanted' => $user->skillsWanted()->with('skill')->get(),
        ]);
    }
}
