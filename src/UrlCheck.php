<?php

namespace App;

class UrlCheck
{
    private ?int $id = null;
    private int $urlId;
    private int $statusCode;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private string $checkDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUrlId(): int
    {
        return $this->urlId;
    }

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function setH1(?string $h1): void
    {
        $this->h1 = $h1;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCheckDate(): string
    {
        return $this->checkDate;
    }

    public function setCheckDate(string $checkDate): void
    {
        $this->checkDate = $checkDate;
    }
}
