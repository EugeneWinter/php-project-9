<?php
declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

$app->group('/urls', function (RouteCollectorProxy $group) {
    $group->get('', function ($request, $response) {
        $urls = $this->get(\App\Infrastructure\Persistence\UrlRepository::class)->findAll();
        $lastChecks = $this->get(\App\Infrastructure\Persistence\UrlCheckRepository::class)->getLastChecks();
        
        return $this->get('renderer')->render(
            $response, 
            'urls/index.phtml', 
            [
                'urls' => $urls,
                'lastChecks' => $lastChecks
            ]
        );
    })->setName('urls.index');

    $group->post('', \App\Presentation\Controllers\UrlController::class . ':store')
         ->setName('urls.store');

    $group->get('/{id:[0-9]+}', \App\Presentation\Controllers\UrlController::class . ':show')
         ->setName('urls.show');
});