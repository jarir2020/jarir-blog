<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 4 — admin guard.
 *
 * Use after the `auth` middleware:
 *
 *     Route::middleware(['auth', 'admin'])->group(...)
 *
 * Returns 403 JSON for API requests, redirects to /login for browser
 * requests when not authenticated, and 403 with a friendly message when
 * authenticated but not an admin.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        if (! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden — admin access required.'], 403);
            }
            abort(403, 'Admin access required.');
        }

        return $next($request);
    }
}
