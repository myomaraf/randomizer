<?php

return [
    'max_uuids' => (int) env('OMARAF_MAX_UUIDS', 20000),
    'rate_limit' => (int) env('OMARAF_RATE_LIMIT', 60),
    'algorithm_version' => (string) env('OMARAF_ALGORITHM_VERSION', '1'),
];
