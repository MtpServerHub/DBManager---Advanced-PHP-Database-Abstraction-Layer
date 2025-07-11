<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | qb_whereClause
 * |- @template | tidy room
 */

namespace DBManager\src\QueryBuilder\traits;

use Couchbase\InvalidRangeException;
use http\Exception\InvalidArgumentException;

trait qb_whereClause
{
    /* ---WhereClause--- */
    /**
     * Method to set conditions
     *
     * @param string $column The Column name
     * @param string $operator Special operator
     * @param mixed $value Column value
     * @param ?string $boolean AND/OR
     * @return $this Continues chain
     * @throws InvalidArgumentException Throws when the operator type is invalid
     */
    /**
     * Add a basic WHERE clause to the query.
     *
     * @param string $column Description of column
     * @param string $operator Description of operator
     * @param mixed $value Description of value
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws InvalidArgumentException If the operator is invalid
     *
     * @example
     * $query->table('table')
    ->where(...);

    // SELECT * FROM table WHERE ...
     */
    public function where(
        string $column,
        string $operator,
        mixed $value,
        ?string $boolean = null
    ): static {
        // Check isReady
        $this->isReady();
        $this->activeWhere = true;

        // Check is valid operator
        if (!$this->isValidOperator($operator)) {
            throw new InvalidArgumentException('The entered operator for where clause is invalid');
        }

        // Check the $boolean
        if ($boolean !== null && !in_array(strtoupper($boolean), ['AND', 'OR'])) {
            throw new InvalidArgumentException('Boolean operator must be either AND or OR');
        }

        $placeholder = ":where_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . "_" . count($this->bindings);
        $cond = [
            'column'      => $column,
            'op'          => $operator,
            'placeholder' => $placeholder,
            'type'        => 'basic',
            'boolean'     => $boolean
        ];

        $this->setWhereClause($cond);
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    /**
     * Add an AND WHERE clause to the query (chainable with other conditions)
     *
     * This is a convenience method that calls where() with 'AND' boolean operator.
     * It adds a condition that must be satisfied along with previous conditions.
     *
     * @param string $column The database column name to compare
     * @param string $operator Comparison operator (e.g., '=', '>', '<>', 'LIKE')
     * @param mixed $value The value to compare against the column
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws InvalidArgumentException If the operator is invalid
     *
     * @example
     * // Simple AND condition
     * $query->table('users')
     *       ->where('age', '>', 18)
     *       ->andWhere('status', '=', 'active')
     *       ->select();
     *
     * // Equivalent to:
     * // SELECT * FROM users WHERE age > 18 AND status = 'active'
     */
    /**
     * Add an AND WHERE clause to the query (chainable with other conditions).
     *
     * @param string $column Description of column
     * @param string $operator Description of operator
     * @param mixed $value Description of value
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws InvalidArgumentException If the operator is invalid
     *
     * @example
     * $query->table('table')
    ->andWhere(...);

    // SELECT * FROM table WHERE ...
     */
    public function andWhere(
        string $column,
        string $operator,
        mixed  $value
    ): static {
        return $this->where($column, $operator, $value, 'AND');
    }

    /**
     * Add an OR WHERE clause to the query (alternative condition)
     *
     * This is a convenience method that calls where() with 'OR' boolean operator.
     * It adds a condition that can be satisfied as an alternative to previous conditions.
     *
     * @param string $column The database column name to compare
     * @param string $operator Comparison operator (e.g., '=', '>', '<>', 'LIKE')
     * @param mixed $value The value to compare against the column
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws InvalidArgumentException If the operator is invalid
     *
     * @example
     * // Combined AND/OR conditions
     * $query->table('products')
     *       ->where('category', '=', 'electronics')
     *       ->orWhere('price', '<', 100)
     *       ->select();
     *
     * // Equivalent to:
     * // SELECT * FROM products WHERE category = 'electronics' OR price < 100
     *
     * @example
     * // Complex condition grouping
     * $query->table('orders')
     *       ->where('status', '=', 'completed')
     *       ->orWhere(function($q) {
     *           $q->where('status', '=', 'pending')
     *             ->andWhere('created_at', '>', '2023-01-01');
     *       })
     *       ->select();
     *
     * // Equivalent to:
     * // SELECT * FROM orders WHERE status = 'completed'
     * // OR (status = 'pending' AND created_at > '2023-01-01')
     */
    /**
     * Add an OR WHERE clause to the query (chainable with other conditions).
     *
     * @param string $column Description of column
     * @param string $operator Description of operator
     * @param mixed $value Description of value
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws InvalidArgumentException If the operator is invalid
     *
     * @example
     * $query->table('table')
    ->orWhere(...);

    // SELECT * FROM table WHERE ...
     */
    public function orWhere(
        string $column,
        string $operator,
        mixed $value
    ): static {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a WHERE NOT clause to the query
     *
     * @param string $column The column name
     * @param string $operator Comparison operator
     * @param mixed $value The value to compare
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     * @throws InvalidArgumentException
     */
    /**
     * Add a WHERE NOT clause to the query.
     *
     * @param string $column Description of column
     * @param string $operator Description of operator
     * @param mixed $value Description of value
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws InvalidArgumentException If the operator is invalid
     *
     * @example
     * $query->table('table')
    ->whereNot(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereNot(
        string $column,
        string $operator,
        mixed $value,
        ?string $boolean = null
    ): static {
        $this->isReady();

        if (!$this->isValidOperator($operator)) {
            throw new InvalidArgumentException('Invalid SQL operator: ' . $operator);
        }

        $placeholder = ":not_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . "_" . count($this->bindings);

        $cond = [
            'column' => $column,
            'op' => 'NOT ' . $operator,
            'placeholder' => $placeholder,
            'type' => 'not',
            'boolean' => $boolean
        ];

        $this->setWhereClause($cond);
        $this->bindings[$placeholder] = $value;

        return $this;
    }

    /**
     * Add a WHERE NOT IN clause to the query
     *
     * @param string $column The column name
     * @param array $values Array of values
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     * @throws InvalidArgumentException
     */
    /**
     * Add a WHERE NOT IN clause.
     *
     * @param string $column Description of column
     * @param array $values Description of values
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereNotIn(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereNotIn(
        string $column,
        array $values,
        ?string $boolean = null
    ): static {
        $this->isReady();

        $placeholders = [];
        foreach ($values as $key => $value) {
            $placeholder = ":notin_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . "_" . $key;
            $placeholders[] = $placeholder;
            $this->bindings[$placeholder] = $value;
        }

        $cond = [
            'column' => $column,
            'op' => 'NOT IN',
            'placeholders' => $placeholders,
            'type' => 'not_in',
            'boolean' => $boolean
        ];

        $this->setWhereClause($cond);
        return $this;
    }

    /**
     * Add a WHERE BETWEEN clause to the query
     *
     * Filters records where the column value is between two specified values (inclusive).
     * This is useful for range queries on numeric, date, or text fields.
     *
     * @param string $column The database column to filter on
     * @param array $values Array containing exactly two values [lower_bound, upper_bound]
     * @param bool $isText Whether the comparison is for text values (affects collation)
     * @param string|null $boolean Logical connector (AND/OR) for chaining conditions
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws \Couchbase\InvalidRangeException When values array doesn't contain exactly 2 elements
     * @throws \RuntimeException When called without first specifying a table
     *
     * @example
     * // Numeric range
     * $query->table('products')
     *       ->whereBetween('price', [100, 500])
     *       ->select();
     * // WHERE price BETWEEN 100 AND 500
     *
     * @example
     * // Date range with AND connector
     * $query->table('orders')
     *       ->whereBetween('order_date', ['2023-01-01', '2023-12-31'], false, 'AND')
     *       ->select();
     * // WHERE order_date BETWEEN '2023-01-01' AND '2023-12-31'
     *
     * @example
     * // Text range (alphabetical)
     * $query->table('customers')
     *       ->whereBetween('last_name', ['A', 'M'], true)
     *       ->select();
     * // WHERE last_name BETWEEN 'A' AND 'M' COLLATE utf8mb4_general_ci
     */
    /**
     * Add a WHERE BETWEEN clause.
     *
     * @param string $column Description of column
     * @param array $values Description of values
     * @param bool $isText Description of isText
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @throws \Couchbase\InvalidRangeException
     * @example
     * $query->table('table')
     * ->whereBetween(...);
     *
     * // SELECT * FROM table WHERE ...
     */
    public function whereBetween(
        string $column,
        array $values,
        bool $isText = false,
        ?string $boolean = null
    ): static {
        $this->isReady();

        if (count($values) !== 2) {
            throw new InvalidRangeException('whereBetween requires exactly 2 values [lower_bound, upper_bound');
        }

        // Ensure proper ordering of values
        if ($values[0] > $values[1]) {
            [$values[0], $values[1]] = [$values[1], $values[0]]; // Swap values
        }

        // Add placeholders
        $placeholders = [];
        foreach ($values as $index => $value) {
            $placeholder = ":btw_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . "_$index";
            $placeholders[] = $placeholder;
            $this->bindings[$placeholder] = $isText ? "'$value'" : $value;
        }

        $cond = [
            'column' => $column,
            'op' => 'BETWEEN',
            'placeholders' => $placeholders,
            'type' => 'between',
            'boolean' => $boolean,
            'isText' => $isText
        ];
        $this->setWhereClause($cond);
        return $this;
    }

    /**
     * Add a WHERE NOT BETWEEN clause to the query
     *
     * Filters records where the column value is NOT between two specified values.
     * This is the inverse of whereBetween() and useful for excluding ranges.
     *
     * @param string $column The database column to filter on
     * @param array $values Array containing exactly two values [lower_bound, upper_bound]
     * @param bool $isText Whether the comparison is for text values (affects collation)
     * @param string|null $boolean Logical connector (AND/OR) for chaining conditions
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws \Couchbase\InvalidRangeException When values array doesn't contain exactly 2 elements
     * @throws \RuntimeException When called without first specifying a table
     *
     * @example
     * // Numeric range exclusion
     * $query->table('products')
     *       ->whereNotBetween('price', [100, 500])
     *       ->select();
     * // WHERE price NOT BETWEEN 100 AND 500
     *
     * @example
     * // Date range exclusion with AND connector
     * $query->table('orders')
     *       ->whereNotBetween('order_date', ['2023-01-01', '2023-12-31'], false, 'AND')
     *       ->select();
     * // WHERE order_date NOT BETWEEN '2023-01-01' AND '2023-12-31'
     *
     * @example
     * // Text range exclusion (alphabetical)
     * $query->table('customers')
     *       ->whereNotBetween('last_name', ['A', 'M'], true)
     *       ->select();
     * // WHERE last_name NOT BETWEEN 'A' AND 'M' COLLATE utf8mb4_general_ci
     *
     * @see whereBetween() For the inverse operation
     */
    /**
     * Add a WHERE NOT BETWEEN clause.
     *
     * @param string $column Description of column
     * @param array $values Description of values
     * @param bool $isText Description of isText
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @throws \Couchbase\InvalidRangeException
     * @example
     * $query->table('table')
     * ->whereNotBetween(...);
     *
     * // SELECT * FROM table WHERE ...
     */
    public function whereNotBetween(
        string $column,
        array $values,
        bool $isText = false,
        ?string $boolean = null
    ): static {
        $this->isReady();

        if (count($values) !== 2) {
            throw new InvalidRangeException('whereNotBetween requires exactly 2 values [lower_bound, upper_bound');
        }

        // Ensure proper ordering of values
        if ($values[0] > $values[1]) {
            [$values[0], $values[1]] = [$values[1], $values[0]]; // Swap values
        }

        // Add placeholders
        $placeholders = [];
        foreach ($values as $index => $value) {
            $placeholder = ":nbtw_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . "_$index";
            $placeholders[] = $placeholder;
            $this->bindings[$placeholder] = $isText ? "'$value'" : $value;
        }

        $cond = [
            'column' => $column,
            'op' => 'NOT BETWEEN',
            'placeholders' => $placeholders,
            'type' => 'not_between',
            'boolean' => $boolean,
            'isText' => $isText
        ];
        $this->setWhereClause($cond);
        return $this;
    }

    /**
     * Add a WHERE IS NULL clause to the query
     *
     * @param string $column The column name
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     */
    /**
     * Add a WHERE IS NULL clause.
     *
     * @param string $column Description of column
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereIsNull(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereIsNull(
        string $column,
        ?string $boolean = null
    ): static {
        $this->isReady();

        $cond = [
            'column' => $column,
            'op' => 'IS NULL',
            'type' => 'is_null',
            'boolean' => $boolean
        ];

        $this->setWhereClause($cond);
        return $this;
    }

    /**
     * Add a WHERE IS NOT NULL clause to the query
     *
     * @param string $column The column name
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     */
    /**
     * Add a WHERE IS NOT NULL clause.
     *
     * @param string $column Description of column
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereNotNull(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereNotNull(
        string $column,
        ?string $boolean = null
    ): static {
        $this->isReady();

        $cond = [
            'column' => $column,
            'op' => 'IS NOT NULL',
            'type' => 'not_null',
            'boolean' => $boolean
        ];

        $this->setWhereClause($cond);
        return $this;
    }

    /**
     * Add a WHERE IN clause to the query
     *
     * @param string $column The column name
     * @param array $values Array of values
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     * @throws InvalidArgumentException
     */
    /**
     * Add a WHERE IN clause.
     *
     * @param string $column Description of column
     * @param array $values Description of values
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereIn(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereIn(
        string $column,
        array $values,
        ?string $boolean = null
    ): static
    {
        $this->isReady();

        $placeholders = [];
        foreach ($values as $key => $value) {
            $placeholder = ":in_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . "_" . $key;
            $placeholders[] = $placeholder;
            $this->bindings[$placeholder] = $value;
        }

        $cond = [
            'column' => $column,
            'op' => 'IN',
            'placeholders' => $placeholders,
            'type' => 'in',
            'boolean' => $boolean
        ];

        $this->setWhereClause($cond);
        return $this;
    }

    /**
     * Add a WHERE LIKE clause to the query
     *
     * @param string $column The column name
     * @param string $pattern The search pattern (e.g., '%keyword%')
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     */
    /**
     * Add a WHERE LIKE clause.
     *
     * @param string $column Description of column
     * @param string $pattern Description of pattern
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereLike(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereLike(string $column, string $pattern, ?string $boolean = null): static
    {
        return $this->where($column, 'LIKE', $pattern, $boolean);
    }

    /**
     * Add a WHERE NOT LIKE clause to the query
     *
     * @param string $column The column name
     * @param string $pattern The search pattern (e.g., '%keyword%')
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     */
    /**
     * Add a WHERE NOT LIKE clause.
     *
     * @param string $column Description of column
     * @param string $pattern Description of pattern
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereNotLike(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereNotLike(string $column, string $pattern, ?string $boolean = null): static
    {
        return $this->where($column, 'NOT LIKE', $pattern, $boolean);
    }

    /**
     * Add a WHERE EXISTS clause to the query
     *
     * @param callable $callback A closure that receives a QueryBuilder instance
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     */
    /**
     * Add a WHERE EXISTS subquery.
     *
     * @param callable $callback Description of callback
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereExists(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereExists(callable $callback, ?string $boolean = null): static
    {
        $this->isReady();

        $subQuery = new self();
        $callback($subQuery);

        $cond = [
            'op' => 'EXISTS',
            'subquery' => $subQuery,
            'type' => 'exists',
            'boolean' => $boolean
        ];

        $this->setWhereClause($cond);
        return $this;
    }

    /**
     * Add a WHERE NOT EXISTS clause to the query
     *
     * @param callable $callback A closure that receives a QueryBuilder instance
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     */
    /**
     * Add a WHERE NOT EXISTS subquery.
     *
     * @param callable $callback Description of callback
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereNotExists(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereNotExists(callable $callback, ?string $boolean = null): static
    {
        $this->isReady();

        $subQuery = new self();
        $callback($subQuery);

        $cond = [
            'op' => 'NOT EXISTS',
            'subquery' => $subQuery,
            'type' => 'not_exists',
            'boolean' => $boolean
        ];

        $this->setWhereClause($cond);
        return $this;
    }

    /**
     * Add a GROUP BY clause to the query
     *
     * @param array|string $columns Columns to group by
     * @return $this
     */
    /**
     * Add a GROUP BY clause to the query.
     *
     * @param array|string $columns Description of columns
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->groupBy(...);

    // SELECT * FROM table WHERE ...
     */
    public function groupBy(array|string $columns): static
    {
        $this->isReady();

        if (is_string($columns)) {
            $columns = [$columns];
        }

        [$id, $type] = $this->checkCurrentOperation();
        $this->{$type . 's'}[$id]['groupBy'] = $columns;

        return $this;
    }

    /**
     * Add a HAVING clause to the query
     *
     * @param string $column The column name
     * @param string $operator Comparison operator
     * @param mixed $value The value to compare
     * @param string|null $boolean Logical connector (AND/OR)
     * @return $this
     */
    /**
     * Add a HAVING clause to the query.
     *
     * @param string $column Description of column
     * @param string $operator Description of operator
     * @param mixed $value Description of value
     * @param ?string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     * @throws InvalidArgumentException If the operator is invalid
     *
     * @example
     * $query->table('table')
    ->having(...);

    // SELECT * FROM table WHERE ...
     */
    public function having(
        string $column,
        string $operator,
        mixed $value,
        ?string $boolean = null
    ): static
    {
        $this->isReady();

        $placeholder = ":having_" . preg_replace('/[^a-zA-Z0-9_]/', '_', $column) . "_" . count($this->bindings);

        $cond = [
            'column' => $column,
            'op' => $operator,
            'placeholder' => $placeholder,
            'type' => 'having',
            'boolean' => $boolean
        ];

        [$id, $type] = $this->checkCurrentOperation();
        $this->{$type . 's'}[$id]['having'][] = $cond;
        $this->bindings[$placeholder] = $value;

        return $this;
    }

    /**
     * Add an ORDER BY clause to the query
     *
     * @param string $column The column name
     * @param string $direction ASC or DESC
     * @return $this
     */
    /**
     * Add an ORDER BY clause.
     *
     * @param string $column Description of column
     * @param string $direction Description of direction
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->orderBy(...);

    // SELECT * FROM table WHERE ...
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->isReady();

        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        [$id, $type] = $this->checkCurrentOperation();
        $this->{$type . 's'}[$id]['orderBy'][] = [
            'column' => $column,
            'direction' => $direction
        ];

        return $this;
    }

    /**
     * Add a LIMIT clause to the query
     *
     * @param int $limit Number of records to return
     * @return $this
     */
    /**
     * Limit the number of results returned.
     *
     * @param int $limit Description of limit
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->limit(...);

    // SELECT * FROM table WHERE ...
     */
    public function limit(int $limit): static
    {
        $this->isReady();

        [$id, $type] = $this->checkCurrentOperation();
        $this->{$type . 's'}[$id]['limit'] = $limit;

        return $this;
    }

    /**
     * Add an OFFSET clause to the query
     *
     * @param int $offset Number of records to skip
     * @return $this
     */
    /**
     * Set the OFFSET for query results.
     *
     * @param int $offset Description of offset
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->offset(...);

    // SELECT * FROM table WHERE ...
     */
    public function offset(int $offset): static
    {
        $this->isReady();

        [$id, $type] = $this->checkCurrentOperation();
        $this->{$type . 's'}[$id]['offset'] = $offset;

        return $this;
    }

    /**
     * Add a nested WHERE condition to the query
     *
     * @param callable $callback A closure that receives a QueryBuilder instance
     * @param string $boolean Logical connector (AND/OR)
     * @return $this
     */
    /**
     * Add a nested WHERE clause.
     *
     * @param callable $callback Description of callback
     * @param string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereNested(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereNested(callable $callback, string $boolean = 'AND'): static
    {
        $this->isReady();

        $nestedQuery = new self();
        $nestedQuery->table(self::$tableName);
        $callback($nestedQuery);

        $cond = [
            'type' => 'nested',
            'query' => $nestedQuery,
            'boolean' => $boolean
        ];

        $this->setWhereClause($cond);
        return $this;
    }

    /**
     * Add a raw WHERE condition to the query
     *
     * @param string $sql The raw SQL condition
     * @param array $bindings Bindings for the SQL
     * @param string $boolean Logical connector (AND/OR)
     * @return $this
     */
    /**
     * Add a raw WHERE SQL clause.
     *
     * @param string $sql Description of sql
     * @param array $bindings Description of bindings
     * @param string $boolean Description of boolean
     * @return $this Returns the QueryBuilder instance for method chaining
     *
     * @example
     * $query->table('table')
    ->whereRaw(...);

    // SELECT * FROM table WHERE ...
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): static
    {
        $this->isReady();

        $cond = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean
        ];

        $this->setWhereClause($cond);
        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }
    /* ---WhereClause--- */
}