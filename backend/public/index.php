<?php

declare(strict_types=1);

use Acme\Application;

require dirname(__DIR__) . '/vendor/autoload.php';

$application = new Application(
    storeConfigPath: dirname(__DIR__) . '/config/store.php',
    rateLimitDirectory: sys_get_temp_dir() . '/acme-rate-limit',
    requestsPerMinute: Application::requestsPerMinute(getenv()),
    logger: Application::createLogger(),
);

Application::emit($application->handle($_SERVER, (string) file_get_contents('php://input')));
