<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | operators
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use DBManager\src\enums\MODE;
use Exception;
use PDO;
use PDOException;

trait operators
{
    /**
     * Checks if a database with the given ID is registered.
     *
     * @param string $id
     *     The database identifier.
     *
     * @return bool
     *     True if the database exists in the registry, false otherwise.
     *
     * @access public
     * @static
     */
    public static function is_database(string $id): bool
    {
        $dbs = self::$databases;
        return array_key_exists($id, $dbs);
    }

    /**
     * Checks if a database connection with the given ID exists.
     *
     * @param string $id
     *     The connection identifier.
     *
     * @return bool
     *     True if the connection exists, false otherwise.
     *
     * @access public
     * @static
     */
    public static function is_connection(string $id): bool
    {
        $conns = self::$conn;
        return array_key_exists($id, $conns);
    }

    /**
     * Checks if an operation with the given ID is defined.
     *
     * @param int $id
     *     The operation ID.
     *
     * @return bool
     *     True if the operation exists, false otherwise.
     *
     * @access public
     * @static
     */
    public static function is_operator(int $id): bool
    {
        $ops = self::$operations;
        return array_key_exists($id, $ops);
    }

    /**
     * Checks if a query with the given ID is defined.
     *
     * @param string $id
     *     The query identifier.
     *
     * @return bool
     *     True if the query exists, false otherwise.
     *
     * @access public
     * @static
     */
    public static function is_query(string $id): bool
    {
        $queries = self::$queries;
        return array_key_exists($id, $queries);
    }

    /**
     * Checks whether the current request is served over HTTPS and SSL is enabled in config.
     *
     * @return bool
     *     True if SSL is enabled and HTTPS is on, false otherwise.
     *
     * @access public
     * @static
     */
    public static function is_safety(): bool
    {
        return (self::$ssl && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    }

    /**
     * Validates that a named parameter exists within the provided SQL query string.
     *
     * The behavior depends on the mode defined in MODE::ATTR_NAME_ERROR_BINDING:
     * - CONTINUE: returns false if not found
     * - THROW: throws an Exception
     * - STOP: logs the error and returns false
     * - Default: logs a generic error
     *
     * @param string $query
     *     The SQL query string to search within.
     *
     * @param string $name
     *     The named parameter (e.g., ':id', ':name') to search for.
     *
     * @return bool
     *     True if the parameter name is found; otherwise, depends on the error handling mode.
     *
     * @throws \Exception
     *     If the mode is THROW and the parameter is not found.
     *
     * @access public
     * @static
     */
    public static function is_spell_true(string $query, string $name): bool
    {
        $mode = MODE::ATTR_NAME_ERROR_BINDING;
        $msg = "While binding parameter: Parameter {-$name-} not found in query!";

        if (array_key_exists($mode, self::$modes)) {
            if (str_contains($query, $name)) {
                return true;
            }

            /* Check responses */
            return match (self::$modes[$mode]) {
                MODE::RESPONSE_CONTINUE => false,
                MODE::RESPONSE_THROW => throw new Exception($msg),
                MODE::RESPONSE_STOP => (function () use ($msg) {
                    self::monitor($msg);
                    return false;
                })(),
                default => (function () {
                    self::monitor('Binding failed: Invalid mode specified');
                    return false;
                })()
            };
        } else {
            self::monitor('While binding parameter: The name-error binding mode is undefined...');
            return false;
        }
    }

    /**
     * Checks whether a given table exists in the currently selected database.
     *
     * This method queries the `INFORMATION_SCHEMA.TABLES` to determine if a table
     * with the specified name exists in the active database of the given connection ID.
     *
     * @param string $dbID       The identifier of the database connection.
     * @param string $tableName  The name of the table to check for existence.
     *
     * @return bool Returns true if the table exists, false otherwise (including on failure or error).
     */
    public static function tableExists(string $dbID, string $tableName): bool
    {
        if (!self::start('tableExists')) {
            return false;
        }

        /* Check connection's id */
        if (!self::is_connection($dbID)) {
            self::monitor("tableExists: Connection {-$dbID-} not found...");
            return false;
        }
        $conn = self::$conn[$dbID];

        try {
            $stmt = $conn->prepare("
            SELECT 1 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = :table
            LIMIT 1");

            $stmt->bindParam(':table', $tableName);
            $stmt->execute();

            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException|Exception $e) {
            self::monitor("tableExists: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}