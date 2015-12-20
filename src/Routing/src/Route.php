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

use ReflectionMethod;
use InvalidArgumentException;

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
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    protected $compiledPath;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var array
     */
    public $defaults = [];

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var array
     */
    public $methods = ['GET'];

    /**
     * @param string $name
     * @param string $path
     * @param string $controller
     * @param array  $defaults
     */
    public function __construct($name, $path, $controller, array $defaults = [])
    {
        $this->name = $name;
        $this->path = $path;
        $this->controller = $controller;
        $this->defaults = $defaults;

        if (strpos($controller, '::') === false) {
            throw new InvalidArgumentException("Controller format must match [Class::method], was: [{$controller}]");
        }
    }

    /**
     * Set allowed method(s).
     *
     * @param string|array $method
     *
     * @return Route
     */
    public function method($method)
    {
        $this->methods = (array) $method;

        return $this;
    }

    /**
     * Get the compiled path.
     *
     * @return string
     */
    public function compiledPath()
    {
        if (!$this->compiledPath) {
            $this->compilePath();
        }

        return $this->compiledPath;
    }

    /**
     * Compile the route path and replace parameters with their regex value.
     */
    public function compilePath()
    {
        $this->compiledPath = $this->path;

        foreach (Router::$tokens as $name => $value) {
            $this->compiledPath = str_replace("{{$name}}", $value, $this->compiledPath);
        }
    }

    /**
     * Returns the parameters from the controllers action.
     *
     * @return array
     */
    public function actionParams()
    {
        $methodInfo = new ReflectionMethod("{$this->controller}Action");

        $routeParams = $this->params + $this->defaults;
        $params = [];

        foreach ($methodInfo->getParameters() as $param) {
            if (isset($routeParams[$param->getName()])) {
                $params[] = $routeParams[$param->getName()];
            }
        }

        unset($methodInfo, $param);
        return $params;
    }
}
