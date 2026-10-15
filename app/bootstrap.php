<?php
declare(strict_types=1);

namespace Icey\Url;

require_once __DIR__ . '/service.php';

function configuration(): array
{
	$local = PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli';
	$file = getenv('ICEY_ENV_FILE') ?: dirname(__DIR__) . ($local ? '/.env.local' : '/.env.production');
	if (!is_file($file)) {
		throw new \RuntimeException('Environment configuration is missing.');
	}
	$config = [];
	foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
		$line = trim($line);
		if ($line === '' || str_starts_with($line, '#')) {
			continue;
		}
		[$key, $value] = array_pad(explode('=', $line, 2), 2, '');
		$config[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
	}
	$url = rtrim($config['APP_URL'] ?? '', '/');
	$parts = parse_url($url);
	if (!$parts || !isset($parts['host'], $parts['scheme']) || isset($parts['user']) || isset($parts['pass'])
		|| isset($parts['query']) || isset($parts['fragment']) || !empty($parts['path'])
		|| !in_array($parts['scheme'], ['http', 'https'], true)) {
		throw new \RuntimeException('Invalid application URL.');
	}
	if (($config['APP_ENV'] ?? '') !== 'local' && $parts['scheme'] !== 'https') {
		throw new \RuntimeException('Production requires HTTPS.');
	}
	if (!preg_match('/\A[a-f0-9]{64}\z/i', $config['IP_HASH_KEY'] ?? '')) {
		throw new \RuntimeException('A random IP hash key is required.');
	}
	$database = $config['DB_PATH'] ?? '';
	if (!preg_match('~^(?:[A-Za-z]:[/\\\\]|/)~', $database) || !is_dir(dirname($database))) {
		throw new \RuntimeException('An existing absolute database directory is required.');
	}
	$parent = str_replace('\\', '/', (string) realpath(dirname($database)));
	$public = str_replace('\\', '/', (string) realpath(dirname(__DIR__) . '/public_html'));
	if (str_starts_with(strtolower($parent . '/'), strtolower($public . '/'))) {
		throw new \RuntimeException('The database must be outside public_html.');
	}
	foreach (['CREATE_LIMIT_DAY' => 10, 'CREATE_LIMIT_MINUTE' => 5] as $key => $default) {
		$config[$key] = filter_var($config[$key] ?? $default, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
		if ($config[$key] === false) {
			throw new \RuntimeException('Invalid quota configuration.');
		}
	}
	$config['APP_URL'] = $url;
	return $config;
}

function browserLanguage(): string
{
	if (in_array($_COOKIE['icey_language'] ?? '', ['sv', 'en'], true)) {
		return $_COOKIE['icey_language'];
	}
	$preferences = [];
	foreach (explode(',', substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 512)) as $index => $part) {
		if (preg_match('/^\s*(sv|en)(?:-[a-z0-9-]+)?(?:\s*;\s*q=(0(?:\.\d+)?|1(?:\.0+)?))?\s*$/i', $part, $matches)) {
			$quality = isset($matches[2]) ? (float) $matches[2] : 1;
			if ($quality > 0) {
				$preferences[] = ['language' => strtolower($matches[1]), 'quality' => $quality, 'index' => $index];
			}
		}
	}
	usort($preferences, static fn(array $a, array $b): int => ($b['quality'] <=> $a['quality']) ?: ($a['index'] <=> $b['index']));
	return $preferences[0]['language'] ?? 'en';
}

function securityHeaders(): void
{
	header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
	header('Referrer-Policy: no-referrer');
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: DENY');
	header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
	header('Cache-Control: no-store');
}

function csrfToken(): string
{
	session_name('icey_url');
	session_start([
		'use_strict_mode' => 1,
		'cache_limiter' => '',
		'cookie_httponly' => true,
		'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
		'cookie_samesite' => 'Lax',
		'cookie_path' => '/',
	]);
	$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
	$token = $_SESSION['csrf'];
	session_write_close();
	return $token;
}

function jsonResponse(array $data, int $status = 200): never
{
	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
	exit;
}

function requestData(string $token): array
{
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		header('Allow: POST');
		throw new Problem('method', 405);
	}
	if (!hash_equals($token, $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
		throw new Problem('session', 403);
	}
	if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
		throw new Problem('request', 415);
	}
	$body = file_get_contents('php://input', false, null, 0, 8193);
	if ($body === false || strlen($body) > 8192) {
		throw new Problem('request', 413);
	}
	try {
		$data = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
	} catch (\JsonException) {
		throw new Problem('request', 400);
	}
	if (!is_array($data) || array_is_list($data)) {
		throw new Problem('request', 400);
	}
	return $data;
}
