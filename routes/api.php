<?php

use App\Http\Controllers\Api\AccountAppealController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\ExchangeController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\ListingSocialController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfessionalController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SkillCategoryController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — consumed by the LearnLoop Flutter app
|--------------------------------------------------------------------------
| Public: register/login. Everything else requires a Sanctum token,
| obtained from register/login and sent as: Authorization: Bearer <token>
*/

// ---- Auth (public) ----
// Throttled so passwords cannot be guessed and fake accounts cannot be mass-created.
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,10');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:8,1');
// Forgot password: a 6-digit code by email, then a new password.
Route::post('/forgot-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'send'])->middleware('throttle:5,10');
Route::post('/forgot-password/verify', [\App\Http\Controllers\Api\PasswordResetController::class, 'verify'])->middleware('throttle:15,10');
Route::post('/forgot-password/reset', [\App\Http\Controllers\Api\PasswordResetController::class, 'reset'])->middleware('throttle:10,10');
// A suspended member cannot log in, so "recover my account" is public (it checks the email + password itself).
Route::post('/account-appeals', [AccountAppealController::class, 'store'])->middleware('throttle:5,10');

Route::middleware(['auth:sanctum', 'active', \App\Http\Middleware\TrackLastSeen::class])->group(function () {

    // ---- Auth / Profile ----
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/presence/offline', [AuthController::class, 'goOffline']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar']);
    Route::post('/profile/banner', [AuthController::class, 'uploadBanner']);
    Route::delete('/profile/avatar', [AuthController::class, 'deleteAvatar']);
    Route::delete('/profile/banner', [AuthController::class, 'deleteBanner']);

    // ---- Settings: password, email, blocked members, other devices ----
    Route::post('/account/password', [AccountController::class, 'changePassword'])->middleware('throttle:6,10');
    Route::put('/account/email', [AccountController::class, 'changeEmail'])->middleware('throttle:6,10');
    Route::post('/account/logout-others', [AccountController::class, 'logoutOthers']);
    Route::get('/blocked-users', [AccountController::class, 'blocked']);

    // ---- Another member's public profile ----
    Route::get('/users/{user}', [UserController::class, 'show']);

    // ---- Skill categories (for the Create Post dropdown) ----
    Route::get('/categories', [SkillCategoryController::class, 'index']);

    // ---- Listings (Feed / Skill Listing & Posting / Search) ----
    Route::get('/listings', [ListingController::class, 'index']);
    Route::post('/listings', [ListingController::class, 'store']);
    Route::get('/listings/{listing}', [ListingController::class, 'show']);
    Route::put('/listings/{listing}', [ListingController::class, 'update']);
    Route::delete('/listings/{listing}', [ListingController::class, 'destroy']);
    Route::patch('/listings/{listing}/close', [ListingController::class, 'close']);
    Route::patch('/listings/{listing}/reopen', [ListingController::class, 'reopen']);
    Route::post('/listings/{listing}/propose', [ListingController::class, 'propose']);
    Route::get('/listings/{listing}/comments', [ListingSocialController::class, 'index']);
    Route::post('/listings/{listing}/comments', [ListingSocialController::class, 'store'])->middleware('throttle:20,1');
    Route::delete('/comments/{comment}', [ListingSocialController::class, 'destroy']);
    Route::post('/listings/{listing}/like', [ListingSocialController::class, 'toggleLike'])->middleware('throttle:60,1');

    // ---- Suggested matches (Find > Matches) ----
    Route::get('/matches', [MatchController::class, 'index']);

    // ---- Exchanges (Requests tab, My Exchanges tab, Negotiation, Status Tracking) ----
    Route::get('/exchanges', [ExchangeController::class, 'index']);
    Route::get('/exchanges/{exchange}', [ExchangeController::class, 'show']);
    Route::patch('/exchanges/{exchange}/accept', [ExchangeController::class, 'accept']);
    Route::patch('/exchanges/{exchange}/decline', [ExchangeController::class, 'decline']);
    Route::patch('/exchanges/{exchange}/negotiate', [ExchangeController::class, 'negotiate']);
    Route::patch('/exchanges/{exchange}/complete', [ExchangeController::class, 'complete']);
    Route::post('/exchanges/{exchange}/dispute', [ExchangeController::class, 'dispute']);
    Route::post('/exchanges/{exchange}/cancel-request', [ExchangeController::class, 'requestCancel']);
    Route::patch('/exchanges/{exchange}/cancel-accept', [ExchangeController::class, 'acceptCancel']);
    Route::patch('/exchanges/{exchange}/cancel-decline', [ExchangeController::class, 'declineCancel']);
    Route::patch('/exchanges/{exchange}/cancel-withdraw', [ExchangeController::class, 'withdrawCancel']);

    // ---- Skill catalog (Learn tab): skills -> levels -> published lessons -> quiz ----
    Route::get('/skills', [SkillController::class, 'index']);

    // ---- Professional level: proof + hard quiz + admin approval (everything else is earned by lessons) ----
    Route::get('/skills/{skill}/professional', [ProfessionalController::class, 'show']);
    Route::post('/skills/{skill}/professional', [ProfessionalController::class, 'start'])->middleware('throttle:6,10');
    Route::post('/skills/{skill}/professional/quiz', [ProfessionalController::class, 'quiz'])->middleware('throttle:12,10');

    // ---- My Lessons (members write lessons; an admin reviews them) ----
    Route::get('/my-lessons', [LessonController::class, 'index']);
    Route::post('/lessons', [LessonController::class, 'store']);
    Route::put('/lessons/reorder', [LessonController::class, 'reorder']); // before {lesson}, or 'reorder' is read as an id
    Route::put('/lessons/{lesson}', [LessonController::class, 'update']);
    Route::delete('/lessons/{lesson}', [LessonController::class, 'destroy']);
    Route::post('/lessons/{lesson}/rating', [LessonController::class, 'rate']);

    // ---- Learning progress: quiz attempts, coach confirmation, private exchange lessons ----
    Route::get('/progress', [ProgressController::class, 'mine']);
    Route::post('/lessons/{lesson}/attempts', [ProgressController::class, 'attempt'])->middleware('throttle:60,1');
    Route::get('/exchanges/{exchange}/progress', [ProgressController::class, 'exchange']);
    Route::post('/exchanges/{exchange}/lessons/{lesson}/confirm', [ProgressController::class, 'confirm']);
    Route::post('/exchanges/{exchange}/extra-lessons', [ProgressController::class, 'storeExtra']);
    Route::post('/exchanges/{exchange}/extra-lessons/{exchangeLesson}/attachment', [ProgressController::class, 'attachExtra']);
    Route::post('/exchanges/{exchange}/extra-lessons/{exchangeLesson}/question-media', [ProgressController::class, 'attachQuestionMedia']);
    Route::put('/exchanges/{exchange}/deadlines', [ProgressController::class, 'setDeadline']);
    Route::delete('/exchanges/{exchange}/extra-lessons/{exchangeLesson}', [ProgressController::class, 'destroyExtra']);
    Route::post('/exchanges/{exchange}/extra-lessons/{exchangeLesson}/attempts', [ProgressController::class, 'attemptExtra']);
    Route::post('/exchanges/{exchange}/extra-lessons/{exchangeLesson}/confirm', [ProgressController::class, 'confirmExtra']);

    // ---- Reports (the admin's Reports page) ----
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'store']);

    // ---- Session Scheduling ----
    Route::post('/exchanges/{exchange}/schedules', [ScheduleController::class, 'store']);
    Route::patch('/schedules/{schedule}', [ScheduleController::class, 'update']);

    // ---- Feedback & Rating ----
    Route::get('/ratings', [RatingController::class, 'mine']);
    Route::post('/exchanges/{exchange}/rating', [RatingController::class, 'store']);

    // ---- In-App Messaging ----
    Route::get('/messages/conversations', [MessageController::class, 'conversations']);
    Route::get('/messages/conversations/{user}', [MessageController::class, 'thread']);
    Route::delete('/messages/conversations/{user}', [MessageController::class, 'clear']);
    Route::post('/messages/conversations/{user}/archive', [MessageController::class, 'archive']);
    Route::delete('/messages/conversations/{user}/archive', [MessageController::class, 'unarchive']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
    Route::post('/messages/typing', [MessageController::class, 'typing']);
    Route::post('/users/{user}/block', [BlockController::class, 'store']);
    Route::delete('/users/{user}/block', [BlockController::class, 'destroy']);

    // ---- Push notifications: this phone's Firebase token ----
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::post('/device-tokens/remove', [DeviceTokenController::class, 'destroy']);

    // ---- Notifications ----
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/summary', [NotificationController::class, 'summary']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

});
