<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\AppealReviewController;
use App\Http\Controllers\LessonReviewController;
use App\Http\Controllers\ProfessionalReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

// Chat voice messages and videos. Served by Laravel (not as a plain static file) so the phone's
// player can ask for parts of the file (Range requests), which it needs for .m4a / .mp4.
Route::get('/media/chat/{file}', function (string $file) {
    $path = \Illuminate\Support\Facades\Storage::disk('public')->path('chat/'.$file);
    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('file', '[A-Za-z0-9._-]+')->name('chat.media');

Route::get('/dashboard', function () {
    return redirect()->route('admin.home');
})->middleware(['auth', 'verified'])->name('dashboard');


/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'two-factor'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

});


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

<<<<<<< HEAD
Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('/admin/home', [PageController::class, 'adminHome'])
        ->name('admin.home');

    Route::get('/admin/users', [PageController::class, 'adminUsers'])
        ->name('admin.users');

    Route::get('/admin/listings', [PageController::class, 'adminListings'])
        ->name('admin.listings');

    Route::get('/admin/reports', [PageController::class, 'adminReports'])
        ->name('admin.reports');

    Route::get('/admin/settings', [PageController::class, 'adminSettings'])
        ->name('admin.settings');
    
    Route::delete('/admin/users/{user}', [PageController::class, 'deleteUser'])
        ->name('admin.users.delete');

    Route::patch('/admin/users/{user}/suspend', [PageController::class, 'suspendUser'])
        ->name('admin.users.suspend');

    Route::patch('/admin/users/{user}/activate', [PageController::class, 'activateUser'])
        ->name('admin.users.activate');    

    Route::patch('/admin/reports/{report}/resolve', [PageController::class, 'resolveReport'])
        ->name('admin.reports.resolve');

    Route::patch('/admin/reports/{report}/dismiss', [PageController::class, 'dismissReport'])
        ->name('admin.reports.dismiss');

    Route::patch('/admin/listings/{listing}/disable', [PageController::class, 'disableListing'])
        ->name('admin.listings.disable');

    Route::patch('/admin/listings/{listing}/approve', [PageController::class, 'approveListing'])
        ->name('admin.listings.approve');

    Route::patch('/admin/listings/{listing}/reject', [PageController::class, 'rejectListing'])
        ->name('admin.listings.reject');  
    
    Route::delete('/admin/listings/{listing}', [PageController::class, 'deleteListing'])
        ->name('admin.listings.delete');

    Route::get('/admin/users/create', [PageController::class, 'createUser'])->name('admin.users.create');
    Route::post('/admin/users', [PageController::class, 'storeUser'])->name('admin.users.store');
    Route::get('/admin/users/{user}', [PageController::class, 'showUser'])->name('admin.users.show');
    Route::get('/admin/users/{user}/edit', [PageController::class, 'editUser'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [PageController::class, 'updateUser'])->name('admin.users.update');    
    Route::get('/admin/listings/create', [PageController::class, 'createListing'])->name('admin.listings.create');
Route::post('/admin/listings', [PageController::class, 'storeListing'])->name('admin.listings.store');
Route::get('/admin/listings/{listing}/edit', [PageController::class, 'editListing'])->name('admin.listings.edit');
Route::put('/admin/listings/{listing}', [PageController::class, 'updateListing'])->name('admin.listings.update');
Route::patch('/admin/listings/{listing}/enable', [PageController::class, 'enableListing'])->name('admin.listings.enable');
Route::delete('/admin/reports/{report}', [PageController::class, 'deleteReport'])->name('admin.reports.delete');
Route::get('/admin/reports/create', [PageController::class, 'createReport'])->name('admin.reports.create');
Route::post('/admin/reports', [PageController::class, 'storeReport'])->name('admin.reports.store');
Route::get('/admin/exchanges', [PageController::class, 'adminExchanges'])->name('admin.exchanges');
Route::get('/admin/exchanges/create', [PageController::class, 'createExchange'])->name('admin.exchanges.create');
Route::post('/admin/exchanges', [PageController::class, 'storeExchange'])->name('admin.exchanges.store');
Route::get('/admin/exchanges/{exchange}', [PageController::class, 'showExchange'])->name('admin.exchanges.show');
Route::patch('/admin/exchanges/{exchange}', [PageController::class, 'updateExchangeStatus'])->name('admin.exchanges.update');
Route::delete('/admin/exchanges/{exchange}', [PageController::class, 'deleteExchange'])->name('admin.exchanges.delete');
Route::get('/admin/categories', [PageController::class, 'adminCategories'])->name('admin.categories');
Route::post('/admin/categories', [PageController::class, 'storeCategory'])->name('admin.categories.store');
Route::delete('/admin/categories/{category}', [PageController::class, 'deleteCategory'])->name('admin.categories.delete');
Route::put('/admin/categories/{category}', [PageController::class, 'updateCategory'])->name('admin.categories.update');

Route::get('/admin/subcategories', [PageController::class, 'adminSubcategories'])
    ->name('admin.subcategories');

Route::post('/admin/subcategories', [PageController::class, 'storeSubcategory'])
    ->name('admin.subcategories.store');

Route::put('/admin/subcategories/{subcategory}', [PageController::class, 'updateSubcategory'])
    ->name('admin.subcategories.update');

Route::delete('/admin/subcategories/{subcategory}', [PageController::class, 'deleteSubcategory'])
    ->name('admin.subcategories.delete');
Route::get('/admin/activity', [PageController::class, 'adminActivity'])->name('admin.activity');

=======
// The email-code prompt: reachable by a signed-in admin who has not entered the code yet.
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/verify', [\App\Http\Controllers\AdminVerifyController::class, 'show'])->name('verify');
    Route::post('/verify', [\App\Http\Controllers\AdminVerifyController::class, 'verify'])->middleware('throttle:15,5')->name('verify.check');
    Route::post('/verify/resend', [\App\Http\Controllers\AdminVerifyController::class, 'resend'])->middleware('throttle:6,10')->name('verify.resend');
>>>>>>> 7128c7a35fdb01d62338eda557a779da41476150
});

Route::middleware(['auth', 'admin', 'two-factor', \App\Http\Middleware\TrackLastSeen::class])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/home', [PageController::class, 'adminHome'])->name('home');

    // ---- Security: email sign-in code + trusted browsers ----
    Route::get('/security', [\App\Http\Controllers\SecurityController::class, 'show'])->name('security');
    Route::post('/security/devices/clear', [\App\Http\Controllers\SecurityController::class, 'removeAllDevices'])->name('security.devices.clear');
    Route::post('/security/devices/{device}/remove', [\App\Http\Controllers\SecurityController::class, 'removeDevice'])->whereNumber('device')->name('security.devices.remove');
    Route::get('/settings', [PageController::class, 'adminSettings'])->name('settings');
    Route::get('/activity', [PageController::class, 'adminActivity'])->name('activity');

    // ---- Users (static routes first so /users/create isn't captured by /users/{user}) ----
    Route::get('/users', [PageController::class, 'adminUsers'])->name('users');
    Route::get('/users/create', [PageController::class, 'createUser'])->name('users.create');
    Route::get('/users/export', [PageController::class, 'exportUsers'])->name('users.export');
    Route::post('/users', [PageController::class, 'storeUser'])->name('users.store');
    Route::post('/users/bulk', [PageController::class, 'bulkUsers'])->name('users.bulk');
    Route::get('/users/{user}', [PageController::class, 'showUser'])->name('users.show');
    Route::get('/users/{user}/edit', [PageController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [PageController::class, 'updateUser'])->name('users.update');
    Route::patch('/users/{user}/suspend', [PageController::class, 'suspendUser'])->name('users.suspend');
    Route::patch('/users/{user}/activate', [PageController::class, 'activateUser'])->name('users.activate');
    Route::delete('/users/{user}', [PageController::class, 'deleteUser'])->name('users.delete');

    Route::get('/dashboard/export', [PageController::class, 'exportDashboard'])->name('dashboard.export');

    // ---- Notifications: your own alerts, what admins sent, and member activity ----
    Route::get('/notifications', [\App\Http\Controllers\NotificationLogController::class, 'index'])->name('notifications');
    Route::post('/notifications', [\App\Http\Controllers\NotificationLogController::class, 'store'])->name('notifications.store');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationLogController::class, 'destroy'])->name('notifications.delete');

    // ---- Listings ----
    Route::post('/listings/bulk', [PageController::class, 'bulkListings'])->name('listings.bulk');
    Route::get('/comments', [\App\Http\Controllers\CommentModerationController::class, 'index'])->name('comments');
    Route::post('/comments/bulk', [\App\Http\Controllers\CommentModerationController::class, 'bulk'])->name('comments.bulk');
    Route::delete('/comments/{comment}', [\App\Http\Controllers\CommentModerationController::class, 'destroy'])->name('comments.delete');
    Route::get('/listings', [PageController::class, 'adminListings'])->name('listings');
    Route::get('/listings/create', [PageController::class, 'createListing'])->name('listings.create');
    Route::post('/listings', [PageController::class, 'storeListing'])->name('listings.store');
    Route::get('/listings/{listing}/edit', [PageController::class, 'editListing'])->name('listings.edit');
    Route::put('/listings/{listing}', [PageController::class, 'updateListing'])->name('listings.update');
    Route::patch('/listings/{listing}/enable', [PageController::class, 'enableListing'])->name('listings.enable');
    Route::patch('/listings/{listing}/disable', [PageController::class, 'disableListing'])->name('listings.disable');
    Route::delete('/listings/{listing}', [PageController::class, 'deleteListing'])->name('listings.delete');

    // ---- Reports ----
    Route::get('/reports', [PageController::class, 'adminReports'])->name('reports');
    Route::get('/reports/create', [PageController::class, 'createReport'])->name('reports.create');
    Route::post('/reports', [PageController::class, 'storeReport'])->name('reports.store');
    Route::patch('/reports/{report}/resolve', [PageController::class, 'resolveReport'])->name('reports.resolve');
    Route::patch('/reports/{report}/dismiss', [PageController::class, 'dismissReport'])->name('reports.dismiss');
    Route::delete('/reports/{report}', [PageController::class, 'deleteReport'])->name('reports.delete');
    Route::post('/reports/bulk', [PageController::class, 'bulkReports'])->name('reports.bulk');

    // ---- Exchanges ----
    Route::get('/exchanges', [PageController::class, 'adminExchanges'])->name('exchanges');
    Route::get('/exchanges/create', [PageController::class, 'createExchange'])->name('exchanges.create');
    Route::post('/exchanges', [PageController::class, 'storeExchange'])->name('exchanges.store');
    Route::get('/exchanges/{exchange}', [PageController::class, 'showExchange'])->name('exchanges.show');
    Route::patch('/exchanges/{exchange}', [PageController::class, 'updateExchangeStatus'])->name('exchanges.update');
    Route::delete('/exchanges/{exchange}', [PageController::class, 'deleteExchange'])->name('exchanges.delete');

    // ---- Skill catalog (skills -> levels -> lessons -> quiz) ----
    Route::get('/skills', [SkillController::class, 'index'])->name('skills');
    Route::get('/skills/create', [SkillController::class, 'create'])->name('skills.create');
    Route::post('/skills', [SkillController::class, 'store'])->name('skills.store');
    Route::get('/skills/{skill}', [SkillController::class, 'show'])->name('skills.show');
    Route::get('/skills/{skill}/edit', [SkillController::class, 'edit'])->name('skills.edit');
    Route::put('/skills/{skill}', [SkillController::class, 'update'])->name('skills.update');
    Route::delete('/skills/{skill}', [SkillController::class, 'destroy'])->name('skills.delete');

    Route::get('/skills/{skill}/lessons/create', [SkillController::class, 'createLesson'])->name('skills.lessons.create');
    Route::post('/skills/{skill}/lessons', [SkillController::class, 'storeLesson'])->name('skills.lessons.store');
    Route::get('/skills/{skill}/lessons/{lesson}/edit', [SkillController::class, 'editLesson'])->name('skills.lessons.edit');
    Route::put('/skills/{skill}/lessons/{lesson}', [SkillController::class, 'updateLesson'])->name('skills.lessons.update');
    Route::delete('/skills/{skill}/lessons/{lesson}', [SkillController::class, 'destroyLesson'])->name('skills.lessons.delete');

    // ---- Member lessons: the review queue ----
    Route::get('/lessons', [LessonReviewController::class, 'index'])->name('lessons');
    Route::get('/lessons/members/{user}', [LessonReviewController::class, 'member'])->name('lessons.member');
    Route::get('/lessons/members/{user}/skills/{skill}', [LessonReviewController::class, 'memberSkill'])->name('lessons.member.skill');
    Route::patch('/lessons/members/{user}/skills/{skill}/move', [LessonReviewController::class, 'moveMemberLesson'])->name('lessons.member.skill.move');
    Route::get('/lessons/{lesson}', [LessonReviewController::class, 'show'])->name('lessons.show');
    Route::patch('/lessons/{lesson}/approve', [LessonReviewController::class, 'approve'])->name('lessons.approve');
    Route::patch('/lessons/{lesson}/reject', [LessonReviewController::class, 'reject'])->name('lessons.reject');

    // ---- Professional level: proof sent by members who already work with a skill ----
    Route::get('/professional', [ProfessionalReviewController::class, 'index'])->name('professional');
    Route::patch('/professional/{verification}/approve', [ProfessionalReviewController::class, 'approve'])->name('professional.approve');
    Route::patch('/professional/{verification}/reject', [ProfessionalReviewController::class, 'reject'])->name('professional.reject');

    // ---- Recovery requests: suspended members asking for their account back ----
    Route::get('/appeals', [AppealReviewController::class, 'index'])->name('appeals');
    Route::patch('/appeals/{appeal}/approve', [AppealReviewController::class, 'approve'])->name('appeals.approve');
    Route::patch('/appeals/{appeal}/decline', [AppealReviewController::class, 'decline'])->name('appeals.decline');

    // ---- Categories ----
    Route::get('/categories', [PageController::class, 'adminCategories'])->name('categories');
    Route::post('/categories', [PageController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [PageController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [PageController::class, 'deleteCategory'])->name('categories.delete');
});

require __DIR__.'/auth.php';
