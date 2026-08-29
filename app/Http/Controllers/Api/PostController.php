<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Support\PostMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of published posts.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 10;

        $posts = Post::with(['author', 'categories', 'tags'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        $this->attachReadingTime($posts);

        return response()->json($posts);
    }

    /**
     * Phase 6 — pick one published post at random. The topbar's
     * "Random" button links here. Returns just the slug so the
     * client can navigate to /blog/{slug}; if the database is empty
     * we return a 404 so the caller can fall back to a sensible UI.
     */
    public function random(): JsonResponse
    {
        $post = Post::query()
            ->published()
            ->inRandomOrder()
            ->first(['slug']);

        if (! $post) {
            return response()->json(['message' => 'No published posts yet.'], 404);
        }

        return response()->json(['slug' => $post->slug]);
    }

    /**
     * Display the specified post.
     */
    public function show(string $slug): JsonResponse
    {
        $post = Post::with(['author', 'categories', 'tags'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Atomic view-count bump. Using DB::table avoids firing Eloquent
        // events and keeps the request cheap.
        Post::where('id', $post->id)->update([
            'views' => \DB::raw('views + 1'),
        ]);
        $post->refresh();
        $post->views = (int) $post->views;

        // Get related posts (same category)
        $categoryIds = $post->categories->pluck('id');
        $relatedPosts = Post::with(['author', 'categories'])
            ->published()
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->where('id', '!=', $post->id)
            ->limit(2)
            ->get();

        $relatedPosts->each(function (Post $p) {
            $p->reading_time = PostMeta::readingTime($p->content);
            $p->word_count = PostMeta::wordCount($p->content);
        });

        $post->reading_time = PostMeta::readingTime($post->content);
        $post->word_count = PostMeta::wordCount($post->content);

        return response()->json([
            'post' => $post,
            'related' => $relatedPosts,
        ]);
    }

    /**
     * GET /api/posts/{slug}/related
     *
     * Phase 5 — split out of `show` so the SPA can request related posts
     * independently (e.g. for "more from this category" widgets) and the
     * `show` payload stays small.
     */
    public function related(string $slug): JsonResponse
    {
        $post = Post::where('slug', $slug)->published()->firstOrFail();

        $categoryIds = $post->categories->pluck('id');

        $relatedPosts = Post::with(['author', 'categories'])
            ->published()
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->where('id', '!=', $post->id)
            ->limit(3)
            ->get();

        $relatedPosts->each(function (Post $p) {
            $p->reading_time = PostMeta::readingTime($p->content);
            $p->word_count = PostMeta::wordCount($p->content);
        });

        return response()->json(['data' => $relatedPosts]);
    }

    /**
     * Display a listing of categories.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::withCount('posts')->get();

        return response()->json($categories);
    }

    /**
     * Display posts by category.
     */
    public function byCategory(string $slug, Request $request): JsonResponse
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $perPage = $request->integer('per_page') ?: 10;

        $posts = $category->posts()
            ->with(['author', 'categories', 'tags'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        $this->attachReadingTime($posts);

        return response()->json($posts);
    }

    /**
     * Full-text-ish search over published posts.
     *
     * GET /api/search?q=foo
     *
     * Matches against title, excerpt, and content (case-insensitive LIKE).
     * Returns a paginator with the same shape as /api/posts so the SPA can
     * reuse the same response handler.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->get('q', ''));
        $perPage = $request->integer('per_page') ?: 10;

        if ($query === '') {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ]);
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';

        // SQLite does not honour backslash as the LIKE escape character by
        // default (MySQL does). Use an explicit ESCAPE clause so the
        // behaviour is identical across drivers.
        $posts = Post::with(['author', 'categories', 'tags'])
            ->published()
            ->where(function ($q) use ($like) {
                $q->whereRaw('title LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('excerpt LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('content LIKE ? ESCAPE ?', [$like, '\\']);
            })
            ->orderBy('published_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $this->attachReadingTime($posts);

        return response()->json($posts);
    }

    /**
     * Attach reading_time + word_count to every post in a paginator
     * (or any LengthAwarePaginator-like object) without an extra query.
     */
    private function attachReadingTime($paginator): void
    {
        $paginator->getCollection()->each(function (Post $post) {
            $post->reading_time = PostMeta::readingTime($post->content);
            $post->word_count = PostMeta::wordCount($post->content);
        });
    }
}
