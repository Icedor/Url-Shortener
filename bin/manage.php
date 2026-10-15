<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

try {
	$service = new Icey\Url\Service(Icey\Url\configuration());
	$command = $argv[1] ?? 'help';
	$argument = $argv[2] ?? '';
	switch ($command) {
		case 'init':
			$service->migrate();
			echo "Database initialized.\n";
			break;
		case 'disable':
			echo $service->disable($argument) ? "Link disabled; alias remains reserved.\n" : "Link not found.\n";
			break;
		case 'block-domain':
			$service->blockDomain($argument);
			echo "Domain and its subdomains blocked.\n";
			break;
		case 'reports':
			echo json_encode($service->reports(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
			break;
		case 'review':
			if (!ctype_digit($argument) || (int) $argument < 1) {
				throw new RuntimeException('A positive report ID is required.');
			}
			echo $service->review((int) $argument) ? "Report reviewed.\n" : "Pending report not found.\n";
			break;
		case 'cleanup':
			echo $service->cleanup() . " expired rate-limit records removed.\n";
			break;
		case 'backup':
			$service->backup($argument);
			echo "Consistent database snapshot created.\n";
			break;
		default:
			echo "Usage: php bin/manage.php init|reports|cleanup|disable ALIAS|block-domain HOST|review ID|backup PRIVATE_PATH\n";
	}
} catch (Throwable $error) {
	fwrite(STDERR, 'Command failed: ' . ($error instanceof Icey\Url\Problem ? $error->reason : 'check configuration, arguments and private paths') . "\n");
	exit(1);
}
