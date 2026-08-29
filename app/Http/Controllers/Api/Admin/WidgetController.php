<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Widget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Phase 6 — admin widget CRUD.
 *
 *   GET    /api/admin/widgets            paginated, ordered by (order, id)
 *   POST   /api/admin/widgets            create
 *   GET    /api/admin/widgets/{id}       show
 *   PUT    /api/admin/widgets/{id}       update
 *   DELETE /api/admin/widgets/{id}       delete
 *
 * Behind auth + admin middleware. The `type` is a free-form string
 * (the SidebarResolver switches on it). The validator keeps the
 * surface honest by allowing only known types so a typo doesn't
 * silently break the public sidebar.
 */
class WidgetController extends Controller
{
    private const ALLOWED_TYPES = [
        'popular_recent_comments',
        'category',
        'video',
        'html',
        'social',
        'archives',
        'newsletter',
    ];

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 50;

        return response()->json(
            Widget::orderBy('order')->orderBy('id')->paginate($perPage)
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['widget' => Widget::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_TYPES)],
            'name' => ['required', 'string', 'min:1', 'max:80'],
            'position' => ['nullable', 'string', 'in:left,right'],
            'order' => ['nullable', 'integer', 'min:0'],
            'enabled' => ['boolean'],
            'settings' => ['nullable', 'array'],
        ])->validate();

        $widget = new Widget([
            'type' => $data['type'],
            'name' => $data['name'],
            'position' => $data['position'] ?? 'right',
            'order' => $data['order'] ?? 0,
            'enabled' => $data['enabled'] ?? true,
            'settings' => $data['settings'] ?? null,
        ]);
        $widget->save();

        return response()->json(['widget' => $widget], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $widget = Widget::findOrFail($id);

        $data = Validator::make($request->all(), [
            'type' => ['sometimes', 'required', 'string', 'in:'.implode(',', self::ALLOWED_TYPES)],
            'name' => ['sometimes', 'required', 'string', 'min:1', 'max:80'],
            'position' => ['nullable', 'string', 'in:left,right'],
            'order' => ['nullable', 'integer', 'min:0'],
            'enabled' => ['boolean'],
            'settings' => ['nullable', 'array'],
        ])->validate();

        foreach (['type', 'name', 'position', 'order', 'enabled', 'settings'] as $field) {
            if (array_key_exists($field, $data)) {
                $widget->{$field} = $data[$field];
            }
        }
        $widget->save();

        return response()->json(['widget' => $widget]);
    }

    public function destroy(int $id): JsonResponse
    {
        $widget = Widget::findOrFail($id);
        $widget->delete();

        return response()->json(['deleted' => true]);
    }
}
