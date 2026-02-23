<?php

$allowedOrigins = array_values(array_filter(array_map(
    static fn (string $origin): string => trim($origin),
    explode(',', (string) env('OMARAF_ALLOWED_ORIGINS', 'https://myomaraf.com,https://www.myomaraf.com'))
), static fn (string $origin): bool => $origin !== ''));

return [
    'allowed_origins' => $allowedOrigins,
    'max_uuids' => (int) env('OMARAF_MAX_UUIDS', 20000),
    'rate_limit_per_minute' => (int) env('OMARAF_RATE_LIMIT_PER_MINUTE', 60),
    'algorithm_version' => (string) env('OMARAF_ALGORITHM_VERSION', 'v1'),
];
