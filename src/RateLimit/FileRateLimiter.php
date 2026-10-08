<?php

namespace Timmsy\PiltoverClient\RateLimit;

use Psr\Http\Message\ResponseInterface;

/** Shares windows between workers on one host using exclusive file locks. */
final class FileRateLimiter implements RateLimiter
{
    public function __construct(private string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create rate-limit storage.');
        }
        if (!is_writable($directory)) {
            throw new \RuntimeException('Rate-limit storage must be writable.');
        }
    }

    public function reserve(string $scope, string $method, float $now): float
    {
        return $this->transaction($scope, fn (InMemoryRateLimiter $limiter) => $limiter->reserve($scope, $method, $now));
    }

    public function observe(string $scope, string $method, ResponseInterface $response, float $now): void
    {
        $this->transaction($scope, function (InMemoryRateLimiter $limiter) use ($scope, $method, $response, $now) {
            $limiter->observe($scope, $method, $response, $now);
            return null;
        });
    }

    private function transaction(string $scope, callable $operation): mixed
    {
        $path = $this->directory . '/' . hash('sha256', $scope) . '.json';
        $file = fopen($path, 'c+');
        if ($file === false) {
            throw new \RuntimeException('Cannot open rate-limit storage.');
        }
        try {
            if (!flock($file, LOCK_EX)) {
                throw new \RuntimeException('Cannot lock rate-limit storage.');
            }
            chmod($path, 0600);
            $raw = stream_get_contents($file);
            $state = $raw === '' ? [] : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $limiter = new InMemoryRateLimiter($state);
            $result = $operation($limiter);
            $encoded = json_encode($limiter->state(), JSON_THROW_ON_ERROR);
            rewind($file);
            if (!ftruncate($file, 0) || fwrite($file, $encoded) !== strlen($encoded) || !fflush($file)) {
                throw new \RuntimeException('Cannot save rate-limit storage.');
            }
            return $result;
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
