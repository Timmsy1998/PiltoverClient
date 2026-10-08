<?php

namespace Timmsy\PiltoverClient\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Timmsy\PiltoverClient\Contracts\RiotClientContract;
use Timmsy\PiltoverClient\Exceptions\RateLimitException;
use Timmsy\PiltoverClient\Exceptions\RiotApiException;
use Timmsy\PiltoverClient\Exceptions\TransportException;
use Timmsy\PiltoverClient\RateLimit\InMemoryRateLimiter;
use Timmsy\PiltoverClient\RateLimit\RateLimiter;
use Timmsy\PiltoverClient\Support\Endpoints;
use Timmsy\PiltoverClient\Support\Routing;

class RiotClient implements RiotClientContract
{
    use LeagueEndpoints;

    private Client $http;
    private array $hosts;
    private RateLimiter $rateLimiter;
    private \Closure $clock;
    private \Closure $sleep;

    public function __construct(
        #[\SensitiveParameter] private string $apiKey,
        string $platform = 'euw1',
        ?string $regional = null,
        string $platformTpl = 'https://{platform}.api.riotgames.com',
        string $regionalTpl = 'https://{regional}.api.riotgames.com',
        float $timeout = 10.0,
        #[\SensitiveParameter] private ?string $tournamentApiKey = null,
        #[\SensitiveParameter] private ?string $rsoAccessToken = null,
        private int $maxRetries = 2,
        private float $maxWaitSeconds = 30.0,
        ?RateLimiter $rateLimiter = null,
        ?callable $handler = null,
        ?callable $clock = null,
        ?callable $sleep = null,
    ) {
        foreach ([$apiKey, $tournamentApiKey, $rsoAccessToken] as $credential) {
            if ($credential !== null && preg_match('/[\x00-\x20\x7f]/', $credential)) {
                throw new \InvalidArgumentException('Credentials must not contain whitespace or control characters.');
            }
        }
        if (!is_finite($timeout) || $timeout <= 0 || !is_finite($maxWaitSeconds) || $maxWaitSeconds < 0 || $maxRetries < 0 || $maxRetries > 10) {
            throw new \InvalidArgumentException('Invalid timeout, wait budget, or retry count.');
        }
        $platform = strtolower($platform);
        $derived = Routing::regionalFromPlatform($platform);
        $regional = Routing::validateRegional($regional ?: $derived);
        $this->hosts = [
            'platform' => Routing::host($platformTpl, 'platform', $platform),
            'regional' => Routing::host($regionalTpl, 'regional', $regional),
            // Account-v1 has three clusters; SEA accounts use Asia.
            'account' => Routing::host($regionalTpl, 'regional', $regional === 'sea' ? 'asia' : $regional),
            'tournament' => 'https://americas.api.riotgames.com',
        ];
        $this->rateLimiter = $rateLimiter ?? new InMemoryRateLimiter();
        $this->clock = \Closure::fromCallable($clock ?? static fn () => microtime(true));
        $this->sleep = \Closure::fromCallable($sleep ?? static fn (float $seconds) => usleep((int) ceil($seconds * 1_000_000)));
        $options = ['timeout' => $timeout, 'connect_timeout' => min(5.0, $timeout),
            'http_errors' => false, 'allow_redirects' => false, 'verify' => true];
        if ($handler !== null) {
            $options['handler'] = $handler;
        }
        $this->http = new Client($options);
    }

    public function ping(): string
    {
        return 'piltover-online';
    }

    public function endpoints(): array
    {
        return Endpoints::all();
    }

    /** Invoke a catalogue operation with encoded path values, query parameters, and an optional JSON body. */
    public function call(string $endpoint, array $path = [], array $query = [], ?array $body = null): mixed
    {
        $definition = Endpoints::get($endpoint);
        preg_match_all('/{(\w+)}/', $definition['path'], $matches);
        if (array_diff(array_keys($path), $matches[1]) || array_diff($matches[1], array_keys($path))) {
            throw new \InvalidArgumentException('Supply exactly the path parameters declared by the endpoint.');
        }
        $urlPath = $definition['path'];
        foreach ($path as $key => $value) {
            if ((!is_string($value) && !is_int($value)) || (string) $value === '' || in_array((string) $value, ['.', '..'], true)) {
                throw new \InvalidArgumentException('Path parameters must be nonempty strings or integers, excluding dot segments.');
            }
            $urlPath = str_replace('{' . $key . '}', rawurlencode((string) $value), $urlPath);
        }
        $allowedQuery = array_column($definition['query'], 'name');
        if (array_diff(array_keys($query), $allowedQuery)) {
            throw new \InvalidArgumentException('Unknown query parameter for this endpoint.');
        }
        foreach ($definition['query'] as $parameter) {
            if ($parameter['required'] && !isset($query[$parameter['name']])) {
                throw new \InvalidArgumentException('Missing required query parameter: ' . $parameter['name']);
            }
        }
        if ($definition['body'] !== ($body !== null)) {
            throw new \InvalidArgumentException($definition['body'] ? 'A JSON body is required.' : 'This endpoint does not accept a JSON body.');
        }
        $credential = match ($definition['auth']) {
            'rso' => $this->rsoAccessToken,
            'tournament' => $this->tournamentApiKey,
            default => $this->apiKey,
        };
        if ($credential === null || $credential === '') {
            throw new \InvalidArgumentException('Missing credential for endpoint authentication: ' . $definition['auth']);
        }
        $host = $this->hosts[$definition['route']];
        $scope = hash('sha256', $credential) . ':' . $host;
        $headers = ['Accept' => 'application/json', 'User-Agent' => 'PiltoverClient/' . trim(file_get_contents(__DIR__ . '/../../VERSION'))];
        $headers[$definition['auth'] === 'rso' ? 'Authorization' : 'X-Riot-Token'] = $definition['auth'] === 'rso' ? 'Bearer ' . $credential : $credential;
        $options = ['headers' => $headers, 'query' => $query];
        if ($body !== null) {
            $options['json'] = $body;
        }
        $waited = 0.0;
        for ($attempt = 0; ; $attempt++) {
            while (($delay = $this->rateLimiter->reserve($scope, $endpoint, ($this->clock)())) > 0) {
                $this->wait($delay, $waited, $endpoint);
            }
            try {
                $response = $this->http->request($definition['method'], $host . $urlPath, $options);
            } catch (GuzzleException) {
                // Transport failures may have sent a write. Never retry them or expose the original request.
                throw new TransportException('Riot API transport failed for ' . $endpoint . '.');
            }
            $now = ($this->clock)();
            $this->rateLimiter->observe($scope, $endpoint, $response, $now);
            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                $raw = (string) $response->getBody();
                if ($status === 204 || trim($raw) === '') {
                    return null;
                }
                try {
                    return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    throw new \UnexpectedValueException('Riot returned invalid JSON for ' . $endpoint . '.');
                }
            }
            $safeRetry = $definition['method'] === 'GET' && $attempt < $this->maxRetries;
            if ($status === 429) {
                $delay = InMemoryRateLimiter::retryAfter($response, $now);
                $type = $response->getHeaderLine('X-Rate-Limit-Type');
                $type = in_array($type, ['application', 'method', 'service'], true) ? $type : 'unknown';
                if (!$safeRetry || $waited + $delay > $this->maxWaitSeconds) {
                    throw new RateLimitException($endpoint, $delay, $type);
                }
                // The limiter retains Retry-After, so the next reservation waits even on a later call.
                continue;
            }
            if ($safeRetry && in_array($status, [500, 502, 503, 504], true)) {
                $delay = max(2 ** $attempt, $response->hasHeader('Retry-After') ? InMemoryRateLimiter::retryAfter($response, $now) : 0);
                if ($waited + $delay <= $this->maxWaitSeconds) {
                    $this->wait($delay, $waited, $endpoint);
                    continue;
                }
            }
            throw new RiotApiException($status, $endpoint);
        }
    }

    private function wait(float $delay, float &$waited, string $endpoint): void
    {
        if (!is_finite($delay) || $waited + $delay > $this->maxWaitSeconds) {
            throw new RateLimitException($endpoint, $delay);
        }
        ($this->sleep)($delay);
        $waited += $delay;
    }

    public function __debugInfo(): array
    {
        return ['hosts' => $this->hosts, 'maxRetries' => $this->maxRetries, 'maxWaitSeconds' => $this->maxWaitSeconds];
    }
}
