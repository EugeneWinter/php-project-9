<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\ValueObjects\PageCheckResult;
use App\Infrastructure\Services\HtmlParser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PageAnalyzer
{
    public function __construct(
        private Client $client,
        private HtmlParser $parser
    ) {
    }

    public function analyze(string $url): PageCheckResult
    {
        try {
            $response = $this->client->request('GET', $url);
            $html = (string)$response->getBody();
            $parsed = $this->parser->parse($html);

            return new PageCheckResult(
                $response->getStatusCode(),
                $parsed['h1'],
                $parsed['title'],
                $parsed['description']
            );
        } catch (GuzzleException $e) {
            return new PageCheckResult(
                error: $e->getMessage()
            );
        }
    }
}
