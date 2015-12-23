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
use Avalon\Http\Request;
use Avalon\Http\Response;
use Avalon\Routing\Router;
use Avalon\Routing\Route;

/**
 * Avalon kernel.
 *
 * @package Avalon\Kernel
 * @author Jack P.
 * @since 2.0.0
 */
class Kernel
{
    const VERSION = '2.0.0';

    /**
     * Route the request and execute the controller.
     */
    public static function process()
    {
        Request::init();
        $route = Router::process();

        if (!$route) {
            $route = Router::getRoute('404');
        }

        if ($route) {
            list($class, $method) = explode('::', $route->controller);
            $action = "{$method}Action";
            Request::$properties->set([
                'controller' => $class,
                'action'     => $method
            ]);

            if (!class_exists($class)) {
                throw new Exception("Controller class [{$class}] not found");
            }

            if (!method_exists($class, $action)) {
                throw new Exception("Controller action [{$route->controller}Action] not found");
            }

            $controller = new $class;

            $response = static::runFilters('before', $controller, $method);

            if (!$response) {
                $response = call_user_func_array([$controller, $action], $route->actionParams());
            }

            static::runFilters('after', $controller, $method);

            if (!($response instanceof Response)) {
                throw new Exception("The controller returned an invalid response");
            }

            return $response;
        } else {
            throw new Exception(sprintf("No route matches [%s] and no 404 controller set", Request::$pathInfo));
        }
    }

    /**
     * Run the before/action filters for the controllers action.
     *
     * @param  string     $when
     * @param  Controller $controller
     * @param  string     $method
     * @return Response
     */
    protected static function runFilters($when, $controller, $method)
    {
        $filters = array_merge(
            isset($controller->{$when}['*']) ? $controller->{$when}['*'] : [],
            isset($controller->{$when}[$method]) ? $controller->{$when}[$method] : []
        );

        foreach ($filters as $filter) {
            $response = $filter();

            if ($response) {
                return $response;
            }
        }
    }
}
