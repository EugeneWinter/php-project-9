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

    public function getLastChecks(): array
    {
        $sql = "SELECT 
                    uc.url_id,
                    uc.status_code,
                    uc.created_at,
                    uc.h1,
                    uc.title,
                    uc.description
                FROM url_checks uc
                INNER JOIN (
                    SELECT url_id, MAX(created_at) as max_date
                    FROM url_checks
                    GROUP BY url_id
                ) latest ON uc.url_id = latest.url_id AND uc.created_at = latest.max_date";
        
        $stmt = $this->pdo->query($sql);
        $results = [];
        
        foreach ($stmt->fetchAll() as $row) {
            $results[$row['url_id']] = $row;
        }
        
        return $results;
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
