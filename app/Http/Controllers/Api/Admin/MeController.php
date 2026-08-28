<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 4 — admin SPA bootstrap.
 *
 *   GET /api/admin/me
 *     - if authenticated: returns { user: {...}, is_admin: bool }
 *     - if not: 401
 *
 * The admin SPA calls this on mount to decide whether to render the
 * admin UI or show a "log in as admin" message.
 */
class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'is_admin' => $user->isAdmin(),
        ]);
    }
}
