<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | connect
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDO;
use PDOException;

trait connect
{
    /**
     * Establishes a PDO connection to a predefined database using its ID.
     *
     * This method uses the internal configuration to look up a database by $dbID
     * and attempts to connect using PDO. If $return is true, the PDO object is returned.
     *
     * ### Example:
     *
     * DBManager::connect('default');             // just connect
     * $pdo = DBManager::connect('main', true);   // connect and get PDO instance
     *
     *
     * @param string $dbID
     *     The identifier of the database (defined in configuration).
     *
     * @param bool $return
     *     If set to true, returns the PDO connection object instead of just status.
     *
     * @return bool|\PDO
     *     Returns true on success, false on failure, or PDO instance if $return = true.
     *
     * @throws \PDOException
     *     If PDO fails to connect.
     *
     * @throws \Exception
     *     For general logic or configuration issues.
     *
     * @access public
     * @static
     */
    public static function connect(
        string $dbID,
        bool   $return = false,
    ): bool|PDO {
        if (!self::start('connecting')) {
            return false;
        }

        /* Check id */
        if (!self::is_database($dbID)) {
            self::monitor("connecting: Database {-$dbID-} not found...");
            return false;
        }
        $db = self::$databases[$dbID];

        try {
            $dsn = "mysql:host={$db['server']};dbname={$db['db-name']};charset={$db['charset']}";
            $conn = new PDO($dsn, $db['db-user'], $db['db-pass']);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conn->setAttribute(PDO::ATTR_PERSISTENT, false);
            $conn->setAttribute(PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT, self::$ssl);
            $conn->setAttribute(PDO::ATTR_AUTOCOMMIT, false);

            self::$conn[$dbID] = $conn;
            self::monitor("connecting: Database {-$dbID-} connected successfully...");
            return ($return ? $conn : true);
        } catch (PDOException|Exception $e) {
            self::monitor("connecting: Some problems found {$e->getMessage()}...");
            return false;
        }
    }
}