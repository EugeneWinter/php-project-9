<?php

use Slim\Routing\RouteCollectorProxy;

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

$app->group('/urls', function (RouteCollectorProxy $group) {
    $group->get('', function ($request, $response) {
        $urls = $this->get(\App\Infrastructure\Persistence\UrlRepository::class)->findAll();
        return $this->get('renderer')->render($response, 'urls/index.phtml', ['urls' => $urls]);
    })->setName('urls.index');

    $group->post('', \App\Presentation\Controllers\UrlController::class . ':store')->setName('urls.store');
    $group->get('/{id}', \App\Presentation\Controllers\UrlController::class . ':show')->setName('urls.show');
});
