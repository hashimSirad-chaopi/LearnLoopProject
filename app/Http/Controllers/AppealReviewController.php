<?php

namespace App\Http\Controllers;

use App\Models\AccountAppeal;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Admin > Recovery Requests: suspended members asking to get their account back. */
class AppealReviewController extends Controller
{
    public function index(Request $request)
    {
        User::liftExpiredSuspensions();

        $status = in_array($request->query('status'), ['pending', 'approved', 'declined'], true)
            ? $request->query('status')
            : 'pending';

        $counts = [
            'pending' => AccountAppeal::where('status', 'pending')->count(),
            'approved' => AccountAppeal::where('status', 'approved')->count(),
            'declined' => AccountAppeal::where('status', 'declined')->count(),
        ];

        $appeals = AccountAppeal::with(['user', 'reviewer'])
            ->where('status', $status)
            ->latest()
            ->get();

        // How many reports against each member an admin upheld (context for the decision).
        $upheld = Report::whereIn('reported_user_id', $appeals->pluck('user_id'))
            ->where('status', 'resolved')
            ->selectRaw('reported_user_id, count(*) as total')
            ->groupBy('reported_user_id')
            ->pluck('total', 'reported_user_id');

        return view('admin.appeals', compact('status', 'counts', 'appeals', 'upheld'));
    }

    public function approve(Request $request, AccountAppeal $appeal)
    {
        $data = $request->validate(['note' => 'nullable|string|max:500']);

        if ($appeal->status !== AccountAppeal::PENDING) {
            return back()->withErrors(['appeal' => 'This request was already answered.']);
        }

        $user = $appeal->user;

        DB::transaction(function () use ($appeal, $user, $data) {
            $user->status = 'active';
            \App\Support\Suspension::clear($user);
            $user->save();

            $appeal->update([
                'status' => AccountAppeal::APPROVED,
                'admin_note' => $data['note'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });

        ActivityLog::record('user.activated', "Reactivated {$user->name} after a recovery request");

        $note = trim((string) ($data['note'] ?? ''));
        Notification::notifyMember(
            $user->id,
            'Account reactivated',
            'An administrator reviewed your recovery request and reactivated your account. You can log in again.'.($note !== '' ? " Note: {$note}" : ''),
            'user',
            $user->id
        );

        return back()->with('success', "{$user->name}'s account was reactivated.");
    }

    public function decline(Request $request, AccountAppeal $appeal)
    {
        $data = $request->validate(['note' => 'required|string|max:500']);

        if ($appeal->status !== AccountAppeal::PENDING) {
            return back()->withErrors(['appeal' => 'This request was already answered.']);
        }

        $appeal->update([
            'status' => AccountAppeal::DECLINED,
            'admin_note' => trim($data['note']),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $user = $appeal->user;

        ActivityLog::record('user.appeal_declined', "Declined the recovery request from {$user->name}");

        Notification::notifyMember(
            $user->id,
            'Recovery request declined',
            'An administrator reviewed your recovery request and kept your account suspended. Reason: '.trim($data['note']),
            'user',
            $user->id
        );

        return back()->with('success', "The request from {$user->name} was declined.");
    }
}
