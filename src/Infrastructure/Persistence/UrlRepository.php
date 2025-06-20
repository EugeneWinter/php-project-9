<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Models\Url;
use PDO;

class UrlRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM urls ORDER BY created_at DESC");
        return array_map([$this, 'mapToEntity'], $stmt->fetchAll());
    }

    public function findByName(string $name): ?Url
    {
        $stmt = $this->pdo->prepare("SELECT * FROM urls WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return ($row = $stmt->fetch()) ? $this->mapToEntity($row) : null;
    }

    public function findById(int $id): ?Url
    {
        $stmt = $this->pdo->prepare("SELECT * FROM urls WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return ($row = $stmt->fetch()) ? $this->mapToEntity($row) : null;
    }

    public function save(Url $url): Url
    {
        $existingUrl = $this->findByName($url->getName());
        if ($existingUrl !== null) {
            return $existingUrl;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO urls (name, created_at) VALUES (?, ?) RETURNING id"
        );

        $createdAt = $url->getCreatedAt() ?: date('Y-m-d H:i:s');
        $stmt->execute([$url->getName(), $createdAt]);

        $id = (int)$stmt->fetchColumn();
        $url->setId($id);

        return $url;
    }

    private function mapToEntity(array $data): Url
    {
        return new Url(
            (int)$data['id'],
            $data['name'],
            $data['created_at']
        );
    }
}
