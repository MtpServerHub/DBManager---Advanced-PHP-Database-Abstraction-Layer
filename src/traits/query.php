<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | query
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

trait query
{
    /**
     * Registers a new SQL query to the DBManager under a specific ID.
     *
     * Useful for defining reusable queries that can be executed later using the operation system.
     *
     * @param string $queryID
     *     A unique identifier for the query.
     *
     * @param string $query
     *     The actual SQL query string (can include named parameters like :id).
     *
     * @return bool
     *     Returns true if the query is successfully registered, false otherwise.
     *
     * @access public
     * @static
     */
    public static function query(
        string $queryID,
        string $query
    ): bool {
        if (!self::start('bindParameter')) {
            return false;
        }

        self::$queries[$queryID] = $query;
        self::monitor("queryMethod: New query {-$queryID-} created successfully.");
        return true;
    }
}