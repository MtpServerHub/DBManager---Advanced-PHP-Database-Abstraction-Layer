<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | options
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use PDOException;
use PDOStatement;

trait options
{
    /**
     * Internal method to retrieve the PDOStatement object for a given operation.
     *
     * @param int $operationId
     *     ID of the operation.
     *
     * @return false|PDOStatement
     *     Returns the PDOStatement if found; otherwise false.
     *
     * @access private
     * @static
     */
    private static function options(int $operationId): false|PDOStatement
    {
        if (!self::start('options')) {
            return false;
        }

        if (!self::is_operator($operationId)) {
            self::monitor("options: Operation {-$operationId-} not found...");
            return false;
        }

        return self::$operations[$operationId];
    }

    /**
     * Retrieves the SQL query string from a prepared operation.
     *
     * @param int $operationId
     *     ID of the operation.
     *
     * @return false|string
     *     The SQL string of the PDOStatement, or false on failure.
     *
     * @access public
     * @static
     */
    public static function getQuery(int $operationId): false|string
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement ? $data->queryString : false);
    }

    /**
     * Gets the number of columns in the result set.
     *
     * @param int $operationId
     *     ID of the operation.
     *
     * @return false|int
     *     Number of columns, or false on failure.
     *
     * @access public
     * @static
     */
    public static function columnCount(int $operationId): false|int
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement ? $data->columnCount() : false);
    }

    /**
     * Returns metadata for a specific column in the result set.
     *
     * @param int $operationId
     *     ID of the operation.
     * @param int $num
     *     Column index (starting from 0).
     *
     * @return false|PDOStatement
     *     Metadata array or false on failure.
     *
     * @access public
     * @static
     */
    public static function getColumnMeta(int $operationId, int $num): false|PDOStatement
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement ? $data->getColumnMeta($num) : false);
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @param int $operationId
     *     ID of the operation.
     *
     * @return false|PDOStatement
     *     True if successful, false otherwise.
     *
     * @access public
     * @static
     */
    public static function closeCursor(int $operationId): false|PDOStatement
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement && $data->closeCursor());
    }

    /**
     * Re-executes the current operation to reset the cursor.
     *
     * @param int $operationId
     *     ID of the operation.
     *
     * @return bool
     *     True if reset is successful, false otherwise.
     *
     * @access public
     * @static
     */
    public static function resetCursor(int $operationId): bool
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement && $data->execute());
    }

    /**
     * Checks whether a given PDO attribute exists for the operation.
     *
     * @param int $operationId
     *     ID of the operation.
     * @param int $attr
     *     PDO attribute constant (e.g. PDO::ATTR_ERRMODE).
     *
     * @return bool
     *     True if attribute is accessible, false if exception occurs.
     *
     * @access public
     * @static
     */
    public static function attrISexists(int $operationId, int $attr): bool
    {
        $data = self::options($operationId);
        try {
            $data->getAttribute($attr);
            return true;
        } catch (PDOException) {return false;}
    }

    /**
     * Gets the value of a specific PDO attribute.
     *
     * @param int $operationId
     *     ID of the operation.
     * @param int $attr
     *     PDO attribute constant.
     *
     * @return mixed
     *     Attribute value or false on failure.
     *
     * @access public
     * @static
     */
    public static function getAttribute(int $operationId, int $attr): mixed
    {
        $data = self::options($operationId);
        try {
            return $data->getAttribute($attr);
        } catch (PDOException) {return false;}
    }

    /**
     * Fetches a single column from the next row in the result set.
     *
     * @param int $operationId
     *     ID of the operation.
     * @param int $num
     *     Column index to fetch.
     *
     * @return bool
     *     Value of the column or false on failure.
     *
     * @access public
     * @static
     */
    public static function fetchColumn(int $operationId, int $num): bool
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement && $data->fetchColumn($num));
    }

    /**
     * Outputs debugging information about a prepared statement.
     *
     * @param int $operationId
     *     ID of the operation.
     *
     * @return ?bool
     *     True on success, null or false on failure.
     *
     * @access public
     * @static
     */
    public static function debugDumpParams(int $operationId): ?bool
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement && $data->debugDumpParams());
    }

    /**
     * Retrieves extended error information from the last operation.
     *
     * @param int $operationId
     *     ID of the operation.
     *
     * @return array|false
     *     Error info array or false on failure.
     *
     * @access public
     * @static
     */
    public static function getErrorInfo(int $operationId): array|false
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement ? $data->errorInfo() : false);
    }

    /**
     * Returns the SQLSTATE error code for the last operation.
     *
     * @param int $operationId
     *     ID of the operation.
     *
     * @return string|false|null
     *     SQLSTATE code, or false/null on failure.
     *
     * @access public
     * @static
     */
    public static function getErrorCode(int $operationId): string|false|null
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement ? $data->errorCode() : false);
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @param int $operationId
     *     ID of the operation.
     *
     * @return false|int
     *     Row count, or false on failure.
     *
     * @access public
     * @static
     */
    public static function rowCount(int $operationId): false|int
    {
        $data = self::options($operationId);
        return ($data instanceof PDOStatement ? $data->rowCount() : false);
    }
}