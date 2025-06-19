<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

class UrlNormalizer
{
    public static function normalize(string $url): string
    {
        $url = parse_url($url);
        return ($url['scheme'] ?? 'https') . '://' . ($url['host'] ?? '');
    }
}
