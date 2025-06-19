<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;

class HtmlParser
{
    public function parse(string $html): array
    {
        $doc = new Document($html);
        return [
            'title' => $this->extractText($doc, 'title'),
            'h1' => $this->extractText($doc, 'h1'),
            'description' => $this->extractAttribute($doc, 'meta[name=description]', 'content')
        ];
    }

    private function extractText(Document $doc, string $selector): ?string
    {
        return $doc->first($selector)?->text();
    }

    private function extractAttribute(Document $doc, string $selector, string $attr): ?string
    {
        return $doc->first($selector)?->attr($attr);
    }
}
