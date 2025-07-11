<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | bindParameter
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDOException;

trait bindParameter
{
    /**
     * Binds a value to a named placeholder in a SQL query for a specific operation.
     *
     * This method is part of the DBManager trait system and is used internally to
     * bind a value to a SQL query using it's named placeholder. It checks whether
     * the operation exists, verifies that the placeholder is present in the query,
     * and attempts to bind the value safely.
     *
     * Usage example:
     *
     * DBManager::bindParameter(1, ':username', 'admin');
     *
     *
     * ### Parameters:
     * @param int $operationId
     *     The ID of the current SQL operation (registered in DBManager).
     *
     * @param string $name
     *     The name of the placeholder in the SQL query (e.g. :username).
     *     If the name does not start with :, it will be automatically prepended.
     *
     * @param mixed $value
     *     The value to be bound to the placeholder. It can be any scalar or
     *     serializable value supported by PDO.
     *
     * ### Returns:
     * @return bool
     *     Returns true if the value was successfully bound, otherwise false.
     *
     * ### Throws:
     * @throws \PDOException
     *     If the PDO layer fails to bind the parameter.
     *
     * @throws \Exception
     *     If there are any other internal errors during binding.
     *
     * ### Internal Workflow:
     * - Validates the operation ID.
     * - Checks if the placeholder exists in the query string.
     * - Formats the placeholder name if missing colon (:).
     * - Calls $op->bindParam($name, $value) to apply the binding.
     * - Updates the operation reference if successful.
     *
     * ### Logging:
     * Logs binding status and errors using self::monitor.
     *
     * @access public
     * @static
     */
    public static function bindParameter(int $operationId, string $name, mixed $value): bool
    {
        if (!self::start('bindParameter')) {
            return false;
        }

        /* Check operation's id */
        if (!self::is_operator($operationId)) {
            self::monitor("bindParameter: Operation {-$operationId-} not found...");
            return false;
        }

        /* Is there a placeholder in the query */
        $op = self::$operations[$operationId];
        if (!(self::is_spell_true($op->queryString, $name))) {
            return false;
        }

        /* Check paramName */
        if (!str_starts_with($name, ':')) {
            $name = ':' . $name;
        }

        try {
            $bind = $op->bindParam($name, $value);
            if ($bind) {
                self::$operations[$operationId] = $op;
                self::monitor("bindParameter: Parameter {-$name-} bound successfully...");
                return true;
            }

            self::monitor("bindParameter: binding {-$name-} failed...");
            return false;
        } catch (PDOException|Exception $e) {
            self::monitor("bindParameter: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}