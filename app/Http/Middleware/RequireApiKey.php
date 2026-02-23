<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawApiKey = trim((string) $request->headers->get('X-API-KEY', ''));

        if ($rawApiKey === '') {
            return $this->unauthorized('API key missing');
        }

        $keyHash = hash('sha256', $rawApiKey);

        $apiKey = ApiKey::query()
            ->where('key_hash', $keyHash)
            ->where('is_active', true)
            ->first();

        if ($apiKey === null) {
            return $this->unauthorized('Invalid API key');
        }

        $apiKey->forceFill([
            'last_used_at' => now(),
        ])->save();

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 401);
    }
}
