<?php

/**
 * Nom du Fichier : Route.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Routes;

use Closure;
use Config\Facades\Container;

class Route
{
    private static Router $router;
    private static Container $container;

    public static function boot(Container $container): void
    {
        if (!isset(self::$router)) {
            self::$container = $container;
            self::$router = new Router(self::$container);
        }
    }

    public static function get(string $path, $callback, $middleware = []): void
    {
        self::$router->get($path, $callback, $middleware);
    }

    public static function post(string $path, $callback, $middleware = []): void
    {
        self::$router->post($path, $callback, $middleware);
    }

    public static function view(string $path, string $view, array $middleware = []): void
    {
        self::$router->view($path, $view, $middleware);
    }

    public static function middlewares(array $middlewares, Closure $callback): void
    {
        self::$router->pushGroup(['middleware' => $middlewares]);
        $callback();
        self::$router->popGroup();
    }

    public static function controllers(string $controller, Closure $callback): void
    {
        self::$router->controller($controller, $callback);
    }

    public static function resolve(): void
    {
        self::$router->resolve();
    }

    public static function getRoutes(): array
    {
        return self::$router->getRoutes();
    }
}

?>