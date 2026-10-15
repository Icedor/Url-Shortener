<?php
declare(strict_types=1);

use Icey\Url\Problem;
use Icey\Url\Service;
use function Icey\Url\{browserLanguage, configuration, csrfToken, jsonResponse, requestData, securityHeaders};

require dirname(__DIR__) . '/app/bootstrap.php';
securityHeaders();
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$isApi = str_starts_with($path, '/api/');
$language = browserLanguage();
$page = 'create';
$errorReason = null;

try {
	$config = configuration();
	$service = new Service($config);
	if ($isApi) {
		$token = csrfToken();
		$data = requestData($token);
		$identity = $service->identity($_SERVER['REMOTE_ADDR'] ?? '');
		foreach ($data as $value) {
			if (!is_string($value)) {
				throw new Problem('request', 400);
			}
		}
		switch ($path) {
			case '/api/availability':
				$service->throttle($identity, 'availability', 60, 60);
				$alias = Service::alias($data['alias'] ?? '');
				jsonResponse(['alias' => $alias, 'available' => $service->available($alias)]);
			case '/api/create':
				$service->throttle($identity, 'create_attempt', 20, 60);
				jsonResponse($service->create($data['url'] ?? '', $data['alias'] ?? '', $identity), 201);
			case '/api/report':
				$service->throttle($identity, 'report_attempt', 10, 60);
				$service->report($data['alias'] ?? '', $data['reason'] ?? '', $identity);
				jsonResponse(['reported' => true], 201);
			default:
				throw new Problem('not_found', 404);
		}
	}
	if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
		header('Allow: GET, HEAD');
		throw new Problem('method', 405);
	}
	if ($path === '/abuse') {
		$page = 'report';
	} elseif ($path === '/privacy') {
		$page = 'privacy';
	} elseif ($path !== '/') {
		if (!preg_match('~\A/([a-zA-Z0-9][a-zA-Z0-9-]{1,46}[a-zA-Z0-9])\z~', $path, $matches)) {
			throw new Problem('not_found', 404);
		}
		$destination = $service->redirect(strtolower($matches[1]));
		header('X-Robots-Tag: noindex, nofollow');
		header('Location: ' . $destination, true, 302);
		exit;
	}
	$token = csrfToken();
} catch (Problem $error) {
	if ($error->retryAfter) {
		header('Retry-After: ' . $error->retryAfter);
	}
	if ($isApi) {
		jsonResponse(['error' => $error->reason, 'retryAfter' => $error->retryAfter], $error->status);
	}
	http_response_code($error->status);
	$page = 'error';
	$errorReason = $error->reason;
} catch (Throwable) {
	// Do not log URLs, credentials, request bodies or exception details.
	error_log('Icey URL: service unavailable; check private configuration and SQLite.');
	if ($isApi) {
		jsonResponse(['error' => 'unavailable'], 503);
	}
	http_response_code(503);
	$page = 'error';
	$errorReason = 'unavailable';
}

header('Content-Type: text/html; charset=utf-8');
if ($page !== 'create') {
	header('X-Robots-Tag: noindex, nofollow');
}
require dirname(__DIR__) . '/app/view.php';
