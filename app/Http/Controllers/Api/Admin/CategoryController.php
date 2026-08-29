<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Phase 5 — admin category CRUD.
 *
 *   GET    /api/admin/categories        paginated, with posts_count
 *   POST   /api/admin/categories        create
 *   GET    /api/admin/categories/{id}   show
 *   PUT    /api/admin/categories/{id}   update
 *   DELETE /api/admin/categories/{id}   delete (blocked if posts are attached)
 *
 * The PUBLIC `/api/categories` endpoint (served by
 * Api\PostController@categories) is unchanged — this controller is
 * admin-only.
 */
class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 50;

        $query = Category::withCount('posts')->orderBy('name');

        // Free-text search across name + slug + description. Matches
        // the posts / statuses search contract.
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        return response()->json(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::withCount('posts')->findOrFail($id);

        return response()->json(['category' => $category]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ])->validate();

        $category = new Category([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? '#6b7280',
            'parent_id' => $data['parent_id'] ?? null,
        ]);
        $category->save();

        return response()->json(['category' => $category], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $data = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:categories,slug,'.$category->id],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ])->validate();

        if (array_key_exists('slug', $data) && $data['slug']) {
            $category->slug = $this->uniqueSlug($data['slug'], $category->id);
        }

        foreach (['name', 'description', 'color', 'parent_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $category->{$field} = $data[$field];
            }
        }

        $category->save();

        return response()->json(['category' => $category->loadCount('posts')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $postsCount = $category->posts()->count();
        if ($postsCount > 0) {
            return response()->json([
                'message' => "Cannot delete \"{$category->name}\" because {$postsCount} post(s) still use it. Detach those posts first.",
            ], 409);
        }

        $category->delete();

        return response()->json(['deleted' => true]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'category';
        $slug = $base;
        $i = 2;

        while (Category::where('slug', $slug)
            ->when($ignoreId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
