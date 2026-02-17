<?php

/**
 * Nom du Fichier : ServiceProvider.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Framework;

use Config\Facades\Container;

interface ServiceProvider
{
    public function register(Container $container): void;
}