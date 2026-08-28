<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 3 — Author profile pages.
 *
 *   GET /api/authors/{username}              -> author bio + post count
 *   GET /api/authors/{username}/posts        -> paginated posts by author
 *
 * `username` is a slug derived from the user's name. The User model
 * already has a `name`; we derive a stable handle on the fly rather than
 * adding a column.
 */
class AuthorController extends Controller
{
    public function show(string $username): JsonResponse
    {
        $author = $this->findByHandle($username);

        $postsCount = Post::where('author_id', $author->id)
            ->published()
            ->count();

        return response()->json([
            'author' => [
                'id' => $author->id,
                'name' => $author->name,
                'handle' => $this->handleFor($author->name),
                'email' => $author->email,
                'posts_count' => $postsCount,
            ],
        ]);
    }

    public function posts(string $username, Request $request): JsonResponse
    {
        $author = $this->findByHandle($username);
        $perPage = $request->integer('per_page') ?: 10;

        $posts = Post::with(['author', 'categories', 'tags'])
            ->where('author_id', $author->id)
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        $posts->getCollection()->each(function (Post $post) {
            $post->reading_time = \App\Support\PostMeta::readingTime($post->content);
            $post->word_count = \App\Support\PostMeta::wordCount($post->content);
        });

        return response()->json($posts);
    }

    private function findByHandle(string $username): User
    {
        $users = User::all(['id', 'name', 'email']);
        foreach ($users as $user) {
            if ($this->handleFor($user->name) === $username) {
                return $user;
            }
        }
        abort(response()->json(['message' => 'Author not found.'], 404));
    }

    private function handleFor(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)) ?? '');
    }
}
