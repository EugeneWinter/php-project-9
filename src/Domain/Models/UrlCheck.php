<?php

declare(strict_types=1);

namespace App\Domain\Models;

class UrlCheck
{
    public function __construct(
        private int $urlId,
        private ?int $id = null,
        private ?int $statusCode = null,
        private ?string $h1 = null,
        private ?string $title = null,
        private ?string $description = null,
        private ?string $createdAt = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUrlId(): int
    {
        return $this->urlId;
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
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
    public function exists(): bool
    {
        return $this->id !== null;
    }
}
