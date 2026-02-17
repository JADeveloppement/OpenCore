<?php

/**
 * Nom du Fichier : Middleware.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Middlewares;

use Config\Facades\Http\Request;

interface Middleware
{
    public function handle(Request $request, callable $next);
}