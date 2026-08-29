<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Phase 5 — admin tag CRUD.
 *
 *   GET    /api/admin/tags         paginated, with posts_count
 *   POST   /api/admin/tags         create
 *   PUT    /api/admin/tags/{tag}   update
 *   DELETE /api/admin/tags/{tag}   delete (blocked if posts are attached)
 *
 * (No `show` — the public `/api/admin/tags` list is enough for the
 * post-edit form, and individual tag lookups aren't used yet.)
 */
class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 50;

        $query = Tag::withCount('posts')->orderBy('name');

        // Free-text search across name + slug. Same shape as the
        // categories / statuses search.
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        return response()->json(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'slug' => ['nullable', 'string', 'max:60', 'unique:tags,slug'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ])->validate();

        $tag = new Tag([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['name']),
            'color' => $data['color'] ?? '#6b7280',
        ]);
        $tag->save();

        return response()->json(['tag' => $tag], 201);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:60'],
            'slug' => ['nullable', 'string', 'max:60', 'unique:tags,slug,'.$tag->id],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ])->validate();

        if (array_key_exists('slug', $data) && $data['slug']) {
            $tag->slug = $this->uniqueSlug($data['slug'], $tag->id);
        }

        foreach (['name', 'color'] as $field) {
            if (array_key_exists($field, $data)) {
                $tag->{$field} = $data[$field];
            }
        }

        $tag->save();

        return response()->json(['tag' => $tag->loadCount('posts')]);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $postsCount = $tag->posts()->count();
        if ($postsCount > 0) {
            return response()->json([
                'message' => "Cannot delete \"{$tag->name}\" because {$postsCount} post(s) still use it. Detach those posts first.",
            ], 409);
        }

        $tag->delete();

        return response()->json(['deleted' => true]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'tag';
        $slug = $base;
        $i = 2;

        while (Tag::where('slug', $slug)
            ->when($ignoreId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
