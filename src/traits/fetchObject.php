<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | fetchObject
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDOException;

trait fetchObject
{
    /**
     * Fetches the next row from a result set as an object of the given class.
     *
     * This method retrieves a single row and maps it into an object.
     * If no $className is provided, it defaults to stdClass.
     *
     * ### Example:
     *
    php
     * $user = DBManager::fetchObject($operationId, User::class, [$dependency]);
     *
     *
     * @param int $operationId
     *     The operation ID of the executed SQL statement.
     *
     * @param string|null $className
     *     The name of the class to instantiate. Defaults to stdClass if null.
     *
     * @param array $constructorArgs
     *     Optional arguments to pass to the class constructor.
     *
     * @return object|false
     *     Returns an instance of the specified class on success, or false on failure.
     *
     * @throws \PDOException
     *     If a database error occurs.
     *
     * @throws \Exception
     *     If operation fails or operation ID is invalid.
     *
     * @access public
     * @static
     */
    public static function fetchObject(int $operationId, ?string $className = 'stdClass', array $constructorArgs = []): object|false
    {
        if (!self::start('fetchObject')) {
            return false;
        }

        /* Check operation's id */
        if (!self::is_operator($operationId)) {
            self::monitor("fetchObject: Operation {-$operationId-} not found...");
            return false;
        }

        /* Get operation */
        $op = self::$operations[$operationId];

        try {
            $fetch = $op->fetchObject($className, $constructorArgs);
            if (!$fetch) {
                self::monitor("fetchObject: fetch failed...");
                return false;
            }

            self::monitor("fetchObject: Successfully fetched object of type {-$className-} from operation {-$operationId-}");
            return $fetch;
        } catch (PDOException|Exception $e) {
            self::monitor("fetchObject: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}