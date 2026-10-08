<?php

return [
    'api_key' => env('RIOT_API_KEY'),
    'tournament_api_key' => env('RIOT_TOURNAMENT_API_KEY'),
    'rso_access_token' => env('RIOT_RSO_ACCESS_TOKEN'),
    'platform' => env('RIOT_PLATFORM', 'euw1'),
    'regional' => env('RIOT_REGIONAL'),
    'base_urls' => [
        'platform' => 'https://{platform}.api.riotgames.com',
        'regional' => 'https://{regional}.api.riotgames.com',
    ],
    'timeout' => (float) env('RIOT_TIMEOUT', 10),
    'max_retries' => 2,
    'max_wait_seconds' => 30,
    'docs' => [
        'enabled' => env('PILTOVER_DOCS_ENABLED', false),
        'path' => env('PILTOVER_DOCS_PATH', 'piltover/docs'),
        'middleware' => ['web'],
        'spec_path' => env('PILTOVER_SPEC_PATH', 'piltover/docs/openapi.json'),
    ],
];
