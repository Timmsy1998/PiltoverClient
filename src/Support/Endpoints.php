<?php

namespace Timmsy\PiltoverClient\Support;

final class Endpoints
{
    public static function all(): array
    {
        static $endpoints;
        return $endpoints ??= json_decode(file_get_contents(__DIR__ . '/../../resources/riot-endpoints.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function get(string $id): array
    {
        return self::all()[$id] ?? throw new \InvalidArgumentException('Unknown Riot endpoint: ' . $id);
    }
}
