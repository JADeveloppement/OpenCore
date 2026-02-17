<?php

/**
 * Nom du Fichier : Command.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Console;

class Command
{
    public static function make(string $type, string $name, ?string $customTable = null)
    {
        $type = strtolower($type);
        $stubPath = __DIR__ . "/stubs/{$type}.stub";

        if (!file_exists($stubPath)) {
            die("Template non trouvé pour : $type\n");
        }

        $folder = match ($type) {
            'controller' => 'app/Controllers',
            'model' => 'app/Models',
            'middleware' => 'app/Middleware',
            default => 'app'
        };

        $fullPath = getcwd() . "/$folder/$name.php";

        if (file_exists($fullPath)) {
            die("Le fichier $name.php existe déjà dans $folder.\n");
        }

        $content = file_get_contents($stubPath);

        $content = str_replace('{{ClassName}}', $name, $content);

        $tableName = $customTable ?? (strtolower($name) . 's');
        $content = str_replace('{{TableName}}', $tableName, $content);

        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }

        file_put_contents($fullPath, $content);
        echo "[CoreEngine] $type créé avec succès : $folder/$name.php (Table: $tableName)\n";
    }
}