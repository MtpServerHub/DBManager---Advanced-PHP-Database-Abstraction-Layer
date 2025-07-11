<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | transaction
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDOException;

trait transaction
{
    /**
     * Runs a database transaction using a callback.
     *
     * Starts a transaction, executes the callback, and commits or rolls back based on the result.
     *
     * @param callable $callback
     *     A function that runs inside the transaction. Return true to commit, false to rollback.
     *
     * @param string $dbID
     *     Identifier of the database connection.
     *
     * @return bool
     *     True if committed successfully, false if rolled back or failed.
     *
     * @throws PDOException|Exception
     *     If an error occurs during execution.
     *
     * @access public
     * @static
     */
    public static function transaction(callable $callback, string $dbID): bool
    {
        if (!self::start('transaction')) {
            return false;
        }

        try {
            self::beingTransaction($dbID);
            $result = $callback();

            if ($result) {
                self::commit($dbID);
                return true;
            } else {
                self::rollBack($dbID);
                return false;
            }
        } catch (Exception $e) {
            if (self::inTransaction($dbID)) {
                self::rollBack($dbID);
            }
            self::monitor("Transaction failed: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Starts a PDO transaction on the given database connection.
     *
     * Optionally sets the isolation level before starting the transaction.
     *
     * @param string $dbID
     *     Database identifier for the connection.
     *
     * @param int|null $isolationLevel
     *     Optional isolation level (e.g., "READ COMMITTED", "SERIALIZABLE").
     *
     * @param bool $developmentMode
     *     If true, throws exceptions instead of logging them.
     *
     * @return bool
     *     True if transaction started successfully, false otherwise.
     *
     * @throws PDOException|Exception
     *     If an error occurs or isolation level is invalid.
     *
     * @access public
     * @static
     */
    public static function beingTransaction(string $dbID, ?int $isolationLevel = null, bool $developmentMode = false): bool
    {
        if (!self::start('beingTransaction')) {
            return false;
        }

        /* Check id */
        if (!self::is_connection($dbID)) {
            self::monitor("beingTransaction: Database {-$dbID-} not found...");
            return false;
        }
        $db = self::$conn[$dbID];

        /* Check isolation level */
        if ($isolationLevel !== null) {
            $isolationQuery = match(strtoupper($isolationLevel)) {
                'READ UNCOMMITTED' => "SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED",
                'READ COMMITTED'   => "SET TRANSACTION ISOLATION LEVEL READ COMMITTED",
                'REPEATABLE READ'  => "SET TRANSACTION ISOLATION LEVEL REPEATABLE READ",
                'SERIALIZABLE'     => "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE",
                default => (function () {
                    self::monitor("transaction: Invalid isolate level...");
                    return false;
                })()
            };

            try {
                $db->exec($isolationQuery);
            } catch (PDOException|Exception $e) {
                if (!$developmentMode) {
                    self::monitor("Failed to set isolation level: " . $e->getMessage());
                    return false;
                }
                throw new Exception('Failed to set isolation level');
            }
        }

        try {
            /* Check that the database is not in a transaction */
            if ($db->inTransaction()) {
                self::monitor("beingTransaction: Already in transaction for {-$dbID-}...");
                return false;
            }

            /* Check transaction status */
            $result = $db->beginTransaction(); // Start transaction
            if ($result) {
                self::monitor("Transaction started successfully for {-$dbID-}...");
                self::$conn[$dbID] = $db; // Save changes
                return true;
            }

            self::monitor("Starting transaction failed {-$dbID-} ...");
            return false;
        } catch (PDOException|Exception $e) {
            if (!$developmentMode) {
                self::monitor("PDO error starting transaction: {$e->getMessage()}...");
                return false;
            }
            throw new Exception('PDO error starting transaction');
        }
    }

    /**
     * Commits an active transaction on the given database connection.
     *
     * @param string $dbID
     *     Database identifier for the connection.
     *
     * @return bool
     *     True if commit succeeded, false otherwise.
     *
     * @throws PDOException|Exception
     *     If an error occurs during the commit.
     *
     * @access public
     * @static
     */
    public static function commit(string $dbID): bool
    {
        if (!self::start('commit')) {
            return false;
        }

        /* Check id */
        if (!self::is_connection($dbID)) {
            self::monitor("commit: Database {-$dbID-} not found...");
            return false;
        }
        $db = self::$conn[$dbID];

        try {
            /* Check that the database is in a transaction */
            if (!$db->inTransaction()) {
                self::monitor("commit: transaction not found for {-$dbID-}...");
                return false;
            }

            /* Check transaction status */
            $result = $db->commit(); // Close transaction
            if ($result) {
                self::monitor("Transaction Commited successfully for {-$dbID-}...");
                self::$conn[$dbID] = $db; // Save changes
                return true;
            }

            self::monitor("Commiting transaction failed {-$dbID-} ...");
            return false;
        } catch (PDOException|Exception $e) {
            self::monitor("PDO error commiting transaction: {$e->getMessage()}...");
            return false;
        }
    }

    /**
     * Rolls back an active transaction on the given database connection.
     *
     * @param string $dbID
     *     Database identifier for the connection.
     *
     * @return bool
     *     True if rollback succeeded, false otherwise.
     *
     * @throws PDOException|Exception
     *     If an error occurs during the rollback.
     *
     * @access public
     * @static
     */
    public static function rollBack(string $dbID): bool
    {
        if (!self::start('rollBack')) {
            return false;
        }

        /* Check id */
        if (!self::is_connection($dbID)) {
            self::monitor("rollBack: Database {-$dbID-} not found...");
            return false;
        }
        $db = self::$conn[$dbID];

        try {
            /* Check that the database is in a transaction */
            if (!($db->inTransaction())) {
                self::monitor("rollBack: transaction not found for {-$dbID-}...");
                return false;
            }

            /* Check transaction status */
            $result = $db->rollBack(); // Rollback transaction
            if ($result) {
                self::monitor("Transaction rollBacked successfully for {-$dbID-}...");
                self::$conn[$dbID] = $db; // Save changes
                return true;
            }

            self::monitor("rollBacking transaction failed {-$dbID-} ...");
            return false;
        } catch (PDOException|Exception $e) {
            self::monitor("PDO error while rollBacking transaction: {$e->getMessage()}...");
            return false;
        }
    }

    /**
     * Checks whether a transaction is currently active on a given connection.
     *
     * @param string $dbID
     *     Database identifier for the connection.
     *
     * @return bool
     *     True if in transaction, false otherwise.
     *
     * @access public
     * @static
     */
    public static function inTransaction(string $dbID): bool
    {
        if (!self::start('rollBack')) {
            return false;
        }

        /* Check id */
        if (!self::is_connection($dbID)) {
            self::monitor("rollBack: Database {-$dbID-} not found...");
            return false;
        }
        $db = self::$conn[$dbID];

        return $db->inTransaction();
    }
}