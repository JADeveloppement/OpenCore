<?php

/**
 * Nom du Fichier : Router.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Routes;

use ReflectionClass;
use ReflectionNamedType;
use Closure;

use Config\Facades\Container;
use Config\Facades\Exception;
use Config\Facades\Http\Request;
use Config\Facades\Http\Response;

class Router
{
    protected array $routes = [];
    protected array $groupStack = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function mergeGroupAttributes(string $path, array $middleware): array
    {
        $group = end($this->groupStack);

        $mergedMiddleware = $middleware;
        $groupController = null;

        if ($group) {
            $groupController = $group['controller'] ?? null;

            $groupMiddleware = $group['middleware'] ?? [];
            $mergedMiddleware = array_merge($groupMiddleware, $middleware);
        }

        $mergedPath = $this->normalizeUri($path);

        return [
            'path' => $mergedPath,
            'middleware' => array_unique($mergedMiddleware),
            'controller' => $groupController
        ];
    }

    public function pushGroup(array $attributes): void
    {
        if (!empty($this->groupStack)) {
            $parent = end($this->groupStack);

            $parentMiddleware = $parent['middleware'] ?? [];
            $currentMiddleware = $attributes['middleware'] ?? [];

            $attributes['middleware'] = array_unique(array_merge($parentMiddleware, $currentMiddleware));

            $parentController = $parent['controller'] ?? null;
            $currentController = $attributes['controller'] ?? null;

            if ($parentController && is_null($currentController)) {
                $attributes['controller'] = $parentController;
            }
        }

        $this->groupStack[] = $attributes;
    }

    public function popGroup(): void
    {
        array_pop($this->groupStack);
    }

    public function controller(string $controllerClass, Closure $callback)
    {
        $this->pushGroup(['controller' => $controllerClass]);

        $callback($this);

        $this->popGroup();
    }

    public function get(string $path, $callback, array $middleware = [])
    {
        $merged = $this->mergeGroupAttributes($path, $middleware);

        $finalCallback = $callback;
        if (isset($merged['controller']) && is_string($callback)) {
            $finalCallback = [$merged['controller'], $callback];
        }

        $this->routes['GET'][$merged['path']] = ['callback' => $finalCallback, 'type' => 'controller', 'middleware' => $merged['middleware']];
    }

    public function post(string $path, $callback, array $middleware = [])
    {
        $merged = $this->mergeGroupAttributes($path, $middleware);

        $finalCallback = $callback;
        if (isset($merged['controller']) && is_string($callback)) {
            $finalCallback = [$merged['controller'], $callback];
        }

        $this->routes['POST'][$merged['path']] = ['callback' => $finalCallback, 'type' => 'controller', 'middleware' => $merged['middleware']];
    }

    public function view(string $path, string $view, array $middleware = [])
    {
        $merged = $this->mergeGroupAttributes($path, $middleware);
        $this->routes['GET'][$merged['path']] = ['callback' => $view, 'type' => 'view', 'middleware' => $merged['middleware']];
    }

    protected function render($viewName, $datas = [])
    {
        $viewPath = dirname(__DIR__, 3) . '/resources/Views/' . $viewName . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("La vue [{$viewName}] est introuvable dans : {$viewPath}");
        }

        extract($datas);

        ob_start();

        include $viewPath;

        return ob_get_clean();
    }

    protected function createController(string $controllerClass)
    {
        $reflector = new ReflectionClass($controllerClass);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Le contrôleur [{$controllerClass}] ne peut pas être instancié.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $controllerClass();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencyClass = $type->getName();

                try {
                    $arguments[] = $this->container->get($dependencyClass);
                } catch (\Exception $e) {
                    throw new \Exception("Impossible de résoudre la dépendance [{$dependencyClass}] pour le contrôleur [{$controllerClass}]. Vérifiez votre Container.");
                }
            } else {
                throw new \Exception("Le paramètre [{$parameter->getName()}] dans le constructeur de [{$controllerClass}] n'est pas typé ou n'est pas une classe. Injection impossible.");
            }
        }

        return $reflector->newInstanceArgs($arguments);
    }

    public function resolve()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = $this->normalizeUri($path);
        $method = $_SERVER['REQUEST_METHOD'];

        $matchedRoute = null;
        $routeParams = [];

        foreach ($this->routes[$method] ?? [] as $routePath => $data) {
            $pattern = "#^" . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath) . "$#";

            if (preg_match($pattern, $path, $matches)) {
                $matchedRoute = $data;
                $routeParams = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                break;
            }
        }

        if (!$matchedRoute) {
            throw new \Config\Facades\Errors\HttpException("La route [{$method}] {$path} n'existe pas.", 404);
        }

        $callback = $matchedRoute['callback'];
        $routeType = $matchedRoute['type'];
        $middlewares = $matchedRoute['middleware'] ?? [];
        $request = new Request();
        $request->setRouteParams($routeParams);

        $pipelineFinalStep = function ($request) use ($callback, $routeType) {
            if ($routeType === 'view') {
                return $this->render($callback, []);
            }

            if ($callback instanceof Closure) {
                return $callback->call($this, $request);
            }

            if (is_array($callback) && count($callback) === 2) {
                [$controllerClass, $methodName] = $callback;

                if (!class_exists($controllerClass)) {
                    throw new \Exception("Le contrôleur '{$controllerClass}' est introuvable.");
                }

                $controller = $this->createController($controllerClass);

                if (!method_exists($controller, $methodName)) {
                    throw new \Exception("La méthode '{$methodName}' n'existe pas dans le contrôleur '{$controllerClass}'.");
                }

                return call_user_func_array([$controller, $methodName], [$request]);
            }

            throw new \Exception("Le format du callback de la route est invalide.");
        };

        $pipeline = array_reduce(
            array_reverse($middlewares),
            function ($next, $middleware) {
                return function ($request) use ($middleware, $next) {
                    if (!class_exists($middleware)) {
                        throw new \Exception("Le middleware [{$middleware}] n'existe pas.");
                    }
                    $instance = new $middleware();
                    return $instance->handle($request, $next);
                };
            },
            $pipelineFinalStep
        );

        $response = $pipeline($request);
        echo $response;
    }

    private function normalizeUri(string $path): string
    {
        $path = strtok($path, '?');

        $path = str_replace(['index.php', '.php'], '', $path);

        if ($path === '//') {
            $path = '/';
        }
        if ($path == 'index.php' || $path == '')
            $path = '/';

        if ($path != '/') {
            $path = '/' . trim($path, '/');
        }

        return $path;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}