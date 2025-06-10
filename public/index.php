<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Простой маршрут для проверки работы
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('Hello, Hexlet!');
    return $response;
});

$app->run();