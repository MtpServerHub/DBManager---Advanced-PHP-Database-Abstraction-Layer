<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @class    | DBManager
 * |- @template | tidy room
 */

namespace DBManager\src;

use DBManager\src\enums\MODE;
use DBManager\src\QueryBuilder\QueryBuilder;
use DBManager\src\traits\bindParameter;
use DBManager\src\traits\bindValue;
use DBManager\src\traits\connect;
use DBManager\src\traits\define;
use DBManager\src\traits\escape;
use DBManager\src\traits\execute;
use DBManager\src\traits\fetch;
use DBManager\src\traits\fetchAll;
use DBManager\src\traits\fetchObject;
use DBManager\src\traits\newOperation;
use DBManager\src\traits\operators;
use DBManager\src\traits\options;
use DBManager\src\traits\query;
use DBManager\src\traits\setAttr;
use DBManager\src\traits\tidy;
use DBManager\src\traits\transaction;

class DBManager extends QueryBuilder implements DBInterface,OPInterface
{
    use tidy;
    use fetch;
    use query;
    use escape;
    use define;
    use connect;
    use execute;
    use setAttr;
    use fetchAll;
    use bindValue;
    use operators;
    use options;
    use transaction;
    use fetchObject;
    use newOperation;
    use bindParameter;

    private static bool  $prepared    = false;
    private static bool  $ssl         = true;
    private static array $modes       = [];
    private static array $databases   = [];
    private static array $operations  = [];
    private static array $queries     = [];
    private static array $logs        = [];
    private static array $conn        = [];
    private static array $opIDS       = [];

    /**
     * Initializes the DBManager class if not already prepared.
     *
     * Sets default modes and logs a startup message.
     * Also registers a default query.
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function prepare(): void
    {
        if (!self::$prepared) {
            if (!array_key_exists(MODE::ATTR_LOG_MONITORING, self::$modes)) {
                self::mode(MODE::ATTR_LOG_MONITORING, MODE::RESPONSE_LOG);
            }
            if (!array_key_exists(MODE::ATTR_NAME_ERROR_BINDING, self::$modes)) {
                self::mode(MODE::ATTR_NAME_ERROR_BINDING, MODE::RESPONSE_STOP);
            }
            self::monitor('DBManager started successfully...');
            self::$prepared = true;
            self::query('default', /** @lang text */ "SELECT * FROM table");
        }
    }

    /**
     * Adds a log entry or handles logging based on current mode.
     *
     * - In debug mode, logs are always added.
     * - Depending on the mode, logs can be added to memory, silenced, or written to a file.
     *
     * @param string $msg
     *     The log message.
     *
     * @param bool $debug
     *     If true, logs are added regardless of the mode.
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function monitor(string $msg = '', bool $debug = false): void
    {
        $date = date('Y/m/y H:i');
        /* Debug Mode */
        if ($debug) {
            self::$logs[] = [$msg, $date, 'DEBUG'];
            return;
        }

        /* Add logs based on modes */
        if (array_key_exists(MODE::ATTR_LOG_MONITORING, self::$modes)) {
            switch (self::$modes[MODE::ATTR_LOG_MONITORING]) {
                case MODE::RESPONSE_LOG :
                    self::$logs[] = [$msg, $date];
                    return;
                case MODE::RESPONSE_LOG_SILENT :
                    return;
                case MODE::RESPONSE_LOG_FILE :
                    $mode = MODE::ATTR_LOG_FILE_PATH;
                    if (array_key_exists($mode, self::$modes)) {
                        $logEntry = ['message' => $msg, 'time' => $date];
                        $logFile = self::$modes[$mode];
                        $logData = json_encode($logEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

                        if (!is_dir(dirname($logFile))) {
                            mkdir(dirname($logFile), 0755, true);
                        }
                        file_put_contents($logFile, $logData, FILE_APPEND | LOCK_EX);
                    } else {
                        self::monitor('{RESPONSE_LOG_FILE} cannot works without {ATTR_LOG_FILE_PATH}...', true);
                    }
                    return;
                default:
                    self::monitor("{".self::$modes[MODE::ATTR_LOG_MONITORING]."} Logging mode is invalid...", true);
                    return;
            }
        }
    }

    /**
     * Enables or disables SSL enforcement globally.
     *
     * @param bool $force
     *     True to enforce SSL; false to disable enforcement.
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function sslForce(bool $force): void
    {
        self::$ssl = $force;
    }

    /**
     * Returns all logged messages as a JSON string.
     *
     * @return false|string
     *     A JSON-formatted string of logs, or false on failure.
     *
     * @access public
     * @static
     */
    public static function print(): false|string
    {
        return stripcslashes(json_encode(self::$logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Sets a DBManager mode (behavior customization).
     *
     * @param int $mode
     *     The mode constant (e.g., MODE::ATTR_LOG_MONITORING).
     *
     * @param mixed $response
     *     The value associated with the mode (e.g., MODE::RESPONSE_LOG).
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function mode(int $mode, mixed $response): void
    {
        self::$modes[$mode] = $response;
    }

    /**
     * Starts the DBManager if it is prepared and SSL (if enabled) is safe.
     *
     * Logs any issues (not prepared or unsafe SSL).
     *
     * @param string $name
     *     A name or label for identifying the calling context in logs.
     *
     * @return bool
     *     True if started successfully, false otherwise.
     *
     * @access public
     * @static
     */
    public static function start(string $name): bool
    {
        /* Is class prepared */
        if (!self::$prepared) {
            self::monitor("$name: Class {-DBManager-} not prepared...");
            return false;
        }

        /* Checking ssl */
        if (self::$ssl && !self::is_safety()) {
            self::monitor("$name: SSL not enabled(HTTPS)...");
            return false;
        }

        return true;
    }
}