<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\MarkdownRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 9 — public read of admin-editable pages.
 *
 *   GET /api/pages                  — list (default: top-level only)
 *   GET /api/pages?parent=about    — sub-pages of `about`
 *   GET /api/pages/{slug}           — single page (with rendered body)
 */
class PageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Page::query()->enabled()->ordered();

        if ($parent = $request->query('parent')) {
            $query->childrenOf($parent);
        } else {
            $query->whereNull('parent_slug');
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function show(string $slug, MarkdownRenderer $markdown): JsonResponse
    {
        $page = Page::query()
            ->enabled()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'excerpt' => $page->excerpt,
                'hero_image' => $page->hero_image,
                'body' => $page->body,
                'body_html' => $markdown->render($page->body),
                'parent_slug' => $page->parent_slug,
                'order' => $page->order,
                'enabled' => $page->enabled,
            ],
        ]);
    }
}
