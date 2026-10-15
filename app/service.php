<?php
declare(strict_types=1);

namespace Icey\Url;

final class Problem extends \RuntimeException
{
	public function __construct(public readonly string $reason, public readonly int $status = 422, public readonly int $retryAfter = 0)
	{
		parent::__construct($reason);
	}
}

final class Service
{
	private \PDO $db;
	private const RESERVED = ['admin', 'api', 'abuse', 'report', 'reports', 'privacy', 'terms', 'login', 'logout', 'register', 'signup', 'support', 'help', 'about', 'contact', 'health', 'status', 'assets', 'css', 'js', 'img', 'bin', 'app', 'docs', 'tests', 'favicon', 'robots', 'sitemap', 'index', 'www', 'icey', 'cgi-bin'];

	public function __construct(private readonly array $config)
	{
		umask(0077);
		$this->db = new \PDO('sqlite:' . $config['DB_PATH'], null, null, [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_TIMEOUT => 5,
		]);
		$this->db->exec('PRAGMA busy_timeout = 5000; PRAGMA foreign_keys = ON;');
	}

	public function migrate(): void
	{
		$this->db->exec('PRAGMA journal_mode = WAL;');
		$this->db->exec('CREATE TABLE IF NOT EXISTS links (
			alias TEXT PRIMARY KEY COLLATE NOCASE,
			destination TEXT NOT NULL,
			host TEXT NOT NULL,
			created_at INTEGER NOT NULL,
			disabled_at INTEGER
		);
		CREATE TABLE IF NOT EXISTS events (
			id INTEGER PRIMARY KEY,
			identity TEXT NOT NULL,
			kind TEXT NOT NULL,
			created_at INTEGER NOT NULL
		);
		CREATE INDEX IF NOT EXISTS events_lookup ON events(identity, kind, created_at);
		CREATE INDEX IF NOT EXISTS events_cleanup ON events(created_at);
		CREATE TABLE IF NOT EXISTS blocked_domains (
			host TEXT PRIMARY KEY,
			created_at INTEGER NOT NULL
		);
		CREATE TABLE IF NOT EXISTS reports (
			id INTEGER PRIMARY KEY,
			alias TEXT NOT NULL REFERENCES links(alias),
			reason TEXT NOT NULL,
			created_at INTEGER NOT NULL,
			reviewed_at INTEGER
		);');
	}

	public function identity(string $ip): string
	{
		$packed = @inet_pton($ip);
		if ($packed === false) {
			throw new Problem('unavailable', 503);
		}
		// Normalize IPv4-mapped IPv6 addresses to the same identity as IPv4.
		if (strlen($packed) === 16 && substr($packed, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
			$packed = substr($packed, 12);
		}
		return hash_hmac('sha256', $packed, $this->config['IP_HASH_KEY']);
	}

	public static function alias(string $value): string
	{
		$value = strtolower(trim($value));
		if (!preg_match('/\A[a-z0-9][a-z0-9-]{1,46}[a-z0-9]\z/', $value)) {
			throw new Problem('alias_invalid');
		}
		if (in_array($value, self::RESERVED, true)) {
			throw new Problem('alias_reserved', 409);
		}
		return $value;
	}

	public function destination(string $url): array
	{
		$url = trim($url);
		if ($url === '' || strlen($url) > 4096 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
			|| preg_match('/%0[ad]/i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
			throw new Problem('url_invalid');
		}
		$parts = parse_url($url);
		if (!$parts || !isset($parts['scheme'], $parts['host']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
			|| isset($parts['user']) || isset($parts['pass'])) {
			throw new Problem('url_invalid');
		}
		$host = strtolower(trim($parts['host'], '[]'));
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)) {
				throw new Problem('url_private');
			}
		} elseif (!preg_match('/\A(?=.{1,253}\z)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})\z/', $host)
			|| preg_match('/(?:\A|\.)(?:localhost|local|internal|intranet|lan|home|test|invalid|onion)\z/', $host)
			|| str_ends_with($host, '.home.arpa')) {
			throw new Problem('url_private');
		}
		$ownHost = strtolower((string) parse_url($this->config['APP_URL'], PHP_URL_HOST));
		foreach ([$ownHost, 'url.icey.se'] as $own) {
			if ($host === $own || str_ends_with($host, '.' . $own)) {
				throw new Problem('url_self');
			}
		}
		if ($this->isBlocked($host)) {
			throw new Problem('url_blocked');
		}
		return [$url, $host];
	}

	private function statement(string $sql, array $values = []): \PDOStatement
	{
		$statement = $this->db->prepare($sql);
		$statement->execute($values);
		return $statement;
	}

	private function transaction(callable $action): mixed
	{
		$this->db->exec('BEGIN IMMEDIATE');
		try {
			$result = $action();
			$this->db->exec('COMMIT');
			return $result;
		} catch (\Throwable $error) {
			$this->db->exec('ROLLBACK');
			throw $error;
		}
	}

	private function limit(string $identity, string $kind, int $maximum, int $window, int $now): void
	{
		$events = $this->statement('SELECT created_at FROM events WHERE identity = ? AND kind = ? AND created_at > ? ORDER BY created_at ASC', [$identity, $kind, $now - $window])->fetchAll(\PDO::FETCH_COLUMN);
		if (count($events) >= $maximum) {
			$release = (int) $events[count($events) - $maximum] + $window;
			throw new Problem($kind === 'created' ? 'quota' : 'rate_limit', 429, max(1, $release - $now));
		}
	}

	public function throttle(string $identity, string $kind, int $maximum, int $window, ?int $now = null): void
	{
		$now ??= time();
		$this->transaction(function () use ($identity, $kind, $maximum, $window, $now): void {
			$this->statement('DELETE FROM events WHERE created_at <= ?', [$now - 86400]);
			$this->limit($identity, $kind, $maximum, $window, $now);
			$this->statement('INSERT INTO events(identity, kind, created_at) VALUES(?, ?, ?)', [$identity, $kind, $now]);
		});
	}

	public function available(string $alias): bool
	{
		$alias = self::alias($alias);
		return $this->statement('SELECT 1 FROM links WHERE alias = ?', [$alias])->fetchColumn() === false;
	}

	public function create(string $url, string $requestedAlias, string $identity, ?int $now = null): array
	{
		[$destination, $host] = $this->destination($url);
		$alias = trim($requestedAlias) === '' ? null : self::alias($requestedAlias);
		$now ??= time();
		return $this->transaction(function () use ($destination, $host, $alias, $identity, $now): array {
			$this->statement('DELETE FROM events WHERE created_at <= ?', [$now - 86400]);
			$this->limit($identity, 'created', $this->config['CREATE_LIMIT_DAY'], 86400, $now);
			$this->limit($identity, 'created', $this->config['CREATE_LIMIT_MINUTE'], 60, $now);
			if ($this->isBlocked($host)) {
				throw new Problem('url_blocked');
			}
			if ($alias !== null && !$this->available($alias)) {
				throw new Problem('alias_taken', 409);
			}
			if ($alias === null) {
				do {
					$alphabet = '23456789abcdefghjkmnpqrstuvwxyz';
					$alias = '';
					for ($index = 0; $index < 8; $index++) {
						$alias .= $alphabet[random_int(0, strlen($alphabet) - 1)];
					}
				} while (!$this->available($alias));
			}
			$this->statement('INSERT INTO links(alias, destination, host, created_at) VALUES(?, ?, ?, ?)', [$alias, $destination, $host, $now]);
			$this->statement('INSERT INTO events(identity, kind, created_at) VALUES(?, ?, ?)', [$identity, 'created', $now]);
			return ['alias' => $alias, 'url' => $this->config['APP_URL'] . '/' . $alias, 'destination' => $destination];
		});
	}

	public function redirect(string $alias): string
	{
		$link = $this->statement('SELECT destination, host, disabled_at FROM links WHERE alias = ?', [$alias])->fetch();
		if (!$link) {
			throw new Problem('not_found', 404);
		}
		if ($link['disabled_at'] !== null || $this->isBlocked($link['host'])) {
			throw new Problem('disabled', 410);
		}
		return $link['destination'];
	}

	public function isBlocked(string $host): bool
	{
		do {
			if ($this->statement('SELECT 1 FROM blocked_domains WHERE host = ?', [$host])->fetchColumn() !== false) {
				return true;
			}
			$dot = strpos($host, '.');
			$host = $dot === false ? '' : substr($host, $dot + 1);
		} while ($host !== '');
		return false;
	}

	public function report(string $alias, string $reason, string $identity): void
	{
		$alias = self::alias($alias);
		if (!in_array($reason, ['phishing', 'malware', 'spam', 'other'], true)) {
			throw new Problem('request');
		}
		if ($this->available($alias)) {
			throw new Problem('not_found', 404);
		}
		$this->transaction(function () use ($alias, $reason, $identity): void {
			$now = time();
			$this->limit($identity, 'reported', 5, 86400, $now);
			$this->statement('INSERT INTO reports(alias, reason, created_at) VALUES(?, ?, ?)', [$alias, $reason, $now]);
			$this->statement('INSERT INTO events(identity, kind, created_at) VALUES(?, ?, ?)', [$identity, 'reported', $now]);
		});
	}

	public function disable(string $alias): bool
	{
		return $this->statement('UPDATE links SET disabled_at = COALESCE(disabled_at, ?) WHERE alias = ?', [time(), self::alias($alias)])->rowCount() > 0;
	}

	public function blockDomain(string $host): void
	{
		$host = strtolower(trim($host));
		// Validate without the current blocklist, so repeated blocking is idempotent.
		if (!preg_match('/\A(?=.{1,253}\z)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}\z/', $host)
			&& !filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) {
			throw new Problem('url_invalid');
		}
		$this->statement('INSERT OR IGNORE INTO blocked_domains(host, created_at) VALUES(?, ?)', [trim($host, '[]'), time()]);
	}

	public function reports(): array
	{
		return $this->statement('SELECT reports.id, reports.alias, reports.reason, reports.created_at, links.host FROM reports JOIN links ON reports.alias = links.alias WHERE reviewed_at IS NULL ORDER BY reports.id LIMIT 100')->fetchAll();
	}

	public function review(int $id): bool
	{
		return $this->statement('UPDATE reports SET reviewed_at = ? WHERE id = ? AND reviewed_at IS NULL', [time(), $id])->rowCount() > 0;
	}

	public function cleanup(): int
	{
		return $this->statement('DELETE FROM events WHERE created_at <= ?', [time() - 86400])->rowCount();
	}

	public function backup(string $path): void
	{
		$parent = str_replace('\\', '/', (string) realpath(dirname($path)));
		$public = str_replace('\\', '/', (string) realpath(dirname(__DIR__) . '/public_html'));
		if (!preg_match('~^(?:[A-Za-z]:[/\\\\]|/)~', $path) || file_exists($path) || !is_dir(dirname($path))
			|| str_starts_with(strtolower($parent . '/'), strtolower($public . '/'))) {
			throw new \RuntimeException('Backup target must be a new file in an existing private directory.');
		}
		$this->statement('VACUUM INTO ?', [$path]);
	}
}
