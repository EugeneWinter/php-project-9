<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

class PageCheckResult
{
    public function __construct(
        private ?int $statusCode = null,
        private ?string $h1 = null,
        private ?string $title = null,
        private ?string $description = null,
        private ?string $error = null
    ) {
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
    public function getH1(): ?string
    {
        return $this->h1;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
