<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Phase 5 — admin status CRUD.
 *
 *   GET    /api/admin/statuses        paginated, ordered by `order, name`
 *   POST   /api/admin/statuses        create
 *   GET    /api/admin/statuses/{id}   show
 *   PUT    /api/admin/statuses/{id}   update
 *   DELETE /api/admin/statuses/{id}   delete (blocked if posts reference it)
 *
 * Slugs are auto-generated from `name` on create. On update the slug is
 * only regenerated if the caller didn't pin one — admins can fix typos
 * without breaking any FK that points at the row.
 */
class StatusController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 50;

        $query = Status::orderBy('order')->orderBy('name');

        // Free-text search across name + label + slug. Same shape as
        // the posts search so the admin UI uses one mental model.
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('label', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        return response()->json(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'status' => Status::findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', 'unique:statuses,slug'],
            'label' => ['nullable', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        $status = new Status([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['name']),
            'label' => $data['label'] ?? $data['name'],
            'color' => $data['color'] ?? '#6b7280',
            'description' => $data['description'] ?? null,
            'order' => $data['order'] ?? 0,
        ]);
        $status->save();

        return response()->json(['status' => $status], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $status = Status::findOrFail($id);

        $data = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', 'unique:statuses,slug,'.$status->id],
            'label' => ['nullable', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        // Regenerate the slug only if the caller explicitly pinned one
        // (we trust the new value) or the name changed and no slug was
        // supplied (keep the URL stable otherwise).
        if (array_key_exists('slug', $data) && $data['slug']) {
            $status->slug = $this->uniqueSlug($data['slug'], $status->id);
        } elseif (isset($data['name']) && $data['name'] !== $status->name && empty($status->slug)) {
            $status->slug = $this->uniqueSlug($data['name'], $status->id);
        }

        foreach (['name', 'label', 'color', 'description', 'order'] as $field) {
            if (array_key_exists($field, $data)) {
                $status->{$field} = $data[$field] ?? $status->{$field};
            }
        }
        if (isset($data['label']) === false && isset($data['name']) && $data['name'] !== $status->getOriginal('name')) {
            // Keep label in sync with name unless the caller already
            // supplied a label. Most admins will rename + relabel
            // together; this is the friendlier default.
            $status->label = $data['name'];
        }

        $status->save();

        return response()->json(['status' => $status]);
    }

    public function destroy(int $id): JsonResponse
    {
        $status = Status::findOrFail($id);

        // Refuse to delete a status that still has posts attached —
        // otherwise the FK nullOnDelete kicks in and silently hides
        // content. Force the admin to migrate posts first.
        $postsCount = $status->posts()->count();
        if ($postsCount > 0) {
            return response()->json([
                'message' => "Cannot delete \"{$status->label}\" because {$postsCount} post(s) still use it. Reassign those posts to another status first.",
            ], 409);
        }

        $status->delete();

        return response()->json(['deleted' => true]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'status';
        $slug = $base;
        $i = 2;

        while (Status::where('slug', $slug)
            ->when($ignoreId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
