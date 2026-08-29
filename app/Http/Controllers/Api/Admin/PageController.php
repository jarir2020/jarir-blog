<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Phase 9 — admin page CRUD.
 *
 *   GET    /api/admin/pages            paginated, ordered by (parent_slug, order, id)
 *   POST   /api/admin/pages            create
 *   GET    /api/admin/pages/{id}       show
 *   PUT    /api/admin/pages/{id}       update
 *   DELETE /api/admin/pages/{id}       delete
 *
 * The slug is a full URL path (e.g. "about/our-mission"), so the
 * regex allows forward slashes. Parent_slug, if set, must reference
 * an existing page's slug — admins can't create an orphan.
 */
class PageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 50;

        return response()->json(
            Page::orderBy('parent_slug')->orderBy('order')->orderBy('id')
                ->paginate($perPage)
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['page' => Page::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), $this->rules())->validate();
        $this->assertParentExists($data['parent_slug'] ?? null);

        $page = Page::create($data);

        return response()->json(['page' => $page], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        $data = Validator::make($request->all(), $this->updateRules($page->id))->validate();

        if (array_key_exists('parent_slug', $data)) {
            $this->assertParentExists($data['parent_slug']);
        }

        $page->update($data);

        return response()->json(['page' => $page]);
    }

    public function destroy(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        // Refuse to delete a page that has children — admins should
        // first move / delete the children, otherwise the index
        // pages get orphan links.
        $childCount = Page::where('parent_slug', $page->slug)->count();
        if ($childCount > 0) {
            return response()->json([
                'message' => "Cannot delete \"{$page->title}\" because {$childCount} sub-page(s) still reference it. Move or delete the sub-pages first.",
            ], 409);
        }

        $page->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Validation rules. `slug` accepts a-z, digits, hyphens, and
     * forward slashes (so "about/our-mission" works). The
     * `parent_slug` rule uses `nullable` so admins can clear a
     * parent assignment.
     *
     * @return array<string, array<int, string>>
     */
    private function rules(?int $ignoreId = null): array
    {
        return [
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9\-\/]*$/', $this->uniqueSlugRule($ignoreId)],
            'title' => ['required', 'string', 'min:1', 'max:160'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'enabled' => ['boolean'],
            'parent_slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9\-\/]*$/'],
        ];
    }

    /**
     * Same as rules() but every field is "sometimes" so the admin
     * can send a partial update (e.g. just `body` to edit one
     * field at a time).
     *
     * @return array<string, array<int, string>>
     */
    private function updateRules(int $ignoreId): array
    {
        $rules = [];
        foreach ($this->rules($ignoreId) as $field => $fieldRules) {
            // Strip "required" so the field is optional on update.
            $rules[$field] = array_values(array_filter(
                $fieldRules,
                fn ($r) => $r !== 'required' && ! (is_string($r) && str_starts_with($r, 'required|')),
            ));
            // Prepend "sometimes" so Laravel doesn't complain
            // about a missing field.
            array_unshift($rules[$field], 'sometimes');
        }
        return $rules;
    }

    private function uniqueSlugRule(?int $ignoreId): string
    {
        return $ignoreId
            ? 'unique:pages,slug,'.$ignoreId
            : 'unique:pages,slug';
    }

    private function assertParentExists(?string $parentSlug): void
    {
        if ($parentSlug === null) {
            return;
        }
        if (! Page::where('slug', $parentSlug)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_slug' => "No page exists with slug \"{$parentSlug}\".",
            ]);
        }
    }
}
