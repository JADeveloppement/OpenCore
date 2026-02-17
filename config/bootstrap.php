<?php

/**
 * Nom du Fichier : bootstrap.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

use Config\Facades\Errors\ExceptionHandler;

$global_handler = function (\Throwable $e) {
    $handler = new ExceptionHandler($e);
    $handler->render();
};

set_exception_handler($global_handler);

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): void
    {
        echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }
}