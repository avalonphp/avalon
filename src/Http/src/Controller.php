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

use Avalon\Templating\View;
use Avalon\Http\Response;
use Avalon\Http\JsonResponse;
use Avalon\Http\RedirectResponse;
use Avalon\Routing\Router;

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
    /**
     * Before filters.
     *
     * @var array
     */
    public $before = [];

    /**
     * After filters.
     *
     * @var array
     */
    public $after = [];

    /**
     * @var string
     */
    protected $layout = 'default.phtml';

    /**
     * Set a global variable on the view.
     *
     * @param string $key
     * @param mixed  $value
     */
    protected function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            View::addGlobal($key, $value);
        }
    }

    /**
     * Get the translated string.
     *
     * @param string $string
     * @param mixed  $replacements
     */
    protected function translate($string, $replacements = [])
    {
        return call_user_func_array('\\Avalon\\Language::translate', func_get_args());
    }

    /**
     * Render a view and wrap it in a response.
     *
     * @param  string $view
     * @param  array  $locals
     * @return Response
     */
    protected function render($view, array $locals = [])
    {
        $locals = $locals + [
            '_layout' => $this->layout
        ];

        return new Response($this->renderView($view, $locals));
    }

    /**
     * Render a view.
     *
     * @param  string $view
     * @param  array  $locals
     * @return Response
     */
    protected function renderView($view, array $locals = [])
    {
        $content = View::render($view, $locals);

        if (isset($locals['_layout']) && $locals['_layout']) {
            $content = View::render("layouts/{$locals['_layout']}", [
                'content' => $content
            ]);
        }

        return $content;
    }

    /**
     * @param string $route
     * @param array  $tokens
     *
     * @return string
     */
    protected function generatePath($route, array $tokens = [])
    {
        return Router::generatePath($route, $tokens);
    }

    /**
     * @param string $route
     * @param array  $tokens
     *
     * @return string
     */
    protected function generateUrl($route, array $tokens = [])
    {
        return Router::generateUrl($route, $tokens);
    }

    /**
     * Redirect to a route.
     *
     * @param string  $route  route name
     * @param array   $tokens
     * @param integer $status
     *
     * @return RedirectResponse
     */
    protected function redirectTo($route, array $tokens = [], $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $tokens), $status);
    }

    /**
     * Redirect to a URL.
     *
     * @param string  $url
     * @param integer $status
     *
     * @return RedirectResponse
     */
    protected function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * JSON response.
     *
     * @param string  $content
     * @param integer $status
     *
     * @return JsonResponse
     */
    protected function jsonResponse($content = '', $status = 200)
    {
        return new JsonResponse($content, $status);
    }

    /**
     * Add before filter.
     *
     * @param string   $action
     * @param callable $callback
     */
    protected function before($action, callable $callback)
    {
        if (is_array($action)) {
            foreach ($action as $act) {
                $this->before($act, $callback);
            }
        } else {
            $this->before[$action][] = $callback;
        }
    }

    /**
     * Add after filter.
     *
     * @param string   $action
     * @param callable $callback
     */
    protected function after($action, callable $callback) {
        if (is_array($action)) {
            foreach ($action as $act) {
                $this->before($act, $callback);
            }
        } else {
            $this->after[$action][] = $callback;
        }
    }

    /**
     * Returns a 404 Not Found response.
     *
     * @return Response
     */
    protected function show404()
    {
        $resp = $this->render('errors/404.phtml');
        $resp->status = 404;
        return $resp;
    }

    /**
     * Returns a 403 Forbidden response.
     *
     * @return Response
     */
    protected function show403()
    {
        $resp = $this->render('errors/403.phtml');
        $resp->status = 403;
        return $resp;
    }

    /**
     * Easily respond to different request types.
     */
    protected function respondTo(callable $callback)
    {
        return $callback(Request::$properties->get('extension', 'html'));
    }
}
