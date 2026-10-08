# Security

The supported release line is 2.x. Report suspected vulnerabilities privately to james@timmsy.dev with reproduction steps and affected versions. Do not include real API keys, bearer tokens or player personal data.

The client validates official HTTPS API origins, verifies certificates, disables redirects, uses header authentication and redacts public exception/debug output. Application logging, injected handlers, process dumps and user code must also protect credentials. Keep storage for shared rate limits outside public directories and rotate compromised Riot keys through the developer portal.

Runtime constraints exclude currently known vulnerable Guzzle/PSR-7 releases. Development dependency resolution uses `roave/security-advisories` to block known vulnerable packages. CI runs Composer advisory checks and Dependabot proposes dependency updates. Maintainers review and release fixes; automated checks cannot guarantee the absence of vulnerabilities.
