<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Widget;
use Illuminate\Support\Collection;

/**
 * Phase 6 — turns a list of enabled Widgets into the public sidebar
 * payload.
 *
 * One switch on `type`. Each case returns the data the Sidebar.vue
 * component needs to render that widget. Keeping the resolver here
 * (rather than in the controller) lets us unit-test it in isolation
 * and reuse it from anywhere — for example a future CLI command that
 * pre-renders the sidebar HTML.
 */
class SidebarResolver
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolve(string $position = 'right'): array
    {
        $widgets = Widget::query()
            ->enabled()
            ->forPosition($position)
            ->get();

        return $widgets
            ->map(fn (Widget $w) => $this->resolveOne($w))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveOne(Widget $widget): ?array
    {
        return match ($widget->type) {
            'popular_recent_comments' => $this->resolvePopularRecentComments($widget),
            'category' => $this->resolveCategory($widget),
            'video' => $this->resolveVideo($widget),
            'html' => $this->resolveHtml($widget),
            'social' => $this->resolveSocial($widget),
            'archives' => $this->resolveArchives($widget),
            'newsletter' => ['type' => 'newsletter', 'id' => $widget->id, 'name' => $widget->name],
            default => null,
        };
    }

    private function resolvePopularRecentComments(Widget $widget): array
    {
        $popular = Post::published()
            ->popular(5)
            ->get(['id', 'title', 'slug', 'views']);

        $recent = Post::with('author')
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'author_id', 'published_at', 'featured_image']);

        $comments = Comment::with('post:id,title,slug')
            ->where('approved', true)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'type' => 'popular_recent_comments',
            'id' => $widget->id,
            'name' => $widget->name,
            'popular' => $popular,
            'recent' => $recent,
            'comments' => $comments,
        ];
    }

    private function resolveCategory(Widget $widget): ?array
    {
        $settings = $widget->settings ?? [];
        $categoryId = $settings['category_id'] ?? null;
        if (! $categoryId) {
            return null;
        }

        $category = Category::find($categoryId);
        if (! $category) {
            return null;
        }

        $posts = Post::published()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'featured_image', 'published_at']);

        return [
            'type' => 'category',
            'id' => $widget->id,
            'name' => $settings['title'] ?? $widget->name,
            'category' => $category,
            'posts' => $posts,
        ];
    }

    private function resolveVideo(Widget $widget): array
    {
        // v1 placeholder. The widget renders a "Video gallery coming
        // soon" notice; if a future version pulls from a YouTube
        // channel, the settings already hold a `placeholder` slot.
        return [
            'type' => 'video',
            'id' => $widget->id,
            'name' => $widget->name,
            'settings' => $widget->settings ?? [],
        ];
    }

    private function resolveHtml(Widget $widget): array
    {
        return [
            'type' => 'html',
            'id' => $widget->id,
            'name' => $widget->name,
            'body' => $widget->settings['body'] ?? '',
        ];
    }

    private function resolveSocial(Widget $widget): array
    {
        return [
            'type' => 'social',
            'id' => $widget->id,
            'name' => $widget->name,
            'links' => $widget->settings['links'] ?? [],
        ];
    }

    private function resolveArchives(Widget $widget): array
    {
        // Group published posts by year-month so the archive dropdown
        // can show "April 2026 (12)" entries. Built from the actual
        // published_at column — no separate table needed.
        $rows = Post::published()
            ->orderBy('published_at', 'desc')
            ->get(['published_at'])
            ->groupBy(fn ($p) => $p->published_at?->format('Y-m'))
            ->map(fn (Collection $group, string $key) => [
                'key' => $key,
                'label' => $group->first()->published_at->format('F Y'),
                'count' => $group->count(),
            ])
            ->values();

        return [
            'type' => 'archives',
            'id' => $widget->id,
            'name' => $widget->name,
            'archives' => $rows,
        ];
    }
}
