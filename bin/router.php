<?php
declare(strict_types=1);

// Development-only router. Never place this file inside public_html.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if (preg_match('~\A/(?:css/[a-z-]+\.css|js/[a-z_-]+\.js|favicon\.png|robots\.txt)\z~', $path)
	&& is_file(dirname(__DIR__) . '/public_html' . $path)) {
	return false;
}
require dirname(__DIR__) . '/public_html/index.php';
