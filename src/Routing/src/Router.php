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
use Avalon\Util\Inflector;

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
     * Regex delimiter to use.
     *
     * @var string
     */
    protected static $regexDelimiter = '%';

    /**
     * Current route.
     *
     * @var Route
     */
    protected static $currentRoute;

    /**
     * Registered routes.
     *
     * @var array
     */
    protected static $routes = [];

    /**
     * Route tokens.
     *
     * @var array
     */
    protected static $tokens = [
        'id'   => "(?<id>\d+)",
        'slug' => "(?<slug>[^/]+)"
    ];

    /**
     * Route extensions.
     *
     * @var array
     */
    public static $extensions = ['.json', '.atom'];

    /**
     * Routed controller class.
     *
     * @var string
     */
    public static $controller;

    /**
     * Routed controller method/action.
     *
     * @var string
     */
    public static $action;

    /**
     * Route parameters.
     *
     * @var array
     */
    public static $params;

    /**
     * Route defaults.
     *
     * @var array
     */
    public static $defaults;

    /**
     * Route extension.
     *
     * @var string
     */
    public static $extension;

    /**
     * Return the routes array.
     */
    public static function getRoutes()
    {
        return static::$routes;
    }

    /**
     * Closure style routing.
     *
     * @example
     *     Router::map(funtion($r)) {
     *         $r->root('Controller.action');
     *     });
     */
    public static function map($block)
    {
        $block(new static);
    }

    /**
     * Returns compiled path for the route.
     *
     * @param string $name   Route name.
     * @param array  $tokens Token values for route.
     *
     * @return string
     *
     * @throws Exception
     */
    public static function generateUrl($name, $tokens = [])
    {
        if (isset(static::$routes[$name])) {
            $route = static::$routes[$name];
        } else {
            foreach (static::$routes as $r) {
                if ($r->name === $name) {
                    $route = $r;
                }
            }
        }

        if (isset($route)) {
            return $route->generateUrl($tokens);
        } else {
            throw new Exception("No route with name [{$name}]");
        }
    }

    /**
     * Sets the root route.
     *
     * @param string $to Controller to route the root URL to.
     */
    public static function root($to = null)
    {
        static::$routes['root'] = new Route('/', 'root');

        if ($to) {
            static::$routes['root']->to($to);
        }

        return static::$routes['root'];
    }

    /**
     * Shortcut for `Router::route(...)->method('get')`
     *
     * @param string $route
     * @param string $name  Route name
     */
    public static function get($route, $name = null)
    {
        return static::route($route, $name)->method('get');
    }

    /**
     * Shortcut for `Router::route(...)->method('post')`
     *
     * @param string $route
     * @param string $name  Route name
     */
    public static function post($route, $name = null)
    {
        return static::route($route, $name)->method('post');
    }

    /**
     * Shortcut for setting up the routes for a resource.
     *
     * @param string $resource   Resource/model name.
     * @param string $controller Controller to use for the resource.
     */
    public static function resources($modelName, $controllerName, array $options = [])
    {
        $options = $options + [
            'name'       => Inflector::underscore($modelName),
            'namePrefix' => null,
            'pathPrefix' => null,
            'token'      => "{id}"
        ];

        // Append name prefix
        if ($options['namePrefix']) {
            $options['name'] = "{$options['namePrefix']}_{$options['name']}";
        }

        $path = '/'. ltrim($options['pathPrefix'], '/') . Inflector::underscore(Inflector::controllerise($modelName));

        // Index
        static::get($path, Inflector::pluralise($options['name']))
            ->to($controllerName . '::index');

        // New
        static::get($path . '/new', 'new_' . $options['name'])
            ->to($controllerName . '::new');

        // Create
        static::post($path . '/new')->to($controllerName . '::create');

        // Show
        static::get($path . "/{$options['token']}", 'show_' . Inflector::singularise($options['name']))
            ->to($controllerName . '::show');

        // Edit
        static::get($path . "/{$options['token']}/edit", 'edit_' . $options['name'])
            ->to($controllerName . '::edit');

        // Save
        static::post($path . "/{$options['token']}/edit")->to($controllerName . '::save');

        // Delete / Destroy confirmation
        static::get($path . "/{$options['token']}/delete", 'delete_' . $options['name'])
            ->to($controllerName . '::delete');

        // Destroy
        static::post($path . "/{$options['token']}/delete")->to($controllerName . '::destroy');
    }

    /**
     * Adds a new route.
     *
     * @param string $route URI to route
     * @param string $name  Route name
     */
    public static function route($route, $name = null)
    {
        // 404 Route
        if ($route == '404') {
            return static::$routes['404'] = new Route('404', '404');
        }

        if ($name) {
            return static::$routes[$name] = new Route($route, $name);
        } else {
            return static::$routes[] = new Route($route);
        }
    }

    /**
     * Routes the request to the controller.
     *
     * @param Request $request
     */
    public static function process(Request $request)
    {
        $requestPath = str_replace(basename($request->scriptFilename()), '', $request->pathInfo());
        $requestPath = '/' . trim($requestPath, '/');

        $extensions  = static::extensionsRegex();

        if ($requestPath == '/') {
            if (!isset(static::$routes['root'])) {
                throw new Exception("No root route set.");
            }

            return static::setRoute(static::$routes['root']);
        }

        foreach (static::$routes as $route) {
            $route->compiledPath = static::compilePath($route->path);
            $pattern = static::regex($route->compiledPath);

            // Match exact path and request method
            if ($route->path == $requestPath
            && in_array(strtolower($request->method()), $route->methods)) {
                $route->params = $route->defaults;
                return static::setRoute($route);
            }
            // Regex match
            elseif (preg_match($pattern, $requestPath, $params)) {
                unset($params[0]);

                // Merge params
                $route->params = $params;
                $route->params = array_merge($route->defaults, $route->params);

                if (in_array(strtolower($request->method()), $route->methods)) {
                    return static::setRoute($route);
                }
            }
        }

        // No matches, try 404 route
        if (isset(static::$routes['404'])) {
            return static::set404();
        }
        // No 404 route, Exception time! FUN :D
        else {
            throw new Exception("No routes found for '{$requestPath}'");
        }
    }

    /**
     * Replaces tokens in the route with their regex values.
     *
     * @param string $route
     *
     * @return string
     */
    public static function compilePath($path)
    {
        foreach (static::$tokens as $token => $regex) {
            $path = str_replace("{{$token}}", $regex, $path);
        }

        return $path;
    }

    /**
     * Sets the route info to that of the 404 route.
     */
    public static function set404()
    {
        if (!isset(static::$routes['404'])) {
            throw new Exception("There is no 404 route set.");
        }

        // Get request file extension
        $extensions = static::extensionsRegex();
        $match = preg_match(static::regex(), Request::$requestUri, $params);

        if (isset($params['extension'])) {
            static::$routes['404']->params['extension'] = $params['extension'];
        }

        return static::setRoute(static::$routes['404']);
    }

    /**
     * Registers a token to replace in routes.
     *
     * @param string $token Token name
     * @param string $value Regex value
     *
     * @example
     *     Router::addToken('post_id', "(?P<post_id>[0-9]+)");
     */
    public static function addToken($token, $value)
    {
        static::$tokens[$token] = $value;
    }

    /**
     * Returns the current route.
     *
     * @return object
     */
    public static function currentRoute()
    {
        return static::$currentRoute;
    }

    /**
     * Returns compiled extensions regex group.
     *
     * @return string
     */
    public static function extensionsRegex()
    {
        $extensions = [];

        foreach (static::$extensions as $extension) {
            $extensions[] = preg_quote($extension, static::$regexDelimiter);
        }

        return "(?P<extension>" . implode("|", $extensions) . ')?';
    }

    /**
     * Wraps the path with the regex delimiter and extensions regex.
     *
     * @param string $compiledPath
     *
     * @return string
     */
    public static function regex($compiledPath = '')
    {
        $extensions = static::extensionsRegex();
        return static::$regexDelimiter . "^{$compiledPath}{$extensions}$" . static::$regexDelimiter . "s";
    }

    /**
     * Sets the current route.
     *
     * @param Route $route
     *
     * @return Route
     */
    protected static function setRoute($route)
    {
        // Set controller extension
        if (isset($route->params['extension'])) {
            $route->extension = $route->params['extension'];
        }

        // Remove the first dot from the extension
        if ($route->extension[0] == '.') {
            $route->extension = substr($route->extension, 1);
        }

        // Allow static use current route info.
        static::$controller = $route->controller;
        static::$action     = $route->action;
        static::$params     = $route->params;
        static::$extension  = $route->extension;

        Request::$properties->set($route->params);

        return static::$currentRoute = $route;
    }
}
