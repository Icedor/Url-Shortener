# Verification

## Interface refinement — 2026-10-15

- Removed both introductory slogans; main heading and initial/dynamic document titles now use "URL-förkortare" / "URL shortener".
- The reset button reuses the existing primary button markup and CSS. Removed the unused intro and text-button styles. Updated CSS/JS query versions so browsers fetch the revised assets; bump these versions when changing those assets later.
- PHP lint and JavaScript syntax checks passed. Locally created real links in both languages; desktop result view visually checked, 390×844 mobile checked without horizontal overflow. Tab reaches the reset button; Enter and pointer activation return to an empty form with destination input focused. Existing dark theme retained.
- Before replacement, the three existing production files were downloaded and matched against the previous Git commit. After publication, all three downloaded replacements matched commit 471d887 byte-for-byte. Live Chrome confirmed SV/EN headings and dynamic titles, absent intro, primary reset-button markup and no console errors. No additional production test links were created for this refinement.
- Storage reading before refinement: 36 KiB production database, 36 KiB existing backup, 278,340 bytes workspace including private verification artifacts. Revised deployable application files total about 79 KiB. Storage grows with destination strings, aliases, reports and temporary quota records; destination pages are never downloaded. Seven rotating backups require roughly seven database-sized snapshots in addition to the live database, with temporary SQLite/backup overhead. Hosting access logs are separate and were not measured.

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

## Production release — 2026-10-15

- Both local suites were rerun before deployment: 112 service and 37 HTTP assertions passed.
- 21 HTTP checks passed against https://url.icey.se: HTTPS, SV/EN browser detection, language-cookie priority, CSP/no-store/secure session cookie, private paths denied, CSRF rejection, private destination rejection, alias availability and creation, exact query/fragment redirects, reports, atomic concurrent alias insertion (one 201 and one 409), and random creation.
- The hosting layer redirects HTTP to HTTPS with 301 before the application's 308 rule. The canonical target and path were verified. The production check was corrected to accept this observed hosting behavior. Destination validation uses the application's 422 status; the check was corrected from 400 to 422. These were test assumptions, not application failures.
- Production SQLite was initialized by a temporary cPanel cron entry; its private log confirmed success. Only this project's cron entry was added/edited. The 13 existing entries were preserved.
- Three test aliases (`release-check-20261015`, `release-lock-20261015`, `hetxue37`) were disabled through the private CLI, their names retained, and the single test report reviewed. These creations count toward the test network's normal rolling 24-hour quota.
- A consistent production snapshot passed SQLite integrity checking, confirmed the three disabled links and reviewed report, and supported a new write after restoration to an isolated local temporary database. No restore was performed over production.
- The temporary release command ran more than once while browser verification was in progress. Later runs correctly refused to overwrite the existing release snapshot; the first snapshot was intact and verified. The temporary command was replaced with the tested rotating maintenance command.
- Maintenance was successfully executed through cron, producing a private weekday backup and an empty pending-report export, then scheduled for 03:17 server time daily. Seven rotating backup slots; no off-site automation or notifications.
- Chrome production checks: desktop and 390×844 mobile, no horizontal overflow; keyboard Tab focus reaches alias; taken alias is shown with text; English switching persists after reload; Swedish restored at handover. Local checks above cover the remaining detailed interactions. No console errors were present at inspection. Device emulation was cleared.
- The privacy page explains seven-day backup rotation. Its PHP syntax was checked after the text addition.
- All 12 deployed app/bin/public_html files were downloaded and compared byte-for-byte using SHA-256 against the reviewed source. Final HTTP checks confirmed 410 for all three disabled test links, both translated backup disclosures and a 200 homepage response. The temporary restore snapshot was removed locally and remotely; the regular weekday backup remains.

The recurring schedule has been saved and its exact command successfully run; future unattended runs cannot be claimed as already verified. Report review and checking maintenance output are ongoing operator duties.
