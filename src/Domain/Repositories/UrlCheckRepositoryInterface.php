<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Models\UrlCheck;

interface UrlCheckRepositoryInterface
{
    public function findByUrlId(int $urlId): array;
    public function save(UrlCheck $check): void;
}
