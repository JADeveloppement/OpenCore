<?php

/**
 * Nom du Fichier : DatabaseServiceProvider.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Providers;

use Config\Framework\ServiceProvider;
use Config\Facades\Container;
use Config\Facades\Database\DB;
use PDO;

class DatabaseServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->set(PDO::class, function () {
            $config = require dirname(__DIR__, 3) . '/config/database.php';

            return $pdo;
        });

        DB::boot($container->get(PDO::class));
    }
}