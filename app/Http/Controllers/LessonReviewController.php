<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Skill;
use App\Models\User;
use App\Support\AuthorTrust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Admin: review queue for lessons that members wrote (Profile -> My Lessons
 * in the app). A submission starts as 'pending'; only an admin publishes it
 * (unless the author is trusted in that skill, which the API decides).
 *
 * Approving sets approved_by: only hand-approved lessons count toward the
 * author's trusted status (3 approved + 5 ratings averaging 4.0 or better).
 */
class LessonReviewController extends Controller
{
    /**
     * Tabs: Users (default) | Pending | Published | Need change.
     * Users -> a member -> categories -> skill -> levels -> numbered lessons.
     * The other three tabs are flat lists of lessons by status.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'users');
        if (! in_array($status, ['users', 'pending', 'published', 'rejected', 'all'], true)) {
            $status = 'users';
        }

        $counts = [
            'pending' => Lesson::whereNotNull('author_id')->where('status', 'pending')->count(),
            'published' => Lesson::whereNotNull('author_id')->where('status', 'published')->count(),
            'rejected' => Lesson::whereNotNull('author_id')->where('status', 'rejected')->count(),
        ];

        if ($status === 'users') {
            $search = trim((string) $request->query('search', ''));

            // One row per author: how many lessons they have in each state.
            $stats = Lesson::whereNotNull('author_id')
                ->selectRaw("author_id, count(*) as total, sum(status = 'pending') as pending, sum(status = 'published') as published, sum(status = 'rejected') as rejected, max(created_at) as last_at")
                ->groupBy('author_id')
                ->get()
                ->keyBy('author_id');

            $members = User::whereIn('id', $stats->keys())
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->get()
                // Members with lessons waiting for you come first, then A-Z.
                ->sort(fn (User $a, User $b) => [-(int) $stats[$a->id]->pending, Str::lower($a->name)]
                    <=> [-(int) $stats[$b->id]->pending, Str::lower($b->name)])
                ->values();

            return view('admin.lessons.index', [
                'status' => $status,
                'counts' => $counts,
                'search' => $search,
                'members' => $members,
                'stats' => $stats,
                'lessons' => collect(),
            ]);
        }

        $search = trim((string) $request->query('search', ''));

        $lessons = Lesson::with(['author', 'skillLevel.skill'])
            ->whereNotNull('author_id')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('author', fn ($a) => $a->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('skillLevel.skill', fn ($s) => $s->where('title', 'like', "%{$search}%"))))
            ->orderByRaw("case status when 'pending' then 0 when 'rejected' then 1 else 2 end")
            ->latest()
            ->get();

        return view('admin.lessons.index', [
            'status' => $status,
            'counts' => $counts,
            'search' => $search,
            'members' => collect(),
            'stats' => collect(),
            'lessons' => $lessons,
        ]);
    }

    /** One member: their lessons grouped by category -> skill, with a category / skill / lesson search. */
    public function member(Request $request, User $user)
    {
        $all = Lesson::with('skillLevel.skill.category')->where('author_id', $user->id)->get();
        abort_if($all->isEmpty(), 404);

        $search = trim((string) $request->query('search', ''));
        $needle = Str::lower($search);

        $lessons = $needle === '' ? $all : $all->filter(function (Lesson $lesson) use ($needle) {
            $skill = $lesson->skillLevel->skill;

            return Str::contains(Str::lower(implode(' ', [$skill->category?->name, $skill->title, $lesson->title])), $needle);
        });

        $categories = $lessons
            ->groupBy(fn (Lesson $l) => $l->skillLevel->skill->category?->name ?? 'Uncategorized')
            ->sortKeys()
            ->map(fn ($inCategory) => $inCategory
                ->groupBy(fn (Lesson $l) => $l->skillLevel->skill_id)
                ->map(fn ($inSkill) => [
                    'skill' => $inSkill->first()->skillLevel->skill,
                    'total' => $inSkill->count(),
                    'pending' => $inSkill->where('status', 'pending')->count(),
                    'published' => $inSkill->where('status', 'published')->count(),
                    'rejected' => $inSkill->where('status', 'rejected')->count(),
                    // While searching, show which lesson titles matched.
                    'matches' => $needle === ''
                        ? collect()
                        : $inSkill->filter(fn (Lesson $l) => Str::contains(Str::lower($l->title), $needle))->pluck('title')->take(3),
                ])
                ->sortBy(fn ($row) => Str::lower($row['skill']->title))
                ->values());

        $totals = [
            'all' => $all->count(),
            'pending' => $all->where('status', 'pending')->count(),
            'published' => $all->where('status', 'published')->count(),
            'rejected' => $all->where('status', 'rejected')->count(),
        ];

        $totals['last'] = $all->max('created_at');

        return view('admin.lessons.member', compact('user', 'categories', 'search', 'totals'));
    }

    /** One member's lessons in one skill: level by level, numbered in the order the member arranged them. */
    public function memberSkill(User $user, Skill $skill)
    {
        $lessons = Lesson::with('skillLevel')
            ->withCount('ratings')
            ->withAvg('ratings', 'stars')
            ->where('author_id', $user->id)
            ->whereHas('skillLevel', fn ($q) => $q->where('skill_id', $skill->id))
            ->get()
            ->sort(fn (Lesson $a, Lesson $b) => [$a->skillLevel->order, $a->order, $a->id]
                <=> [$b->skillLevel->order, $b->order, $b->id])
            ->values();

        abort_if($lessons->isEmpty(), 404);

        $skill->load('category');
        $levels = $lessons->groupBy(fn (Lesson $l) => $l->skillLevel->name);

        return view('admin.lessons.member-skill', compact('user', 'skill', 'levels'));
    }

    /**
     * Move one of a member's lessons up or down inside its level (the admin's
     * version of the arrows in the app's My Lessons).
     *
     * The order column is shared with the admin's own lessons and other members'
     * lessons in the same level, so the member's lessons only swap among the
     * positions they already hold; nobody else's lessons are moved.
     * Order never changes the review status or ratings.
     */
    public function moveMemberLesson(Request $request, User $user, Skill $skill)
    {
        $data = $request->validate([
            'lesson_id' => 'required|integer',
            'direction' => 'required|in:up,down',
        ]);

        $lesson = Lesson::with('skillLevel')->where('author_id', $user->id)->findOrFail($data['lesson_id']);
        abort_unless((int) $lesson->skillLevel->skill_id === (int) $skill->id, 404);

        $mine = Lesson::where('skill_level_id', $lesson->skill_level_id)
            ->where('author_id', $user->id)
            ->orderBy('order')->orderBy('id')
            ->get();

        $ids = $mine->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $from = array_search((int) $lesson->id, $ids, true);
        $to = $data['direction'] === 'up' ? $from - 1 : $from + 1;

        if ($from !== false && isset($ids[$to])) {
            // The positions the member already holds, lowest first (kept strictly increasing).
            $slots = $mine->pluck('order')->map(fn ($n) => (int) $n)->values()->all();
            for ($i = 1; $i < count($slots); $i++) {
                if ($slots[$i] <= $slots[$i - 1]) {
                    $slots[$i] = $slots[$i - 1] + 1;
                }
            }

            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

            DB::transaction(function () use ($ids, $slots) {
                foreach ($ids as $i => $id) {
                    Lesson::whereKey($id)->update(['order' => $slots[$i]]);
                }
            });

            ActivityLog::record('lesson.reordered', "Moved lesson \"{$lesson->title}\" {$data['direction']} in {$skill->title} ({$user->name})");
        }

        // Back to the same row, so the page does not jump to the top.
        return redirect(route('admin.lessons.member.skill', [$user, $skill]).'#lesson-'.$lesson->id);
    }

    /**
     * Where "back" and the redirect after a decision should go: the list the admin
     * came from (Users, a member, a skill, or a status tab), else that member's skill page.
     */
    private function backFor(Request $request, Lesson $lesson): string
    {
        $candidate = (string) ($request->input('back') ?: url()->previous());
        $isLessonList = str_starts_with($candidate, url('/admin/lessons'))
            && ! preg_match('#/admin/lessons/\d+#', $candidate); // not a lesson page itself

        return $isLessonList
            ? $candidate
            : route('admin.lessons.member.skill', [$lesson->author_id, $lesson->skillLevel->skill_id]);
    }

    public function show(Request $request, Lesson $lesson)
    {
        abort_unless($lesson->isMemberWritten(), 404);

        $lesson->load(['author', 'approver', 'skillLevel.skill', 'questions.options', 'ratings']);

        $skill = $lesson->skillLevel->skill;
        $trust = AuthorTrust::statusFor($lesson->author_id, $skill->id);

        return view('admin.lessons.show', [
            'lesson' => $lesson,
            'skill' => $skill,
            'trust' => $trust,
            'ratingAverage' => $lesson->ratings->count() ? round($lesson->ratings->avg('stars'), 1) : null,
            'back' => $this->backFor($request, $lesson),
        ]);
    }

    public function approve(Request $request, Lesson $lesson)
    {
        abort_unless($lesson->isMemberWritten(), 404);

        $lesson->update([
            'status' => 'published',
            'approved_by' => auth()->id(),
            'rejection_reason' => null,
            'reviewed_at' => now(),
        ]);

        Notification::notifyMember(
            $lesson->author_id,
            'Lesson approved',
            "Your lesson \"{$lesson->title}\" was approved and is now visible to learners.",
            'lesson',
            $lesson->id
        );

        ActivityLog::record('lesson.approved', "Approved lesson \"{$lesson->title}\"");

        return redirect($this->backFor($request, $lesson))->with('success', 'Lesson approved and published.');
    }

    public function reject(Request $request, Lesson $lesson)
    {
        abort_unless($lesson->isMemberWritten(), 404);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ], [
            'rejection_reason.required' => 'Tell the author what to change.',
        ]);

        $lesson->update([
            'status' => 'rejected',
            'approved_by' => null,
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_at' => now(),
        ]);

        Notification::notifyMember(
            $lesson->author_id,
            'Lesson needs changes',
            "Your lesson \"{$lesson->title}\" needs changes: {$validated['rejection_reason']}",
            'lesson',
            $lesson->id
        );

        ActivityLog::record('lesson.rejected', "Asked for changes on lesson \"{$lesson->title}\"");

        return redirect($this->backFor($request, $lesson))->with('success', 'The author has been told what to change.');
    }
}
