<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | MtpServer
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | qb_buildQuery
 */

namespace DBManager\src\QueryBuilder\traits;

use InvalidArgumentException;
use RuntimeException;

trait qb_buildQuery
{
    /**
     * Build the final SQL query based on the current operation
     *
     * @return string The generated SQL query
     * @throws RuntimeException If no active operation is found
     */
    protected function buildQuery(): string
    {
        [$id, $type] = $this->checkCurrentOperation();
        $method = 'build' . ucfirst($type) . 'Query';

        if (!method_exists($this, $method)) {
            throw new RuntimeException("Unsupported operation type: $type");
        }

        $this->query = ['query' => $this->$method($id), 'bindings' => $this->bindings];
        return $this->query['query'];
    }

    /**
     * Build a SELECT query
     *
     * @param string $id The operation ID
     * @return string The generated SQL query
     */
    protected function buildSelectQuery(string $id): string
    {
        $select = $this->selects[$id];
        $columns = is_array($select['params']) ? implode(', ', $select['params']) : $select['params'];
        $sql = "SELECT $columns  FROM " . self::$tableName;

        if (!empty($select['where'])) {
            $sql .= ' WHERE ' . $this->buildWhereClause($select['where']);
        }

        if (!empty($select['groupBy'])) {
            $sql .= ' GROUP BY ' . implode(', ', $select['groupBy']);
        }

        if (!empty($select['having'])) {
            $sql .= ' HAVING ' . $this->buildWhereClause($select['having']);
        }

        if (!empty($select['orderBy'])) {
            $orderBy = [];
            foreach ($select['orderBy'] as $order) {
                $orderBy[] = "{$order['column']} {$order['direction']}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderBy);
        }

        if (isset($select['limit'])) {
            $sql .= " LIMIT {$select['limit']}";
        }

        if (isset($select['offset'])) {
            $sql .= " OFFSET {$select['offset']}";
        }

        return $sql;
    }

    /**
     * Build an INSERT query
     *
     * @param string $id The operation ID
     * @return string The generated SQL query
     */
    protected function buildInsertQuery(string $id): string
    {
        $insert = $this->inserts[$id];
        $columns = implode(', ', $insert['columns']);
        $placeholders = implode(', ', array_keys($insert['values']));

        return "INSERT INTO ".self::$tableName." ($columns) VALUES ($placeholders)";
    }

    /**
     * Build an UPDATE query
     *
     * @param string $id The operation ID
     * @return string The generated SQL query
     */
    protected function buildUpdateQuery(string $id): string
    {
        $update = $this->updates[$id];
        $sets = [];

        foreach ($update['set'] as $column => $value) {
            $sets[] = "$column = $value";
        }

        $sql = "UPDATE ".self::$tableName." SET " . implode(', ', $sets);

        if (!empty($update['where'])) {
            $sql .= ' WHERE ' . $this->buildWhereClause($update['where']);
        }

        return $sql;
    }

    /**
     * Build a DELETE query
     *
     * @param string $id The operation ID
     * @return string The generated SQL query
     */
    protected function buildDeleteQuery(string $id): string
    {
        $delete = $this->deletes[$id];
        $sql = "DELETE FROM " . self::$tableName;

        if (!empty($delete['where'])) {
            $sql .= ' WHERE ' . $this->buildWhereClause($delete['where']);
        }

        return $sql;
    }

    /**
     * Build a WHERE clause from conditions
     *
     * @param array $conditions Array of conditions
     * @return string The generated WHERE clause
     */
    protected function buildWhereClause(array $conditions): string
    {
        $whereParts = [];

        foreach ($conditions as $condition) {
            $part = $this->buildCondition($condition);
            if (!empty($part)) {
                $whereParts[] = $part;
            }
        }

        return implode(' ', $whereParts);
    }

    /**
     * Build a single condition
     *
     * @param array $condition The condition array
     * @return string The generated condition SQL
     */
    protected function buildCondition(array $condition): string
    {
        $sql = '';
        $boolean = $condition['boolean'] ?? null;

        if ($boolean !== null) {
            $sql .= strtoupper($boolean) . ' qb_buildQuery.php';
        }

        switch ($condition['type']) {
            case 'basic':
            case 'not':
                $sql .= "{$condition['column']} {$condition['op']} {$condition['placeholder']}";
                break;
            case 'in':
            case 'not_in':
                $placeholders = implode(', ', $condition['placeholders']);
                $sql .= "{$condition['column']} {$condition['op']} ($placeholders)";
                break;
            case 'between':
            case 'not_between':
                $placeholders = implode(' AND ', $condition['placeholders']);
                $sql .= "{$condition['column']} {$condition['op']} $placeholders";
                break;
            case 'is_null':
            case 'not_null':
                $sql .= "{$condition['column']} {$condition['op']}";
                break;
            case 'exists':
            case 'not_exists':
                $subQuery = $condition['subquery']->toSql();
                $sql .= "{$condition['op']} ($subQuery)";
                break;
            case 'nested':
                $nestedQuery = $condition['query']->toSql();
                $sql .= "($nestedQuery)";
                break;
            case 'raw':
                $sql .= $condition['sql'];
                break;
            default:
                throw new InvalidArgumentException("Unknown condition type: {$condition['type']}");
        }

        return $sql;
    }

    /**
     * Get the final SQL query
     *
     * @return string|array The generated SQL query
     * @throws RuntimeException If no active operation is found
     */
    public function toSql(bool $assoc = true): string|array
    {
        $this->buildQuery();
        return ($assoc ? $this->query : $this->query['query']);
    }
}