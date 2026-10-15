# Operations and synchronization

## Startup

1. Read AGENTS.md. Verify Drive is synchronized and only this computer writes; ask if uncertain.
2. Keep Git metadata outside cloud synchronization. On this computer it is `C:/Users/hello/AppData/Local/IceyGit/Url-Shortener.git`, with core.worktree pointing to the Drive Dev folder. Use `git --git-dir=<that path>` for every Git command. There is no .git inside Drive.
3. Fetch origin without modifying working files. Inspect status, diff, remote heads and last common commit. Back up unpublished work before any update.
4. Preserve local-only changes. Apply remote-only changes only to tracked files. Stop and present differences if both sides changed or the baseline is uncertain. Never force push or mirror-delete.
5. Before computer handover, push verified work or document unpublished changes, then verify Drive synchronization.

Initial check on 2026-10-15: user confirmed Drive sync and sole writer; Dev/public_html was empty; GitHub returned no refs; fetch succeeded. Git metadata created in local AppData, whose checked ancestors are not reparse points. Drive's registered backup-root table was empty. Git Credential Manager already lists Icedor; the existing saved credential is reused. No new personal access token is needed if it can push; never promise an irrevocable or permanent token.

## Environment and local runtime

PHP 8.2+ with PDO SQLite is required. Local PHP is `C:/Users/hello/scoop/apps/php/current/php.exe` (8.5.9). Its PDO SQLite DLL is installed but not enabled by default; use `-d extension=php_pdo_sqlite.dll` for project commands. Do not modify the global PHP installation.

Keep `.env.local` in Dev, excluded from Git. Runtime SQLite files must be on a local, non-synced disk, outside public_html. Production uses `.env.production` outside public_html. Use a separate random IP_HASH_KEY per environment.

## Production preparation

Deploy when the user requests "pusha live". Connection details are in private `.env.production` and `URL/Dokument/Uppgifter.txt`. After the user authorized the existing key, SFTP with `icey_cpanel` succeeded on port 22. Its fingerprint is recorded in Uppgifter.txt. The key is named MaximusDatorus in cPanel. Interactive shell is disabled, but SFTP works; this is not a deployment blocker and does not require the user to enable shell access.

Verified remote project root: `/home/icey/url.icey.se`; public webroot: `/home/icey/url.icey.se/public_html`. SFTP including hidden files showed only `.well-known` and `cgi-bin` before publication. Preserve both. PHP Selector shows account PHP 8.5 with PDO SQLite enabled. cPanel exposes Cron Management, and SFTP confirmed `/opt/alt/php85/usr/bin/php` exists. No account-wide PHP settings or cron jobs were changed. Actual execution for this site, Apache/LiteSpeed rewrites and SQLite locking remain to be checked at release.

The private `URL/Dokument/Uppgifter.txt` is outside this repo. Preserve its existing formatting and credentials; append verified operational facts without duplicating secrets into documentation.

## Local commands on this computer

```powershell
& 'C:\Users\hello\scoop\apps\php\current\php.exe' -d 'extension=php_pdo_sqlite.dll' bin/manage.php init
& 'C:\Users\hello\scoop\apps\php\current\php.exe' -d 'extension=php_pdo_sqlite.dll' -S 127.0.0.1:8093 -t public_html bin/router.php
& 'C:\Users\hello\scoop\apps\php\current\php.exe' -d 'extension=php_pdo_sqlite.dll' tests/run.php
$env:ICEY_PHP_BINARY = 'C:\Users\hello\scoop\apps\php\current\php.exe'
node tests/http.mjs
```

The preview needs write access to the AppData runtime. A workspace-only sandbox can serve the homepage while failing SQLite writes; use the appropriately authorized local process. Tests use their own temporary data and do not alter preview or production records. Stop the owned preview process at handover.

## Release procedure without shell login

1. Fetch and compare changes; pass the relevant tests, inspect the UI, update docs, commit and push. Record the exact commit and a file-hash manifest.
2. Inspect only this remote project. Download existing project source/configuration before replacing it. Never recursively delete or mirror the account. Keep production runtime data, .well-known and cgi-bin.
3. Upload `app/` and `bin/` alongside `public_html/` using SFTP and the existing host-key verification. Upload `.env.production` to the project root with mode 0600. Create `var/` with mode 0700. DB_PATH points to `var/links.sqlite`, outside public_html.
4. Initialize using the existing cPanel cron facility (not a shell login). The command is `ICEY_ENV_FILE=/home/icey/url.icey.se/.env.production /opt/alt/php85/usr/bin/php /home/icey/url.icey.se/bin/manage.php init`. Use a temporary project-specific entry, verify success, then remove only that temporary entry. Keep other cron jobs untouched. The command is idempotent and never deletes existing links.
5. Upload the reviewed public files, including .htaccess. Its HTTPS redirect is production-only; PHP's local router ignores .htaccess. Do not disable TLS checks or weaken host-key checking.
6. Verify HTTPS, both languages, rewrite routes, real PDO SQLite execution, creation and redirect behavior, quotas, denied private paths and no application errors. Test links should be explicitly named and retained/disabled, never reused.
7. Compare deployed source hashes with the published commit and record the outcome. Take a consistent backup and retain the previous source version for rollback.

## Operator commands

Run with the production ICEY_ENV_FILE prefix above via cPanel cron when shell login is unavailable. Redirect output to a private file in var/ when it must be downloaded through SFTP, never to public_html. Do not email command output. Remove temporary operation entries once their result is verified.

- `reports`: list up to 100 pending abuse reports (host only, not full destination URLs).
- `disable ALIAS`: deactivate a link and permanently retain its alias.
- `block-domain example.org`: reject new links and stop existing redirects to that domain and all its subdomains.
- `review ID`: mark an abuse report reviewed; it does not disable the link.
- `cleanup`: delete quota identifiers older than 24 hours. API writes also perform this cleanup.
- `backup /absolute/private/path/new-file.sqlite`: take a consistent VACUUM INTO snapshot. Download that completed snapshot using SFTP; do not copy a live SQLite file or separate WAL sidecars.

Restore a tested snapshot only during a maintenance window with writes stopped, preserving a copy of the current database first. Never restore test data over production. Production backup cadence and operator report-review routine must be agreed before opening the service to ongoing public use.
