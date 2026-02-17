<?php

/**
 * Nom du Fichier : Log.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades;

use Exception;

class Log
{
    private static ?string $logfile = null;
    private static ?string $logDir = null;

    public static function initialize()
    {
        if (self::$logDir === null)
            self::$logDir = dirname(__DIR__, 2) . "/html/logs";

        if (self::$logfile === null)
            self::$logfile = self::$logDir . "/jad_mini_framework.log";

        if (!is_dir(self::$logDir)) {
            if (!mkdir(self::$logDir, 0777, true)) {
                throw new \Exception("\n" . __CLASS__ . " > " . __FUNCTION__ . " > Fatal Exception : Failed to create log directory: " . self::$logDir);
            }
        }

        if (!file_exists(self::$logfile)) {
            if (!touch(self::$logfile)) {
                throw new \Exception("\n" . __CLASS__ . " > " . __FUNCTION__ . " > Fatal Exception : Failed to create log file: " . self::$logfile);
            }
        }
    }

    private static function writelog(string $type, string $message)
    {
        self::initialize();

        $timestamp = date("Y-m-d H:i:s");
        $requestUrl = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN_CLI_OR_MISSING_URI';
        $logMessage = "$timestamp---$type---$requestUrl---$message<br>\n";

        file_put_contents(self::$logfile, $logMessage, FILE_APPEND);
    }

    /**
     * Clears the entire content of the log file.
     * * It first initializes the class (to ensure $logfile is set) and then 
     * overwrites the file with an empty string, effectively deleting all logs.
     * * @return bool True on success, false on failure.
     */
    public static function clearLog(): bool
    {
        self::initialize();

        $success = @file_put_contents(self::$logfile, '');

        return $success !== false;
    }

    public static function warning(string $message)
    {
        self::writelog('WARNING', $message);
    }

    public static function exception(string $message)
    {
        self::writelog('EXCEPTION', $message);
        throw new Exception($message);
    }

    public static function info(string $message)
    {
        self::writelog('INFO', $message);
    }

    public static function displayLogs()
    {
        self::initialize();
        return file_get_contents(self::$logfile);
    }
}