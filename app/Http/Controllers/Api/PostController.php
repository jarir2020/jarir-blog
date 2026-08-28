<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    /**
     * Display a listing of published posts.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        
        $posts = Post::with(['author', 'categories', 'tags'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        return response()->json($posts);
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

        return response()->json([
            'post' => $post,
            'related' => $relatedPosts
        ]);
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
        $perPage = $request->get('per_page', 10);

        $posts = $category->posts()
            ->with(['author', 'categories', 'tags'])
            ->published()
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        return response()->json($posts);
    }
}
