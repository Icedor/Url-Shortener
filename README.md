# Icey URL

A public Swedish/English URL shortener for `url.icey.se`, built with native PHP, SQLite, HTML, CSS and JavaScript. No application dependencies or build step.

Read [AGENTS.md](AGENTS.md) before working and [docs/README.md](docs/README.md) for requirements, operations and verification.

## Run locally

Use PHP 8.2+ with PDO SQLite. Copy `.env.example` to `.env.local`, generate a 32-byte random IP hash key, and choose an existing private local directory for SQLite. Never keep an active database on a synchronized drive.

```sh
php bin/manage.php init
php -S 127.0.0.1:8093 -t public_html bin/router.php
```

On this Windows computer, use the PHP executable and extension option in [operations](docs/operations.md). `public_html` is the only webroot; configuration, code, database and tools stay outside it.

## Verify

```sh
php tests/run.php
node tests/http.mjs
```

The tests use standard libraries and isolated temporary databases. Node 22.16+ is a development test runner only; it is not needed to host the service. Set `ICEY_PHP_BINARY` when PHP is not on PATH.

## Operate

`bin/manage.php` provides initialization, abuse report review, link disabling, domain blocking, cleanup and consistent backups. On hosting without an interactive shell, PHP commands run through cPanel cron. The service was published at https://url.icey.se on 2026-10-15. See [operations](docs/operations.md) and [verification](docs/verification.md).
