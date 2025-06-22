<?php

session_start();
session_regenerate_id(true);

require __DIR__ . '/../vendor/autoload.php';

use App\Url;
use App\UrlCheckRepository;
use App\UrlValidator;
use App\UrlRepository;
use DI\Container;
use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Routing\RouteContext;
use Slim\Views\PhpRenderer;
use Psr\Container\ContainerInterface;

$container = new Container();

$container->set(PDO::class, function () {
    $databaseUrl = parse_url($_ENV['DATABASE_URL']);

    $dbHost = $databaseUrl['host'];
    $dbPort = $databaseUrl['port'] ?? '5432';
    $dbName = ltrim($databaseUrl['path'], '/');
    $dbUser = $databaseUrl['user'];
    $dbPass = $databaseUrl['pass'];

    $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}";

    $connection = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    return $connection;
});

$container->set('flash', function () {
    return new Messages();
});

$container->set(UrlRepository::class, function (ContainerInterface $c) {
    return new UrlRepository($c->get(PDO::class));
});

$container->set(UrlCheckRepository::class, function (ContainerInterface $c) {
    return new UrlCheckRepository($c->get(PDO::class));
});

$container->set(UrlValidator::class, function () {
    return new UrlValidator();
});

$app = AppFactory::createFromContainer($container);

$container->set('router', fn() => $app->getRouteCollector()->getRouteParser());

$container->set('renderer', function ($container) {
    $renderer = new PhpRenderer(__DIR__ . '/../templates', ['router' => $container->get('router')]);
    $renderer->setLayout('layouts/layout.php');
    return $renderer;
});

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(
    HttpNotFoundException::class,
    function ($request, $exception, $displayErrorDetails) use ($app) {
        $response = new \Slim\Psr7\Response();
        return $app->getContainer()->get('renderer')->render($response->withStatus(404), "errors/404.phtml");
    }
);

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    return $response;
});

$app->get('/', function ($request, $response) {
    $params = [
        'url' => ['name' => '']
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('/');

$app->get('/urls', function ($request, $response) {
    $urls = $this->get(UrlRepository::class)->getEntities();
    $lastChecks = $this->get(UrlCheckRepository::class)->getAllLastChecks();

    $lastChecksIndexed = [];
    foreach ($lastChecks as $check) {
        $lastChecksIndexed[$check->getUrlId()] = [
            'status_code' => $check->getStatusCode(),
            'created_at' => $check->getCheckDate()
        ];
    }

    $params = [
        'urls' => $urls,
        'lastChecks' => $lastChecksIndexed
    ];
    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id:[0-9]+}', function ($request, $response, $args) {
    $messages = $this->get('flash')->getMessages();
    $id = $args['id'];

    $url = $this->get(UrlRepository::class)->find($id);
    if (is_null($url)) {
        return $this->get('renderer')->render($response->withStatus(404), "errors/404.phtml");
    }

    $params = [
        'flash' => $messages,
        'url' => $url,
        'checkData' => $this->get(UrlCheckRepository::class)->getChecks($args['id'])
    ];

    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('urls.show');

$app->post('/urls', function ($request, $response) {
    $urlData = $request->getParsedBodyParam('url');
    $urlString = $urlData['name'] ?? '';

    if (!str_starts_with($urlString, 'http')) {
        $urlString = 'https://' . $urlString;
    }

    $validator = $this->get(UrlValidator::class);
    $errors = $validator->validate(['name' => $urlString]);

    if (count($errors) > 0) {
        $params = [
            'errors' => $errors,
            'url' => ['name' => $urlData['name'] ?? '']
        ];
        return $this->get('renderer')->render($response->withStatus(422), 'index.phtml', $params);
    }

    $parsedUrl = parse_url($urlString);
    $normalizedUrl = mb_strtolower("{$parsedUrl['scheme']}://{$parsedUrl['host']}");

    $url = $this->get(UrlRepository::class)->findByName($normalizedUrl);
    if ($url) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        return $response->withRedirect($this->get('router')->urlFor('urls.show', ['id' => $url->getId()]));
    }

    $url = new Url($normalizedUrl);
    $this->get(UrlRepository::class)->save($url);
    $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
    return $response->withRedirect($this->get('router')->urlFor('urls.show', ['id' => $url->getId()]));
});

$app->post('/urls/{url_id:[0-9]+}/checks', function ($request, $response, $args) {
    $urlId = $args['url_id'];
    $url = $this->get(UrlRepository::class)->find($urlId);

    if (!$url) {
        return $this->get('renderer')->render($response->withStatus(404), "errors/404.phtml");
    }

    $client = new Client(['timeout' => 5, 'connect_timeout' => 3]);
    try {
        $responseResult = $client->get($url->getName());
        $document = new Document($responseResult->getBody()->getContents());

        $h1Element = $document->first('h1');
        $titleElement = $document->first('title');
        $descriptionElement = $document->first('meta[name=description]');

        $this->get(UrlCheckRepository::class)->addCheck(
            $urlId,
            $responseResult->getStatusCode(),
            $h1Element ? trim($h1Element->text()) : null,
            $titleElement ? trim($titleElement->text()) : null,
            $descriptionElement ? trim($descriptionElement->getAttribute('content')) : null
        );
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } catch (Exception $e) {
        $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке');
    }

    return $response->withRedirect($this->get('router')->urlFor('urls.show', ['id' => $urlId]));
});

$app->run();
