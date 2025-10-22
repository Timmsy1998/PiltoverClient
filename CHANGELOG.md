# 🧠 PiltoverClient — Changelog

All notable changes to this project will be documented in this file.

The format is based on **[Keep a Changelog](https://keepachangelog.com/en/1.1.0/)**
and this project adheres to **[Semantic Versioning](https://semver.org/spec/v2.0.0.html)**.

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
