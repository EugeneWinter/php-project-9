<?php

use Carbon\Carbon;
use Valitron\Validator;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$container->set('flash', function () {
    return new class {
        private $messages = [];
        
        public function addMessage($key, $message) {
            $this->messages[$key][] = $message;
        }
        
        public function getMessages() {
            $messages = $this->messages;
            $this->messages = [];
            return $messages;
        }
    };
});

$container->set('view', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$databaseUrl = getenv('DATABASE_URL') ?: 'pgsql://postgres:1337@localhost:5432/url_checker';
$databaseUrl = parse_url($databaseUrl);

$dbConfig = [
    'host' => $databaseUrl['host'],
    'port' => $databaseUrl['port'] ?? 5432,
    'dbname' => ltrim($databaseUrl['path'], '/'),
    'user' => $databaseUrl['user'],
    'pass' => $databaseUrl['pass']
];

$dsn = sprintf(
    'pgsql:host=%s;port=%d;dbname=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['dbname']
);

try {
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $container->set('db', $pdo);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response) {
    $flash = $this->get('flash')->getMessages();
    return $this->get('view')->render($response, 'index.phtml', [
        'error' => $flash['error'][0] ?? null,
        'url' => $flash['url'][0] ?? null
    ]);
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
        $flash->addMessage('error', 'Ошибка при сохранении URL');
        return $response->withHeader('Location', '/')->withStatus(302);
    }
})->setName('urls.store');

$app->get('/urls', function (Request $request, Response $response) {
    $db = $this->get('db');
    $stmt = $db->query('
        SELECT u.id, u.name, 
               MAX(uc.created_at) as last_check_date,
               uc.status_code
        FROM urls u
        LEFT JOIN url_checks uc ON u.id = uc.url_id
        GROUP BY u.id, uc.status_code
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
        'error' => $flash['error'][0] ?? null
    ]);
})->setName('urls.show');

$app->post('/urls/{id}/checks', function (Request $request, Response $response, $args) {
    $urlId = $args['id'];
    $db = $this->get('db');
    $flash = $this->get('flash');
    
    $stmt = $db->prepare('SELECT * FROM urls WHERE id = ?');
    $stmt->execute([$urlId]);
    $url = $stmt->fetch();
    
    if (!$url) {
        return $response->withStatus(404);
    }
    
    try {
        $stmt = $db->prepare('INSERT INTO url_checks (url_id, created_at) VALUES (?, ?)');
        $stmt->execute([$urlId, Carbon::now()]);
        $flash->addMessage('success', 'Страница успешно проверена');
    } catch (PDOException $e) {
        $flash->addMessage('error', 'Ошибка при проверке страницы');
    }
    
    return $response->withHeader('Location', "/urls/{$urlId}")->withStatus(302);
})->setName('urls.checks');

function normalizeUrl(string $url): string
{
    $parsedUrl = parse_url(trim($url));
    
    if (!isset($parsedUrl['scheme'])) {
        $url = "https://{$url}";
        $parsedUrl = parse_url($url);
    }
    
    $scheme = strtolower($parsedUrl['scheme']);
    $host = strtolower($parsedUrl['host'] ?? $parsedUrl['path'] ?? '');
    
    if (empty($host)) {
        throw new InvalidArgumentException('Некорректный URL');
    }
    
    return "{$scheme}://{$host}";
}

$app->run();
