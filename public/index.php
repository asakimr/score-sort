<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$containerBuilder = new ContainerBuilder();
$settings = require __DIR__ . '/../src/settings.php';
$dependencies = require __DIR__ . '/../src/dependencies.php';
$repositories = require __DIR__ . '/../src/repositories.php';

$containerBuilder->addDefinitions($settings);
$containerBuilder->addDefinitions($dependencies);
$containerBuilder->addDefinitions($repositories);

$container = $containerBuilder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

(require __DIR__ . '/../src/routes/web.php')($app);

$app->run();
