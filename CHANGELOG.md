# 🧠 PiltoverClient — Changelog

All notable changes to this project will be documented in this file.

The format is based on **[Keep a Changelog](https://keepachangelog.com/en/1.1.0/)**
and this project adheres to **[Semantic Versioning](https://semver.org/spec/v2.0.0.html)**.

---

## [2.0.0] — 2026-10-08

### Added
- All 50 current League of Legends/account developer-portal operations, including tournament-v5, tournament-stub-v5 and RSO endpoints.
- Generated helpers, contract, endpoint catalogue and OpenAPI reference.
- Response-driven application/method rate limits, bounded GET retries, Retry-After handling and shared file reservations.
- Redacted API/rate-limit/transport exceptions, strict HTTPS origin validation, disabled redirects and separate tournament/RSO credentials.
- Standalone Composer installation without Laravel dependencies; migration and security documentation.
- Compatibility/advisory CI, weekly endpoint drift checks, Dependabot and conventional-commit release automation with annotated tags.

### Changed
- Minimum PHP is 8.2; patched Guzzle 7.15.2+ and PSR-7 2.12.3+ are required. Laravel integration supports patched 12.69+ and 13.30+ releases.
- Oceania match routing uses SEA; SEA account requests use Asia. Invalid routing values fail explicitly.
- Documentation routes are opt-in; the bundled specification is JSON.
- Expanded client contract and changed exception types; see docs/migration-v2.md.

## [1.1.0] — 2025-10-22
### ✨ Added — Laravel API Documentation System

**New Laravel-integrated API documentation routes and OpenAPI spec viewer**

#### 🚀 Features
- Added `/piltover/docs` ReDoc UI powered by the package’s built-in OpenAPI spec  
- Added `/piltover/docs/openapi.json` and `/piltover/docs/openapi.yaml` endpoints  
- New config section (`piltover.docs`) to enable, disable, or customize docs:
  ```php
  'docs' => [
      'enabled'    => env('PILTOVER_DOCS_ENABLED', true),
      'path'       => env('PILTOVER_DOCS_PATH', 'piltover/docs'),
      'spec_path'  => env('PILTOVER_SPEC_PATH', 'piltover/docs/openapi.json'),
      'middleware' => ['web'],
  ]
  ```
- Added controllers:
  - `DocsController` → renders the ReDoc Blade view  
  - `OpenApiController` → serves OpenAPI JSON/YAML spec  
- Added `resources/views/docs.blade.php` ReDoc layout  
- Added `resources/openapi/openapi.yaml` spec defining account-v1, summoner-v4, and match-v5 endpoints  
- Updated `config/piltover.php` to include docs configuration block  
- Added new `.env.example` variables:
  - `PILTOVER_DOCS_ENABLED`
  - `PILTOVER_DOCS_PATH`
  - `PILTOVER_SPEC_PATH`
- Updated README to document API Docs usage  
- Added new `composer docs:serve` script for standalone static docs viewing  

#### 🧩 Developer Experience
- Docs work seamlessly in both **standalone (composer docs:serve)** and **Laravel-integrated** setups  
- Routes auto-discovered via `PiltoverClientServiceProvider`  
- Optional middleware support for authenticated docs access  

---

### 🔧 Internal
- No breaking changes — previous functionality and API methods remain unaffected.
- Minimum version of Laravel remains unchanged (10+).

---

## [1.0.0] — 2025-10-22
### 🎉 Initial Release

**PiltoverClient** — a PHP / Laravel-ready API wrapper for the **Riot Games Developer API**.

#### 🚀 Added
- **Core Riot API Client**
  - Implemented modern Riot ID → PUUID flow using:
    - `account-v1` → `accountByRiotId()`
    - `summoner-v4` → `summonerByPuuid()`
    - `match-v5` → `matchesByPuuid()` and `matchById()`
  - Automatic **platform → regional** routing
    (e.g. `euw1 → europe`, `na1 → americas`, `kr → asia`)
  - PSR-compliant **Guzzle** HTTP client with configurable timeout and headers

- **Laravel Integration**
  - `PiltoverClientServiceProvider` with auto-discovery
  - `Piltover` Facade and global `piltover()` helper
  - Publishable config file at `config/piltover.php`
  - Environment configuration via `.env` or `.env.example`

- **Configuration & Security**
  - `.env.example` with documented keys
  - `.gitignore` to protect `.env` and vendor directories

- **Documentation**
  - Comprehensive `README.md` with install, usage, and endpoint reference
  - MIT license and project metadata

- **Testing & CI**
  - PHPUnit 11 + Orchestra Testbench setup
  - Unit tests for Routing and RiotClient
  - Feature tests for Laravel bindings and Facade
  - `phpunit.xml` configuration
  - GitHub Actions workflow for automated CI

---

## 📦 Versioning
This project follows [Semantic Versioning 2.0.0](https://semver.org/).
Breaking API changes will increment the **MAJOR** version.

---

### © 2025 James Timms (@Timmsy1998)
Released under the **MIT License**.
