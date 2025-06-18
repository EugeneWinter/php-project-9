<?php

declare(strict_types=1);

/**
 * Функция для нормализации URL
 * 
 * @param string $url Исходный URL
 * @return string Нормализованный URL
 * @throws InvalidArgumentException
 */
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
