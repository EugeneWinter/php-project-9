<?php

namespace App;

use Carbon\Carbon;

class UrlRepository
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getEntities(): array
    {
        $urls = [];
        $sql = "SELECT * FROM urls ORDER BY created_at DESC";
        $stmt = $this->connection->query($sql);

        if ($stmt) {
            while ($row = $stmt->fetch()) {
                $url = new Url($row['name']);
                $url->setId($row['id']);
                $url->setDate($row['created_at']);
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function save(Url $url): void
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->connection->prepare($sql);
        $name = $url->getName();
        $date = Carbon::now()->toDateTimeString();
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':created_at', $date);
        $stmt->execute();
        $id = (int) $this->connection->lastInsertId();
        $url->setId($id);
    }

    public function find(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id]);

        if ($row = $stmt->fetch()) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setDate($row['created_at']);
            return $url;
        }
        
        return null;
    }

    public function findByName(string $name): ?Url
    {
        $sql = "SELECT * FROM urls WHERE name = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$name]);

        if ($row = $stmt->fetch()) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setDate($row['created_at']);
            return $url;
        }

        return null;
    }

    public function getUrlName(int $url_id): string
    {
        $sql = "SELECT name FROM urls WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$url_id]);

        if ($row = $stmt->fetch()) {
            return $row['name'];
        }

        return '';
    }
}
