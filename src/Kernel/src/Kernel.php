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

namespace Avalon;

use Exception;
use ReflectionMethod;
use Avalon\Routing\Route;
use Avalon\Http\Request;
use Avalon\Http\Response;

/**
 * Avalon kernel.
 *
 * @package Avalon\Kernel
 * @author Jack P.
 * @since 2.0.0
 */
class Kernel
{
    const VERSION = "2.0.0";

    /**
     * Execute the route.
     *
     * @param Route $route
     */
    public static function execute(Request $request, Route $route)
    {
        if (!class_exists($route->controller)
        || !method_exists($route->controller, "{$route->action}Action")) {
            throw new Exception("Unable to find controller [{$route->controller}::{$route->action}Action]");
        }

        // Get controller action parameters
        $actionParams = static::getParameters($route);

        // Instantiate the controller
        $controller = new $route->controller;

        // Run before filters
        $response = $controller->runBeforeFilters($route->action);

        // Execute action
        if (!$response instanceof Response) {
            $response = call_user_func_array([$controller, "{$route->action}Action"], $actionParams);
        }

        // Run after filters
        $afterResponse = $controller->runAfterFilters($route->action);

        if ($afterResponse instanceof Response) {
            $response = $afterResponse;
        }

        // Shutdown the controller
        $controller->__shutdown();

        // Send response
        if (!$response instanceof Response) {
            $message = "The controller [{$route->controller}::{$route->action}Action] returned an invalid response.";
            throw new Exception($message);
        } else {
            return $response;
        }
    }

    /**
     * Get the routed actions parameters.
     *
     * @param Route $route
     *
     * @return array
     */
    protected static function getParameters(Route $route)
    {
        $methodInfo = new ReflectionMethod("{$route->controller}::{$route->action}Action");
        $params = [];

        foreach ($methodInfo->getParameters() as $param) {
            if (isset($route->params[$param->getName()])) {
                $params[] = $route->params[$param->getName()];
            }
        }

        unset($methodInfo, $param);
        return $params;
    }
}
