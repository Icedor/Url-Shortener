# Architecture

Implemented layout:

- public_html: the only public directory; front controller, CSS, JavaScript and favicon.
- app: PHP configuration, validation, SQLite persistence and translated view.
- bin: operator CLI for reports, link/domain blocking, backups and cleanup.
- tests: isolated integration tests using disposable local SQLite databases.
- .env.local / .env.production: private configuration outside public_html.

Creation atomically checks both quotas and reserves the alias inside BEGIN IMMEDIATE. SQLite uses WAL and a five-second busy timeout. HMAC-derived IP identifiers exist only in short-lived rate-limit records, not permanent links. IPv4-mapped IPv6 addresses are normalized. REMOTE_ADDR is used; client-supplied proxy headers are ignored. Database unique constraints and serialized insertion resolve alias races. Prepared statements, strict cookie sessions, CSRF tokens, JSON size limits, security headers and generic error responses protect API requests.

Domain blocks cover the domain and its subdomains at both creation and redirect time. Deactivated codes remain present and unavailable. Abuse reports are queued locally; submitting one never automatically blocks a link.

API routes accept POST JSON plus a session-bound X-CSRF-Token: `/api/availability`, `/api/create`, `/api/report`. Quota failures return 429, a Retry-After header and structured retry timing. Availability is limited to 60 requests/minute, creation attempts to 20/minute, and report attempts to 10/minute; only successful creations consume the 10/day and 5/minute creation limits. Successful reports are limited to 5/day/IP.

Aliases are case-insensitive lowercase ASCII, 3–48 characters, with internal hyphens permitted. Random codes use eight cryptographically random characters. Redirects are 302/no-store and preserve the complete destination, including its query and fragment. No click counters or destination fetches are implemented.

Language preference is held in a one-year first-party cookie. Otherwise PHP chooses Swedish or English from Accept-Language weights, falling back to English. Switching updates static text, field placeholders and current status messages without navigation. The only theme is the reference dark theme. The background uses local canvas rendering and respects reduced-motion and page visibility.

Limitations: quotas can be evaded with multiple IP addresses. Host syntax and literal IP validation do not establish whether a destination is benign or prevent later DNS/content changes. No external reputation provider is integrated. Operator review of reports is required.
