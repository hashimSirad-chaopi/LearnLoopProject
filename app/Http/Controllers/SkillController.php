<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Http\Controllers\Api\ProgressController;
use App\Models\Skill;
use App\Models\Exchange;
use App\Models\Listing;
use App\Models\SkillLevel;
use App\Support\StorageCleanup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Admin: the skill catalog (skills -> levels -> lessons -> quiz questions).
 * This is the content the Flutter app shows in the Learn tab; members' own
 * lessons are reviewed separately in LessonReviewController.
 */
class SkillController extends Controller
{
    public const LEVELS = ['easy' => 1, 'moderate' => 2, 'hard' => 3, 'expert' => 4];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $category = (string) $request->query('category', '');          // '' | 'none' | category id
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '';

        $skills = Skill::with('category')
            ->withCount('levels')
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($category === 'none', fn ($q) => $q->whereNull('category_id'))
            ->when($category !== '' && ctype_digit($category), fn ($q) => $q->where('category_id', (int) $category))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderBy('title')
            ->get();

        // Published CATALOG lessons per skill in ONE query (not one query per skill).
        $lessonCounts = DB::table('lessons')
            ->join('skill_levels', 'skill_levels.id', '=', 'lessons.skill_level_id')
            ->where('lessons.status', 'published')
            ->whereNull('lessons.author_id') // the admin's own lessons only; users' posts live in User lessons
            ->selectRaw('skill_levels.skill_id as sid, COUNT(*) as total')
            ->groupBy('skill_levels.skill_id')
            ->pluck('total', 'sid');

        $categories = Category::orderBy('name')->get(['id', 'name']);

        // Group by category, "No category" last.
        $groups = $skills->groupBy(fn ($skill) => $skill->category_id ?? 0);
        $names = $categories->pluck('name', 'id');
        $groups = $groups->sortBy(fn ($group, $id) => $id === 0 ? 'zzzz' : mb_strtolower($names[$id] ?? ''));

        return view('admin.skills.index', [
            'groups' => $groups,
            'names' => $names,
            'categories' => $categories,
            'lessonCounts' => $lessonCounts,
            'total' => $skills->count(),
            'allCount' => Skill::count(),
            'search' => $search,
            'category' => $category,
            'status' => $status,
        ]);
    }

    public function create()
    {
        return view('admin.skills.form', [
            // ?category=ID (from the Categories page) pre-selects the category.
            'skill' => new Skill(['status' => 'active', 'category_id' => request('category')]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSkill($request);

        $skill = Skill::create($validated + ['created_by' => auth()->id()]);

        ActivityLog::record('skill.created', "Created skill \"{$skill->title}\"");

        return redirect()->route('admin.skills.show', $skill)->with('success', 'Skill created. Now add its lessons.');
    }

    public function show(Skill $skill)
    {
        $skill->load(['category', 'levels.lessons.questions']);

        $memberLessons = Lesson::with('author')
            ->whereNotNull('author_id')
            ->whereHas('skillLevel', fn ($q) => $q->where('skill_id', $skill->id))
            ->latest()
            ->get();

        $levelNames = array_keys(self::LEVELS);

        return view('admin.skills.show', compact('skill', 'memberLessons', 'levelNames'));
    }

    public function edit(Skill $skill)
    {
        return view('admin.skills.form', [
            'skill' => $skill,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Skill $skill)
    {
        $skill->update($this->validateSkill($request));

        ActivityLog::record('skill.updated', "Updated skill \"{$skill->title}\"");

        return redirect()->route('admin.skills.show', $skill)->with('success', 'Skill updated.');
    }

    public function destroy(Skill $skill)
    {
        // Deleting a skill removes its lessons, every member's progress in it and the skill
        // from members' profiles, and blanks it out of listings. Refuse while it is in live use;
        // setting it to Inactive hides it from the app without losing anything.
        $liveListings = Listing::whereIn('status', ['active', 'pending'])
            ->where(fn ($q) => $q->where('offers_skill_id', $skill->id)->orWhere('wants_skill_id', $skill->id))
            ->count();
        $openExchanges = Exchange::whereIn('status', ['pending', 'ongoing', 'disputed'])
            ->where(fn ($q) => $q->whereRaw('lower(skill_offered) = ?', [mb_strtolower($skill->title)])
                ->orWhereRaw('lower(skill_wanted) = ?', [mb_strtolower($skill->title)]))
            ->count();

        if ($liveListings > 0 || $openExchanges > 0) {
            $parts = array_filter([
                $liveListings > 0 ? $liveListings.' '.\Illuminate\Support\Str::plural('listing', $liveListings) : null,
                $openExchanges > 0 ? $openExchanges.' open '.\Illuminate\Support\Str::plural('exchange', $openExchanges) : null,
            ]);

            return back()->withErrors(['skill' => "\"{$skill->title}\" is still used by ".implode(' and ', $parts).'. Set the skill to Inactive instead, or deal with those first.']);
        }

        $title = $skill->title;
        $paths = StorageCleanup::forSkill($skill);
        $skill->delete();
        StorageCleanup::delete($paths);

        ActivityLog::record('skill.deleted', "Deleted skill \"{$title}\"");

        return redirect()->route('admin.skills')->with('success', 'Skill deleted.');
    }

    // ------------------------------------------------------------------
    // Catalog lessons (written by an admin, so they are published at once)
    // ------------------------------------------------------------------

    public function createLesson(Skill $skill)
    {
        return view('admin.lessons.form', [
            'skill' => $skill,
            'lesson' => new Lesson(['status' => 'published']),
            'levelName' => request('level', 'easy'),
            'questions' => old('questions', [$this->blankQuestion()]),
            'levels' => array_keys(self::LEVELS),
        ]);
    }

    public function storeLesson(Request $request, Skill $skill)
    {
        $data = $this->validateLesson($request);

        DB::transaction(function () use ($request, $skill, $data, &$lesson) {
            $level = SkillLevel::firstOrCreate(
                ['skill_id' => $skill->id, 'name' => $data['level']],
                ['order' => self::LEVELS[$data['level']]]
            );

            $lesson = Lesson::create([
                'skill_level_id' => $level->id,
                'title' => $data['title'],
                'content' => (string) ($data['content'] ?? ''),
                'content_type' => $data['content_type'],
                'order' => (int) Lesson::where('skill_level_id', $level->id)->max('order') + 1,
                'status' => 'published',
            ]);

            $this->saveAttachment($request, $lesson, $data['content_type']);

            $this->saveQuestions($lesson, $data['questions']);
        });

        ActivityLog::record('lesson.created', "Added lesson \"{$lesson->title}\" to {$skill->title}");

        return redirect()->route('admin.skills.show', $skill)->with('success', 'Lesson added.');
    }

    public function editLesson(Skill $skill, Lesson $lesson)
    {
        abort_unless($lesson->skillLevel?->skill_id === $skill->id, 404);

        $lesson->load('questions.options');

        $questions = old('questions', $lesson->questions->map(fn ($q) => [
            'text' => $q->question_text,
            'options' => $q->options->pluck('option_text')->all(),
            'correct' => max(0, $q->options->search(fn ($o) => $o->is_correct)),
            'media_type' => $q->media_type,
            'media_path' => $q->media_path,
            'media_url' => \App\Support\QuestionMedia::url($q->media_path),
        ])->all());

        return view('admin.lessons.form', [
            'skill' => $skill,
            'lesson' => $lesson,
            'levelName' => $lesson->skillLevel->name,
            'questions' => $questions ?: [$this->blankQuestion()],
            'levels' => array_keys(self::LEVELS),
        ]);
    }

    public function updateLesson(Request $request, Skill $skill, Lesson $lesson)
    {
        abort_unless($lesson->skillLevel?->skill_id === $skill->id, 404);

        $data = $this->validateLesson($request);

        DB::transaction(function () use ($request, $skill, $lesson, $data) {
            $level = SkillLevel::firstOrCreate(
                ['skill_id' => $skill->id, 'name' => $data['level']],
                ['order' => self::LEVELS[$data['level']]]
            );

            $lesson->update([
                'skill_level_id' => $level->id,
                'title' => $data['title'],
                'content' => (string) ($data['content'] ?? ''),
                'content_type' => $data['content_type'],
            ]);

            $this->saveAttachment($request, $lesson, $data['content_type']);

            $old = $lesson->questions()->get(['media_type', 'media_path'])->filter(fn ($q) => $q->media_path)
                ->mapWithKeys(fn ($q) => [$q->media_path => $q->media_type])->all();
            $lesson->questions()->each(fn (Question $q) => $q->delete()); // options cascade
            $this->saveQuestions($lesson, $data['questions'], $old);
        });

        ActivityLog::record('lesson.updated', "Updated lesson \"{$lesson->title}\" in {$skill->title}");

        return redirect()->route('admin.skills.show', $skill)->with('success', 'Lesson updated.');
    }

    public function destroyLesson(Skill $skill, Lesson $lesson)
    {
        abort_unless($lesson->skillLevel?->skill_id === $skill->id, 404);

        $title = $lesson->title;
        $paths = StorageCleanup::forLesson($lesson); // its own file and the media above its quiz questions
        $lesson->delete();
        StorageCleanup::delete($paths);

        ActivityLog::record('lesson.deleted', "Deleted lesson \"{$title}\" from {$skill->title}");

        return back()->with('success', 'Lesson deleted.');
    }

    // ------------------------------------------------------------------

    private function validateSkill(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('skills', 'title')->ignore($request->route('skill'))],
            'category_id' => 'required|exists:categories,id',
            'icon' => 'nullable|string|max:16',
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'experience_based' => 'nullable|boolean',
        ]);

        // An unticked checkbox sends nothing: that means "a normal skill with lessons".
        $data['experience_based'] = $request->boolean('experience_based');

        return $data;
    }

    /**
     * Same quiz rules as the app: 4 options per question, exactly one correct.
     * (Catalog lessons need at least 1 question; member lessons need 3.)
     */
    private function validateLesson(Request $request): array
    {
        $type = $request->input('content_type', 'text');
        $lesson = $request->route('lesson');
        $hasFile = $lesson instanceof Lesson && $lesson->attachment_path && ($lesson->content_type ?: 'text') === $type;

        // Text lesson: the written lesson. Any other type: an uploaded file/audio/video/photo
        // (required unless this lesson already has one of the same type), `content` is a short note.
        $fileRules = $type === 'text'
            ? ['nullable']
            : ($hasFile ? array_values(array_filter(ProgressController::attachmentRules($type), fn ($rule) => $rule !== 'required')) : ProgressController::attachmentRules($type));

        return $request->validate([
            'title' => 'required|string|max:255',
            'level' => 'required|in:'.implode(',', array_keys(self::LEVELS)),
            'content_type' => 'required|in:'.implode(',', Lesson::CONTENT_TYPES),
            'content' => [$type === 'text' ? 'required' : 'nullable', 'string'],
            'attachment' => $fileRules,
            'questions' => 'required|array|min:1|max:10',
            'questions.*.text' => 'required|string|max:500',
            'questions.*.options' => 'required|array|size:4',
            'questions.*.options.*' => 'required|string|max:255',
            'questions.*.correct' => 'required|integer|between:0,3',
            'questions.*.media_type' => 'nullable|in:'.implode(',', \App\Support\QuestionMedia::TYPES),
            'questions.*.media' => 'nullable|file|max:1048576',
            'questions.*.keep_media' => 'nullable|string|max:255',
            'questions.*.remove_media' => 'nullable|boolean',
        ], [
            'questions.*.text.required' => 'Every question needs its text.',
            'questions.*.options.*.required' => 'Every question needs all four answer options.',
            'questions.*.correct.required' => 'Pick the correct answer for every question.',
        ]);
    }

    /** Store an uploaded file for a non-text lesson; a text lesson drops any old file. */
    private function saveAttachment(Request $request, Lesson $lesson, string $type): void
    {
        if ($type === 'text') {
            if ($lesson->attachment_path) {
                Storage::disk('public')->delete($lesson->attachment_path);
                $lesson->update(['attachment_path' => null, 'attachment_name' => null]);
            }

            return;
        }

        if (! $request->hasFile('attachment')) {
            return; // keep the file the lesson already has
        }

        if ($lesson->attachment_path) {
            Storage::disk('public')->delete($lesson->attachment_path);
        }
        $file = $request->file('attachment');
        $lesson->update([
            'attachment_path' => $file->store('lessons', 'public'),
            'attachment_name' => mb_substr($file->getClientOriginalName(), 0, 255),
        ]);
    }

    /**
     * @param  array<string,string>  $old  media files (path => type) the lesson's previous questions had.
     *                                     A question keeps one of them when its form row says so.
     */
    private function saveQuestions(Lesson $lesson, array $questions, array $old = []): void
    {
        $kept = [];

        foreach (array_values($questions) as $qi => $q) {
            [$mediaType, $mediaPath] = $this->questionMedia($q, $old, $qi);
            if ($mediaPath && isset($old[$mediaPath])) {
                $kept[] = $mediaPath;
            }

            $question = Question::create([
                'lesson_id' => $lesson->id,
                'question_text' => $q['text'],
                'order' => $qi + 1,
                'media_type' => $mediaType,
                'media_path' => $mediaPath,
            ]);

            foreach (array_values($q['options']) as $oi => $text) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $text,
                    'is_correct' => $oi === (int) $q['correct'],
                    'order' => $oi + 1,
                ]);
            }
        }

        // Files no question uses any more.
        foreach (array_diff(array_keys($old), $kept) as $path) {
            Storage::disk('public')->delete($path);
        }
    }

    /** [type, path] of the photo / video / audio above one question: a new upload, the old file, or none. */
    private function questionMedia(array $q, array $old, int $index): array
    {
        $file = $q['media'] ?? null;
        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $ext = strtolower($file->getClientOriginalExtension());
            $type = match (true) {
                in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) => 'photo',
                in_array($ext, ['mp4', 'mov', 'webm', 'm4v'], true) => 'video',
                in_array($ext, ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'oga'], true) => 'audio',
                default => null,
            };
            if (! $type) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "questions.$index.media" => 'Question '.($index + 1).': choose a photo (jpg, png, webp), video (mp4, mov, webm) or audio (mp3, m4a, wav, ogg) file.',
                ]);
            }
            $check = \Illuminate\Support\Facades\Validator::make(['media' => $file], ['media' => \App\Support\QuestionMedia::rules($type)]);
            if ($check->fails()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "questions.$index.media" => 'Question '.($index + 1).': '.$check->errors()->first('media'),
                ]);
            }

            return [$type, $file->store('question-media', 'public')];
        }

        $keep = $q['keep_media'] ?? null;
        if ($keep && empty($q['remove_media']) && isset($old[$keep])) {
            return [$old[$keep], $keep];
        }

        return [null, null];
    }

    private function blankQuestion(): array
    {
        return ['text' => '', 'options' => ['', '', '', ''], 'correct' => 0, 'media_type' => null, 'media_path' => null, 'media_url' => null];
    }
}
