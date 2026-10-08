# PiltoverClient

A Composer package for Riot's League of Legends developer APIs, usable in plain PHP and Laravel. PHP 8.2+ is required. Laravel is optional.

Version 2 covers **50 distinct operations across 14 API families**, reviewed against [Riot's current API reference](https://developer.riotgames.com/apis/): account, summoner, champion rotation, champion mastery, league, league-exp, clash, challenges, status, spectator, match, RSO match, tournament and tournament-stub. See the [complete method catalogue](docs/endpoints.md). This scope is the developer portal; Data Dragon, local game/replay APIs and the unsupported League Client API are separate services.

## Install

Once the release is published to Packagist:

```sh
composer require timmsy/piltover-client:^2.0
```

Until then, add the repository to your application's `composer.json`, then run the same command after the tag has been pushed:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/Timmsy1998/PiltoverClient" }
  ],
  "require": { "timmsy/piltover-client": "^2.0" }
}
```

For local development, use a Composer `path` repository pointing to this checkout with `"options": {"versions": {"timmsy/piltover-client": "2.0.0"}}`. Composer handles autoloading and dependencies; manually copying source files is unnecessary.

## Plain PHP

```php
require 'vendor/autoload.php';

use Timmsy\PiltoverClient\Http\RiotClient;

$client = new RiotClient(
    apiKey: getenv('RIOT_API_KEY') ?: '',
    platform: 'euw1',
);
$account = $client->accountByRiotId('TheCHEF', 'EUW');
$summoner = $client->summonerByPuuid($account['puuid']);
$ids = $client->matchesByPuuid($account['puuid'], ['count' => 5]);
if ($ids !== []) {
    $match = $client->matchById($ids[0]);
    $timeline = $client->matchTimeline($ids[0]);
}
$mastery = $client->championMasteryByPuuid($account['puuid'], 99);
$challenger = $client->challengerLeague('RANKED_SOLO_5x5');
```

JSON objects/lists return PHP arrays, integer responses return integers, and empty responses return `null`. Helpers accept encoded-safe raw IDs and optional query arrays. Riot validates request DTOs; follow the linked official reference for fields and permissions. Pagination is explicit: adjust `start`/`count` or `page` yourself to control API consumption.

Every operation also supports a catalogue call:

```php
$timeline = $client->call('match-v5.getTimeline', ['matchId' => 'EUW1_123456']);
$operations = $client->endpoints();
```

Platforms derive their regional cluster automatically. Oceania and SEA matches use `sea`; account-v1 has three clusters, so SEA accounts use `asia`. `regional:` can explicitly override the derived cluster. Tournament APIs use the Americas cluster regardless of platform; the registration body selects the tournament's game region.

## Tournament APIs

[Production tournaments require Riot approval and a tournament-enabled key](https://developer.riotgames.com/docs/lol#tournament-api). Installing the package does not grant access. Stub endpoints use your normal API key and are for development.

```php
$client = new RiotClient(
    apiKey: getenv('RIOT_API_KEY') ?: '',
    platform: 'euw1',
    tournamentApiKey: getenv('RIOT_TOURNAMENT_API_KEY') ?: null,
);
$providerId = $client->registerTournamentProvider([
    'region' => 'EUW',
    'url' => 'https://your-app.example/riot/tournament-results',
]);
$tournamentId = $client->registerTournament([
    'providerId' => $providerId,
    'name' => 'Community Cup',
]);
$codes = $client->createTournamentCodes([
    'mapType' => 'SUMMONERS_RIFT',
    'pickType' => 'TOURNAMENT_DRAFT',
    'spectatorType' => 'ALL',
    'teamSize' => 5,
], ['tournamentId' => $tournamentId, 'count' => 1]);
$details = $client->tournamentCode($codes[0]);
$events = $client->tournamentLobbyEvents($codes[0]);
$games = $client->tournamentGames($codes[0]);
$client->updateTournamentCode($codes[0], ['spectatorType' => 'LOBBYONLY']);
```

Stub equivalents are `registerTournamentStubProvider`, `registerTournamentStub`, `createTournamentStubCodes`, `tournamentStubCode`, and `tournamentStubLobbyEvents`. Riot's callbacks arrive at your application; this package does not expose a callback receiver. Use Riot's documented callback handling and fetch canonical game data before acting on notifications.

## Riot Sign On

RSO requires separate approval and an OAuth integration. Pass the player's bearer token for `accountMe()`, `summonerMe()`, `rsoMatchIds()`, `rsoMatchById()` and `rsoMatchTimeline()`:

```php
$playerClient = new RiotClient(apiKey: '', platform: 'euw1', rsoAccessToken: $playerAccessToken);
$player = $playerClient->accountMe();
```

Create a client per player token. OAuth authorization, token refresh and storage belong to your application. A normal API key cannot replace an RSO token.

## Rate limits and errors

The client learns application and method windows from Riot's response headers and reserves capacity before requests. It respects `Retry-After`, including service limits, and retries GET responses for 429/500/502/503/504 at most twice. POST/PUT and transport failures are never automatically retried, avoiding duplicate providers, tournaments or codes. Defaults are a 10-second request timeout and a 30-second total sleep budget per call; request time is additional.

```php
use Timmsy\PiltoverClient\Exceptions\RateLimitException;
use Timmsy\PiltoverClient\Exceptions\RiotApiException;
use Timmsy\PiltoverClient\Exceptions\TransportException;

try {
    $status = $client->platformStatus();
} catch (RateLimitException $e) {
    // Queue another attempt after $e->retryAfter seconds.
    // $e->limitType is application, method, service or unknown.
} catch (RiotApiException $e) {
    // Inspect $e->statusCode and $e->endpoint; e.g. 403 for key/permission problems.
} catch (TransportException $e) {
    // For writes, verify their outcome before attempting again.
}
```

Use `maxRetries: 0, maxWaitSeconds: 0` for immediate rate-limit exceptions and queue-based scheduling. Rate limits depend on your key and host, rather than fixed development-key quotas. Limits are unknown until the first response, so coordinate startup bursts. Requests from other applications sharing the key also consume its allowance.

The default limiter lives in one client instance. Share a limiter between clients, and use file storage for workers on the same machine:

```php
use Timmsy\PiltoverClient\RateLimit\FileRateLimiter;

$client = new RiotClient(
    apiKey: getenv('RIOT_API_KEY') ?: '',
    rateLimiter: new FileRateLimiter('/private/app/storage/riot-rate-limits'),
);
```

The directory must be private, outside the web root, with reliable local file locking. File reservations are atomic across processes, but do not coordinate separate servers. For distributed workers implement `RateLimit\RateLimiter` using atomic Redis/database transactions, including shared cooldowns. Store hashed credential scopes, never plaintext keys.

## Laravel

Laravel 12.69+ and 13.30+ applications discover the provider and facade automatically. Publish config:

```sh
php artisan vendor:publish --tag=piltover-config
```

Set `RIOT_API_KEY`, `RIOT_PLATFORM`, and optionally `RIOT_TOURNAMENT_API_KEY`. Access the client through the contract, `Piltover` facade or `piltover()` helper. To share limits across Laravel workers, bind the limiter in your application's service provider before resolving the client:

```php
$this->app->singleton(
    \Timmsy\PiltoverClient\RateLimit\RateLimiter::class,
    fn () => new \Timmsy\PiltoverClient\RateLimit\FileRateLimiter(storage_path('app/riot-rate-limits')),
);
```

Do not configure a global player-specific RSO token in applications with multiple users; construct a per-player client instead.

## Security and documentation

Keys travel in headers over verified HTTPS, only to allowed Riot API origins. Redirects are disabled, arbitrary proxy origins are rejected, and exceptions/debug output omit credentials and upstream response bodies. Keep keys in server-side secrets, never browser bundles or source control. Injected HTTP handlers are trusted application code and can inspect credentials.

Documentation routes are disabled by default. Enable `PILTOVER_DOCS_ENABLED=true` for `/piltover/docs` and `/piltover/docs/openapi.json`; set `docs.middleware` to `['web', 'auth']` when restricting access. The UI loads a pinned ReDoc script from its CDN. `composer docs:serve` starts a local viewer at `http://127.0.0.1:8765/docs/`. The generated OpenAPI describes Riot's remote endpoints, not a proxy hosted by your application; consult Riot for full DTO schemas.

## Maintenance and releases

```sh
composer install
composer test
composer generate
composer check:endpoints
composer release -- auto --dry-run
composer release -- auto
```

The reviewed JSON catalogue generates helpers, the contract, OpenAPI and endpoint documentation. CI checks PHP/Laravel combinations, standalone installation, dependency advisories and generated-file consistency. Development dependency resolution blocks known vulnerable versions, including in minimum-dependency checks. A weekly workflow compares documented verbs/paths with Riot's reference and fails on drift; maintainers must review changes and release updates. Dependabot checks Composer dependencies and GitHub Actions weekly. These workflows run after being pushed to GitHub and do not replace human maintenance.

Releases use annotated `vMAJOR.MINOR.PATCH` tags. `auto` reads conventional commits since the current tag: `fix:` bumps patch, `feat:` bumps minor, and `!:`/`BREAKING CHANGE:` bumps major. Explicit `patch`, `minor` and `major` also work. The release command requires a clean checkout, validates Composer, runs tests, updates VERSION/OpenAPI/changelog, commits, and tags locally. Review before `git push --follow-tags`. The manual GitHub Release workflow performs the same checks and pushes the commit and tag atomically from the default branch.

Register the repository at Packagist and configure its GitHub hook for automatic tag imports; Composer infers versions from Git tags, so `composer.json` intentionally has no hardcoded `version`. See [migration notes](docs/migration-v2.md) and [security reporting](SECURITY.md).

MIT License. PiltoverClient is not endorsed by Riot Games; League of Legends and Riot Games are trademarks of Riot Games, Inc.
