<?php

return [
    "api_key" => env("RIOT_API_KEY"),
    "platform" => env("RIOT_PLATFORM", "euw1"),
    "regional" => env("RIOT_REGIONAL", null),
    "base_urls" => [
        "platform" => "https://{platform}.api.riotgames.com",
        "regional" => "https://{regional}.api.riotgames.com",
    ],
    "timeout" => 10,

    "docs" => [
        "enabled" => env("PILTOVER_DOCS_ENABLED", true),
        "path" => env("PILTOVER_DOCS_PATH", "piltover/docs"),
        "middleware" => ["web"],
        "spec_path" => env("PILTOVER_SPEC_PATH", "piltover/docs/openapi.json"),
    ],
];
