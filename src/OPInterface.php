<?php
/*
 * |- @software  | PHP 8.2
 * |- @package   | DBManager
 * |- @author    | 3PHRCoder
 * |- @version   | V1.0.0
 * |- @source    | MtpServer.ir
 * |- @interface | OPInterface
 * |- @template  | tidy room
 */

namespace DBManager\src;

interface OPInterface
{
    public static function is_database(string $id): bool;
    public static function is_connection(string $id): bool;
    public static function is_operator(int $id): bool;
    public static function is_query(string $id): bool;
    public static function is_safety(): bool;
    public static function is_spell_true(string $query, string $name): bool;
}