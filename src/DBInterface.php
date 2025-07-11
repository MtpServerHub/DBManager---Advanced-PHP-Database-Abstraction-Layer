<?php
/*
 * |- @software  | PHP 8.2
 * |- @package   | DBManager
 * |- @author    | 3PHRCoder
 * |- @version   | V1.0.0
 * |- @source    | MtpServer.ir
 * |- @interface | DBInterface
 * |- @template  | tidy room
 */

namespace DBManager\src;

use PDO;
use PDOStatement;

interface DBInterface
{
    public static function bindParameter(int $operationId, string $name, mixed $value): bool;
    public static function bindValue(int $operationId, string $name, mixed $value): bool;
    public static function connect(string $id, bool $return = false): bool|PDO;
    public static function define(
        string $server,
        string $id,
        string $dbName,
        string $dbUsername,
        string $dbPassword,
        string $charset
    ): bool;
    public static function escape(string $value): string;
    public static function buildEscapeCache(): bool;
    public static function execute(int $id, array|callable $value = []): bool;
    public static function fetch(int $operationId, int $type = PDO::FETCH_ASSOC, mixed ...$args): mixed;
    public static function fetchAll(int $operationId, int $type = PDO::FETCH_ASSOC, ...$args): mixed;
    public static function fetchObject(int $operationId, ?string $className = 'stdClass', array $constructorArgs = []): object|false;
    public static function newOperation(string $connectionID, int $id, bool $return = false, string $use = 'default'): bool|PDOStatement;
    public static function query(string $queryID, string $query): bool;
    public static function setAttr(string $dbID, mixed $attribute, mixed $value): bool;
    public static function tidy(string $dbID, string $tableName, string $orderBy = 'id'): bool;
    public static function transaction(callable $callback, string $dbID): bool;
    public static function beingTransaction(string $dbID, ?int $isolationLevel = null, bool $developmentMode = false): bool;
    public static function commit(string $dbID): bool;
    public static function rollBack(string $dbID): bool;
    public static function inTransaction(string $dbID): bool;
}