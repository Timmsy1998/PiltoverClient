# Migrating from 1.x

- Upgrade PHP to 8.2+. Runtime dependencies are Guzzle 7 and PSR HTTP messages; Illuminate is now optional. Laravel 12.69+ and 13.30+ integrations retain auto-discovery, facade and helper support.
- The original four named methods and constructor arguments remain available. Implementations of `RiotClientContract` must now implement the additional methods, `call()` and `endpoints()`.
- Handle `RiotApiException`, `RateLimitException` and `TransportException` instead of Guzzle exceptions. Upstream bodies and requests are deliberately absent from exceptions.
- Requests can wait for known limits and retry GET failures within configured budgets. Use zero retries/wait budget with a job queue if synchronous waiting is unsuitable.
- Custom base URLs must be official Riot HTTPS origins. Test with a Guzzle mock handler rather than a remote proxy. Redirect responses are errors.
- Invalid platforms/regions now throw rather than silently routing to Europe. Oceania matches route to SEA; SEA account-v1 routes to Asia. Old PH2/TH2 routing values remain accepted for migration.
- Re-publish or merge configuration to add tournament/RSO credentials and retry settings. Documentation is opt-in and the bundled specification is JSON. Review custom docs paths and middleware.
- Tournament endpoints require an explicit tournament key; stub endpoints use the ordinary key. RSO helpers require a player bearer token and a separate Riot-approved OAuth integration.

Only current operations in the Riot developer reference are exposed. Deprecated summoner-name/ID, old tournament versions and undocumented League Client endpoints are not restored.
