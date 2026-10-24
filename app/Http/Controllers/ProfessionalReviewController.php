<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\ProfessionalVerification;
use App\Models\UserSkill;
use App\Support\TeachingLevels;
use Illuminate\Http\Request;

/**
 * Admin: "Professional" requests. A member who already works with a skill sends proof and
 * passes the hard quiz in the app; an admin looks at the proof here and approves or rejects it.
 * Approving makes them Professional in that skill (they can teach it at every level).
 */
class ProfessionalReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'in_review');
        if (! in_array($status, ['in_review', 'approved', 'rejected', 'quiz', 'failed'], true)) {
            $status = 'in_review';
        }

        $counts = ProfessionalVerification::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $requests = ProfessionalVerification::with(['user', 'skill.category', 'reviewer'])
            ->where('status', $status)
            ->latest('updated_at')
            ->get();

        return view('admin.professional.index', compact('status', 'counts', 'requests'));
    }

    public function approve(Request $request, ProfessionalVerification $verification)
    {
        if ($verification->status !== 'in_review') {
            return back()->with('error', 'Only a request that passed the quiz can be approved.');
        }

        $verification->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        // They now teach this skill as a Professional: make sure it is on their profile.
        UserSkill::updateOrCreate(
            ['user_id' => $verification->user_id, 'skill_id' => $verification->skill_id, 'type' => 'offered'],
            ['level' => 'professional']
        );
        TeachingLevels::syncOffered($verification->user_id, $verification->skill_id);

        $skill = $verification->skill->title;
        Notification::notifyMember(
            $verification->user_id,
            'You are now a Professional',
            "Your proof for {$skill} was approved. You can teach {$skill} at every level.",
            'skill',
            $verification->skill_id
        );
        ActivityLog::record('professional.approved', "Approved {$verification->user->name} as Professional in {$skill}");

        return back()->with('success', "{$verification->user->name} is now a Professional in {$skill}.");
    }

    public function reject(Request $request, ProfessionalVerification $verification)
    {
        if (! in_array($verification->status, ['in_review', 'approved'], true)) {
            return back()->with('error', 'This request is not waiting for a decision.');
        }

        $data = $request->validate(['rejection_reason' => 'required|string|max:1000']);
        $wasApproved = $verification->status === 'approved';

        $verification->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        if ($wasApproved) {
            TeachingLevels::syncOffered($verification->user_id, $verification->skill_id); // back to the level they earned by lessons
        }

        $skill = $verification->skill->title;
        Notification::notifyMember(
            $verification->user_id,
            'Professional request declined',
            "Your proof for {$skill} was not accepted: {$data['rejection_reason']} You can send new proof any time.",
            'skill',
            $verification->skill_id
        );
        ActivityLog::record('professional.rejected', "Declined {$verification->user->name}'s Professional request for {$skill}");

        return back()->with('success', 'Request declined. The member was told why and can send new proof.');
    }
}
