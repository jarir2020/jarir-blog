<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 4 — admin view of newsletter subscribers.
 */
class SubscriberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page') ?: 50;

        return response()->json(
            Subscriber::orderBy('subscribed_at', 'desc')->paginate($perPage)
        );
    }
}
