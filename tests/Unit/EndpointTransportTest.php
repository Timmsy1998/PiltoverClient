<?php

declare(strict_types=1);

namespace Timmsy\PiltoverClient\Tests\Unit;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Timmsy\PiltoverClient\Http\RiotClient;
use Timmsy\PiltoverClient\Support\Endpoints;
use Timmsy\PiltoverClient\Exceptions\RateLimitException;
use Timmsy\PiltoverClient\Exceptions\RiotApiException;
use Timmsy\PiltoverClient\Exceptions\TransportException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

class EndpointTransportTest extends TestCase
{
    public static function endpoints(): iterable
    {
        foreach (Endpoints::all() as $id => $definition) {
            yield $id => [$definition];
        }
    }

    #[DataProvider('endpoints')]
    public function test_every_named_helper_routes_and_authenticates(array $definition): void
    {
        $history = [];
        $response = $definition['return'] === 'int' ? '42' : ($definition['return'] === 'null' ? '' : '{"ok":true}');
        $handler = HandlerStack::create(new MockHandler([new Response(200, [], $response)]));
        $handler->push(Middleware::history($history));
        $client = new RiotClient('standard-key', tournamentApiKey: 'tournament-key', rsoAccessToken: 'rso-token', handler: $handler);
        preg_match_all('/{(\w+)}/', $definition['path'], $matches);
        $arguments = []; $expectedPath = $definition['path'];
        foreach ($matches[1] as $key) {
            $arguments[] = 'value /#';
            $expectedPath = str_replace('{' . $key . '}', 'value%20%2F%23', $expectedPath);
        }
        if ($definition['body']) {
            $arguments[] = ['name' => 'test'];
        }
        if ($definition['query']) {
            $arguments[] = array_fill_keys(array_column($definition['query'], 'name'), 1);
        }
        $client->{$definition['helper']}(...$arguments);
        $request = $history[0]['request'];
        $host = match ($definition['route']) {
            'platform' => 'euw1', 'tournament' => 'americas', default => 'europe',
        };
        self::assertSame($host . '.api.riotgames.com', $request->getUri()->getHost());
        self::assertSame($expectedPath, $request->getUri()->getPath());
        self::assertSame($definition['method'], $request->getMethod());
        self::assertFalse($history[0]['options']['allow_redirects']);
        self::assertTrue($history[0]['options']['verify']);
        if ($definition['auth'] === 'rso') {
            self::assertSame('Bearer rso-token', $request->getHeaderLine('Authorization'));
            self::assertFalse($request->hasHeader('X-Riot-Token'));
        } else {
            self::assertSame($definition['auth'] === 'tournament' ? 'tournament-key' : 'standard-key', $request->getHeaderLine('X-Riot-Token'));
            self::assertFalse($request->hasHeader('Authorization'));
        }
        if ($definition['body']) {
            self::assertSame(['name' => 'test'], json_decode((string) $request->getBody(), true));
        }
        self::assertStringNotContainsString('-key', (string) $request->getUri());
    }

    public function test_retry_after_and_header_windows_wait_without_network(): void
    {
        $now = 100.0; $sleeps = []; $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(429, ['Retry-After' => '2', 'X-Rate-Limit-Type' => 'application']),
            new Response(200, ['X-App-Rate-Limit' => '1:10', 'X-App-Rate-Limit-Count' => '1:10'], '{}'),
            new Response(200, [], '{}'),
        ]));
        $handler->push(Middleware::history($history));
        $client = new RiotClient('key', handler: $handler, clock: function () use (&$now) { return $now; },
            sleep: function (float $seconds) use (&$now, &$sleeps) { $now += $seconds; $sleeps[] = $seconds; });
        $client->summonerByPuuid('first');
        $client->championRotations();
        self::assertSame([2.0, 10.0], $sleeps);
        self::assertCount(3, $history);
    }

    public function test_writes_are_never_retried_and_errors_are_redacted(): void
    {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([new Response(503, [], 'secret-tournament-key')]));
        $handler->push(Middleware::history($history));
        $client = new RiotClient('key', tournamentApiKey: 'secret-tournament-key', handler: $handler);
        try {
            $client->registerTournamentProvider(['region' => 'EUW', 'url' => 'https://example.org/callback']);
            self::fail('Expected API exception.');
        } catch (RiotApiException $e) {
            self::assertSame(503, $e->statusCode);
            self::assertStringNotContainsString('secret-tournament-key', (string) $e);
            self::assertNull($e->getPrevious());
        }
        self::assertCount(1, $history);
        ob_start(); var_dump($client); $debug = ob_get_clean();
        self::assertStringNotContainsString('secret-tournament-key', $debug);
    }

    public function test_wait_budget_returns_retry_metadata(): void
    {
        $client = new RiotClient('key', maxWaitSeconds: 1, handler: HandlerStack::create(new MockHandler([
            new Response(429, ['Retry-After' => '5', 'X-Rate-Limit-Type' => 'service']),
        ])));
        try {
            $client->platformStatus();
            self::fail('Expected rate limit.');
        } catch (RateLimitException $e) {
            self::assertSame(5.0, $e->retryAfter);
            self::assertSame('service', $e->limitType);
        }
    }

    public function test_sea_account_and_match_use_different_clusters(): void
    {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([new Response(200, [], '{}'), new Response(200, [], '[]')]));
        $handler->push(Middleware::history($history));
        $client = new RiotClient('key', 'oc1', handler: $handler);
        $client->accountByPuuid('puuid'); $client->matchesByPuuid('puuid');
        self::assertSame('asia.api.riotgames.com', $history[0]['request']->getUri()->getHost());
        self::assertSame('sea.api.riotgames.com', $history[1]['request']->getUri()->getHost());
    }

    public static function unsafeHosts(): iterable
    {
        foreach (['http://euw1.api.riotgames.com', 'https://evil.example', 'https://euw1.api.riotgames.com.evil.example',
            'https://user@euw1.api.riotgames.com', 'https://euw1.api.riotgames.com:443', 'https://euw1.api.riotgames.com/path',
            'https://euw1.api.riotgames.com?key=x'] as $host) { yield [$host]; }
    }

    #[DataProvider('unsafeHosts')]
    public function test_unsafe_origins_are_rejected(string $host): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RiotClient('key', platformTpl: $host);
    }

    public function test_missing_tournament_credential_fails_before_sending(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new RiotClient('normal-key'))->tournamentCode('code');
    }

    public function test_transport_exception_hides_request_credentials(): void
    {
        $client = new RiotClient('secret', handler: HandlerStack::create(new MockHandler([
            new ConnectException('secret', new Request('GET', 'https://euw1.api.riotgames.com', ['X-Riot-Token' => 'secret'])),
        ])));
        try { $client->platformStatus(); self::fail('Expected transport exception.'); }
        catch (TransportException $e) { self::assertStringNotContainsString('secret', (string) $e); self::assertNull($e->getPrevious()); }
    }

    public function test_redirects_are_errors(): void
    {
        $client = new RiotClient('key', handler: HandlerStack::create(new MockHandler([new Response(302, ['Location' => 'https://evil.example'])])));
        $this->expectException(RiotApiException::class);
        $client->platformStatus();
    }

    public function test_invalid_queries_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new RiotClient('key'))->matchesByPuuid('puuid', ['api_key' => 'secret']);
    }
}
