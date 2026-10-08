<?php

namespace Timmsy\PiltoverClient\RateLimit;

use Psr\Http\Message\ResponseInterface;

/** Implement atomically in a shared store when multiple workers use the same key. */
interface RateLimiter
{
    /** Reserve one request, or return the seconds until it can be reserved. */
    public function reserve(string $scope, string $method, float $now): float;
    public function observe(string $scope, string $method, ResponseInterface $response, float $now): void;
}
