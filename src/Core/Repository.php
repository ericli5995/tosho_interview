<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Thin base for repositories: prepared-statement helpers over the shared PDO.
 * No query builder, no ORM - just enough to keep SQL in the subclasses tidy.
 */
abstract class Repository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::pdo();
    }

    /**
     * @param array<string|int,mixed> $params
     * @return array<string,mixed>|null
     */
    protected function fetch(string $sql, array $params = []): ?array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int,mixed> $params
     * @return list<array<string,mixed>>
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /** @param array<string|int,mixed> $params */
    protected function scalar(string $sql, array $params = []): mixed
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn();
    }

    /** @param array<string|int,mixed> $params */
    protected function execute(string $sql, array $params = []): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    protected function lastId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}
