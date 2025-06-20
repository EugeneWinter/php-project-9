<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;

session_start();

$container = new Container();
AppFactory::setContainer($container);

$container->set(PDO::class, function () {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        getenv('DB_HOST') ?: 'db',
        getenv('DB_PORT') ?: '5432',
        getenv('DB_NAME') ?: 'url_checker'
    );

    return new PDO(
        $dsn,
        getenv('DB_USER') ?: 'postgres',
        getenv('DB_PASSWORD') ?: '1337',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false
        ]
    );
});

$container->set('renderer', function () {
    $renderer = new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
    return $renderer;
});

$container->set(\App\Infrastructure\Persistence\UrlRepository::class, function ($c) {
    return new \App\Infrastructure\Persistence\UrlRepository($c->get(PDO::class));
});

$app = AppFactory::create();

$app->add(new MethodOverrideMiddleware());
$app->addErrorMiddleware(true, true, true);

require __DIR__ . '/../src/Infrastructure/App/routes.php';

$app->run();
