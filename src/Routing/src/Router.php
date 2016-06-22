<?php
/*!
 * Avalon
 * Copyright 2011-2015 Jack P.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Avalon\Routing;

use Exception;
use Avalon\Http\Request;

/**
 * HTTP Router.
 *
 * @package Avalon\Routing
 * @author Jack P.
 * @since 0.1.0
 */
class Router
{
    /**
     * Routes.
     *
     * @var array
     */
    protected static $routes = [];

    /**
     * Route tokens.
     *
     * @var array
     */
    public static $tokens = [
        'id'   => "(?<id>\d+)",
        'slug' => "(?<slug>[^/]*?)"
    ];

    /**
     * Route extensions.
     *
     * @var array
     */
    public static $extensions = ['json', 'atom'];

    /**
     * Add route token.
     *
     * @param string $name
     * @param mixed  $regex
     */
    public static function addToken($name, $regex)
    {
        static::$tokens[$name] = $regex;
    }

    /**
     * Add file extension.
     *
     * @param string $extension
     */
    public static function addExtension($extension)
    {
        static::$extensions[] = $extension;
    }

    /**
     * Set root controller
     *
     * @param string $controller
     *
     * @return Route
     */
    public static function root($controller)
    {
        return static::get('root', '/', $controller);
    }

    /**
     * Set 404 route.
     *
     * @param string $controller
     */
    public static function set404($controller)
    {
        return static::$routes['404'] = new Route('404', '404', $controller);
    }

    /**
     * Route `GET` request.
     *
     * @param  string $name
     * @param  string $path
     * @param  string $controller
     * @param  array  $defaults
     *
     * @return Route
     */
    public static function get($name, $path, $controller, array $defaults = [])
    {
        return static::$routes[$name] = new Route($name, $path, $controller, $defaults);
    }

    /**
     * Route `POST` request.
     *
     * @see Route::get()
     *
     * @return Route
     */
    public static function post($name, $path, $controller, array $defaults = [])
    {
        static::$routes[$name] = new Route($name, $path, $controller, $defaults);
        static::$routes[$name]->method('POST');
        return static::$routes[$name];
    }

    /**
     * Route `DELETE` request.
     *
     * @see Route::get()
     *
     * @return Route
     */
    public static function delete($name, $path, $controller, array $defaults = [])
    {
        static::$routes[$name] = new Route($name, $path, $controller, $defaults);
        static::$routes[$name]->method('DELETE');
        return static::$routes[$name];
    }

    /**
     * Route `PUT` request.
     *
     * @see Route::get()
     *
     * @return Route
     */
    public static function put($name, $path, $controller, array $defaults = [])
    {
        static::$routes[$name] = new Route($name, $path, $controller, $defaults);
        static::$routes[$name]->method('PUT');
        return static::$routes[$name];
    }

    /**
     * Route `PATCH` request.
     *
     * @see Route::get()
     *
     * @return Route
     */
    public static function patch($name, $path, $controller, array $defaults = [])
    {
        static::$routes[$name] = new Route($name, $path, $controller, $defaults);
        static::$routes[$name]->method('PATCH');
        return static::$routes[$name];
    }

    /**
     * Return the route that matches the name.
     *
     * @param  string $name
     *
     * @return Route
     */
    public static function getRoute($name)
    {
        if (!isset(static::$routes[$name])) {
            return false;
        }

        return static::$routes[$name];
    }

    /**
     * Generate the routes path and replace and placeholders wiht the value.
     *
     * @param string $routeName
     * @param array  $tokens
     *
     * @return string
     */
    public static function generatePath($routeName, array $tokens = [])
    {
        $route = static::getRoute($routeName);

        if (!$route) {
            throw new Exception("No route with name [{$routeName}]");
        }

        $tokens = $tokens + Request::$properties->getProperties();

        $path = $route->path;

        foreach ($tokens as $name => $value) {
            $path = str_replace("{{$name}}", $value, $path);
        }

        return $path;
    }

    /**
     * Generate the routes URL and replace and placeholders wiht the value.
     *
     * @param string $routeName
     * @param array  $tokens
     *
     * @return string
     */
    public static function generateUrl($routeName, array $tokens = [])
    {
        return Request::$basePath . static::generatePath($routeName, $tokens);
    }

    /**
     * Process the request.
     *
     * @return Route
     */
    public static function process()
    {
        $requestPath = Request::pathInfo();

        if (Request::pathInfo() === '/') {
            return static::getRoute('root');
        }

        foreach (static::$routes as $route) {
            $pattern = static::regex($route->compiledPath());

            if (!in_array(Request::$method, array_map('strtoupper', $route->methods))) {
                continue;
            }

            // Match exact path and request method
            if ($route->path == $requestPath) {
                $route->params = $route->defaults;
                Request::$properties->set($route->params);
                return $route;
            }
            // Regex match
            elseif (preg_match($pattern, $requestPath, $params)) {
                unset($params[0]);

                // Merge params
                $route->params = $params + $route->defaults;
                Request::$properties->set($route->params);

                return $route;
            }
        }
    }

    /**
     * Wrap the passed regex pattern and add the file extension pattern.
     *
     * @param  string $pattern
     *
     * @return string
     */
    protected static function regex($pattern)
    {
        $extensions = static::extensionsRegex();
        return "%^{$pattern}{$extensions}$%";
    }

    /**
     * Returns compiled extensions regex group.
     *
     * @return string
     */
    protected static function extensionsRegex()
    {
        $extensions = [];

        foreach (static::$extensions as $extension) {
            $extensions[] = preg_quote($extension, '%');
        }

        return "(\.(?P<extension>" . implode("|", $extensions) . '))?';
    }
}
