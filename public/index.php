<?php

use Carbon\Carbon;
use Valitron\Validator;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use DiDom\Document;
use Slim\Flash\Messages;
use Selective\BasePath\BasePathMiddleware;
use Illuminate\Support\Str;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/UrlHelper.php';

try {
    $pdo = new PDO(
        "pgsql:host=localhost;port=5432;dbname=url_checker",
        "postgres",
        "1337"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

session_start();

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->setBasePath('');
$app->add(new BasePathMiddleware($app));

$container->set('flash', function () {
    return new Messages();
});

$container->set('view', function () {
    $templatePath = __DIR__ . '/../templates';
    if (!is_dir($templatePath)) {
        throw new RuntimeException("Template directory not found: {$templatePath}");
    }
    return new PhpRenderer($templatePath);
});

$container->set('db', function () use ($pdo) {
    return $pdo;
});

$app->addErrorMiddleware(true, true, true);

$app->get('/favicon.ico', function (Request $request, Response $response) {
    return $response->withStatus(204);
});

$app->get('/', function (Request $request, Response $response) {
    $flash = $this->get('flash')->getMessages();
    error_log("Rendering index template");
    $templatePath = $this->get('view')->getTemplatePath();
    error_log("Template path: {$templatePath}");
    
    try {
        $response = $this->get('view')->render($response, 'index.phtml', [
            'error' => $flash['error'][0] ?? null,
            'url' => $flash['url'][0] ?? null
        ]);
        
        $body = (string)$response->getBody();
        error_log("Response body length: " . strlen($body));
        error_log("Title tag exists: " . (strpos($body, '<title>') !== false ? 'yes' : 'no'));
        
        return $response;
    } catch (Exception $e) {
        error_log("Template rendering error: " . $e->getMessage());
        throw $e;
    }
})->setName('home');

$app->post('/urls', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $url = $data['url']['name'] ?? '';

    $v = new Validator(['url' => $url]);
    $v->rule('required', 'url')->message('URL не должен быть пустым');
    $v->rule('url', 'url')->message('Некорректный URL');
    $v->rule('lengthMax', 'url', 255)->message('URL превышает 255 символов');

    $flash = $this->get('flash');

    if (!$v->validate()) {
        $errors = $v->errors();
        $flash->addMessage('error', $errors['url'][0]);
        $flash->addMessage('url', $url);
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    try {
        $normalizedUrl = normalizeUrl($url);
        $db = $this->get('db');

        $stmt = $db->prepare('SELECT id FROM urls WHERE name = ?');
        $stmt->execute([$normalizedUrl]);
        $existingUrl = $stmt->fetch();

        if ($existingUrl) {
            $id = $existingUrl['id'];
            $flash->addMessage('success', 'Страница уже существует');
        } else {
            $stmt = $db->prepare('INSERT INTO urls (name, created_at) VALUES (?, ?)');
            $stmt->execute([$normalizedUrl, Carbon::now()]);
            $id = $db->lastInsertId();
            $flash->addMessage('success', 'Страница успешно добавлена');
        }

        return $response->withHeader('Location', "/urls/{$id}")->withStatus(302);
    } catch (PDOException $e) {
        $flash->addMessage('error', 'Ошибка при сохранении URL: ' . $e->getMessage());
        return $response->withHeader('Location', '/')->withStatus(302);
    }
})->setName('urls.store');

$app->get('/urls', function (Request $request, Response $response) {
    $db = $this->get('db');
    $stmt = $db->query('
        SELECT u.id, u.name, 
               MAX(uc.created_at) as last_check_date,
               (SELECT uc2.status_code 
                FROM url_checks uc2 
                WHERE uc2.url_id = u.id 
                ORDER BY uc2.created_at DESC 
                LIMIT 1) as status_code
        FROM urls u
        LEFT JOIN url_checks uc ON u.id = uc.url_id
        GROUP BY u.id
        ORDER BY u.id DESC
    ');
    $urls = new Collection($stmt->fetchAll());

    return $this->get('view')->render($response, 'urls/index.phtml', [
        'urls' => $urls
    ]);
})->setName('urls.index');

$app->get('/urls/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $db = $this->get('db');

    try {
        $stmt = $db->prepare('SELECT * FROM urls WHERE id = ?');
        $stmt->execute([$id]);
        $url = $stmt->fetch();

        if (!$url) {
            return $response->withStatus(404);
        }

        $stmt = $db->prepare('SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC');
        $stmt->execute([$id]);
        $checks = new Collection($stmt->fetchAll());

        $flash = $this->get('flash')->getMessages();

        return $this->get('view')->render($response, 'urls/show.phtml', [
            'url' => $url,
            'checks' => $checks,
            'success' => $flash['success'][0] ?? null,
            'error' => $flash['error'][0] ?? null,
            'warning' => $flash['warning'][0] ?? null
        ]);
    } catch (PDOException $e) {
        $this->get('flash')->addMessage('error', 'Database error: ' . $e->getMessage());
        return $response->withStatus(500);
    }
})->setName('urls.show');

$app->post('/urls/{id}/checks', function (Request $request, Response $response, $args) {
    $urlId = $args['id'];
    $db = $this->get('db');
    $flash = $this->get('flash');

    try {
        $stmt = $db->prepare('SELECT * FROM urls WHERE id = ?');
        $stmt->execute([$urlId]);
        $url = $stmt->fetch();

        if (!$url) {
            return $response->withStatus(404);
        }

        $client = new Client([
            'timeout' => 5,
            'allow_redirects' => true,
            'http_errors' => false,
            'verify' => false
        ]);

        try {
            $res = $client->request('GET', $url['name']);
            $statusCode = $res->getStatusCode();
            $body = (string)$res->getBody();

            $document = new Document($body);
            $h1 = $document->first('h1') ? Str::limit($document->first('h1')->text(), 252, '...') : '';
            $title = $document->first('title') ? Str::limit($document->first('title')->text(), 252, '...') : '';
            $description = $document->first('meta[name=description]')
                ? Str::limit($document->first('meta[name=description]')->getAttribute('content'), 252, '...')
                : '';

            $stmt = $db->prepare('
                INSERT INTO url_checks 
                (url_id, status_code, h1, title, description, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $urlId,
                $statusCode,
                $h1,
                $title,
                $description,
                Carbon::now()
            ]);

            $flash->addMessage('success', 'Страница успешно проверена');
        } catch (ConnectException $e) {
            $flash->addMessage('error', 'Не удалось подключиться к сайту: ' . $e->getMessage());
        } catch (RequestException $e) {
            $flash->addMessage('warning', 'Ошибка при выполнении запроса: ' . $e->getMessage());
        }

        return $response->withHeader('Location', "/urls/{$urlId}")->withStatus(302);
    } catch (PDOException $e) {
        $flash->addMessage('error', 'Ошибка базы данных: ' . $e->getMessage());
        return $response->withHeader('Location', '/')->withStatus(302);
    }
})->setName('urls.checks');

$app->run();
