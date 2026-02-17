<?php

/**
 * Nom du Fichier : Application.php
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
use Config\Facades\Routes\Route;

class Application
{
    protected static Container $container;

    public static function bootApplication()
    {
        try {
            if (!isset(self::$container)) {
                self::$container = new Container();
            }

            $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
            foreach ($appConfig['providers'] as $providerClass) {
                $provider = new $providerClass();
                $provider->register(self::$container);
            }

            Route::boot(self::$container);

            require_once dirname(__DIR__, 2) . '/routes/web.php';

            Route::resolve();

        } catch (\Throwable $e) {
            $handler = new \Config\Facades\Errors\ExceptionHandler($e);
            $handler->render();
        }
    }
}