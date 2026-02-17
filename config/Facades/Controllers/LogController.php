<?php

/**
 * Nom du Fichier : LogController.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Controllers;

use Config\Facades\Log;

class LogController extends Controllers
{

    public function index()
    {
        return $this->render('Debug/logs');
    }

    public function clearLog()
    {
        return json_encode([
            "r" => Log::clearLog(),
            "datas" => "Effacé avec succès"
        ]);

    }
}