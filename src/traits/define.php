<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | define
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

trait define
{
    /**
     * Defines and registers a new database connection configuration.
     *
     * This method stores connection credentials under a custom $dbID to allow future
     * connection via connect($dbID). It supports multiple database profiles.
     *
     * ### Example:
     *
     * DBManager::define('localhost', 'main', 'mydb', 'root', 'secret', 'utf8mb4');
     *
     *
     * @param string $server
     *     The database server hostname or IP (e.g., 'localhost').
     *
     * @param string $dbID
     *     The identifier for the database connection, used later in connect().
     *
     * @param string $dbName
     *     The name of the database to connect to.
     *
     * @param string $dbUsername
     *     The username for database access.
     *
     * @param string $dbPassword
     *     The password for the specified user.
     *
     * @param string $charset
     *     Character set for the connection. Default is 'utf8mb4'.
     *
     * @return bool
     *     Returns true if the configuration is saved successfully, false otherwise.
     *
     * @access public
     * @static
     */
    public static function define(
        string $server,
        string $dbID,
        string $dbName,
        string $dbUsername,
        string $dbPassword,
        string $charset = 'utf8mb4'
    ): bool {
        if (!self::start('defineMethod')) {
            return false;
        }

        /* Add new database */
        $information = [
            'server' => $server,
            'db-name' => $dbName,
            'db-user' => $dbUsername,
            'db-pass' => $dbPassword,
            'charset' => $charset,
        ];
        $toJson = @json_encode($information);

        self::$databases[$dbID] = $information;
        self::monitor("defineMethod: New database defined $toJson");
        return true;
    }
}