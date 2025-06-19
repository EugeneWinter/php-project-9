<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Models\UrlCheck;
use PDO;

class UrlCheckRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getChecks(int $urlId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC");
        $stmt->execute([$urlId]);

        return array_map(
            fn($row) => new UrlCheck(
                $row['url_id'],
                $row['id'],
                $row['status_code'],
                $row['h1'],
                $row['title'],
                $row['description'],
                $row['created_at']
            ),
            $stmt->fetchAll()
        );
    }

    public function createCheck(UrlCheck $check): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO url_checks 
            (url_id, h1, status_code, title, description, created_at)
            VALUES 
            (:url_id, :h1, :status_code, :title, :description, NOW())
        ");
        $stmt->execute([
            'url_id' => $check->getUrlId(),
            'h1' => $check->getH1(),
            'status_code' => $check->getStatusCode(),
            'title' => $check->getTitle(),
            'description' => $check->getDescription()
        ]);
    }
}
