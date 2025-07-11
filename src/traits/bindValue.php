<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | bindValue
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDOException;

trait bindValue
{
    /**
     * Binds a direct value to a named placeholder in the SQL query of a given operation.
     *
     * This method is similar to bindParam, but it binds the actual value at the time of call,
     * not a reference. This is useful when the value should not change later.
     *
     * ### Example:
     *
     * DBManager::bindValue(1, ':email', 'test@example.com');
     *
     *
     * @param int $operationId
     *     The ID of the operation (from the operation stack).
     *
     * @param string $name
     *     The named placeholder (e.g., ':email'). If the : is missing, it will be prepended automatically.
     *
     * @param mixed $value
     *     The value to bind (e.g., string, int, float, etc.). This value is immediately set.
     *
     * @return bool
     *     Returns true on success, false on failure.
     *
     * @throws \PDOException
     *     If PDO fails to bind the value.
     *
     * @throws \Exception
     *     For general logic or internal operation errors.
     *
     * @access public
     * @static
     */
    public static function bindValue(int $operationId, string $name, mixed $value): bool
    {
        if (!self::start('bindValue')) {
            return false;
        }

        /* Check operation's id */
        if (!self::is_operator($operationId)) {
            self::monitor("bindValue: Operation {-$operationId-} not found...");
            return false;
        }

        /* Is there a placeholder in the query */
        $op = self::$operations[$operationId];
        if (!(self::is_spell_true($op->queryString, $name))) {
            return false;
        }

        try {
            $bind = $op->bindValue($name, $value);
            if ($bind) {
                self::$operations[$operationId] = $op;
                self::monitor("bindValue: Parameter {-$name-} bound successfully...");
                return true;
            }

            self::monitor("bindValue: binding {-$name-} failed...");
            return false;
        } catch (PDOException|Exception $e) {
            self::monitor("bindValue: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}