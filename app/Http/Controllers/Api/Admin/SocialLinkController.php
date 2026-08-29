<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Phase 8 — admin social link CRUD.
 *
 *   GET    /api/admin/social-links          paginated, ordered (order, id)
 *   POST   /api/admin/social-links          create
 *   GET    /api/admin/social-links/{id}     show
 *   PUT    /api/admin/social-links/{id}     update
 *   DELETE /api/admin/social-links/{id}     delete
 *
 * The `icon` column is auto-set from `platform` in this controller
 * (so admins can't accidentally key the two fields wrong) but
 * `icon` is included in the response so the Blade chrome can read
 * whichever field it prefers.
 */
class SocialLinkController extends Controller
{
    /**
     * Keep `icon` in sync with `platform`. The partial
     * `resources/views/components/site/_social-icon.blade.php` is
     * the source of truth for which platforms have icons; here we
     * just copy `platform` into `icon` on save.
     */
    private const PLATFORMS = [
        'facebook', 'x', 'youtube', 'instagram', 'linkedin', 'github', 'rss', 'custom',
    ];

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 50;

        return response()->json(
            SocialLink::orderBy('order')->orderBy('id')->paginate($perPage)
        );
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['social_link' => SocialLink::findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'platform' => ['required', 'string', 'in:'.implode(',', self::PLATFORMS)],
            'label' => ['required', 'string', 'min:1', 'max:80'],
            'url' => ['required', 'url', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
            'enabled' => ['boolean'],
        ])->validate();

        $link = new SocialLink([
            'platform' => $data['platform'],
            'label' => $data['label'],
            'url' => $data['url'],
            'icon' => $data['platform'], // mirror
            'order' => $data['order'] ?? 0,
            'enabled' => $data['enabled'] ?? true,
        ]);
        $link->save();

        return response()->json(['social_link' => $link], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $link = SocialLink::findOrFail($id);

        $data = Validator::make($request->all(), [
            'platform' => ['sometimes', 'required', 'string', 'in:'.implode(',', self::PLATFORMS)],
            'label' => ['sometimes', 'required', 'string', 'min:1', 'max:80'],
            'url' => ['sometimes', 'required', 'url', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
            'enabled' => ['boolean'],
        ])->validate();

        // Re-mirror icon when platform changes. Admins can never
        // set the two fields independently.
        if (array_key_exists('platform', $data) && $data['platform'] !== $link->platform) {
            $link->platform = $data['platform'];
            $link->icon = $data['platform'];
        }
        foreach (['label', 'url', 'order', 'enabled'] as $field) {
            if (array_key_exists($field, $data)) {
                $link->{$field} = $data[$field];
            }
        }
        $link->save();

        return response()->json(['social_link' => $link]);
    }

    public function destroy(int $id): JsonResponse
    {
        $link = SocialLink::findOrFail($id);
        $link->delete();

        return response()->json(['deleted' => true]);
    }
}
