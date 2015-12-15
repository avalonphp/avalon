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
use ReflectionObject;
use Avalon\Kernel;
use Avalon\Database\ConnectionManager;
use Avalon\Routing\Router;
use Avalon\Http\Request;
use Avalon\Http\Response;
use Avalon\Templating\View;
use Avalon\Templating\Engines\PhpEngine;

/**
 * Application kernel.
 *
 * @package Avalon\Kernel
 * @author Jack P.
 * @since 2.0
 */
class AppKernel
{
    /**
     * Application directory.
     *
     * @var string
     */
    protected $path;

    /**
     * Application config.
     *
     * @var array
     */
    protected $config;

    /**
     * @var ReflectionObject
     */
    protected $classInfo;

    public function __construct()
    {
        $this->classInfo = new ReflectionObject($this);
        $this->path = dirname($this->classInfo->getFilename());

        $this->loadConfiguration();
        $this->connectDatabase();
        $this->loadRoutes();
        $this->setupTemplating();
    }

    /**
     * Loads the applications configuration.
     */
    protected function loadConfiguration()
    {
        $path = "{$this->path}/config/config.php";

        if (file_exists($path)) {
            $this->config = require $path;

            if (isset($this->config['environment'])) {
                $_ENV['environment'] = $this->config['environment'];

                // Load environment
                $environemntPath = "{$this->path}/config/environment/{$_ENV['environment']}.php";
                if (file_exists($environemntPath)) {
                    require $environemntPath;
                }
            }
        } else {
            throw new Exception("Error loading configuration file: [{$path}]");
        }
    }

    /**
     * Setup database connection for current environment.
     */
    protected function connectDatabase()
    {
        if (isset($this->config['database'])
        && isset($this->config['database'][$this->config['environment']])) {
            ConnectionManager::create($this->config['database'][$this->config['environment']]);
        }
    }

    /**
     * Loads the applications routes.
     */
    protected function loadRoutes()
    {
        $path = "{$this->path}/config/routes.php";

        if (file_exists($path)) {
            require $path;
        } else {
            throw new Exception("Error loading routes file: [{$path}]");
        }
    }

    /**
     * Setup templating.
     */
    protected function setupTemplating()
    {
        View::setEngine(new PhpEngine);
        View::addPath("{$this->path}/views");
    }

    /**
     * Process the request and route to the controller.
     *
     * @param Request $request Optional request
     */
    public function run(Request $request = null)
    {
        if (!$request) {
            $request = new Request;
        }

        $route = Router::process($request);

        return Kernel::execute($request, $route);
    }
}
