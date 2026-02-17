<?php

/**
 * Nom du Fichier : database.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

use Config\Facades\Log;

try {
    $pdo = new PDO(
        "mysql:host=" . env('DB_HOST', 'jad-minifw-mariadb') . ";dbname=" . env('DB_NAME', "jadeveloppement_db") . ";charset=utf8mb4",
        env('DB_USERNAME', "jadeveloppement_user"),
        env('DB_PASSWORD', "jadeveloppement_password")
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}