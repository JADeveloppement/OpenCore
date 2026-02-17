<?php

/**
 * Nom du Fichier : Response.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Http;

use Config\Facades\Errors\HttpException;

class Response
{
    public static function notFound(string $message = "Oups, page not found !"): void
    {
        throw new HttpException($message, 404);
    }

    public static function internalError(string $message = "Internal Server Error"): void
    {
        throw new \Exception($message);
    }

    public static function json(array $data, int $code = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    public static function redirect(string $url, int $code = 302): void
    {
        $url = '/' . ltrim($url, '/');

        http_response_code($code);
        header("Location: $url");
        exit;
    }
}
