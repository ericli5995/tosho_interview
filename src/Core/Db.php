<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * The one database collaborator: owns the PDO connection and offers the small
 * set of helpers the repositories need. Injected into repositories (has-a),
 * never inherited.
 */
final class Db
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @param array<string,mixed> $config keys: host, port, database, username, password, charset */
    public static function connect(array $config): self
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '3306',
            $config['database'] ?? '',
            $config['charset'] ?? 'utf8mb4'
        );

        $pdo = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return new self($pdo);
    }

    /* --- reads ------------------------------------------------------------ */

    /**
     * @param array<string|int,mixed> $params
     * @return array<string,mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int,mixed> $params
     * @return list<array<string,mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /** @param array<string|int,mixed> $params */
    public function scalar(string $sql, array $params = []): mixed
    {
        return $this->run($sql, $params)->fetchColumn();
    }

    /* --- writes ------------------------------------------------------------ */

    /** @param array<string|int,mixed> $params */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * INSERT one row from a column => value map; returns the new auto-increment id.
     *
     * @param array<string,mixed> $row
     */
    public function insert(string $table, array $row): int
    {
        $columns = array_keys($row);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn (string $c): string => "`{$c}`", $columns)),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        $this->run($sql, array_values($row));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * UPDATE rows matching $where from a column => value map; returns affected rows.
     *
     * @param array<string,mixed> $row
     * @param array<int,mixed> $whereParams positional params for $where
     */
    public function update(string $table, array $row, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(
            static fn (string $c): string => "`{$c}` = ?",
            array_keys($row)
        ));

        return $this->execute(
            "UPDATE `{$table}` SET {$set} WHERE {$where}",
            array_merge(array_values($row), $whereParams)
        );
    }

    /**
     * Run $fn inside a transaction; commit on return, roll back and rethrow on
     * any throwable. Returns whatever $fn returns.
     */
    public function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $fn($this);
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /* ------------------------------------------------------------------ */

    /** @param array<string|int,mixed> $params */
    private function run(string $sql, array $params): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }
}
