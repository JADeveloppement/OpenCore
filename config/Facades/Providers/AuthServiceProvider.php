<?php

/**
 * Nom du Fichier : AuthServiceProvider.php
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
use Config\Facades\Auth;

class AuthServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        Auth::boot($container);

        // $container->set(Auth::class, function() {
        //     return new Auth(); 
        // });
    }
}