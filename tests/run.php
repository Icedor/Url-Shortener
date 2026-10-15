<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use Icey\Url\Problem;
use Icey\Url\Service;

$directory = sys_get_temp_dir() . '/icey-url-tests-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
$assertions = 0;
$files = [];

function check(bool $condition, string $label): void
{
	global $assertions;
	if (!$condition) throw new RuntimeException($label);
	$assertions++;
}

function rejects(callable $action, string $reason): Problem
{
	try { $action(); } catch (Problem $error) {
		check($error->reason === $reason, 'Expected ' . $reason . ', got ' . $error->reason);
		return $error;
	}
	throw new RuntimeException('Expected rejection: ' . $reason);
}

function makeService(string $name): Service
{
	global $directory, $files;
	$path = $directory . '/' . $name . '.sqlite';
	$files[] = $path;
	$service = new Service([
		'DB_PATH' => $path, 'APP_URL' => 'https://url.icey.se', 'IP_HASH_KEY' => str_repeat('a', 64),
		'CREATE_LIMIT_DAY' => 10, 'CREATE_LIMIT_MINUTE' => 5,
	]);
	$service->migrate();
	return $service;
}

try {
	$service = makeService('main');
	$id = $service->identity('203.0.113.1');
	check($id === $service->identity('::ffff:203.0.113.1'), 'IPv4 identity normalization');
	check($id !== $service->identity('203.0.113.2'), 'IP isolation');
	foreach (['javascript:alert(1)', 'ftp://example.com/file', 'https://user:password@example.com/', 'https://example.com/a b', "https://example.com/\r\nX:yes", 'https://example.com/%0d%0a'] as $url) {
		rejects(fn() => $service->destination($url), 'url_invalid');
	}
	foreach (['http://localhost/', 'http://127.0.0.1/', 'http://10.2.3.4/', 'http://192.168.1.1/', 'http://169.254.169.254/', 'http://100.64.0.1/', 'http://[::1]/', 'http://[fd00::1]/', 'http://[::ffff:127.0.0.1]/', 'http://2130706433/', 'http://127.1/', 'http://test.local/', 'http://router.home.arpa/'] as $url) {
		rejects(fn() => $service->destination($url), 'url_private');
	}
	foreach (['https://url.icey.se/foo', 'https://sub.url.icey.se/foo'] as $url) {
		rejects(fn() => $service->destination($url), 'url_self');
	}
	foreach (['https://example.com/path?q=a%20b#fragment', 'http://example.org/', 'https://8.8.8.8/', 'https://[2606:4700:4700::1111]/', 'https://xn--bcher-kva.de/'] as $url) {
		check($service->destination($url)[0] === $url, 'Public URL accepted');
	}
	foreach (['ab', '-abc', 'abc-', 'hello/world', 'åäö', str_repeat('a', 49)] as $alias) {
		rejects(fn() => Service::alias($alias), 'alias_invalid');
	}
	rejects(fn() => Service::alias('ADMIN'), 'alias_reserved');
	check(Service::alias(' Summer-2026 ') === 'summer-2026', 'Alias normalization');
	$link = $service->create('https://example.com/path?q=1#part', 'Summer-2026', $id);
	check($link['url'] === 'https://url.icey.se/summer-2026', 'Canonical short link');
	check(!$service->available('SUMMER-2026'), 'Case-insensitive availability');
	check($service->redirect('SUMMER-2026') === 'https://example.com/path?q=1#part', 'Query and fragment preserved');
	rejects(fn() => $service->create('https://example.org/', 'summer-2026', $id), 'alias_taken');
	$random = $service->create('https://example.org/', '', $id);
	check(strlen($random['alias']) === 8, 'Random code length');
	$service->report('summer-2026', 'phishing', $id);
	check(count($service->reports()) === 1, 'Report queue');
	check($service->review(1), 'Review report');
	check(count($service->reports()) === 0, 'Reviewed report removed from queue');
	check($service->disable('summer-2026'), 'Disable link');
	check(!$service->available('summer-2026'), 'Disabled alias never reused');
	rejects(fn() => $service->redirect('summer-2026'), 'disabled');
	$service->create('https://news.example.org/', 'domain-test', $id);
	$service->blockDomain('example.org');
	rejects(fn() => $service->redirect('domain-test'), 'disabled');
	rejects(fn() => $service->create('https://sub.example.org/', '', $id), 'url_blocked');
	check(!$service->isBlocked('notexample.org'), 'Domain label boundary');
	rejects(fn() => $service->redirect('not-created'), 'not_found');
	$backup = $directory . '/backup.sqlite';
	$files[] = $backup;
	$service->backup($backup);
	$backupDb = new PDO('sqlite:' . $backup);
	check($backupDb->query('PRAGMA integrity_check')->fetchColumn() === 'ok', 'Consistent backup integrity');
	check((int) $backupDb->query('SELECT count(*) FROM links')->fetchColumn() === 3, 'Backup includes all links');
	unset($backupDb, $service);

	$service = makeService('quota');
	$start = time();
	for ($index = 0; $index < 5; $index++) $service->create('https://example.com/', 'quota-a' . $index, $id, $start);
	$error = rejects(fn() => $service->create('https://example.com/', '', $id, $start + 10), 'quota');
	check($error->retryAfter === 50, 'Minute retry timing');
	for ($index = 0; $index < 5; $index++) $service->create('https://example.com/', 'quota-b' . $index, $id, $start + 60);
	$error = rejects(fn() => $service->create('https://example.com/', '', $id, $start + 120), 'quota');
	check($error->retryAfter === 86280, 'Rolling-day retry timing');
	$service->create('https://example.com/', 'different-ip', $service->identity('203.0.113.2'), $start + 120);
	$service->create('https://example.com/', 'next-window', $id, $start + 86400);
	check($service->redirect('next-window') === 'https://example.com/', 'Rolling window reopens');
	unset($service);

	$service = makeService('invalid-quota');
	for ($index = 0; $index < 12; $index++) rejects(fn() => $service->create('bad', '', $id), 'url_invalid');
	for ($index = 0; $index < 5; $index++) $service->create('https://example.com/', '', $id);
	check(true, 'Invalid input does not consume creation quota');
	for ($index = 0; $index < 3; $index++) $service->throttle($id, 'test', 3, 60, $start);
	rejects(fn() => $service->throttle($id, 'test', 3, 60, $start), 'rate_limit');
	unset($service);

	foreach (['race', 'quota-race'] as $scenario) {
		$service = makeService($scenario);
		unset($service);
		$processes = [];
		for ($index = 0; $index < 16; $index++) {
			$args = [PHP_BINARY, '-d', 'extension=php_pdo_sqlite.dll', __DIR__ . '/worker.php', $directory . '/' . $scenario . '.sqlite', $scenario === 'race' ? 'same-alias' : 'race-' . $index, $scenario === 'race' ? '203.0.113.' . ($index + 1) : '203.0.113.1'];
			if (PHP_OS_FAMILY !== 'Windows') array_splice($args, 1, 2);
			$process = proc_open($args, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
			fclose($pipes[0]);
			$processes[] = [$process, $pipes];
		}
		$results = [];
		foreach ($processes as [$process, $pipes]) {
			$results[] = stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]); fclose($pipes[2]);
			check(proc_close($process) === 0 && $stderr === '', 'Concurrent worker exits cleanly');
		}
		$counts = array_count_values($results);
		check(($counts['created'] ?? 0) === ($scenario === 'race' ? 1 : 10), 'Concurrent creation count: ' . $scenario);
		check(($counts[$scenario === 'race' ? 'alias_taken' : 'quota'] ?? 0) === ($scenario === 'race' ? 15 : 6), 'Concurrent rejection count: ' . $scenario);
	}

	$_COOKIE = [];
	$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'sv-SE,sv;q=0.9,en;q=0.8';
	check(Icey\Url\browserLanguage() === 'sv', 'Swedish detection');
	$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,en;q=0.8,sv;q=0.5';
	check(Icey\Url\browserLanguage() === 'en', 'Quality-weighted language');
	$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'sv;q=0,en;q=0.5';
	check(Icey\Url\browserLanguage() === 'en', 'Zero-quality language excluded');
	$_COOKIE['icey_language'] = 'sv';
	check(Icey\Url\browserLanguage() === 'sv', 'Manual preference wins');
	echo "PASS: {$assertions} assertions, including 32 concurrent workers.\n";
} catch (Throwable $error) {
	fwrite(STDERR, 'FAIL: ' . $error->getMessage() . "\n");
	exit(1);
} finally {
	unset($service, $backupDb);
	foreach ($files as $file) {
		foreach ([$file, $file . '-wal', $file . '-shm'] as $candidate) {
			if (dirname($candidate) === $directory && is_file($candidate)) @unlink($candidate);
		}
	}
	@rmdir($directory);
}
