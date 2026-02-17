<?php

/**
 * Nom du Fichier : env_loader.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

/**
 * Loads environment variables from the specified .env file path.
 * This version uses regex for robust parsing of values, including spaces and quotes.
 * * @param string $filePath The path to the .env file.
 * @throws \Exception if the .env file is not found.
 */
function load_env(string $filePath): void
{
    if (!file_exists($filePath)) {
        throw new \Exception(".env file not found at: {$filePath}");
    }

    $lines = file($filePath, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if (str_starts_with($line, '#') || empty($line)) {
            continue;
        }

        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                $value = $matches[1];
            }

            if (!empty($key)) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;

    if (is_string($value)) {
        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
        }
    }

    return $value;
}

try {
    load_env(dirname(__DIR__) . '/.env');
} catch (\Exception $e) {
    die("Critique : " . $e->getMessage());
}

$isDebug = env('APP_DEBUG', false);

if ($isDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity))
            return;
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
}

if (env('APP_MAINTENANCE', false)) {
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $maintenancePath = '/maintenance';

    if ($currentPath !== $maintenancePath) {
        http_response_code(503);
        header('Retry-After: 3600');
        header("Location: {$maintenancePath}");
        exit;
    }
}