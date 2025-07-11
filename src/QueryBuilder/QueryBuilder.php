<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @class    | QueryBuilder
 * |- @template | tidy room
 */

namespace DBManager\src\QueryBuilder;

use DBManager\src\DBManager;
use DBManager\src\QueryBuilder\traits\qb_buildQuery;
use DBManager\src\QueryBuilder\traits\qb_CRUD;
use DBManager\src\QueryBuilder\traits\qb_operators;
use DBManager\src\QueryBuilder\traits\qb_whereClause;
use http\Exception\RuntimeException;

class QueryBuilder
{
    use qb_operators;
    use qb_CRUD;
    use qb_whereClause;
    use qb_buildQuery;

    // Operation
    private static bool $active;

    // Basement
    private static string $tableName = '';
    private array  $currentOperation = [];
    private array  $query = [];
    private array  $bindings = [];

    // CRUD
    private array $selects = [];
    private array $inserts = [];
    private array $deletes = [];
    private array $updates = [];

    /* ---Basement--- */
    /**
     * Prepares internal state for a new query operation.
     *
     * @param string $tableName
     *     Name of the table to be used in the query.
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function getReady(string $tableName): void
    {
        self::$active = true;
        self::$tableName = $tableName;
    }

    /**
     * Starts a new query builder operation on the given table.
     *
     * This static method ensures DBManager is started and returns a new QueryBuilder instance.
     *
     * @param string $tableName
     *     The name of the database table to run queries on.
     *
     * @return static
     *     An initialized instance of the QueryBuilder for chaining.
     *
     * @throws RuntimeException
     *     If DBManager::start fails to initialize.
     *
     * @access public
     * @static
     */
    public static function table(string $tableName): static
    {
        if (!DBManager::start('QueryBuilder(table)')) {
            throw new RuntimeException('Check DBManager monitor!');
        }

        self::getReady($tableName);
        return new self();
    }
    /* ---Basement--- */
}