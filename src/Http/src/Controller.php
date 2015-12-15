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

namespace Avalon\Http;

use Exception;
use ReflectionClass;
use Avalon\Kernel;
use Avalon\EventDispatcher;
use Avalon\Routing\Router;
use Avalon\Routing\Route;
use Avalon\Http\Request;
use Avalon\Http\Response;
use Avalon\Http\Controller\Filterable;
use Avalon\Language;
use Avalon\Templating\View;

/**
 * Controller
 *
 * @author Jack P.
 * @package Avalon\Http
 * @since 0.3.0
 * @uses Filterable
 */
class Controller
{
    use Filterable;

    /**
     * Name of the layout to render.
     *
     * @var string
     */
    protected $layout = 'default.phtml';

    /**
     * Name of the 404 Not Found view.
     *
     * @var string
     */
    protected $notFoundView = 'errors/404.phtml';

    /**
     * Name of the 403 Forbidden view.
     *
     * @var string
     */
    protected $forbiddenView = 'errors/403.phtml';

    /**
     * Whether or not to execute the routed action.
     *
     * @var boolean
     */
    public $executeAction = true;

    /**
     * Current request.
     *
     * @var Request
     */
    protected $request;

    /**
     * Current route information.
     *
     * @var Route
     */
    protected $route;

    /**
     * @var array
     */
    protected $beforeFilters = [];

    /**
     * @var array
     */
    protected $afterFilters = [];

    /**
     * Sets the request, route, database, view and response variables.
     */
    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->route   = Router::currentRoute();
    }

    /**
     * Sends the variable to the view.
     *
     * @param string $name  Variable name.
     * @param mixed  $value Value.
     */
    public function set($name, $value = null)
    {
        View::addGlobal($name, $value);
    }

    /**
     * Renders a response.
     *
     * @param string $view   View to render.
     * @param array  $locals Variables for the view.
     *
     * @return Response
     */
    public function render($view, array $locals = [])
    {
        $locals = $locals + [
            '_layout' => $this->layout
        ];

        return new Response(function($resp) use ($view, $locals) {
            $resp->body = $this->renderView($view, $locals);
        });
    }

    /**
     * Renders the view.
     *
     * @param string $view   View to render.
     * @param array  $locals Variables for the view.
     *
     * @return string
     */
    public function renderView($view, array $locals = [])
    {
        $content = View::render($view, $locals);

        if (isset($locals['_layout']) && $locals['_layout']) {
            $content = $this->renderView("layouts/{$locals['_layout']}", [
                'content' => $content
            ]);
        }

        return $content;
    }

    /**
     * Returns the compiled path for the route.
     *
     * @param string $routeName
     * @param array  $tokens
     *
     * @return string
     */
    protected function generateUrl($routeName, array $tokens = [])
    {
        return Router::generateUrl($routeName, $tokens);
    }

    /**
     * Translates the passed string.
     *
     * @param string $string       String to translate.
     * @param array  $replacements Replacements to be inserted into the string.
     *
     * @return string
     */
    public function translate($string, array $replacements = [])
    {
        return Language::translate($string, $replacements);
    }

    /**
     * Redirects to the specified path.
     *
     * @param string  $path
     * @param integer $status
     *
     * @return RedirectResponse
     */
    public function redirect($path, $status = 302)
    {
        $path = Request::basePath($path);
        return new RedirectResponse($path, function ($resp) use ($status) {
            $resp->status = $status;
        });
    }

    /**
     * Redirects to the specified route.
     *
     * @param string  $route
     * @param integer $status
     *
     * @return RedirectResponse
     */
    public function redirectTo($route, $status = 302)
    {
        return $this->redirect($this->generateUrl($route), $status);
    }

    /**
     * Easily respond to different request formats.
     *
     * @param callable $func
     *
     * @return Response
     */
    public function respondTo($func)
    {
        $response = $func($this->route->extension, $this);

        if ($response === null) {
            return $this->show404();
        }

        return $response;
    }

    /**
     * Returns a 404 Not Found response.
     *
     * @return Response
     */
    public function show404()
    {
        $this->executeAction = false;
        return new Response(function ($resp) {
            $resp->status = 404;
            $resp->body   = $this->renderView($this->notFoundView, [
                '_layout' => $this->layout
            ]);
        });
    }

    /**
     * Returns a 403 Forbidden response.
     *
     * @return Response
     */
    public function show403()
    {
        $this->executeAction = false;
        return new Response(function ($resp) {
            $resp->status = 404;
            $resp->body   = $this->renderView($this->notFoundView, [
                '_layout' => $this->layout
            ]);
        });
    }

    /**
     * Handles controller shutdown.
     */
    public function __shutdown() {}
}
