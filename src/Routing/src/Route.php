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

/**
 * HTTP Route.
 *
 * @package Avalon\Routing
 * @author Jack P.
 * @since 2.0.0
 */
class Route
{
    /**
     * Route name.
     *
     * @var string
     */
    public $name;

    /**
     * Route path.
     *
     * @var string
     */
    public $path;

    /**
     * Compiled route.
     *
     * @var string
     */
    public $compiledPath;

    /**
     * Controller class.
     *
     * @var string
     */
    public $controller;

    /**
     * Controller method/action.
     *
     * @var string
     */
    public $action;

    /**
     * Default params.
     *
     * @var array
     */
    public $defaults = [];

    /**
     * Routed params.
     *
     * @param array
     */
    public $params = [];

    /**
     * Accepted HTTP request methods.
     *
     * @var string[]
     */
    public $methods = ['get', 'post'];

    /**
     * Route extension.
     *
     * @var string
     */
    public $extension = 'html';

    /**
     * Creates a new route.
     *
     * @param string $path
     * @param string $name Route name
     */
    public function __construct($path, $name = null)
    {
        $this->path = $path;
        $this->name = $name;
    }

    /**
     * Destination class and method of route.
     *
     * @param string $controller Class and method to route to
     * @param array  $defaults   Arguments to pass to the routed method
     *
     * @example
     *     to('Admin\Settings::index')
     *
     * @return Route
     */
    public function to($controller, array $defaults = [])
    {
        if ($this->name === null) {
            $this->name = strtolower(
                str_replace(['\\', '::'], '_', $controller)
            );
        }

        $controller = explode('::', $controller);

        $this->controller = $controller[0];
        $this->action     = $controller[1];
        $this->defaults   = $defaults;

        return $this;
    }

    /**
     * Sets the routes name.
     *
     * @param string $name
     *
     * @return Route
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * HTTP methods to accept.
     *
     * @param mixed $method
     *
     * @example
     *     method('get');
     *     method(['get', 'post']);
     *
     * @return Route
     */
    public function method($method)
    {
        // Convert to an array if needed
        if (!is_array($method)) {
            $method = [$method];
        }

        $this->methods = $method;
        return $this;
    }

    /**
     * Generates the path, replacing tokens with specified values.
     *
     * @param array $tokens
     *
     * @return string
     */
    public function generateUrl(array $tokens = [])
    {
        $path = $this->path;

        foreach ($tokens as $key => $value) {
            if (!is_array($value)) {
                $path = str_replace("{{$key}}", $value, $path);
            }
        }

        return $path;
    }
}
