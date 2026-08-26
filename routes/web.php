<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'admin') {
        return redirect()->route('admin.home');
    }

    return redirect()->route('user.home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/home', [PageController::class, 'adminHome'])->name('admin.home');

    Route::get('/user/home', [PageController::class, 'userHome'])->name('user.home');
    Route::get('/user/matches', [PageController::class, 'matches'])->name('matches');
    Route::get('/user/exchanges', [PageController::class, 'exchanges'])->name('exchanges');
    Route::get('/user/messages', [PageController::class, 'messages'])->name('messages');
    Route::get('/user/profile', [PageController::class, 'profile'])->name('profile');
});

require __DIR__.'/auth.php';