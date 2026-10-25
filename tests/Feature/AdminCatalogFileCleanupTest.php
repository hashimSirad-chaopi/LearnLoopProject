
<?php

use App\Models\Lesson;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeFileCleanupAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
}

function makeFileCleanupSkill(User $admin, string $title): Skill
{
    return Skill::create([
        'title' => $title,
        'description' => 'File-cleanup regression test.',
        'icon' => '🧪',
        'status' => 'active',
        'created_by' => $admin->id,
        'category_id' => null,
    ]);
}

function makeFileCleanupQuestions(
    UploadedFile $validImage,
    UploadedFile $invalidMedia
): array {
    return [
        [
            'text' => 'What is the first answer?',
            'options' => ['Correct', 'Wrong A', 'Wrong B', 'Wrong C'],
            'correct' => 0,
            'media' => $validImage,
        ],
        [
            'text' => 'What happens next?',
            'options' => ['Answer A', 'Answer B', 'Answer C', 'Answer D'],
            'correct' => 0,
            'media' => $invalidMedia,
        ],
    ];
}

test('failed catalog lesson creation removes newly uploaded quiz media', function () {
    Storage::fake('public');

    $admin = makeFileCleanupAdmin();
    $skill = makeFileCleanupSkill($admin, 'Failed Create Cleanup');

    $validImage = UploadedFile::fake()->image('question.png');
    $invalidMedia = UploadedFile::fake()->create(
        'invalid.exe',
        10,
        'application/octet-stream'
    );

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.skills.show', $skill))
        ->post(route('admin.skills.lessons.store', $skill), [
            'title' => 'Lesson That Must Roll Back',
            'level' => 'easy',
            'content_type' => 'text',
            'content' => 'A test lesson.',
            'questions' => makeFileCleanupQuestions($validImage, $invalidMedia),
        ])
        ->assertRedirect(route('admin.skills.show', $skill))
        ->assertSessionHasErrors('questions.1.media');

    $this->assertDatabaseMissing('lessons', [
        'title' => 'Lesson That Must Roll Back',
    ]);

    expect(Storage::disk('public')->allFiles('question-media'))->toBe([]);
});

test('failed catalog lesson update preserves its original attachment and removes new uploads', function () {
    Storage::fake('public');

    $admin = makeFileCleanupAdmin();
    $skill = makeFileCleanupSkill($admin, 'Failed Update Cleanup');

    $level = SkillLevel::create([
        'skill_id' => $skill->id,
        'name' => 'easy',
        'order' => 1,
    ]);

    $originalPath = 'lessons/original.pdf';
    Storage::disk('public')->put($originalPath, 'Original lesson file');

    $lesson = Lesson::create([
        'skill_level_id' => $level->id,
        'title' => 'Original Lesson',
        'content' => 'Original note.',
        'order' => 1,
        'status' => 'published',
        'author_id' => null,
        'content_type' => 'file',
        'attachment_path' => $originalPath,
        'attachment_name' => 'original.pdf',
    ]);

    $replacement = UploadedFile::fake()->create(
        'replacement.pdf',
        10,
        'application/pdf'
    );

    $validImage = UploadedFile::fake()->image('replacement-question.png');
    $invalidMedia = UploadedFile::fake()->create(
        'invalid.exe',
        10,
        'application/octet-stream'
    );

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.skills.show', $skill))
        ->put(route('admin.skills.lessons.update', [$skill, $lesson]), [
            'title' => 'Updated Lesson',
            'level' => 'easy',
            'content_type' => 'file',
            'content' => 'Updated note.',
            'attachment' => $replacement,
            'questions' => makeFileCleanupQuestions($validImage, $invalidMedia),
        ])
        ->assertRedirect(route('admin.skills.show', $skill))
        ->assertSessionHasErrors('questions.1.media');

    expect($lesson->fresh()->title)->toBe('Original Lesson');
    expect($lesson->fresh()->attachment_path)->toBe($originalPath);

    // The failed update must not delete the file still referenced by the database.
    Storage::disk('public')->assertExists($originalPath);

    // Newly uploaded files must not be left behind after the rollback.
    expect(Storage::disk('public')->allFiles('lessons'))
        ->toBe([$originalPath]);

    expect(Storage::disk('public')->allFiles('question-media'))->toBe([]);
});
