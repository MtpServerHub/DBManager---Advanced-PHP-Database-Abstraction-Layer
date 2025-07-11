<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | execute
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDO;
use PDOException;

trait execute
{
    /**
     * Executes a prepared SQL operation by ID with optional data or callback.
     *
     * This method executes the previously prepared SQL statement identified by $operationId.
     * It can accept either:
     * - an array of parameters to bind to the statement
     * - or a callable function for custom logic
     *
     * ### Example:
     *
    php
     * DBManager::execute($operationId, [':id' => 10]);
     *
     *
     * @param int $operationId
     *     The operation ID (previously registered via query or build).
     *
     * @param array|callable $value
     *     The values to bind to the statement OR a function to manipulate the statement.
     *
     * @param array $data
     *     Optional data passed to the callable function (if used).
     *
     * @return bool
     *     Returns true if execution was successful, otherwise false.
     *
     * @throws \PDOException
     *     If the PDO execution fails.
     *
     * @throws \Exception
     *     If operation ID is invalid or other internal errors occur.
     *
     * @access public
     * @static
     */
    public static function execute(int $operationId, array|callable $value = [], array $data = [], bool $commit = true): bool
    {
        if (!self::start('executeMethod')) {
            return false;
        }

        /* Check operation's id */
        if (!self::is_operator($operationId)) {
            self::monitor("executeMethod: Operation {-$operationId-} not found...");
            return false;
        }

        $op = self::$operations[$operationId];
        try {
            if (!empty($value)) {
                if (is_array($value)) {
                    $exec = $op->execute($value);
                } else {
                    if (!$value($data)) {
                        return false;
                    }
                    $exec = $op->execute();
                }
            } else {
                $exec = $op->execute();
            }

            if ($exec) {
                try {
                    $id = self::$opIDS[$operationId][0];
                    $db = self::$conn[$id];
                    $db->getAttribute(PDO::ATTR_AUTOCOMMIT);
                    self::commit($id);
                } catch (PDOException) {
                }
            }

            if ($exec) {
                self::monitor("executeMethod: Operation {-$operationId-} executed successfully...");
                return true;
            }

            self::monitor("executeMethod: executed failed {-$operationId-}...");
            return false;
        } catch (PDOException|Exception $e) {
            self::monitor("executeMethod: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}