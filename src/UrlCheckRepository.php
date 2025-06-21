<?php

namespace App;

use Carbon\Carbon;

class UrlCheckRepository
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function addCheck(int $url_id, int $status_code, ?string $h1, ?string $title, ?string $description): void
    {
        $sql = "INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at) 
        VALUES (:url_id, :status_code, :h1, :title, :description, :created_at)";
        $stmt = $this->connection->prepare($sql);
        $created_at = date('Y-m-d H:i:s');
        $stmt->execute([
            ':url_id' => $url_id,
            ':status_code' => $status_code,
            ':h1' => $h1,
            ':title' => $title,
            ':description' => $description,
            ':created_at' => $created_at
        ]);
    }

    public function getChecks(int $url_id): array
    {
        $checkData = [];
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$url_id]);

        while ($row = $stmt->fetch()) {
            $check = new UrlCheck();
            $check->setId($row['id']);
            $check->setUrlId($row['url_id']);
            $check->setStatusCode($row['status_code']);
            $check->setH1($row['h1'] ?? null);
            $check->setTitle($row['title'] ?? null);
            $check->setDescription($row['description'] ?? null);
            $check->setCheckDate($row['created_at']);
            $checkData[] = $check;
        }

        return $checkData;
    }

    public function getLastCheck(int $url_id): ?UrlCheck
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$url_id]);

        if ($row = $stmt->fetch()) {
            $lastCheck = new UrlCheck();
            $lastCheck->setId($row['id']);
            $lastCheck->setUrlId($row['url_id']);
            $lastCheck->setStatusCode($row['status_code']);
            $lastCheck->setH1($row['h1'] ?? null);
            $lastCheck->setTitle($row['title'] ?? null);
            $lastCheck->setDescription($row['description'] ?? null);
            $lastCheck->setCheckDate($row['created_at']);
            return $lastCheck;
        }

        return null;
    }

    public function getAllLastChecks(): array
    {
        $allLastChecksArr = [];

        $sql = "SELECT DISTINCT ON (url_id) id, url_id, status_code, created_at FROM url_checks 
        ORDER BY url_id, created_at DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $check = new UrlCheck();
            $check->setId($row['id']);
            $check->setUrlId($row['url_id']);
            $check->setStatusCode($row['status_code']);
            $check->setCheckDate($row['created_at']);
            $allLastChecksArr[] = $check;
        }

        return $allLastChecksArr;
    }
}
