<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | fetch
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDO;
use PDOException;

trait fetch
{
    /**
     * Fetches the result of a previously executed SQL operation.
     *
     * This method retrieves the result set from the executed statement corresponding
     * to the given $operationId. It supports different fetch styles and extra arguments.
     *
     * ### Example:
     *
    php
     * $row = DBManager::fetch($operationId); // Default: FETCH_ASSOC
     * $obj = DBManager::fetch($operationId, PDO::FETCH_OBJ);
     *
     *
     * @param int $operationId
     *     The ID of the SQL operation to fetch results from.
     *
     * @param int $type
     *     Fetch mode (e.g., PDO::FETCH_ASSOC, PDO::FETCH_OBJ, etc.). Default is PDO::FETCH_ASSOC.
     *
     * @param mixed ...$args
     *     Additional arguments passed to PDO's fetch() method (optional).
     *
     * @return mixed
     *     The fetched result (array, object, scalar), or false on failure.
     *
     * @throws \PDOException
     *     If PDO encounters a database-level error during fetching.
     *
     * @throws \Exception
     *     If any other internal logic error occurs.
     *
     * @access public
     * @static
     */
    public static function fetch(int $operationId, int $type = PDO::FETCH_ASSOC, mixed ...$args): mixed
    {
        if (!self::start('fetchMethod')) {
            return false;
        }

        /* Check operation's id */
        if (!self::is_operator($operationId)) {
            self::monitor("fetchMethod: Operation {-$operationId-} not found...");
            return false;
        }

        try {
            $op = self::$operations[$operationId];
            $fetch = $op->fetch($type, ...$args);
            if (!$fetch) {
                self::monitor("fetchMethod: fetch failed {-$operationId-}...");
                return false;
            }

            self::monitor("fetchMethod: Operation {-$operationId-} successfully fetched ...");
            return $fetch;
        } catch (PDOException|Exception $e) {
            self::monitor("fetchMethod: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}