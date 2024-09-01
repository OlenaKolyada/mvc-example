<?php

namespace App\Kernel;

use App\Kernel\Router\Exception\RouteNotFoundException;
use App\Kernel\Router\Route;
use App\Kernel\Router\RouteFactory;

/**
 * Class Router
 *
 * @package App\Kernel
 * @author Jérémy GUERIBA
 */
class Router
{
    private const string DEFAULT_BASE_FILE_PATH = '/index.php';

    private array $routes = [];

    /**
     * @throws Router\Exception\CallbackIsNotCallableException
     */
    public function addRoute(Route $route): void
    {
        //TODO handle unique route
        $this->routes[] = $route;
    }

    /**
     * @throws Router\Exception\CallbackIsNotCallableException
     */
    public function loadFromConfigData(array $definedRoutes): void
    {
        foreach ($definedRoutes as $uri => $routeConfiguration) {
            $this->addRoute(RouteFactory::createRoute(
                $routeConfiguration['method'],
                $uri,
                $routeConfiguration['callable']
            ));
        }
    }

    /**
     * @throws RouteNotFoundException
     */
    public function dispatch(string $method, string $uri, string $scriptNamePath): mixed
    {
        $scriptName = $scriptNamePath;
        $basePath = str_replace(self::DEFAULT_BASE_FILE_PATH, '', $scriptName);
        $requestUri = trim(substr($uri, strlen($basePath)), '/');

        /** @var Route $route */
        foreach ($this->routes as $route) {
            $path = trim($route->getPath(), '/');

            // Replace parameters like :id by regexp
            $path = preg_replace('#:([\w]+)#', '([^/]+)', $path);
            $regexp = "#^" . $path . "$#";

            if ($method === $route->getMethod() && preg_match($regexp, $requestUri, $matches)) {
                array_shift($matches); // Remove first unusable match
                return call_user_func_array($route->getCallable(), $matches);
            }
        }

        throw new RouteNotFoundException('Route not found');
    }
}
