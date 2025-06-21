<?php

namespace App;

class Url
{
    private int $id;
    private string $name;
    private string $createdAt;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDate(): ?string
    {
        return $this->createdAt;
    }

    public function setDate(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
