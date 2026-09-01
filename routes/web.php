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

});


require __DIR__.'/auth.php';
