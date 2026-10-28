<?php

namespace App\Support;

use App\Models\Lesson;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Uploaded files (photos, videos, audio, proofs, chat pictures) live on the public
 * disk and are only linked from database rows. When the admin deletes a member, an
 * exchange, a lesson or a skill, the database removes the rows by itself (foreign key
 * cascade), which does NOT run any Eloquent code, so the files would stay on disk
 * forever. Collect the paths first, delete the rows, then remove the files:
 *
 *     $paths = StorageCleanup::forUser($user);
 *     $user->delete();
 *     StorageCleanup::delete($paths);
 */
class StorageCleanup
{
    /** Remove files from the public disk. Missing files and empty paths are ignored. */
    public static function delete(iterable $paths): void
    {
        foreach (collect($paths)->filter(fn ($p) => is_string($p) && $p !== '')->unique() as $path) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                report($e); // never block the admin because one file could not be removed
            }
        }
    }

    /** A catalog or member lesson: its own file plus the media above its quiz questions. */
    public static function forLesson(Lesson $lesson): array
    {
        // The media column only exists once the add_media_to_questions migration has run.
        $media = Schema::hasColumn('questions', 'media_path')
            ? DB::table('questions')->where('lesson_id', $lesson->id)->pluck('media_path')
            : collect();

        return collect([$lesson->attachment_path])
            ->merge($media)
            ->filter()
            ->values()
            ->all();
    }

    /** Everything an exchange stored: private lessons, their quiz media, chat pictures, rating proofs. */
    public static function forExchanges(array $exchangeIds): array
    {
        if ($exchangeIds === []) {
            return [];
        }

        $paths = collect();

        foreach (DB::table('exchange_lessons')->whereIn('exchange_id', $exchangeIds)->get(['attachment_path', 'questions']) as $row) {
            $paths->push($row->attachment_path);
            foreach ((array) json_decode((string) $row->questions, true) as $question) {
                $paths->push(is_array($question) ? ($question['media_path'] ?? null) : null);
            }
        }

        $paths = $paths
            ->merge(DB::table('messages')->whereIn('exchange_id', $exchangeIds)->pluck('attachment_path'))
            ->merge(DB::table('rating_attachments')
                ->whereIn('rating_id', DB::table('ratings')->whereIn('exchange_id', $exchangeIds)->select('id'))
                ->pluck('path'));

        return $paths->filter()->values()->all();
    }

    /**
     * Files of a skill and everything under it. Call BEFORE deleting the skill.
     */
    public static function forSkill(Skill $skill): array
    {
        $paths = collect();

        $lessons = Lesson::whereHas('skillLevel', fn ($q) => $q->where('skill_id', $skill->id))->get();
        foreach ($lessons as $lesson) {
            $paths = $paths->merge(self::forLesson($lesson));
        }

        return $paths
            ->merge(DB::table('professional_verifications')->where('skill_id', $skill->id)->pluck('proof_path'))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Files of a member, and the member's lessons that nobody can see any more.
     * Call BEFORE deleting the user.
     *
     * Lessons that were never published (pending / needs changes) would be left behind with
     * no author and no way to review them, so they are removed here. Published lessons stay:
     * other members are studying them.
     */
    public static function forUser(User $user): array
    {
        $paths = collect([$user->avatar_path, $user->banner_path]);

        $exchangeIds = DB::table('exchanges')
            ->where('provider_id', $user->id)->orWhere('learner_id', $user->id)
            ->pluck('id')->all();

        $paths = $paths->merge(self::forExchanges($exchangeIds));

        $paths = $paths
            ->merge(DB::table('messages')
                ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('recipient_id', $user->id))
                ->pluck('attachment_path'))
            ->merge(DB::table('professional_verifications')->where('user_id', $user->id)->pluck('proof_path'))
            ->merge(DB::table('report_attachments')
                ->whereIn('report_id', DB::table('reports')
                    ->where(fn ($q) => $q->where('reported_by', $user->id)->orWhere('reported_user_id', $user->id))
                    ->select('id'))
                ->pluck('path'))
            ->merge(DB::table('rating_attachments')
                ->whereIn('rating_id', DB::table('ratings')
                    ->where(fn ($q) => $q->where('rater_id', $user->id)->orWhere('rated_user_id', $user->id))
                    ->select('id'))
                ->pluck('path'));

        Lesson::where('author_id', $user->id)->where('status', '!=', 'published')->get()
            ->each(function (Lesson $lesson) use (&$paths) {
                $paths = $paths->merge(self::forLesson($lesson));
                $lesson->delete();
            });

        return $paths->filter()->values()->all();
    }
}
