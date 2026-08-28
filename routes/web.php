<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Api\PostController;

// Main SPA route - serves the Vue.js application
Route::get('/', function () {
    return view('welcome');
});

Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');

// API Routes for blog posts
Route::prefix('api')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{slug}', [PostController::class, 'show']);
    Route::get('/categories', [PostController::class, 'categories']);
    Route::get('/categories/{slug}/posts', [PostController::class, 'byCategory']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

