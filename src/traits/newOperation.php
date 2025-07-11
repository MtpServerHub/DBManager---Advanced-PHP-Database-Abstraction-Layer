<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | newOperation
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDOException;
use PDOStatement;

trait newOperation
{
    /**
     * Creates and stores a new incomplete PDOStatement operation.
     *
     * This method prepares a SQL statement (without execution) and registers it under the specified
     * $operationId, which can later be executed or modified. It is useful when preparing reusable
     * statements or delayed execution logic.
     *
     * ### Example:
     *
    php
     * DBManager::newOperation('main', 101, true, 'selectUser');
     *
     *
     * @param string $dbID
     *     The ID of the connected database where the operation should be prepared.
     *
     * @param int $operationId
     *     A numeric identifier to store and reference the new operation.
     *
     * @param bool $return
     *     If true, returns the PDOStatement object; otherwise, returns a boolean.
     *
     * @param string $queryId
     *     The ID of the previously defined SQL query (via defineQuery() or equivalent).
     *     Default is 'default'.
     *
     * @return bool|PDOStatement
     *     Returns true on success, false on failure, or the PDOStatement if $return is true.
     *
     * @throws \PDOException
     *     If preparation of the statement fails at the PDO level.
     *
     * @throws \Exception
     *     For general errors such as missing connection or query ID.
     *
     * @access public
     * @static
     */
    public static function newOperation(
        string $dbID,
        int    $operationId,
        bool   $return = false,
        string $queryId = 'default',
    ): bool|PDOStatement
    {
        if (!self::start('newOperation')) {
            return false;
        }

        /* Check connection's id */
        if (!self::is_connection($dbID)) {
            self::monitor("newOperation: Connection {-$dbID-} not found...");
            return false;
        }

        /* Check query id */
        if (!self::is_query($queryId)) {
            self::monitor("newOperation: Query {-$queryId-} not found...");
            return false;
        }
        $query = self::$queries[$queryId];

        try {
            $conn = self::$conn[$dbID];
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                self::monitor("newOperation: Operation failed...");
                return false;
            }

            self::$operations[$operationId] = $stmt;
            self::$opIDS[$operationId] = [$dbID, $queryId];
            self::monitor("New operation {-$operationId-} successfully created...");
            return ($return ? $stmt : true);
        } catch (PDOException|Exception $e) {
            self::monitor("newOperation: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}