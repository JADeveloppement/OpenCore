<?php

/**
 * Nom du Fichier : Strings.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Helpers;

class Strings
{
    public static function generateRandomString(int $length = 60): string
    {
        $bytes = random_bytes(ceil($length * 0.75));
        $randomString = substr(preg_replace('/[^A-Za-z0-9]/', '', base64_encode($bytes)), 0, $length);

        return $randomString;
    }
}