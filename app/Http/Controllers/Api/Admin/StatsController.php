<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 — admin dashboard stats.
 *
 *   GET /api/admin/stats
 *
 * One round trip per dashboard mount. Returns the numbers and recent
 * activity the AdminDashboard Vue view needs.
 *
 * Post counts are grouped by `statuses.slug` rather than the old
 * `posts.status` enum. The response keys (`.published / .draft /
 * .archived`) are preserved so the dashboard view doesn't need to
 * change; if a status is missing from the table we default to 0.
 */
class StatsController extends Controller
{
    public function show(): JsonResponse
    {
        // Count posts grouped by status slug. The join keeps the
        // public shape (`published/draft/archived`) even if an admin
        // renames a status.
        $postCounts = DB::table('posts')
            ->join('statuses', 'statuses.id', '=', 'posts.status_id')
            ->select('statuses.slug as slug', DB::raw('count(*) as count'))
            ->groupBy('statuses.slug')
            ->pluck('count', 'slug');

        // Comments: normalise the `approved` column to true/false so
        // the keys work the same on every driver (MySQL returns
        // 0/1, SQLite returns 0/1, but the boolean cast is the
        // contract the rest of the codebase relies on).
        $approved = 0;
        $pending = 0;
        foreach (Comment::select('approved', DB::raw('count(*) as count'))->groupBy('approved')->get() as $row) {
            if ($row->approved) {
                $approved = (int) $row->count;
            } else {
                $pending = (int) $row->count;
            }
        }

        $totalViews = (int) Post::sum('views');
        $userCount = User::count();

        $recentPosts = Post::with(['author', 'status:id,slug,label,color'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'status_id', 'is_featured', 'views', 'author_id', 'updated_at', 'published_at']);

        $recentComments = Comment::with('post:id,title,slug')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'posts' => [
                'total' => array_sum($postCounts->all()),
                'published' => $postCounts['published'] ?? 0,
                'draft' => $postCounts['draft'] ?? 0,
                'archived' => $postCounts['archived'] ?? 0,
                'views' => $totalViews,
            ],
            'comments' => [
                'total' => $approved + $pending,
                'pending' => $pending,
                'approved' => $approved,
            ],
            'subscribers' => Subscriber::count(),
            'users' => $userCount,
            'recent_posts' => $recentPosts,
            'recent_comments' => $recentComments,
        ]);
    }
}
