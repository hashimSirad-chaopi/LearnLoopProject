
<?php

use App\Models\Lesson;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\User;

function makeCatalogBoundaryAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
}

function makeCatalogBoundarySkill(User $admin, string $title): Skill
{
    return Skill::create([
        'title' => $title,
        'description' => 'Test skill for catalog authorization.',
        'icon' => '🧪',
        'status' => 'active',
        'created_by' => $admin->id,
        'category_id' => null,
    ]);
}

function makeCatalogBoundaryLesson(
    Skill $skill,
    ?int $authorId,
    string $title = 'Test lesson'
): Lesson {
    $level = SkillLevel::create([
        'skill_id' => $skill->id,
        'name' => 'easy',
        'order' => 1,
    ]);

    return Lesson::create([
        'skill_level_id' => $level->id,
        'title' => $title,
        'content' => 'Test lesson content.',
        'order' => 1,
        'status' => $authorId === null ? 'published' : 'pending',
        'author_id' => $authorId,
        'content_type' => 'text',
    ]);
}

test('catalog edit route rejects a member-written lesson', function () {
    $admin = makeCatalogBoundaryAdmin();
    $member = User::factory()->create(['role' => 'user', 'status' => 'active']);
    $skill = makeCatalogBoundarySkill($admin, 'Catalog Edit Boundary');
    $lesson = makeCatalogBoundaryLesson($skill, $member->id);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->get(route('admin.skills.lessons.edit', [$skill, $lesson]))
        ->assertNotFound();

    expect($lesson->fresh())->not->toBeNull();
});

test('catalog update route cannot modify a member-written lesson', function () {
    $admin = makeCatalogBoundaryAdmin();
    $member = User::factory()->create(['role' => 'user', 'status' => 'active']);
    $skill = makeCatalogBoundarySkill($admin, 'Catalog Update Boundary');
    $lesson = makeCatalogBoundaryLesson($skill, $member->id, 'Original member lesson');

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->put(route('admin.skills.lessons.update', [$skill, $lesson]), [
            'title' => 'Changed through catalog',
            'level' => 'easy',
            'content_type' => 'text',
            'content' => 'Modified content.',
            'questions' => [
                [
                    'text' => 'Test question?',
                    'options' => ['Answer A', 'Answer B', 'Answer C', 'Answer D'],
                    'correct' => 0,
                ],
            ],
        ])
        ->assertNotFound();

    expect($lesson->fresh()->title)->toBe('Original member lesson');
});

test('catalog delete route cannot delete a member-written lesson', function () {
    $admin = makeCatalogBoundaryAdmin();
    $member = User::factory()->create(['role' => 'user', 'status' => 'active']);
    $skill = makeCatalogBoundarySkill($admin, 'Catalog Delete Boundary');
    $lesson = makeCatalogBoundaryLesson($skill, $member->id);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->delete(route('admin.skills.lessons.delete', [$skill, $lesson]))
        ->assertNotFound();

    $this->assertDatabaseHas('lessons', [
        'id' => $lesson->id,
        'author_id' => $member->id,
    ]);
});

test('catalog edit route rejects a lesson belonging to another skill', function () {
    $admin = makeCatalogBoundaryAdmin();
    $skillA = makeCatalogBoundarySkill($admin, 'First Catalog Skill');
    $skillB = makeCatalogBoundarySkill($admin, 'Second Catalog Skill');
    $lesson = makeCatalogBoundaryLesson($skillA, null);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->get(route('admin.skills.lessons.edit', [$skillB, $lesson]))
        ->assertNotFound();
});

test('catalog edit route still allows an admin-created lesson in the correct skill', function () {
    $admin = makeCatalogBoundaryAdmin();
    $skill = makeCatalogBoundarySkill($admin, 'Valid Catalog Skill');
    $lesson = makeCatalogBoundaryLesson($skill, null);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->get(route('admin.skills.lessons.edit', [$skill, $lesson]))
        ->assertOk();
});
