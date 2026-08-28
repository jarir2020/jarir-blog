<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Phase 3 — newsletter subscriptions.
 *
 *   POST /api/subscribe   { email }
 *
 * Idempotent: re-subscribing the same email is a no-op success.
 */
class SubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:120'],
        ])->validate();

        $subscriber = Subscriber::firstOrCreate(
            ['email' => strtolower($data['email'])],
            ['subscribed_at' => now()]
        );

        return response()->json([
            'subscribed' => true,
            'email' => $subscriber->email,
        ], 201);
    }
}
