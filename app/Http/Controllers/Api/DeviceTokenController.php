<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

/** The app registers its Firebase token after login so the server can push to this phone. */
class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:500',
            'platform' => 'nullable|string|max:20',
        ]);

        // A phone belongs to whoever is signed in on it now.
        DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            ['user_id' => $request->user()->id, 'platform' => $validated['platform'] ?? null]
        );

        return response()->json(['message' => 'Registered']);
    }

    /** POST /device-tokens/remove — on logout, so this phone stops getting that member's pushes. */
    public function destroy(Request $request)
    {
        $validated = $request->validate(['token' => 'required|string']);

        DeviceToken::where('token', $validated['token'])->where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Removed']);
    }
}
