<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 4 — comment moderation queue.
 *
 *   GET    /api/admin/comments              paginated, includes unapproved
 *   POST   /api/admin/comments/{id}/approve mark approved = true
 *   POST   /api/admin/comments/{id}/reject  mark approved = false
 *   DELETE /api/admin/comments/{id}         delete
 */
class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 25;
        $filter = $request->get('filter', 'all'); // all|approved|pending

        $query = Comment::with('post:id,title,slug')
            ->orderBy('created_at', 'desc');

        if ($filter === 'pending') {
            $query->where('approved', false);
        } elseif ($filter === 'approved') {
            $query->where('approved', true);
        }

        return response()->json($query->paginate($perPage));
    }

    public function approve(int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->approved = true;
        $comment->save();

        return response()->json(['comment' => $comment]);
    }

    public function reject(int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->approved = false;
        $comment->save();

        return response()->json(['comment' => $comment]);
    }

    public function destroy(int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json(['deleted' => true]);
    }
}
