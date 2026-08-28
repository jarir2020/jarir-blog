<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 4 — admin tag list (for the post-edit form).
 *
 *   GET /api/admin/tags   -> paginated tag list, ordered by name.
 */
class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 100;

        return response()->json(
            Tag::orderBy('name')->paginate($perPage)
        );
    }
}
