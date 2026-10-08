<?php

namespace Timmsy\PiltoverClient\RateLimit;

use Psr\Http\Message\ResponseInterface;

/** Response-driven application/method windows; state is local to this instance. */
final class InMemoryRateLimiter implements RateLimiter
{
    private array $windows = [];
    private array $cooldowns = [];

    public function __construct(array $state = [])
    {
        $this->windows = $state['windows'] ?? [];
        $this->cooldowns = $state['cooldowns'] ?? [];
    }

    public function state(): array
    {
        return ['windows' => $this->windows, 'cooldowns' => $this->cooldowns];
    }

    public function reserve(string $scope, string $method, float $now): float
    {
        $keys = [$scope, $scope . ':' . $method];
        $wait = max(0.0, ($this->cooldowns[$scope] ?? 0) - $now);
        foreach ($keys as $key) {
            foreach ($this->windows[$key] ?? [] as $seconds => $window) {
                if ($now >= $window['reset']) {
                    $this->windows[$key][$seconds]['count'] = 0;
                    $this->windows[$key][$seconds]['reset'] = $now + $seconds;
                } elseif ($window['count'] >= $window['limit']) {
                    $wait = max($wait, $window['reset'] - $now);
                }
            }
        }
        if ($wait <= 0) {
            foreach ($keys as $key) {
                foreach (array_keys($this->windows[$key] ?? []) as $seconds) {
                    $this->windows[$key][$seconds]['count']++;
                }
            }
        }
        return $wait;
    }

    public function observe(string $scope, string $method, ResponseInterface $response, float $now): void
    {
        foreach (['App' => $scope, 'Method' => $scope . ':' . $method] as $type => $key) {
            $limits = $this->pairs($response->getHeaderLine("X-{$type}-Rate-Limit"));
            $counts = $this->pairs($response->getHeaderLine("X-{$type}-Rate-Limit-Count"));
            foreach ($limits as $seconds => $limit) {
                $old = $this->windows[$key][$seconds] ?? null;
                $active = $old && $old['reset'] > $now;
                $this->windows[$key][$seconds] = [
                    'limit' => $limit,
                    'count' => max($counts[$seconds] ?? 1, $active ? $old['count'] : 0),
                    // Start the unknown server window at observation time, conservatively.
                    'reset' => $active ? $old['reset'] : $now + $seconds,
                ];
            }
        }
        if ($response->getStatusCode() === 429) {
            // Conservatively halt the entire credential/host scope, including service/unknown limits.
            $this->cooldowns[$scope] = max($this->cooldowns[$scope] ?? 0, $now + self::retryAfter($response, $now));
        }
    }

    public static function retryAfter(ResponseInterface $response, float $now): float
    {
        $value = trim($response->getHeaderLine('Retry-After'));
        if (is_numeric($value)) {
            return max(1.0, (float) $value);
        }
        $date = $value !== '' ? strtotime($value) : false;
        return $date === false ? 1.0 : max(1.0, $date - $now);
    }

    private function pairs(string $header): array
    {
        $pairs = [];
        foreach (explode(',', $header) as $pair) {
            if (preg_match('/^\s*(\d+):(\d+)\s*$/D', $pair, $m) && (int) $m[2] > 0) {
                $pairs[(int) $m[2]] = (int) $m[1];
            }
        }
        return $pairs;
    }
}
