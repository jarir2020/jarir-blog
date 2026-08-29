<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\SidebarResolver;
use Illuminate\Http\JsonResponse;

/**
 * Phase 6 — public sidebar data feed.
 *
 * One endpoint, one round trip. The response shape:
 *
 *   {
 *     "recent":   [...5 posts],   // legacy, kept for the old Sidebar.vue shape
 *     "popular":  [...5 posts],   // legacy
 *     "tags":     [...N tags],    // legacy
 *     "widgets":  [...N resolved widgets in `order`]  // new — drives the redesign
 *   }
 *
 * Existing views that read `recent/popular/tags` keep working. The
 * Sidebar.vue component reads `widgets` and renders the new chrome.
 */
class SidebarController extends Controller
{
    public function index(): JsonResponse
    {
        $resolver = app(SidebarResolver::class);

        return response()->json([
            // Legacy keys — small, cheap, no resolver involved.
            'recent' => $this->recent(),
            'popular' => $this->popular(),
            'tags' => $this->tags(),

            // New: admin-configured widget list.
            'widgets' => $resolver->resolve('right'),
        ]);
    }

    private function recent()
    {
        return \App\Models\Post::with('author')
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'author_id', 'published_at', 'featured_image']);
    }

    private function popular()
    {
        return \App\Models\Post::published()
            ->popular(5)
            ->get(['id', 'title', 'slug', 'views']);
    }

    private function tags()
    {
        return Tag::whereHas('posts', function ($q) {
            $q->whereHas('status', fn ($sq) => $sq->where('slug', 'published'));
        })
            ->withCount(['posts' => function ($q) {
                $q->whereHas('status', fn ($sq) => $sq->where('slug', 'published'));
            }])
            ->orderByDesc('posts_count')
            ->limit(20)
            ->get(['id', 'name', 'slug']);
    }
}
