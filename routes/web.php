<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/zoeken', [SearchController::class, 'index'])->name('search');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/blog.php';

// Kept last and constrained to known country slugs so this catch-all shape
// can't shadow other three-segment routes (e.g. Fortify's passkey routes).
Route::get('/{country}/{state}/{city}', [LocationController::class, 'show'])
    ->where(['country' => 'nederland', 'state' => '[a-z0-9-]+', 'city' => '[a-z0-9-]+'])
    ->name('location.show');
