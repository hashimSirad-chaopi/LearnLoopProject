<?php

use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\User;

function makeLessonReviewAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
}

function makeLessonReviewMember(): User
{
    return User::factory()->create([
        'role' => 'user',
        'status' => 'active',
    ]);
}

function makeMemberReviewLesson(
    User $author,
    string $status = 'pending'
): Lesson {
    $skill = Skill::create([
        'title' => 'Review Test Skill',
        'description' => 'Skill created for lesson-review testing.',
        'icon' => '🧪',
        'status' => 'active',
        'created_by' => $author->id,
        'category_id' => null,
    ]);

    $level = SkillLevel::create([
        'skill_id' => $skill->id,
        'name' => 'easy',
        'order' => 1,
    ]);

    return Lesson::create([
        'skill_level_id' => $level->id,
        'title' => 'Member Review Test Lesson',
        'content' => 'Lesson content for testing.',
        'order' => 1,
        'status' => $status,
        'author_id' => $author->id,
        'content_type' => 'text',
        'rejection_reason' => null,
        'approved_by' => null,
        'reviewed_at' => null,
    ]);
}

test('approving a member lesson publishes it and records the review', function () {
    $admin = makeLessonReviewAdmin();
    $author = makeLessonReviewMember();
    $lesson = makeMemberReviewLesson($author);

    $destination = route('admin.lessons', ['status' => 'pending']);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from($destination)
        ->patch(route('admin.lessons.approve', $lesson), [
            'back' => $destination,
        ])
        ->assertRedirect($destination)
        ->assertSessionHas('success');

    $lesson->refresh();

    expect($lesson->status)->toBe('published');
    expect($lesson->approved_by)->toBe($admin->id);
    expect($lesson->rejection_reason)->toBeNull();
    expect($lesson->reviewed_at)->not->toBeNull();

    $this->assertDatabaseHas('notifications', [
        'user_id' => $author->id,
        'type' => 'admin_notice',
        'source' => 'admin',
        'title' => 'Lesson approved',
        'related_type' => 'lesson',
        'related_id' => (string) $lesson->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'lesson.approved',
    ]);
});

test('rejecting a member lesson records the reason and notifies its author', function () {
    $admin = makeLessonReviewAdmin();
    $author = makeLessonReviewMember();
    $lesson = makeMemberReviewLesson($author);

    $destination = route('admin.lessons', ['status' => 'pending']);
    $reason = 'Please add more explanation and correct the second quiz question.';

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from($destination)
        ->patch(route('admin.lessons.reject', $lesson), [
            'back' => $destination,
            'rejection_reason' => $reason,
        ])
        ->assertRedirect($destination)
        ->assertSessionHas('success');

    $lesson->refresh();

    expect($lesson->status)->toBe('rejected');
    expect($lesson->approved_by)->toBeNull();
    expect($lesson->rejection_reason)->toBe($reason);
    expect($lesson->reviewed_at)->not->toBeNull();

    $this->assertDatabaseHas('notifications', [
        'user_id' => $author->id,
        'type' => 'admin_notice',
        'source' => 'admin',
        'title' => 'Lesson needs changes',
        'related_type' => 'lesson',
        'related_id' => (string) $lesson->id,
    ]);

    expect(
        Notification::where('user_id', $author->id)
            ->where('title', 'Lesson needs changes')
            ->where('message', 'like', '%'.$reason.'%')
            ->exists()
    )->toBeTrue();

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'lesson.rejected',
    ]);
});

test('rejecting a lesson without a reason leaves it pending', function () {
    $admin = makeLessonReviewAdmin();
    $author = makeLessonReviewMember();
    $lesson = makeMemberReviewLesson($author);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.lessons', ['status' => 'pending']))
        ->patch(route('admin.lessons.reject', $lesson), [
            'rejection_reason' => '',
        ])
        ->assertRedirect(route('admin.lessons', ['status' => 'pending']))
        ->assertSessionHasErrors('rejection_reason');

    expect($lesson->fresh()->status)->toBe('pending');

    expect(
        Notification::where('user_id', $author->id)
            ->where('title', 'Lesson needs changes')
            ->exists()
    )->toBeFalse();
});

test('catalog lessons cannot be approved or rejected through member review routes', function () {
    $admin = makeLessonReviewAdmin();

    $skill = Skill::create([
        'title' => 'Catalog Boundary Review Skill',
        'description' => 'Catalog lesson for boundary testing.',
        'icon' => '🧪',
        'status' => 'active',
        'created_by' => $admin->id,
        'category_id' => null,
    ]);

    $level = SkillLevel::create([
        'skill_id' => $skill->id,
        'name' => 'easy',
        'order' => 1,
    ]);

    $lesson = Lesson::create([
        'skill_level_id' => $level->id,
        'title' => 'Admin Catalog Lesson',
        'content' => 'Catalog lesson content.',
        'order' => 1,
        'status' => 'published',
        'author_id' => null,
        'content_type' => 'text',
    ]);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->patch(route('admin.lessons.approve', $lesson))
        ->assertNotFound();

    $this->patch(route('admin.lessons.reject', $lesson), [
        'rejection_reason' => 'This should not apply.',
    ])->assertNotFound();

    expect($lesson->fresh()->status)->toBe('published');
    expect($lesson->fresh()->approved_by)->toBeNull();

    $this->assertDatabaseMissing('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'lesson.approved',
    ]);

    $this->assertDatabaseMissing('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'lesson.rejected',
    ]);
});
