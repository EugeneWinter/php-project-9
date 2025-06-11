<?php

use Carbon\Carbon;
use Valitron\Validator;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$databaseUrl = parse_url(getenv('DATABASE_URL'));
$dsn = sprintf(
    'pgsql:host=%s;port=%d;dbname=%s',
    $databaseUrl['host'],
    $databaseUrl['port'],
    ltrim($databaseUrl['path'], '/')
);
$pdo = new PDO($dsn, $databaseUrl['user'], $databaseUrl['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$storage = [];
$flash = new Messages($storage);
$app->add(function ($request, $handler) use ($flash) {
    $response = $handler->handle($request);
    $flash->__construct($_SESSION['flash'] ?? []);
    return $response;
});

$renderer = new PhpRenderer(__DIR__ . '/../templates', [
    'flash' => $flash
]);

$app->get('/', function (Request $request, Response $response) use ($renderer) {
    return $renderer->render($response, 'index.phtml');
})->setName('home');

$app->post('/urls', function (Request $request, Response $response) use ($pdo, $flash) {
    $data = $request->getParsedBody();
    $url = $data['url']['name'] ?? '';
    
    $v = new Validator(['url' => $url]);
    $v->rule('required', 'url')->message('URL не должен быть пустым');
    $v->rule('url', 'url')->message('Некорректный URL');
    $v->rule('lengthMax', 'url', 255)->message('URL превышает 255 символов');
    
    if (!$v->validate()) {
        $errors = $v->errors();
        $flash->addMessage('error', $errors['url'][0]);
        return $response->withHeader('Location', '/')->withStatus(302);
    }
    
    $normalizedUrl = normalizeUrl($url);
    
    try {
        $stmt = $pdo->prepare('SELECT id FROM urls WHERE name = ?');
        $stmt->execute([$normalizedUrl]);
        $existingUrl = $stmt->fetch();
        
        if ($existingUrl) {
            $id = $existingUrl['id'];
            $flash->addMessage('success', 'Страница уже существует');
        } else {
            $stmt = $pdo->prepare('INSERT INTO urls (name, created_at) VALUES (?, ?)');
            $stmt->execute([$normalizedUrl, Carbon::now()]);
            $id = $pdo->lastInsertId();
            $flash->addMessage('success', 'Страница успешно добавлена');
        }
        
        return $response->withHeader('Location', "/urls/{$id}")->withStatus(302);
    } catch (PDOException $e) {
        $flash->addMessage('error', 'Ошибка при сохранении URL');
        return $response->withHeader('Location', '/')->withStatus(302);
    }
})->setName('urls.store');

$app->get('/urls', function (Request $request, Response $response) use ($pdo, $renderer) {
    $stmt = $pdo->query('
        SELECT urls.id, urls.name, 
               MAX(url_checks.created_at) as last_check_date,
               url_checks.status_code
        FROM urls
        LEFT JOIN url_checks ON urls.id = url_checks.url_id
        GROUP BY urls.id, url_checks.status_code
        ORDER BY urls.id DESC
    ');
    $urls = new Collection($stmt->fetchAll());
    
    return $renderer->render($response, 'urls/index.phtml', [
        'urls' => $urls
    ]);
})->setName('urls.index');

$app->get('/urls/{id}', function (Request $request, Response $response, array $args) use ($pdo, $renderer) {
    $id = $args['id'];
    
    $stmt = $pdo->prepare('SELECT * FROM urls WHERE id = ?');
    $stmt->execute([$id]);
    $url = $stmt->fetch();
    
    if (!$url) {
        return $response->withStatus(404);
    }
    
    $stmt = $pdo->prepare('SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC');
    $stmt->execute([$id]);
    $checks = new Collection($stmt->fetchAll());
    
    return $renderer->render($response, 'urls/show.phtml', [
        'url' => $url,
        'checks' => $checks
    ]);
})->setName('urls.show');

function normalizeUrl(string $url): string
{
    $parsedUrl = parse_url($url);
    $scheme = $parsedUrl['scheme'] ?? 'https';
    $host = $parsedUrl['host'] ?? $parsedUrl['path'] ?? '';
    return strtolower("{$scheme}://{$host}");
}

$app->run();