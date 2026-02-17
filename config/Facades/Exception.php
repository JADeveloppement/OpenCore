<?php

/**
 * Nom du Fichier : Exception.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades;

use Exception as StdException;

class Exception
{
    public static function exception(string $message)
    {
        http_response_code(500);
        Log::exception($message);
        throw new StdException($message);
    }

    public static function warning(string $message)
    {
        Log::warning($message);
        error_log($message);
    }
}