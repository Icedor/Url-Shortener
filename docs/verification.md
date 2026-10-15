# Verification

Environment checks completed on 2026-10-15:

- Empty remote confirmed with git ls-remote; initial fetch succeeded.
- PHP 8.5.9 runs locally; PDO SQLite loads with a per-command extension option.
- Reference source inspected and compared visually with the live Secret page.
- After user key authorization, SFTP succeeded. cPanel shows PHP 8.5 and PDO SQLite enabled. Actual site execution remains a release check; interactive shell is disabled and is not required for SFTP publication.

## Automated tests

- `tests/run.php`: 112 assertions passed, including 32 concurrent PHP workers. Covers allowed/rejected destinations, local/private address literals, alias validation and reservation, case-insensitive uniqueness, query/fragment preservation, random codes, abuse reports, review, link disabling, parent-domain blocking, backup integrity, quota boundaries/retry timing, separate request throttling and language selection.
- `tests/http.mjs`: 37 assertions passed against an isolated temporary PHP server. Covers initial Swedish HTML, security/cookie/cache headers, invalid CSRF, method/type/body-size validation, availability, creation, conflicts, redirects and HEAD, reports, quotas and Retry-After, spoofed forwarding headers, private-path denial and language-cookie priority.
- PHP and browser JavaScript syntax checks passed. No external test packages are used.
- Staged-file secret scan passed before the first commit: real local/production IP hash keys, private-key material and common GitHub token formats were absent. Both private .env files are ignored; no .git file or directory exists inside Drive.
- Source commit `335ff2faa5ae8ac5b00175aeed3329803ed4656a` was pushed successfully and checked against GitHub main using git ls-remote.
- A cache-header test found that PHP's default session cache limiter overwrote the explicit header; fixed by disabling that limiter while retaining the explicit no-store header.

## Browser verification

Chrome checks completed on 2026-10-15:

- Desktop at 1722×1248; mobile at 390×844 and 320×740, with no horizontal overflow at either mobile width.
- Swedish browser detection, English manual switch, persistence after reload, translated dynamic success/error messages and keyboard language switching.
- Custom alias availability, taken status in red with text, successful custom and random creation, result destination and short-link display.
- Clipboard copy verified against the generated URL. When clipboard support was temporarily removed from the test page, the manual-copy fallback selected the link and displayed instructions. The page was reloaded afterwards, restoring the API.
- Keyboard Tab moves from destination to alias to submit; Enter creates a link and focus moves to the result heading.
- Abuse report submission displayed a confirmed saved result.
- Reduced-motion emulation produced identical canvas frames. Emulation and viewport overrides were reset afterwards. Only the reference dark theme exists.
- Browser console inspection showed no JavaScript errors at the checked point.

Production-only checks still required: actual domain webroot/runtime, HTTPS and .htaccess behavior on LiteSpeed, file permissions, SQLite locking under that host's PHP handler, initialization/cron execution, deployed commit hashes and backup/restore procedure. Local tests do not prove these production behaviors.
