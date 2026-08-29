<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\JsonResponse;

/**
 * Phase 8 — public read of admin-managed social links.
 *
 *   GET /api/social-links
 *
 * Returns the enabled list, ordered by `(order, id)`. The Blade
 * chrome (top utility bar + footer) also reads these via a view
 * composer in `AppServiceProvider`, so this endpoint is primarily
 * here for symmetry / future use (e.g. a footer that mounts the
 * list via JS, or an "open graph" consumer that wants the brand's
 * social handles).
 */
class SocialLinkController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SocialLink::query()
                ->enabled()
                ->ordered()
                ->get(['id', 'platform', 'label', 'url', 'icon', 'order']),
        ]);
    }
}
