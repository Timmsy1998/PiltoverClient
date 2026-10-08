<?php

namespace Timmsy\PiltoverClient\Support;

final class Routing
{
    private const REGIONS = [
        'br1' => 'americas', 'la1' => 'americas', 'la2' => 'americas', 'na1' => 'americas',
        'euw1' => 'europe', 'eun1' => 'europe', 'tr1' => 'europe', 'ru' => 'europe', 'me1' => 'europe',
        'kr' => 'asia', 'jp1' => 'asia',
        'oc1' => 'sea', 'sg2' => 'sea', 'tw2' => 'sea', 'vn2' => 'sea',
        // Legacy SEA platforms are accepted for applications migrating stored routing values.
        'ph2' => 'sea', 'th2' => 'sea',
    ];

    public static function regionalFromPlatform(string $platform): string
    {
        return self::REGIONS[strtolower($platform)] ?? throw new \InvalidArgumentException('Unsupported Riot platform.');
    }

    public static function validateRegional(string $regional): string
    {
        $regional = strtolower($regional);
        if (!in_array($regional, ['americas', 'europe', 'asia', 'sea'], true)) {
            throw new \InvalidArgumentException('Unsupported Riot region.');
        }
        return $regional;
    }

    public static function host(string $template, string $key, string $value): string
    {
        $host = str_replace('{' . $key . '}', $value, $template);
        $parts = parse_url($host);
        // Credentials must never be sent to a proxy, arbitrary URL, or insecure transport.
        if (!$parts || ($parts['scheme'] ?? '') !== 'https'
            || !preg_match('/^(?:' . implode('|', array_keys(self::REGIONS)) . '|americas|europe|asia|sea)\.api\.riotgames\.com$/D', $parts['host'] ?? '')
            || isset($parts['user'], $parts['pass']) || isset($parts['user'])
            || isset($parts['port']) || isset($parts['query']) || isset($parts['fragment'])
            || !in_array($parts['path'] ?? '', ['', '/'], true)) {
            throw new \InvalidArgumentException('Base URL must be an official Riot HTTPS API origin.');
        }
        return rtrim($host, '/');
    }
}
