<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use Throwable;

final class DB
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = (string) config('database.driver', 'mysql');
        $database = (string) config('database.database', 'osas_beacon');

        $dsn = $driver === 'sqlite'
            ? 'sqlite:' . $database
            : sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                config('database.host'),
                config('database.port'),
                $database,
                config('database.charset', 'utf8mb4'),
            );

        try {
            self::$pdo = new PDO(
                $dsn,
                (string) config('database.username', ''),
                (string) config('database.password', ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ],
            );
        } catch (PDOException $exception) {
            throw new PDOException('Database connection failed: ' . $exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        return self::$pdo;
    }

    /** @return list<array<string, mixed>> */
    public static function select(string $sql, array $bindings = []): array
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public static function first(string $sql, array $bindings = []): ?array
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $bindings = []): mixed
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);
        $value = $statement->fetchColumn();

        return $value === false ? null : $value;
    }

    public static function insert(string $sql, array $bindings = []): int
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);

        return (int) self::connection()->lastInsertId();
    }

    public static function update(string $sql, array $bindings = []): int
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    public static function delete(string $sql, array $bindings = []): int
    {
        return self::update($sql, $bindings);
    }

    public static function statement(string $sql): void
    {
        self::connection()->exec($sql);
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}
