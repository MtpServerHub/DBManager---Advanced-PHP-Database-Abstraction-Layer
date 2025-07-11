<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | qb_operators
 * |- @template | tidy room
 */

namespace DBManager\src\QueryBuilder\traits;

use http\Exception\InvalidArgumentException;
use http\Exception\RuntimeException;

trait qb_operators
{
    /* ---Operators--- */
    /**
     * Retrieves the details of the currently active query operation.
     *
     * @return array
     *     An array containing the operation ID and type.
     *
     * @throws RuntimeException
     *     If there is no active operation.
     */
    protected function checkCurrentOperation(): array
    {
        // Check is operation active
        if (empty($this->currentOperation)) {
            throw new RuntimeException("There is no active operation!");
        }

        $id = $this->currentOperation['id'];
        $type = $this->currentOperation['type'];

        return [$id, $type];
    }

    /**
     * Verifies that the table method was called before executing an operation.
     *
     * Ensures that the query builder is initialized and ready to perform actions.
     *
     * @return void
     *
     * @throws RuntimeException
     *     If the table method has not been called yet.
     */
    protected function isReady(): void
    {
        if (!self::$active) {
            throw new RuntimeException("Can not use without table method");
        }
    }

    /**
     * Sets the WHERE clause for the current query operation.
     *
     * Depending on the operation type (SELECT, UPDATE, DELETE), the condition will
     * be assigned to the correct internal storage. INSERT operations are not allowed
     * to use WHERE clauses.
     *
     * @param array $condition
     *     An associative array representing a condition to be added to the WHERE clause.
     *
     * @return void
     *
     * @throws RuntimeException
     *     If the operation type is INSERT and a WHERE clause is attempted.
     *
     * @throws InvalidArgumentException
     *     If the operation type is not one of the supported CRUD operations.
     */
    protected function setWhereClause(array $condition): void
    {
        [$id, $type] = $this->checkCurrentOperation();

        /* Check the operation is not (INSERT INTO)*/
        if ($type === 'insert' && debug_backtrace()[1]['function'] === 'where') {
            throw new RuntimeException('WHERE clause is not allowed for INSERT operations');
        }

        switch ($type) {
            case 'select':
                $this->selects[$id]['where'][] = $condition;
                break;
            case 'insert':
                $this->inserts[$id]['where'][] = $condition;
                break;
            case 'delete':
                $this->deletes[$id]['where'][] = $condition;
                break;
            case 'update':
                $this->updates[$id]['where'][] = $condition;
                break;
            default:
                throw new InvalidArgumentException('The operation type is not like CRUD records');
        }
    }

    /**
     * Validates that the provided SQL operator is supported.
     *
     * @param string $operator
     *     The SQL operator to validate (e.g. '=', 'IN', 'LIKE', etc.)
     *
     * @return bool
     *     True if the operator is supported, false otherwise.
     */
    protected function isValidOperator(string $operator): bool
    {
        return in_array(strtoupper($operator), [
            '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
            'LIKE', 'NOT LIKE', 'ILIKE',
            'IN', 'NOT IN',
            'BETWEEN', 'NOT BETWEEN',
            'IS NULL', 'IS NOT NULL',
        ]);
    }
    /* ---Operators--- */
}