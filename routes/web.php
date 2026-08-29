<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Main SPA route - serves the Vue.js application
Route::get('/', function () {
    return view('welcome');
});

// API Routes for blog posts - registered BEFORE the SPA catch-all so that
// /api/* is not shadowed by the {any?} route below.
//
// Order matters: more specific routes (`/posts/{slug}/comments`) MUST be
// declared before the catch-style `/posts/{slug}` route, otherwise
// `/api/posts/foo/comments` will match `posts/{slug}` with slug=foo/comments.
Route::prefix('api')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
    // /posts/random must come before /posts/{slug} so the literal
    // segment wins. Used by the topbar's "Random" button.
    Route::get('/posts/random', [PostController::class, 'random']);
    Route::get('/posts/{slug}/comments', [\App\Http\Controllers\Api\CommentController::class, 'index']);
    Route::post('/posts/{slug}/comments', [\App\Http\Controllers\Api\CommentController::class, 'store']);
    Route::get('/posts/{slug}/related', [PostController::class, 'related']);
    Route::get('/posts/{slug}', [PostController::class, 'show']);

    Route::get('/categories', [PostController::class, 'categories']);
    Route::get('/categories/{slug}/posts', [PostController::class, 'byCategory']);
    Route::get('/search', [PostController::class, 'search']);
    Route::get('/sidebar', [\App\Http\Controllers\Api\SidebarController::class, 'index']);
    Route::get('/social-links', [\App\Http\Controllers\Api\SocialLinkController::class, 'index']);

    Route::get('/authors/{username}', [\App\Http\Controllers\Api\AuthorController::class, 'show']);
    Route::get('/authors/{username}/posts', [\App\Http\Controllers\Api\AuthorController::class, 'posts']);

    Route::post('/subscribe', [\App\Http\Controllers\Api\SubscriptionController::class, 'store']);

    // Phase 4 — admin SPA bootstrap. `me` is accessible to any authenticated
    // user (it tells the SPA whether they're an admin). All other /api/admin/*
    // routes are gated by the admin middleware below.
    Route::middleware('auth')->group(function () {
        Route::get('/admin/me', [\App\Http\Controllers\Api\Admin\MeController::class, 'show']);
    });

    // Phase 2 — admin image upload.
    // Phase 4 — tightened to require the admin role.
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/admin/images', [\App\Http\Controllers\Api\Admin\ImageController::class, 'store']);

        Route::get('/admin/posts', [\App\Http\Controllers\Api\Admin\PostController::class, 'index']);
        Route::post('/admin/posts', [\App\Http\Controllers\Api\Admin\PostController::class, 'store']);
        Route::get('/admin/posts/{id}', [\App\Http\Controllers\Api\Admin\PostController::class, 'show']);
        Route::put('/admin/posts/{id}', [\App\Http\Controllers\Api\Admin\PostController::class, 'update']);
        Route::delete('/admin/posts/{id}', [\App\Http\Controllers\Api\Admin\PostController::class, 'destroy']);

        Route::get('/admin/comments', [\App\Http\Controllers\Api\Admin\CommentController::class, 'index']);
        Route::post('/admin/comments/{id}/approve', [\App\Http\Controllers\Api\Admin\CommentController::class, 'approve']);
        Route::post('/admin/comments/{id}/reject', [\App\Http\Controllers\Api\Admin\CommentController::class, 'reject']);
        Route::delete('/admin/comments/{id}', [\App\Http\Controllers\Api\Admin\CommentController::class, 'destroy']);

        Route::get('/admin/subscribers', [\App\Http\Controllers\Api\Admin\SubscriberController::class, 'index']);
        Route::get('/admin/stats', [\App\Http\Controllers\Api\Admin\StatsController::class, 'show']);

        // Phase 5 — taxonomy CRUD. Statuses and categories are full
        // API resources (apiResource). Tags only have explicit
        // store/update/destroy on top of the existing index because we
        // don't have a need for a public `show` endpoint.
        Route::apiResource('admin/statuses', \App\Http\Controllers\Api\Admin\StatusController::class);
        Route::apiResource('admin/categories', \App\Http\Controllers\Api\Admin\CategoryController::class);
        Route::post('/admin/tags', [\App\Http\Controllers\Api\Admin\TagController::class, 'store']);
        Route::put('/admin/tags/{tag}', [\App\Http\Controllers\Api\Admin\TagController::class, 'update']);
        Route::delete('/admin/tags/{tag}', [\App\Http\Controllers\Api\Admin\TagController::class, 'destroy']);
        Route::get('/admin/tags', [\App\Http\Controllers\Api\Admin\TagController::class, 'index']);

        // Phase 6 — sidebar widget CRUD. The public sidebar reads
        // these via /api/sidebar's `widgets` key (resolved server-side
        // by App\Support\SidebarResolver).
        Route::apiResource('admin/widgets', \App\Http\Controllers\Api\Admin\WidgetController::class);

        // Phase 8 — admin-editable social links. The public
        // site-layout reads these via a view composer in
        // AppServiceProvider (one source of truth for the topbar
        // and footer).
        Route::apiResource('admin/social-links', \App\Http\Controllers\Api\Admin\SocialLinkController::class);
    });
});

// Breeze auth routes (/login, /register, /forgot-password, /reset-password,
// /verify-email, /confirm-password, /logout). MUST be loaded before the
// SPA catch-all below, otherwise the catch-all will swallow them and serve
// the empty Vue shell instead of the Blade login form.
require __DIR__.'/auth.php';

// Phase 3 — RSS / Atom feed at the top level, not under /api.
Route::get('/feed.xml', [\App\Http\Controllers\FeedController::class, 'index']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = request()->user();
        $isAdmin = $user?->isAdmin() ?? false;

        return view('dashboard', ['isAdmin' => $isAdmin]);
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Phase 4 — admin sign-in page. Served as a themed Blade view (the same
// `auth.login` view the public /login uses, but with the admin chrome)
// so the URL renders correctly even when reached directly. Must be
// registered BEFORE the /admin/{any?} SPA catch-all below.
Route::get('/admin/login', function () {
    return view('auth.login', [
        'isAdmin' => true,
        'intended' => request()->query('intended'),
    ]);
})->name('admin.login');

// Phase 4 — admin SPA. The Vue admin app lives under /admin and is
// served by the admin layout (dark sidebar + topbar). The SPA fetches
// /api/admin/me on mount to decide whether the current user is allowed
// in. The server-rendered chrome acts as a fallback if the JS bundle
// is slow to load.
Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*');

// SPA catch-all - must come last so it does not shadow the API or auth routes.
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
