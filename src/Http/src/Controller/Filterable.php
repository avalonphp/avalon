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

namespace Avalon\Http\Controller;

/**
 * Filterable controller trait.
 *
 * @author Jack P.
 * @package Avalon\Http\Controller
 * @since 2.0.0
 */
trait Filterable
{
    /**
     * Add before filter.
     *
     * @param string $action
     * @param mixed  $callback
     *
     * @example
     *     $this->before('create', 'checkPermission'); // calls the controllers `checkPermission` method
     *     $this->before('create', [$currentUser, 'checkPermission']); // calls `$currentUser->checkPermission()`
     *     $this->before('create', function(){
     *         // Calls the closure / anonymous function
     *     });
     */
    protected function before($action, $callback)
    {
        $this->addFilter('before', $action, $callback);
    }

    /**
     * Add before filter.
     *
     * @param string $action
     * @param mixed  $callback
     */
    protected function after($action, $callback)
    {
        $this->addFilter('after', $action, $callback);
    }

    /**
     * Adds the filter to the event dispatcher.
     *
     * @param string $when     Either 'before' or 'after'
     * @param string $action
     * @param mixed  $callback
     */
    protected function addFilter($when, $action, $callback)
    {
        $whenVar = "{$when}Filters";

        if (!is_callable($callback) && !is_array($callback)) {
            $callback = [$this, $callback];
        }

        if (is_array($action)) {
            foreach ($action as $method) {
                $this->addFilter($when, $method, $callback);
            }
        } else {
            if (!isset($this->{$whenVar}[$action])) {
                $this->{$whenVar}[$action] = [];
            }

            $this->{$whenVar}[$action][] = $callback;
        }
    }

    /**
     * Run before filters for the specified action.
     *
     * @param string $action
     *
     * @return Response
     */
    public function runBeforeFilters($action)
    {
        return $this->runFilters('before', $action);
    }

    /**
     * Run after filters for the specified action.
     *
     * @param string $action
     *
     * @return Response
     */
    public function runAfterFilters($action)
    {
        return $this->runFilters('after', $action);
    }

    /**
     * Run filters for the specified time and action.
     *
     * @param string $when
     * @param string $action
     *
     * @return Response
     */
    protected function runFilters($when, $action)
    {
        $when = "{$when}Filters";

        $filters = array_merge(
            isset($this->{$when}['*']) ? $this->{$when}['*'] : [],
            isset($this->{$when}[$action]) ? $this->{$when}[$action] : []
        );

        foreach ($filters as $filter) {
            $response = $filter();

            if ($response) {
                return $response;
            }
        }
    }
}
