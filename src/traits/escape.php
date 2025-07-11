<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | escape
 * |- @template | tidy room
 */

namespace DBManager\src\traits;

use DBManager\src\DBManager;
use DBManager\src\enums\MODE;

trait escape
{
    private static mixed $sqlKeywordsCache = null;

    /**
     * Escapes an input string by removing dangerous SQL keywords.
     *
     * This method loads a cache of SQL keywords (e.g., SELECT, DROP) and removes them
     * from the input string if present. Useful for sanitizing raw user input.
     *
     * ### Example:
     *
    php
     * $clean = DBManager::escape("DROP TABLE users;");
     * // Output: "TABLE users;"
     *
     *
     * @param string $value
     *     Raw input string to be sanitized.
     *
     * @return string
     *     The escaped string. If no dangerous keywords are found, returns original input.
     *
     * @access public
     * @static
     */
    public static function escape(string $value): string
    {
        if (self::$sqlKeywordsCache === null) {
            $mode = MODE::ATTR_ESCAPE_CACHE_FILE;
            if (array_key_exists($mode, self::$modes)) {
                $gzipPath = self::$modes[$mode] . 'SQLKeywords.gz';
            } else {
                self::monitor("escapeMethod: {ATTR_ESCAPE_CACHE_FILE} not found...");
                return false;
            }

            // Load and cache keywords with Gzip decompression
            self::$sqlKeywordsCache = unserialize(gzdecode(file_get_contents($gzipPath)));
        }

        // Fast check for SQL keywords using strpos (micro-optimization)
        foreach (self::$sqlKeywordsCache['keywords'] as $keyword) {
            if (stripos($value, $keyword) !== false) {
                return str_ireplace(self::$sqlKeywordsCache['patterns'], '', $value);
            }
        }

        return $value;
    }

    /**
     * Builds and stores a gzip-compressed cache of SQL keywords and patterns for escaping input.
     *
     * This method loads a list of SQL keywords from SQLList.php, generates
     * multiple variants of those keywords (e.g., with spaces, parentheses),
     * and then stores them in a gzip-encoded cache file for fast lookup and sanitization.
     *
     * This cache is later used by the escape() method to remove or neutralize SQL injection attempts.
     *
     * ### Example:
     *
    php
     * DBManager::buildEscapeCache(); // Generates SQLKeywords.gz
     *
     *
     * @return bool
     *     Returns true if cache is successfully created and stored; otherwise false.
     *
     * @access public
     * @static
     *
     * @see DBManager::escape()
     */
    public static function buildEscapeCache(): bool
    {
        $keywords = include dirname(__FILE__, 3) . '/cache/SQLList.php';

        $patterns = [];
        foreach ($keywords as $word) {
            $patterns[] = $word;
            $patterns[] = $word . ' ';
            $patterns[] = ' ' . $word;
            $patterns[] = '(' . $word;
        }

        $data = [
            'keywords' => $keywords, // For fast detection
            'patterns' => $patterns  // For actual replacement
        ];

        $mode = MODE::ATTR_ESCAPE_CACHE_FILE;
        if (array_key_exists($mode, self::$modes)) {
            $gzipPath = self::$modes[$mode] . 'SQLKeywords.gz';
            file_put_contents($gzipPath, gzencode(serialize($data), 9));
            self::monitor("buildEscapeCache: Cache built...");
            return true;
        } else {
            self::monitor("buildEscapeCache: {ATTR_ESCAPE_CACHE_FILE} not found...");
            return false;
        }
    }
}