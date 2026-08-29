<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Phase 10 — admin site settings.
 *
 *   GET  /api/admin/site-settings   read the full key/value map
 *   PUT  /api/admin/site-settings   update one or more keys
 *
 * Only the keys in the `allowedKeys` allowlist are accepted on
 * PUT — this prevents admins (or a compromised admin session)
 * from injecting arbitrary keys into the table. New settings
 * require a code change + migration (intentional).
 */
class SiteSettingsController extends Controller
{
    /**
     * Settings the admin UI exposes. Adding a new admin-editable
     * setting means: add a row to the migration's seed, add a
     * getter to App\Support\Settings, and add a row here.
     */
    private const ALLOWED_KEYS = [
        'site.name',
        'site.tagline',
        'contact.email',
        'contact.address',
        'contact.phone',
    ];

    public function index(Settings $settings): JsonResponse
    {
        return response()->json(['settings' => $settings->all()]);
    }

    public function update(Request $request, Settings $settings): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        $requested = $data['settings'];
        $updates = array_intersect_key($requested, array_flip(self::ALLOWED_KEYS));

        if (empty($updates)) {
            return response()->json(['settings' => $settings->all()]);
        }

        foreach ($updates as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        // Invalidate the cache so the next read picks up the
        // changes immediately (instead of waiting up to 60s).
        $settings->flush();

        return response()->json(['settings' => $settings->all()]);
    }
}
