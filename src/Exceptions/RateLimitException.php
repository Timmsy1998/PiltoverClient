<?php

namespace Timmsy\PiltoverClient\Exceptions;

class RateLimitException extends RiotApiException
{
    public function __construct(string $endpoint, public readonly float $retryAfter, public readonly string $limitType = 'unknown')
    {
        parent::__construct(429, $endpoint);
    }
}
