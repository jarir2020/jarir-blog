<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;

/**
 * Phase 10 — public read of admin-editable site settings.
 *
 *   GET /api/site-settings
 *
 * Returns a flat key => value map. The Vue SPA's Contact view
 * (and any other future client-side consumer) reads this to
 * render contact details without needing to know the underlying
 * database structure.
 */
class SiteSettingsController extends Controller
{
    public function index(Settings $settings): JsonResponse
    {
        return response()->json([
            'settings' => $settings->all(),
        ]);
    }
}
