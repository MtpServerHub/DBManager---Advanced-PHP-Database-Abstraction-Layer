<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | qb_CRUD
 * |- @template | tidy room
 */

namespace DBManager\src\QueryBuilder\traits;

use InvalidArgumentException;
use RuntimeException;

trait qb_CRUD
{
    /* ---CRUD Record--- */
    /**
     * Create a SELECT query
     *
     * Initializes a SELECT operation on the specified table. If no columns are specified,
     * selects all columns (*).
     *
     * @param array|null $params Array of column names to select, or null to select all columns
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws RuntimeException If table() hasn't been called first
     *
     * @example
     * // Select specific columns
     * $query->table('users')->select(['id', 'name', 'email']);
     *
     * @example
     * // Select all columns
     * $query->table('products')->select(null);
     * // Equivalent to: SELECT * FROM products
     */
    public function select(?array $params): static
    {
        // Check isReady
        $this->isReady();

        $id = uniqid('qb_');
        $this->selects[$id] = [
            'params' => (is_null($params) ? '*' : $params)
        ];

        $this->currentOperation = ['id' => $id, 'type' => 'select'];
        return $this;
    }

    /**
     * Create an INSERT query
     *
     * Initializes an INSERT operation on the specified table with the given column-value pairs.
     * The columns and values arrays must have matching lengths.
     *
     * @param array $columns Array of column names to insert into
     * @param array $values Array of corresponding values to insert
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws RuntimeException If table() hasn't been called first
     * @throws InvalidArgumentException If columns and values counts don't match
     *
     * @example
     * // Basic insert
     * $query->table('users')
     *       ->insert(
     *           ['name', 'email', 'age'],
     *           ['John Doe', 'john@example.com', 30]
     *       );
     * // Equivalent to:
     * // INSERT INTO users (name, email, age) VALUES ('John Doe', 'john@example.com', 30)
     */
    public function insert(array $columns, array $values): static
    {
        // Check isReady
        $this->isReady();

        $id = uniqid('qb_');
        $this->inserts[$id] = [
            'columns' => $columns,
            'values'  => $values
        ];

        $this->currentOperation = ['id' => $id, 'type' => 'insert'];
        return $this;
    }

    /**
     * Create a DELETE query
     *
     * Initializes a DELETE operation on the specified table. Note that without WHERE
     * conditions, this will delete all rows from the table.
     *
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws RuntimeException If table() hasn't been called first
     *
     * @example
     * // Delete with condition
     * $query->table('users')
     *       ->delete()
     *       ->where('id', '=', 5);
     * // Equivalent to: DELETE FROM users WHERE id = 5
     *
     * @warning Be careful with DELETE queries - always verify your conditions
     */
    public function delete(): static
    {
        // Check isReady
        $this->isReady();

        $id = uniqid('qb_');
        $this->deletes[$id] = [];

        $this->currentOperation = ['id' => $id, 'type' => 'delete'];
        return $this;
    }


    /**
     * Create an UPDATE query
     *
     * Initializes an UPDATE operation on the specified table with the given column-value pairs.
     * Typically used with WHERE conditions to specify which rows to update.
     *
     * @param array $columns Associative array of column-value pairs to update
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws RuntimeException If table() hasn't been called first
     *
     * @example
     * // Basic update with condition
     * $query->table('users')
     *       ->update(['name' => 'Jane', 'age' => 25])
     *       ->where('id', '=', 10);
     * // Equivalent to:
     * // UPDATE users SET name = 'Jane', age = 25 WHERE id = 10
     *
     * @warning Without WHERE conditions, this will update all rows in the table
     */
    public function update(array $columns): static
    {
        // Check isReady
        $this->isReady();

        $id = uniqid('qb_');
        $this->updates[$id] = [
            'set' => $columns
        ];

        $this->currentOperation = ['id' => $id, 'type' => 'update'];
        return $this;
    }
    /* ---CRUD Record--- */
}