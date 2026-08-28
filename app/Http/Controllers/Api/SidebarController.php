<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

/**
 * Phase 3 — Sidebar data feed.
 *
 * One endpoint, one round trip. Returns the three pieces the sidebar
 * component needs:
 *   - recent posts (latest 5)
 *   - popular posts (top 5 by view count)
 *   - tag cloud (tag id, name, slug, post count)
 */
class SidebarController extends Controller
{
    public function index(): JsonResponse
    {
        $recent = Post::with('author')
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'author_id', 'published_at', 'featured_image']);

        $popular = Post::published()
            ->popular(5)
            ->get(['id', 'title', 'slug', 'views']);

        $tags = Tag::whereHas('posts', function ($q) {
            $q->where('status', 'published');
        })
            ->withCount(['posts' => function ($q) {
                $q->where('status', 'published');
            }])
            ->orderByDesc('posts_count')
            ->limit(20)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'recent' => $recent,
            'popular' => $popular,
            'tags' => $tags,
        ]);
    }
}
