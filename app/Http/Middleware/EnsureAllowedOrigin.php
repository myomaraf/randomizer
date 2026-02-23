<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAllowedOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = $this->allowedOrigins();

        $origin = $this->normalizeOrigin((string) $request->headers->get('Origin', ''));
        $refererOrigin = $this->normalizeOrigin((string) $request->headers->get('Referer', ''));

        $isAllowed = ($origin !== null && in_array($origin, $allowedOrigins, true))
            || ($refererOrigin !== null && in_array($refererOrigin, $allowedOrigins, true));

        if (! $isAllowed) {
            return $this->forbiddenResponse($allowedOrigins);
        }

        return $next($request);
    }

    private function forbiddenResponse(array $allowedOrigins): JsonResponse
    {
        return response()->json([
            'message' => 'Request origin is not allowed.',
            'allowed_origins' => $allowedOrigins,
        ], 403);
    }

    private function allowedOrigins(): array
    {
        $configuredOrigins = config('omaraf.allowed_origins', []);

        if (! is_array($configuredOrigins)) {
            return [];
        }

        $normalized = [];

        foreach ($configuredOrigins as $origin) {
            if (! is_string($origin)) {
                continue;
            }

            $normalizedOrigin = $this->normalizeOrigin($origin);

            if ($normalizedOrigin !== null) {
                $normalized[] = $normalizedOrigin;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeOrigin(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        // Accept full referer URLs and plain origins, then reduce to scheme://host[:port].
        $parts = parse_url($value);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? null;

        $origin = $scheme.'://'.$host;

        if (is_int($port)) {
            $isDefaultPort = ($scheme === 'http' && $port === 80)
                || ($scheme === 'https' && $port === 443);

            if (! $isDefaultPort) {
                $origin .= ':'.$port;
            }
        }

        return $origin;
    }
}
