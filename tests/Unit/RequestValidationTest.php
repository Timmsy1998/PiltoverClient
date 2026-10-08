<?php

namespace Timmsy\PiltoverClient\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Timmsy\PiltoverClient\Http\RiotClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Timmsy\PiltoverClient\Exceptions\RiotApiException;

class RequestValidationTest extends TestCase
{
    public static function invalidCalls(): iterable
    {
        yield ['unknown.endpoint', [], [], null];
        yield ['match-v5.getMatch', [], [], null];
        yield ['match-v5.getMatch', ['matchId' => 'id', 'extra' => 'x'], [], null];
        yield ['match-v5.getMatch', ['matchId' => '..'], [], null];
        yield ['match-v5.getMatch', ['matchId' => ''], [], null];
        yield ['match-v5.getMatch', ['matchId' => []], [], null];
        yield ['match-v5.getMatch', ['matchId' => 'id'], [], ['unexpected' => 1]];
        yield ['tournament-stub-v5.createTournamentCode', [], [], []];
        yield ['tournament-stub-v5.registerProviderData', [], [], null];
    }

    #[DataProvider('invalidCalls')]
    public function test_invalid_inputs_fail_before_network(string $endpoint, array $path, array $query, ?array $body): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new RiotClient('key'))->call($endpoint, $path, $query, $body);
    }

    public function test_header_injection_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RiotClient("key\r\nInjected: value");
    }

    public function test_invalid_json_is_redacted(): void
    {
        $client = new RiotClient('key', handler: HandlerStack::create(new MockHandler([new Response(200, [], 'sensitive body')])));
        try { $client->platformStatus(); self::fail('Expected JSON error.'); }
        catch (\UnexpectedValueException $e) { self::assertStringNotContainsString('sensitive body', (string) $e); }
    }

    public function test_server_retries_are_bounded(): void
    {
        $now = 0.0; $slept = [];
        $client = new RiotClient('key', handler: HandlerStack::create(new MockHandler([
            new Response(503), new Response(502), new Response(500),
        ])), clock: function () use (&$now) { return $now; },
            sleep: function ($delay) use (&$now, &$slept) { $now += $delay; $slept[] = $delay; });
        try { $client->platformStatus(); self::fail('Expected API error.'); }
        catch (RiotApiException $e) { self::assertSame(500, $e->statusCode); }
        self::assertSame([1.0, 2.0], $slept);
    }

    public function test_permissions_errors_are_not_retried(): void
    {
        $client = new RiotClient('key', handler: HandlerStack::create(new MockHandler([new Response(403)])));
        $this->expectException(RiotApiException::class);
        $client->platformStatus();
    }
}
