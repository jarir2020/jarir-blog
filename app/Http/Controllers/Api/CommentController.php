<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Phase 3 — public comments on a post.
 *
 *   GET  /api/posts/{slug}/comments   -> all comments, newest first
 *   POST /api/posts/{slug}/comments   -> create a comment
 */
class CommentController extends Controller
{
    public function index(string $slug): JsonResponse
    {
        $post = Post::where('slug', $slug)->published()->firstOrFail();

        $comments = Comment::where('post_id', $post->id)
            ->approved()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $comments,
        ]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $post = Post::where('slug', $slug)->published()->firstOrFail();

        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ])->validate();

        $comment = Comment::create([
            'post_id' => $post->id,
            'name' => strip_tags($data['name']),
            'email' => $data['email'],
            'body' => strip_tags($data['body']),
        ]);

        return response()->json($comment, 201);
    }
}
