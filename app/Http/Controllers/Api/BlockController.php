<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\Request;

/** Block / unblock a member: neither of you can message the other while a block exists. */
class BlockController extends Controller
{
    public function store(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, "You can't block yourself.");

        UserBlock::firstOrCreate(['blocker_id' => $request->user()->id, 'blocked_id' => $user->id]);

        return response()->json(['message' => 'User blocked']);
    }

    public function destroy(Request $request, User $user)
    {
        UserBlock::where('blocker_id', $request->user()->id)->where('blocked_id', $user->id)->delete();

        return response()->json(['message' => 'User unblocked']);
    }
}
