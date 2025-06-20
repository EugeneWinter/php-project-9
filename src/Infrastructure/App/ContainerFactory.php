<?php

declare(strict_types=1);

namespace App\Infrastructure\App;

use DI\Container;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Slim\Views\PhpRenderer;

class ContainerFactory
{
    public static function create(): ContainerInterface
    {
        self::loadEnvironment();
        $container = new Container();
        $container->set(\PDO::class, self::createPdo());
        $container->set(\GuzzleHttp\Client::class, fn() => new \GuzzleHttp\Client([
            'timeout' => 10,
            'verify' => true
        ]));

        $container->set('renderer', function () {
            $templatePath = realpath(__DIR__ . '/../../../templates');
            if ($templatePath === false) {
                throw new \RuntimeException('Templates directory not found');
            }
            return new PhpRenderer($templatePath);
        });

        $container->set('flash', fn() => new \Slim\Flash\Messages());
        return $container;
    }

    private static function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad();
    }

    private static function createPdo(): \PDO
    {
        $url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
        $url = str_replace('postgres://', 'postgresql://', $url);
        $parts = parse_url($url);
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $parts['host'] ?? '',
            $parts['port'] ?? '5432',
            ltrim($parts['path'] ?? '', '/')
        );
        return new \PDO(
            $dsn,
            $parts['user'] ?? '',
            $parts['pass'] ?? '',
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]
        );
    }
}
