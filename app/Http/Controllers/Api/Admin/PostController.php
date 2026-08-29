<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Phase 5 — admin post CRUD.
 *
 *   GET    /api/admin/posts            paginated, includes drafts
 *   POST   /api/admin/posts            create
 *   GET    /api/admin/posts/{id}      fetch one
 *   PUT    /api/admin/posts/{id}      update
 *   DELETE /api/admin/posts/{id}      delete
 *
 * Behind auth + admin middleware. Slugs are auto-generated from the
 * title and made unique by suffixing -2, -3, ... on collision.
 *
 * Status is now a FK (`status_id` -> `statuses.id`) instead of a string
 * column. We accept `status_id` and auto-stamp `published_at` when the
 * chosen status's slug is `published`.
 */
class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 20;

        $query = Post::with(['author', 'categories', 'tags', 'status'])
            ->orderBy('updated_at', 'desc');

        // Free-text search across title + excerpt + content. `like` is
        // fine here — the admin list is small and the LIKE clauses hit
        // the same indexes the public search uses. If this ever grows
        // we'll switch to a FULLTEXT index; the controller contract
        // (the `q` param) stays the same.
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('content', 'like', $like);
            });
        }

        // Status filter. Accept either `status_id` (preferred) or
        // `status` (a slug) for back-compat with the old hardcoded
        // enum callers. Empty / unknown values are ignored.
        $statusId = $request->integer('status_id');
        if ($statusId > 0) {
            $query->where('status_id', $statusId);
        } else {
            $statusSlug = $request->query('status');
            if (is_string($statusSlug) && $statusSlug !== '') {
                $resolvedId = Status::where('slug', $statusSlug)->value('id');
                if ($resolvedId) {
                    $query->where('status_id', $resolvedId);
                }
            }
        }

        // Category filter. Posts can belong to multiple categories, so
        // we go through the pivot table rather than a column on `posts`.
        $categoryId = $request->integer('category_id');
        if ($categoryId > 0) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
        }

        $posts = $query->paginate($perPage)->appends($request->query());

        $posts->getCollection()->each(function (Post $post) {
            $post->reading_time = \App\Support\PostMeta::readingTime($post->content);
            $post->word_count = \App\Support\PostMeta::wordCount($post->content);
        });

        return response()->json($posts);
    }

    public function show(int $id): JsonResponse
    {
        $post = Post::with(['author', 'categories', 'tags', 'status'])->findOrFail($id);
        $post->reading_time = \App\Support\PostMeta::readingTime($post->content);
        $post->word_count = \App\Support\PostMeta::wordCount($post->content);

        return response()->json(['post' => $post]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'min:2'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
            'is_featured' => ['boolean'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ])->validate();

        $status = Status::find($data['status_id']);

        $post = new Post([
            'author_id' => $request->user()->id,
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'featured_image' => $data['featured_image'] ?? null,
            'status_id' => $data['status_id'],
            'is_featured' => $data['is_featured'] ?? false,
            'published_at' => $status?->slug === 'published' ? now() : null,
        ]);
        $post->save();

        if (! empty($data['category_ids'])) {
            $post->categories()->sync($data['category_ids']);
        }
        if (! empty($data['tag_ids'])) {
            $post->tags()->sync($data['tag_ids']);
        }

        $post->load(['author', 'categories', 'tags', 'status']);

        return response()->json(['post' => $post], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        $data = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'min:2', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['sometimes', 'required', 'string', 'min:2'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'status_id' => ['sometimes', 'required', 'integer', 'exists:statuses,id'],
            'is_featured' => ['boolean'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ])->validate();

        // If the title changed, regenerate the slug — unless the caller
        // pinned one via the `slug` field, in which case we honour it.
        if (isset($data['title']) && $data['title'] !== $post->title && ! $request->has('slug')) {
            $post->slug = $this->uniqueSlug($data['title'], $post->id);
        }
        if ($request->has('slug') && $request->input('slug')) {
            $post->slug = $this->uniqueSlug($request->input('slug'), $post->id);
        }

        foreach (['title', 'excerpt', 'content', 'featured_image', 'status_id', 'is_featured'] as $field) {
            if (array_key_exists($field, $data)) {
                $post->{$field} = $data[$field];
            }
        }

        // Auto-stamp `published_at` the first time the post is moved
        // to the `published` status. Clear it if the post is moved
        // back to draft / archived so the published-scope filter
        // hides it immediately.
        if (array_key_exists('status_id', $data)) {
            $status = Status::find($data['status_id']);
            if ($status?->slug === 'published') {
                if ($post->published_at === null) {
                    $post->published_at = now();
                }
            } else {
                $post->published_at = null;
            }
        }

        $post->save();

        if (array_key_exists('category_ids', $data)) {
            $post->categories()->sync($data['category_ids'] ?? []);
        }
        if (array_key_exists('tag_ids', $data)) {
            $post->tags()->sync($data['tag_ids'] ?? []);
        }

        $post->load(['author', 'categories', 'tags', 'status']);

        return response()->json(['post' => $post]);
    }

    public function destroy(int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Build a unique slug from a title, excluding a given post id (so
     * updates can keep their slug).
     */
    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'post';
        $slug = $base;
        $i = 2;

        while (Post::where('slug', $slug)->when($ignoreId, fn ($q, $id) => $q->where('id', '!=', $id))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
