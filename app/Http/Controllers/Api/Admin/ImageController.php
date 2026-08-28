<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Phase 2 — minimal admin endpoint for uploading featured images.
 *
 *   POST /api/admin/images
 *     - field: "image"
 *     - accepted: jpg, jpeg, png, webp, gif
 *     - max: 5 MB
 *     - response: { "url": "/storage/posts/abc123.jpg" }
 *
 * Stored on the public disk under `posts/` so it is reachable at
 * `/storage/posts/...` once `php artisan storage:link` has run.
 *
 * This is intentionally minimal — full admin (post create/edit) is Phase 4.
 */
class ImageController extends Controller
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $file = $request->file('image');

        // Defence-in-depth: reject files whose mime type does not match the
        // declared extension. file->getMimeType() inspects contents.
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            return response()->json([
                'message' => 'Unsupported image type.',
            ], 422);
        }

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = Str::random(24).'.'.$extension;

        $path = $file->storeAs('posts', $filename, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
            'bytes' => $file->getSize(),
        ], 201);
    }
}
