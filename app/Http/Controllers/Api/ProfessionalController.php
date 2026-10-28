<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfessionalVerification as Verification;
use App\Models\Question;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * "Professional" level (Needs_Improvement.docx): someone who already works with a skill does not
 * have to grind every lesson. They
 *   1. send PROOF (certificate, portfolio, work ID, ...),
 *   2. pass a HARD quiz (80%), drawn from the Hard + Expert lessons of the skill,
 *   3. and an admin looks at the proof and approves it.
 * Only then is the member Professional in that skill and can teach it at any level.
 *
 *   GET  /api/skills/{skill}/professional          where am I, and may I start?
 *   POST /api/skills/{skill}/professional          multipart: proof (+ note)  -> returns the quiz
 *   POST /api/skills/{skill}/professional/quiz     { answers: [...] }         -> graded here
 */
class ProfessionalController extends Controller
{
    public function show(Request $request, Skill $skill)
    {
        $record = Verification::where('user_id', $request->user()->id)->where('skill_id', $skill->id)->first();

        return response()->json($this->payload($skill, $record));
    }

    public function start(Request $request, Skill $skill)
    {
        if ($skill->status !== 'active') {
            return response()->json(['message' => 'This skill is not open'], 422);
        }

        $validator = Validator::make($request->all(), [
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'note' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $me = $request->user()->id;
        $record = Verification::where('user_id', $me)->where('skill_id', $skill->id)->first();

        if ($record) {
            if ($record->status === 'approved') {
                return response()->json(['message' => 'You are already Professional in '.$skill->title], 422);
            }
            if ($record->status === 'in_review') {
                return response()->json(['message' => 'Your proof is being reviewed. We will tell you when an admin has decided.'], 422);
            }
            if ($record->status === 'failed' && $record->retry_after?->isFuture()) {
                return response()->json(['message' => 'You can try the hard quiz again '.$record->retry_after->diffForHumans().'.'], 422);
            }
        }

        // Experience skill: no lessons, so no quiz. The certification goes straight to an admin.
        if ($skill->experience_based) {
            if ($record && $record->proof_path) {
                Storage::disk('public')->delete($record->proof_path);
            }
            $file = $request->file('proof');

            $record = Verification::updateOrCreate(
                ['user_id' => $me, 'skill_id' => $skill->id],
                [
                    'status' => 'in_review',
                    'proof_path' => $file->store('professional-proofs', 'public'),
                    'proof_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'proof_note' => $request->note,
                    'question_ids' => null,
                    'quiz_score' => null,
                    'quiz_total' => null,
                    'retry_after' => null,
                    'rejection_reason' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]
            );

            return response()->json($this->payload($skill, $record), 201);
        }

        $pool = $this->questionPool($skill);
        if ($pool->count() < Verification::MIN_QUESTIONS) {
            return response()->json(['message' => 'The Professional quiz for '.$skill->title.' is not ready yet. Please check back later.'], 422);
        }

        $ids = $pool->shuffle()->take(Verification::QUIZ_SIZE)->pluck('id')->values()->all();

        if ($record && $record->proof_path) {
            Storage::disk('public')->delete($record->proof_path);
        }
        $file = $request->file('proof');

        $record = Verification::updateOrCreate(
            ['user_id' => $me, 'skill_id' => $skill->id],
            [
                'status' => 'quiz',
                'proof_path' => $file->store('professional-proofs', 'public'),
                'proof_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'proof_note' => $request->note,
                'question_ids' => $ids,
                'quiz_score' => null,
                'quiz_total' => count($ids),
                'retry_after' => null,
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return response()->json($this->payload($skill, $record), 201);
    }

    public function quiz(Request $request, Skill $skill)
    {
        $record = Verification::where('user_id', $request->user()->id)->where('skill_id', $skill->id)->first();

        if (! $record || $record->status !== 'quiz') {
            return response()->json(['message' => 'Send your proof first, then take the quiz.'], 422);
        }

        $questions = $this->drawnQuestions($record);
        $total = $questions->count();
        if ($total === 0) {
            return response()->json(['message' => 'This quiz is no longer available. Please start again.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'answers' => ['required', 'array', 'size:'.$total],
            'answers.*' => ['required', 'integer', 'min:0', 'max:9'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Answer every question before you finish', 'errors' => $validator->errors()], 422);
        }

        [$score] = ProgressController::grade($questions, array_values($request->answers));
        $passed = $score >= Verification::passMark($total);

        $record->update([
            'status' => $passed ? 'in_review' : 'failed',
            'quiz_score' => $score,
            'quiz_total' => $total,
            'attempts' => $record->attempts + 1,
            'retry_after' => $passed ? null : now()->addHours(Verification::RETRY_HOURS),
        ]);

        // The right answers are NOT returned: the questions are redrawn on a retry.
        return response()->json(['passed' => $passed, 'score' => $score, 'total' => $total, 'pass_mark' => Verification::passMark($total)] + $this->payload($skill, $record->fresh()));
    }

    // ------------------------------------------------------------------

    /** Questions of the admin's published Hard + Expert lessons that have exactly one right answer. */
    private function questionPool(Skill $skill)
    {
        return Question::query()
            ->whereHas('lesson', fn ($l) => $l->where('status', 'published')->whereNull('author_id')
                ->whereHas('skillLevel', fn ($sl) => $sl->where('skill_id', $skill->id)->whereIn('name', ['hard', 'expert'])))
            ->with('options')
            ->get()
            ->filter(fn ($q) => $q->options->count() >= 2 && $q->options->where('is_correct', true)->count() === 1)
            ->values();
    }

    /** The questions drawn for this attempt, in the order the member sees them. */
    private function drawnQuestions(Verification $record)
    {
        $ids = $record->question_ids ?? [];
        $found = Question::with('options')->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $found->get($id))->filter()->values();
    }

    private function payload(Skill $skill, ?Verification $record): array
    {
        $experience = (bool) $skill->experience_based;
        $poolSize = $experience ? 0 : $this->questionPool($skill)->count();
        $status = $record?->status;

        $out = [
            'skill_id' => $skill->id,
            'title' => $skill->title,
            'status' => $status,                               // null | quiz | failed | in_review | approved | rejected
            'experience_based' => $experience,                 // true: certification only, no quiz
            'quiz_ready' => $experience || $poolSize >= Verification::MIN_QUESTIONS,
            'quiz_size' => min(Verification::QUIZ_SIZE, $poolSize),
            'pass_percent' => (int) (Verification::PASS_RATIO * 100),
            'can_start' => ($experience || $poolSize >= Verification::MIN_QUESTIONS)
                && (! $record || $status === 'rejected' || ($status === 'failed' && ! $record->retry_after?->isFuture())),
            'retry_after' => $record?->retry_after?->toIso8601String(),
            'rejection_reason' => $status === 'rejected' ? $record->rejection_reason : null,
            'quiz_score' => $record?->quiz_score,
            'quiz_total' => $record?->quiz_total,
            'questions' => [],
        ];

        if ($status === 'quiz') {
            $out['questions'] = $this->drawnQuestions($record)->map(fn ($q) => [
                'text' => $q->question_text,
                'options' => $q->options->pluck('option_text')->values(),
            ] + $q->mediaPayload())->values();
        }

        return $out;
    }
}
