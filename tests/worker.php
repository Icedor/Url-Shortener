<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/service.php';
$service = new Icey\Url\Service([
	'DB_PATH' => $argv[1], 'APP_URL' => 'https://url.icey.se', 'IP_HASH_KEY' => str_repeat('a', 64),
	'CREATE_LIMIT_DAY' => 10, 'CREATE_LIMIT_MINUTE' => 100,
]);
try {
	$service->create('https://example.com/', $argv[2], $service->identity($argv[3]));
	echo 'created';
} catch (Icey\Url\Problem $error) {
	echo $error->reason;
} catch (Throwable) {
	echo 'unexpected';
	exit(1);
}
