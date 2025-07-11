<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | fetchAll
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDO;
use PDOException;

trait fetchAll
{
    /**
     * Fetches all rows from a previously executed SQL operation.
     *
     * This method retrieves the entire result set for a given operation using the provided
     * fetch mode (e.g., associative array, objects, etc.).
     *
     * ### Example:
     *
    php
     * $rows = DBManager::fetchAll($operationId); // Default: FETCH_ASSOC
     *
     *
     * @param int $operationId
     *     The operation ID of the executed SQL statement.
     *
     * @param int $type
     *     The fetch style (e.g., PDO::FETCH_ASSOC, PDO::FETCH_OBJ). Default is PDO::FETCH_ASSOC.
     *
     * @param mixed ...$args
     *     Optional arguments to be passed to PDOStatement::fetchAll().
     *
     * @return mixed
     *     The fetched result set. Returns false if the operation fails.
     *
     * @throws \PDOException
     *     If PDO encounters a database error.
     *
     * @throws \Exception
     *     If internal operation fails or operation ID is invalid.
     *
     * @access public
     * @static
     */
    public static function fetchAll(int $operationId, int $type = PDO::FETCH_ASSOC, ...$args): mixed
    {
        if (!self::start('fetchAll')) {
            return false;
        }

        /* Check operation's id */
        if (!self::is_operator($operationId)) {
            self::monitor("fetchAll: Operation {-$operationId-} not found...");
            return false;
        }

        try {
            $op = self::$operations[$operationId];
            $fetch = $op->fetchAll($type, ...$args);
            if (!$fetch) {
                self::monitor("fetchAll: fetch failed...");
                return false;
            }

            self::monitor("fetchAll: Operation {-$operationId-} successfully fetched ...");
            return $fetch;
        } catch (PDOException|Exception $e) {
            self::monitor("fetchAll: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}