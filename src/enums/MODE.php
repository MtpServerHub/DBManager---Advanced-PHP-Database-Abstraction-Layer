<?php
/*
 * |- @software | PHP 8.2
 * |- @package  | DBManager
 * |- @author   | 3PHRCoder
 * |- @version  | V1.0.0
 * |- @source   | MtpServer.ir
 * |- @method   | MODE
 * |- @template | tidy room
 */

namespace DBManager\src\enums;

enum MODE: int
{
    /* Modes */
    const ATTR_NAME_ERROR_BINDING = 100;
    const ATTR_LOG_MONITORING = 101;
    const ATTR_LOG_FILE_PATH = 103;
    const ATTR_ESCAPE_CACHE_FILE = 104;

    /* Responses 100 */
    const RESPONSE_STOP = 200;
    const RESPONSE_CONTINUE = 201;
    const RESPONSE_THROW = 202;

    /* Responses 101 */
    const RESPONSE_LOG = 203;
    const RESPONSE_LOG_SILENT = 204;
    const RESPONSE_LOG_FILE = 205;
}