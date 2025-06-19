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
    $url = trim($url);
    if (empty($url)) {
        throw new InvalidArgumentException('URL не должен быть пустым');
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = "https://{$url}";
    }

    $parsedUrl = parse_url($url);
    if ($parsedUrl === false || !isset($parsedUrl['host'])) {
        throw new InvalidArgumentException('Некорректный URL');
    }

    $scheme = strtolower($parsedUrl['scheme'] ?? 'https');
    if (!in_array($scheme, ['http', 'https'])) {
        throw new InvalidArgumentException('Некорректный URL');
    }

    $host = strtolower($parsedUrl['host']);
    $path = $parsedUrl['path'] ?? '';

    return "{$scheme}://{$host}{$path}";
}
