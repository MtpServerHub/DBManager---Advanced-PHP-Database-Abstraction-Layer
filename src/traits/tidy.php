<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | tidy
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use Exception;
use PDO;
use PDOException;

trait tidy
{
    /**
     * Sorts and reorders a table based on a specified column (default: id).
     *
     * This method:
     *  - Creates a temporary table
     *  - Copies sorted rows
     *  - Truncates the original table
     *  - Re-inserts sorted rows with reset AUTO_INCREMENT
     *
     * Note: It disables and re-enables foreign key checks internally.
     *
     * @param string $dbID
     *     ID of the connected database.
     *
     * @param string $tableName
     *     Name of the table to be tidied.
     *
     * @param string $orderBy
     *     Column name to order by (default: "id").
     *
     * @return bool
     *     True on successful sort and rebuild; false otherwise.
     *
     * @throws PDOException|Exception
     *     If any step in the transaction fails, an error is thrown and transaction is rolled back.
     *
     * @access public
     * @static
     */
    public static function tidy(string $dbID, string $tableName, string $orderBy = 'id'): bool
    {
        if (!self::start('tidy')) {
            return false;
        }

        /* Check connection's id */
        if (!self::is_connection($dbID)) {
            self::monitor("tidy: Database {-$dbID-} not found...");
            return false;
        }

        $db = self::$conn[$dbID];

        try {

            $db->beginTransaction();

            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->exec("CREATE TEMPORARY TABLE temp_tidy_table LIKE $tableName");
            $db->exec("INSERT INTO temp_tidy_table SELECT * FROM $tableName ORDER BY $orderBy");
            $db->exec("TRUNCATE TABLE $tableName");
            $db->exec("ALTER TABLE $tableName AUTO_INCREMENT = 1");

            $stmt = $db->query("SELECT * FROM temp_tidy_table");
            $columns = array_keys($stmt->fetch(PDO::FETCH_ASSOC));

            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $insertQuery = "INSERT INTO $tableName VALUES ($placeholders)";
            $insertStmt = $db->prepare($insertQuery);

            $newId = 1;
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['id'] = $newId++;
                $insertStmt->execute(array_values($row));
            }

            $db->exec("DROP TEMPORARY TABLE temp_tidy_table");
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            $db->commit();

            self::monitor("tidy: Table $tableName tidied successfully");
            return true;
        } catch (PDOException|Exception $e) {
            $db->rollBack();
            self::monitor("tidy: Error tidying table $tableName - " . $e->getMessage());
            return false;
        }
    }
}