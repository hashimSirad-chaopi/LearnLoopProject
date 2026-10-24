<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\ExchangeLesson;
use App\Models\ExchangeLessonAttempt;
use App\Models\Lesson;
use App\Models\LessonConfirmation;
use App\Models\LessonDeadline;
use App\Models\Notification;
use App\Models\Skill;
use App\Models\UserLessonAttempt;
use App\Support\QuestionMedia;
use App\Support\TeachingLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Learning progress, as the Flutter app tracks it:
 *  - quiz attempts (a pass needs 70% of the answers, same as the app),
 *  - which catalog lessons the member has passed (their "mastered" set),
 *  - per-exchange progress for both directions of an exchange,
 *  - coach confirmation, and the private lessons a coach writes for one exchange.
 */
class ProgressController extends Controller
{
    private static function passMark(int $total): int
    {
        return TeachingLevels::passMark($total);
    }

    /** GET /api/progress — every catalog lesson id this member has passed. */
    public function mine(Request $request)
    {
        $ids = UserLessonAttempt::where('user_id', $request->user()->id)
            ->where('passed', true)
            ->distinct()
            ->pluck('lesson_id')
            ->values();

        // When each lesson was first passed (the "Mastered" list in Profile shows this date).
        $mastered = UserLessonAttempt::where('user_id', $request->user()->id)
            ->where('passed', true)
            ->selectRaw('lesson_id, MIN(COALESCE(attempted_at, created_at)) as mastered_at')
            ->groupBy('lesson_id')
            ->get()
            ->map(fn ($row) => [
                'lesson_id' => $row->lesson_id,
                'mastered_at' => $row->mastered_at ? \Illuminate\Support\Carbon::parse($row->mastered_at)->toIso8601String() : null,
            ])
            ->values();

        // Continue Learning: per skill, per level progress and the level this member has EARNED
        // (the highest level they may teach). See App\Support\TeachingLevels.
        return response()->json([
            'passed_lesson_ids' => $ids,
            'mastered' => $mastered,
            'skills' => TeachingLevels::all($request->user()->id),
        ]);
    }

    /**
     * POST /api/lessons/{lesson}/attempts  { answers: [chosen option index per question, in order] }
     *
     * The SERVER grades the quiz (the answers are never sent to the app), so a teaching level can
     * only be earned by really passing. Lessons are taken in order: pass the ones before it first.
     * Answers: { attempt, passed, score, total, correct: [right option index per question] }.
     */
    public function attempt(Request $request, Lesson $lesson)
    {
        if ($lesson->status !== 'published') {
            return response()->json(['message' => 'This lesson is not open'], 422);
        }

        $questions = $lesson->questions()->with('options')->get();
        $total = $questions->count();
        if ($total === 0) {
            return response()->json(['message' => 'This lesson has no quiz'], 422);
        }

        $validator = Validator::make($request->all(), [
            'answers' => ['required', 'array', 'size:'.$total],
            'answers.*' => ['required', 'integer', 'min:0', 'max:9'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Answer every question before you finish', 'errors' => $validator->errors()], 422);
        }

        $me = $request->user()->id;
        if (! TeachingLevels::isLessonUnlocked($me, $lesson)) {
            return response()->json(['message' => 'Pass the lessons before this one first. Levels are earned in order.'], 403);
        }

        $skillId = $lesson->skillLevel?->skill_id;
        $before = $skillId ? TeachingLevels::earned($me, $skillId) : null;

        [$score, $correct] = self::grade($questions, array_values($request->answers));

        $attempt = UserLessonAttempt::create([
            'user_id' => $me,
            'lesson_id' => $lesson->id,
            'score' => $score,
            'total_questions' => $total,
            'passed' => $score >= TeachingLevels::passMark($total),
            'attempted_at' => now(),
        ]);

        $earned = $before;
        if ($attempt->passed && $skillId) {
            $earned = TeachingLevels::earned($me, $skillId);
            TeachingLevels::syncOffered($me, $skillId);
            if ($earned !== $before && $earned !== null) {
                $title = $lesson->skillLevel->skill->title;
                Notification::notifyFromMember(
                    $me, 'level_earned', 'New teaching level',
                    'You finished the '.ucfirst($earned)." lessons of {$title}. You can now teach it on ".ucfirst($earned).'.',
                    'skill', $skillId
                );
            }
        }

        return response()->json([
            'attempt' => $attempt,
            'passed' => (bool) $attempt->passed,
            'score' => $score,
            'total' => $total,
            'correct' => $correct,
            'earned_level' => $earned,
            'level_up' => $attempt->passed && $earned !== $before,
        ], 201);
    }

    /**
     * Grade a quiz. $answers[i] is the option index picked for question i.
     *
     * @return array{0:int,1:list<int>} [score, right option index per question]
     */
    public static function grade($questions, array $answers): array
    {
        $score = 0;
        $correct = [];
        foreach ($questions->values() as $i => $question) {
            $right = $question->options->values()->search(fn ($o) => $o->is_correct);
            $right = $right === false ? -1 : (int) $right;
            $correct[] = $right;
            if (isset($answers[$i]) && (int) $answers[$i] === $right) {
                $score++;
            }
        }

        return [$score, $correct];
    }

    /**
     * GET /api/exchanges/{exchange}/progress
     * Two tracks (one per direction) plus the private lessons coaches wrote.
     * Only lessons that have any activity are listed; the app treats the rest as untouched.
     */
    public function exchange(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        $deadlines = LessonDeadline::where('exchange_id', $exchange->id)->get();
        $catalogDue = $deadlines->whereNotNull('lesson_id')->keyBy('lesson_id');
        $extraDue = $deadlines->whereNotNull('exchange_lesson_id')->keyBy('exchange_lesson_id');

        $tracks = [];
        foreach ($this->tracks($exchange) as $track) {
            $skill = Skill::where('title', $track['skill'])->first();
            $lessonIds = $skill
                ? Lesson::whereIn('skill_level_id', $skill->levels()->pluck('id'))->pluck('id')
                : collect();

            $attempts = UserLessonAttempt::where('user_id', $track['learner_id'])
                ->whereIn('lesson_id', $lessonIds)
                ->get()
                ->groupBy('lesson_id');

            $confirmed = LessonConfirmation::where('exchange_id', $exchange->id)
                ->where('learner_id', $track['learner_id'])
                ->whereNotNull('lesson_id')
                ->pluck('lesson_id')
                ->all();

            $rows = [];
            // Exchanges only hold the lessons the two members add for each other (extra lessons).
            // The admin's catalog lessons are studied in Continue Learning, so none are listed here.
            foreach ([] as $lessonId) {
                $list = $attempts->get($lessonId, collect());
                $isConfirmed = in_array($lessonId, $confirmed, true);
                $due = $catalogDue->get($lessonId)?->due_at;
                if ($list->isEmpty() && ! $isConfirmed && ! $due) {
                    continue;
                }
                $rows[] = [
                    'lesson_id' => $lessonId,
                    'due_at' => $due?->toIso8601String(),
                    'attempts' => $list->count(),
                    'best_score' => (int) $list->max('score'),
                    'total' => (int) ($list->last()->total_questions ?? 0),
                    'passed' => $list->contains('passed', true),
                    'confirmed' => $isConfirmed,
                ];
            }

            $tracks[] = ['learner_id' => $track['learner_id'], 'coach_id' => $track['coach_id'], 'skill' => $track['skill'], 'lessons' => $rows];
        }

        $extra = $exchange->exchangeLessons()->with('attempts')->get()->map(function (ExchangeLesson $lesson) use ($exchange, $extraDue) {
            $learnerId = $lesson->author_id === $exchange->provider_id ? $exchange->learner_id : $exchange->provider_id;
            $list = $lesson->attempts->where('user_id', $learnerId);

            return [
                'id' => $lesson->id,
                'author_id' => $lesson->author_id,
                'learner_id' => $learnerId,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'content_type' => $lesson->content_type ?: 'text',
                'attachment_url' => $lesson->attachment_url,
                'attachment_name' => $lesson->attachment_name,
                'due_at' => $extraDue->get($lesson->id)?->due_at?->toIso8601String(),
                'questions' => collect($lesson->questions ?? [])->map(function ($q) {
                    $path = $q['media_path'] ?? null;
                    unset($q['media_path']);

                    return $q + QuestionMedia::payload($q['media_type'] ?? null, $path);
                })->values(),
                'attempts' => $list->count(),
                'best_score' => (int) $list->max('score'),
                'total' => (int) ($list->last()->total_questions ?? 0),
                'passed' => $list->contains('passed', true),
                'confirmed' => LessonConfirmation::where('exchange_id', $exchange->id)
                    ->where('exchange_lesson_id', $lesson->id)->exists(),
            ];
        })->values();

        return response()->json(['tracks' => $tracks, 'extra_lessons' => $extra]);
    }

    /**
     * POST /api/exchanges/{exchange}/lessons/{lesson}/confirm
     * The COACH of this lesson's track confirms the learner can do it. Needs the
     * learner's passed quiz, and the coach must have passed the lesson too.
     */
    public function confirm(Request $request, Exchange $exchange, Lesson $lesson)
    {
        return response()->json(['message' => 'Exchange lessons are the ones you and your partner add (photo, video, audio or file).'], 422);

        $this->authorizeParty($request, $exchange);
        $me = $request->user()->id;

        $track = $this->trackForLesson($exchange, $lesson);
        if (! $track) {
            return response()->json(['message' => 'This lesson is not part of this exchange'], 422);
        }
        if ($track['coach_id'] !== $me) {
            return response()->json(['message' => 'Only the coach for this lesson can confirm it'], 403);
        }
        if ($exchange->status !== 'ongoing') {
            return response()->json(['message' => 'The exchange is not ongoing'], 422);
        }
        if (! UserLessonAttempt::where('user_id', $me)->where('lesson_id', $lesson->id)->where('passed', true)->exists()) {
            return response()->json(['message' => 'Pass this lesson\'s quiz yourself before you confirm it'], 403);
        }
        if (! UserLessonAttempt::where('user_id', $track['learner_id'])->where('lesson_id', $lesson->id)->where('passed', true)->exists()) {
            return response()->json(['message' => 'Your partner has not passed the quiz yet'], 422);
        }

        LessonConfirmation::firstOrCreate(
            ['exchange_id' => $exchange->id, 'lesson_id' => $lesson->id, 'learner_id' => $track['learner_id']],
            ['coach_id' => $me]
        );

        $this->tellLearner($request, $track['learner_id'], $lesson->title, $exchange);

        return response()->json(['confirmed' => true]);
    }

    // ------------------------------------------------------------------
    // Private lessons a coach writes inside ONE exchange
    // ------------------------------------------------------------------

    /** POST /api/exchanges/{exchange}/extra-lessons — only a member who teaches in this exchange. */
    public function storeExtra(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        if (! in_array($exchange->status, ['ongoing', 'pending'], true)) {
            return response()->json(['message' => 'You can only add lessons to a live exchange'], 422);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            // text = the written lesson; file | audio | video | photo = an upload that follows
            // (POST .../extra-lessons/{id}/attachment), with `content` as an optional note.
            'content_type' => 'required|in:photo,video,audio,file',
            'content' => 'nullable|string',
            'due_at' => 'nullable|date|after:now',
            'questions' => 'required|array|min:1|max:10',
            'questions.*.text' => 'required|string|max:500',
            'questions.*.options' => 'required|array|size:4',
            'questions.*.options.*' => 'required|string|max:255',
            'questions.*.correct' => 'required|integer|between:0,3',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $type = $request->input('content_type');
        $lesson = $exchange->exchangeLessons()->create([
            'author_id' => $request->user()->id,
            'title' => $request->title,
            'content' => (string) $request->content,
            'content_type' => $type,
            'questions' => array_values(array_map(fn ($q) => [
                'text' => $q['text'],
                'options' => array_values($q['options']),
                'correct' => (int) $q['correct'],
            ], $request->questions)),
        ]);

        if ($request->filled('due_at')) {
            LessonDeadline::create([
                'exchange_id' => $exchange->id,
                'exchange_lesson_id' => $lesson->id,
                'set_by' => $request->user()->id,
                'due_at' => $request->due_at,
            ]);
        }

        // A lesson that carries a file tells the learner once the file is attached; a text lesson right now.
        if ($type === 'text') {
            $this->tellLearnerAboutLesson($request, $exchange, $lesson);
        }

        return response()->json(['lesson' => $lesson->fresh()], 201);
    }

    /** DELETE — the author, and only before the learner has tried it (as in the app). */
    public function destroyExtra(Request $request, Exchange $exchange, ExchangeLesson $exchangeLesson)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchangeLesson->exchange_id !== $exchange->id || $exchangeLesson->author_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only delete your own lessons'], 403);
        }
        if ($exchangeLesson->attempts()->exists()) {
            return response()->json(['message' => 'Your partner has already started this lesson'], 422);
        }

        if ($exchangeLesson->attachment_path) {
            Storage::disk('public')->delete($exchangeLesson->attachment_path);
        }
        $exchangeLesson->delete();

        return response()->json(['message' => 'Lesson deleted']);
    }

    /** POST .../extra-lessons/{id}/attempts { score } — the learner (not the author) takes the quiz. */
    public function attemptExtra(Request $request, Exchange $exchange, ExchangeLesson $exchangeLesson)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchangeLesson->exchange_id !== $exchange->id || $exchangeLesson->author_id === $request->user()->id) {
            return response()->json(['message' => 'Only your partner can take this quiz'], 403);
        }

        $validator = Validator::make($request->all(), ['score' => 'required|integer|min:0']);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $total = count($exchangeLesson->questions ?? []);
        $score = min((int) $request->score, $total);

        $attempt = ExchangeLessonAttempt::create([
            'exchange_lesson_id' => $exchangeLesson->id,
            'user_id' => $request->user()->id,
            'score' => $score,
            'total_questions' => $total,
            'passed' => $total > 0 && $score >= self::passMark($total),
        ]);

        return response()->json(['attempt' => $attempt], 201);
    }

    /** POST .../extra-lessons/{id}/confirm — the author confirms the learner passed it. */
    public function confirmExtra(Request $request, Exchange $exchange, ExchangeLesson $exchangeLesson)
    {
        $this->authorizeParty($request, $exchange);
        $me = $request->user()->id;

        if ($exchangeLesson->exchange_id !== $exchange->id || $exchangeLesson->author_id !== $me) {
            return response()->json(['message' => 'Only the coach who wrote this lesson can confirm it'], 403);
        }

        $learnerId = $me === $exchange->provider_id ? $exchange->learner_id : $exchange->provider_id;

        if (! $exchangeLesson->attempts()->where('user_id', $learnerId)->where('passed', true)->exists()) {
            return response()->json(['message' => 'Your partner has not passed the quiz yet'], 422);
        }

        LessonConfirmation::firstOrCreate(
            ['exchange_id' => $exchange->id, 'exchange_lesson_id' => $exchangeLesson->id, 'learner_id' => $learnerId],
            ['coach_id' => $me]
        );

        $this->tellLearner($request, $learnerId, $exchangeLesson->title, $exchange);

        return response()->json(['confirmed' => true]);
    }

    /**
     * POST /api/exchanges/{exchange}/extra-lessons/{exchangeLesson}/attachment   multipart: file
     * The coach uploads the lesson's file / audio / video / photo (what its content_type says).
     */
    public function attachExtra(Request $request, Exchange $exchange, ExchangeLesson $exchangeLesson)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchangeLesson->exchange_id !== $exchange->id || $exchangeLesson->author_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only attach files to your own lessons'], 403);
        }
        if (($exchangeLesson->content_type ?: 'text') === 'text') {
            return response()->json(['message' => 'This is a text lesson. Choose file, audio, video or photo to attach something.'], 422);
        }

        $validator = Validator::make($request->all(), ['file' => self::attachmentRules($exchangeLesson->content_type)]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($exchangeLesson->attachment_path) {
            Storage::disk('public')->delete($exchangeLesson->attachment_path);
        }
        $file = $request->file('file');
        $exchangeLesson->update([
            'attachment_path' => $file->store('exchange-lessons', 'public'),
            'attachment_name' => mb_substr($file->getClientOriginalName(), 0, 255),
        ]);

        $this->tellLearnerAboutLesson($request, $exchange, $exchangeLesson);

        return response()->json(['lesson' => $exchangeLesson->fresh()]);
    }

    /**
     * POST /api/exchanges/{exchange}/extra-lessons/{id}/question-media   multipart: index, type, file
     * Puts a photo, video or audio clip above one quiz question of the author's own lesson.
     */
    public function attachQuestionMedia(Request $request, Exchange $exchange, ExchangeLesson $exchangeLesson)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchangeLesson->exchange_id !== $exchange->id || $exchangeLesson->author_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only change your own lessons'], 403);
        }

        $type = (string) $request->input('type');
        $validator = Validator::make($request->all(), [
            'index' => 'required|integer|min:0',
            'type' => 'required|in:'.implode(',', QuestionMedia::TYPES),
            'file' => array_merge(['required'], QuestionMedia::rules($type)),
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $questions = $exchangeLesson->questions ?? [];
        $i = (int) $request->index;
        if (! isset($questions[$i])) {
            return response()->json(['message' => 'That question does not exist'], 422);
        }

        if (! empty($questions[$i]['media_path'])) {
            Storage::disk('public')->delete($questions[$i]['media_path']);
        }
        $questions[$i]['media_type'] = $type;
        $questions[$i]['media_path'] = $request->file('file')->store('question-media', 'public');
        $exchangeLesson->update(['questions' => $questions]);

        return response()->json(['ok' => true, 'media_type' => $type, 'media_url' => QuestionMedia::url($questions[$i]['media_path'])]);
    }

    /** Upload rules per lesson type (shared with the admin's lesson form). */
    public static function attachmentRules(?string $type): array
    {
        return match ($type) {
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2097152'],
            'audio' => ['required', 'file', 'mimes:mp3,m4a,aac,wav,ogg,oga', 'max:2097152'],
            'video' => ['required', 'file', 'mimes:mp4,mov,webm,m4v', 'max:2097152'],
            default => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,csv', 'max:2097152'],
        };
    }

    /**
     * PUT /api/exchanges/{exchange}/deadlines   { lesson_id | extra_lesson_id, due_at | null }
     * The COACH of a lesson puts a deadline on it (or clears it with due_at = null), like a module.
     * The learner is told, and the app shows "Due ..." / "Overdue" on the lesson.
     */
    public function setDeadline(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);
        $me = $request->user()->id;

        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required_without:extra_lesson_id|nullable|integer|exists:lessons,id',
            'extra_lesson_id' => 'required_without:lesson_id|nullable|integer|exists:exchange_lessons,id',
            'due_at' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        if (! in_array($exchange->status, ['pending', 'ongoing'], true)) {
            return response()->json(['message' => 'Deadlines can only be set on a live exchange'], 422);
        }
        if ($request->filled('due_at') && \Illuminate\Support\Carbon::parse($request->due_at)->isPast()) {
            return response()->json(['message' => 'Pick a deadline in the future'], 422);
        }

        if ($request->filled('extra_lesson_id')) {
            $lesson = ExchangeLesson::find($request->extra_lesson_id);
            if (! $lesson || $lesson->exchange_id !== $exchange->id) {
                return response()->json(['message' => 'This lesson is not part of this exchange'], 422);
            }
            $coachId = $lesson->author_id;
            $learnerId = $coachId === $exchange->provider_id ? $exchange->learner_id : $exchange->provider_id;
            $where = ['exchange_id' => $exchange->id, 'exchange_lesson_id' => $lesson->id];
            $title = $lesson->title;
        } else {
            return response()->json(['message' => 'Deadlines go on the lessons you add in an exchange.'], 422);
        }

        if ($coachId !== $me) {
            return response()->json(['message' => 'Only the coach for this lesson can set its deadline'], 403);
        }

        if (! $request->filled('due_at')) {
            LessonDeadline::where($where)->delete();

            return response()->json(['due_at' => null]);
        }

        $deadline = LessonDeadline::updateOrCreate($where, ['set_by' => $me, 'due_at' => $request->due_at]);

        Notification::notifyFromMember(
            $learnerId, 'lesson_deadline', 'Lesson deadline',
            "{$request->user()->name} set a deadline for \"{$title}\": ".$deadline->due_at->format('M j, Y g:i A').'.',
            'exchange', $exchange->id
        );

        return response()->json(['due_at' => $deadline->due_at->toIso8601String()]);
    }

    private function tellLearnerAboutLesson(Request $request, Exchange $exchange, ExchangeLesson $lesson): void
    {
        $learnerId = $request->user()->id === $exchange->provider_id ? $exchange->learner_id : $exchange->provider_id;
        Notification::notifyFromMember($learnerId, 'lesson_added', 'New lesson from your coach', "{$request->user()->name} added \"{$lesson->title}\" to your exchange.", 'exchange', $exchange->id);
    }

    // ------------------------------------------------------------------

    /**
     * The two directions of an exchange. provider teaches skill_offered to the
     * learner; the learner teaches skill_wanted back (when there is one).
     */
    private function tracks(Exchange $exchange): array
    {
        $tracks = [];
        if ($exchange->skill_offered) {
            $tracks[] = ['skill' => $exchange->skill_offered, 'learner_id' => $exchange->learner_id, 'coach_id' => $exchange->provider_id];
        }
        if ($exchange->skill_wanted) {
            $tracks[] = ['skill' => $exchange->skill_wanted, 'learner_id' => $exchange->provider_id, 'coach_id' => $exchange->learner_id];
        }

        return $tracks;
    }

    private function trackForLesson(Exchange $exchange, Lesson $lesson): ?array
    {
        $skillTitle = $lesson->skillLevel?->skill?->title;

        foreach ($this->tracks($exchange) as $track) {
            if ($track['skill'] === $skillTitle) {
                return $track;
            }
        }

        return null;
    }

    private function authorizeParty(Request $request, Exchange $exchange): void
    {
        $id = $request->user()->id;
        abort_unless($exchange->provider_id === $id || $exchange->learner_id === $id, 403, 'This is not your exchange');
    }

    private function tellLearner(Request $request, int $learnerId, string $lessonTitle, Exchange $exchange): void
    {
        Notification::notifyFromMember(
            $learnerId,
            'lesson_confirmed',
            'Lesson confirmed',
            "{$request->user()->name} confirmed you completed \"{$lessonTitle}\".",
            'exchange',
            $exchange->id
        );
    }
}
