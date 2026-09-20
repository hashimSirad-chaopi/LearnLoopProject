<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.home');
})->middleware(['auth', 'verified'])->name('dashboard');


/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

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

Route::middleware(['auth'])->group(function () {

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

    Route::patch('/admin/reports/{report}/resolve', [PageController::class, 'resolveReport'])
        ->name('admin.reports.resolve');

    Route::patch('/admin/reports/{report}/dismiss', [PageController::class, 'dismissReport'])
        ->name('admin.reports.dismiss');

    Route::patch('/admin/listings/{listing}/disable', [PageController::class, 'disableListing'])
        ->name('admin.listings.disable');

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

});




require __DIR__.'/auth.php';
