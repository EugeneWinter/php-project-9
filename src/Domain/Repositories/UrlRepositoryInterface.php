<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\Url;

interface UrlRepositoryInterface
{
    public function findAll(): array;
    public function findByName(string $name): ?Url;
    public function findById(int $id): ?Url;
    public function save(Url $url): Url;
}
