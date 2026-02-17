<?php

/**
 * Nom du Fichier : app.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

return [
    'providers' => [
        \Config\Facades\Providers\DatabaseServiceProvider::class,
        \Config\Facades\Providers\AuthServiceProvider::class,
    ]
];