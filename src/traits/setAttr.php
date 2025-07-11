<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | setAttr
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDOException;

trait setAttr
{
    /**
     * Sets a PDO attribute for a specific database connection.
     *
     * This method allows configuring specific attributes such as error mode, emulation, etc.
     *
     * @param string $dbID
     *     The identifier of the connected database.
     *
     * @param mixed $attribute
     *     The PDO::ATTR_* constant to set (e.g., PDO::ATTR_ERRMODE).
     *
     * @param mixed $value
     *     The value to assign to the attribute (e.g., PDO::ERRMODE_EXCEPTION).
     *
     * @return bool
     *     Returns true if the attribute is successfully set, false otherwise.
     *
     * @throws PDOException|Exception
     *     If the operation fails due to an invalid connection or PDO error.
     *
     * @access public
     * @static
     */
    public static function setAttr(string $dbID, mixed $attribute, mixed $value): bool
    {
        if (!self::start('bindParameter')) {
            return false;
        }

        /* Check connection's id */
        if (!self::is_connection($dbID)) {
            self::monitor("While setting attribute: Connection {-$dbID-} not found...");
            return false;
        }

        try {
            $conn = self::$conn[$dbID];
            $conn->setAttribute($attribute, $value);

            self::$conn[$dbID] = $conn;
            self::monitor("Attribute set...");
            return true;
        } catch (PDOException|Exception $e) {
            self::monitor("While setting attribute: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}