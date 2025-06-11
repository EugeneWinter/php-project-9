<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$templatesPath = __DIR__ . '/../templates';

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$phpView = new PhpRenderer($templatesPath);

$app->get('/', function (Request $request, Response $response) use ($phpView) {
    return $phpView->render($response, 'home.phtml', [
        'title' => 'Главная страница'
    ]);
});

$app->run();