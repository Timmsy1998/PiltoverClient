<?php

namespace Timmsy\PiltoverClient\Exceptions;

class RiotApiException extends \RuntimeException
{
    public function __construct(public readonly int $statusCode, public readonly string $endpoint)
    {
        // Do not retain the request, response body, URL identifiers, credentials, or a Guzzle exception.
        parent::__construct("Riot API request failed (HTTP {$statusCode}) for {$endpoint}.", $statusCode);
    }
}
