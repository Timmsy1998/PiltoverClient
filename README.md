# 🧠 PiltoverClient

> A PHP / Laravel-ready API wrapper for the **Riot Games Developer API**, built by **Timmsy**.

---

## 🚀 Overview

PiltoverClient is a lightweight, framework-agnostic API client for the **Riot Games Developer API**.
It integrates seamlessly with **Laravel** but can also be used in any PHP project.

The client implements the modern Riot API flow:
- 🔹 Resolve players via **account-v1** using their Riot ID (`gameName` + `tagLine`)
- 🔹 Use **PUUID** to fetch summoner profiles, matches, and more
- 🔹 Automatically maps platform → regional clusters (EUW → EUROPE, NA → AMERICAS, etc.)
- 🔹 Provides Laravel Facade + helper for elegant usage
- 🔹 Fully PSR-compliant, Guzzle-powered

---

## ⚙️ Installation

```bash
composer require timmsy/piltover-client
```

---

## 🧩 Laravel Setup

If you're using Laravel, PiltoverClient auto-discovers its service provider and facade.

### Publish Configuration

```bash
php artisan vendor:publish --tag=piltover-config
```

### Add to `.env`

```
RIOT_API_KEY=your_api_key_here
RIOT_PLATFORM=euw1
# Optional (will be derived automatically if omitted)
# RIOT_REGIONAL=europe
```

---

## 💻 Usage Examples

### 🔸 Laravel Facade

```php
use Timmsy\PiltoverClient\Facades\Piltover;

// 1. Get Account Info by Riot ID
$account = Piltover::accountByRiotId('TheCHEF', 'EUW');

// 2. Get Summoner Info using PUUID
$puuid = $account['puuid'] ?? null;
$summoner = $puuid ? Piltover::summonerByPuuid($puuid) : null;

// 3. Get Matches using PUUID
$matches = $puuid ? Piltover::matchesByPuuid($puuid, ['count' => 5]) : [];

// 4. Get Single Match Details
$match = !empty($matches) ? Piltover::matchById($matches[0]) : null;
```

### 🔸 Standalone PHP

```php
use Timmsy\PiltoverClient\Http\RiotClient;

$client = new RiotClient('your_api_key', 'euw1');
$acct = $client->accountByRiotId('TheCHEF', 'EUW');
print_r($acct);
```

---

## 🧭 Supported Endpoints

| Endpoint | Scope | Method | Description |
|-----------|--------|---------|-------------|
| `/riot/account/v1/accounts/by-riot-id/{gameName}/{tagLine}` | Regional | `accountByRiotId()` | Get player account and PUUID |
| `/lol/summoner/v4/summoners/by-puuid/{puuid}` | Platform | `summonerByPuuid()` | Get Summoner info |
| `/lol/match/v5/matches/by-puuid/{puuid}/ids` | Regional | `matchesByPuuid()` | Get recent match IDs |
| `/lol/match/v5/matches/{matchId}` | Regional | `matchById()` | Get full match details |

---

## 🧰 Helpers

```php
piltover()->matchById('EUW1_123456789');
```

---

## 🧪 Testing

```bash
composer test
```

Includes PHPUnit + Orchestra Testbench for Laravel context tests.

---

## ⚙️ Config File (`config/piltover.php`)

```php
return [
    'api_key'  => env('RIOT_API_KEY'),
    'platform' => env('RIOT_PLATFORM', 'euw1'),
    'regional' => env('RIOT_REGIONAL', null),
    'base_urls' => [
        'platform' => 'https://{platform}.api.riotgames.com',
        'regional' => 'https://{regional}.api.riotgames.com',
    ],
    'timeout' => 10,
];
```

---

## 📚 API Documentation

PiltoverClient includes a built-in **OpenAPI 3.1** specification and **ReDoc-powered** documentation viewer.
This allows developers to explore all supported Riot Games API endpoints visually.

---

### 🔧 Laravel Integration

Once installed in a Laravel app, the docs are automatically available at:

- **ReDoc UI:** [`/piltover/docs`](http://your-app.test/piltover/docs)
- **Raw Spec:** [`/piltover/docs/openapi.json`](http://your-app.test/piltover/docs/openapi.json)

Configure in `config/piltover.php` or `.env`:

```dotenv
PILTOVER_DOCS_ENABLED=true
PILTOVER_DOCS_PATH=piltover/docs
PILTOVER_SPEC_PATH=piltover/docs/openapi.json
```

Optional authentication:

```php
'middleware' => ['web', 'auth'],
```

---

### 🧩 Standalone Mode

PiltoverClient also ships with a local static docs server:

```bash
composer docs:serve
```

Visit: [http://127.0.0.1:8765/docs/](http://127.0.0.1:8765/docs/)
This uses the same OpenAPI spec from `resources/openapi/`.

---

### 🧠 OpenAPI Overview

The included spec documents:

| Endpoint | Scope | Method | Description |
|-----------|--------|---------|-------------|
| `/riot/account/v1/accounts/by-riot-id/{gameName}/{tagLine}` | Regional | GET | Retrieve account + PUUID by Riot ID |
| `/lol/summoner/v4/summoners/by-puuid/{puuid}` | Platform | GET | Fetch Summoner profile by PUUID |
| `/lol/match/v5/matches/by-puuid/{puuid}/ids` | Regional | GET | List recent matches by PUUID |
| `/lol/match/v5/matches/{matchId}` | Regional | GET | Retrieve match details |

---

### 🧾 Notes

- Works both in Laravel and standalone PHP contexts.
- Fully PSR-4 compliant — no runtime dependencies beyond `illuminate/support` and `guzzle`.
- You can extend the `resources/openapi/openapi.yaml` spec as you add endpoints.

---

## 📦 Future Roadmap

- [ ] Add all Riot endpoints (League, Spectator, Champion)
- [ ] Rate-limit & retry middleware
- [ ] Data Dragon (champions, icons, maps)
- [ ] Cached responses
- [ ] CLI utilities for dev testing

---

## 📜 License

MIT License © 2025 **James Timms**
Created and maintained by [@Timmsy1998](https://github.com/Timmsy1998)
