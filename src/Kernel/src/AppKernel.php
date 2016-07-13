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
use ReflectionClass;
use Avalon\Templating\View;
use Avalon\Database\ConnectionManager;

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
     * Application directory path.
     *
     * @var string
     */
    protected $path;

    /**
     * Configuration directory path.
     *
     * @var string
     */
    protected $configDir;

    /**
     * Configuration.
     *
     * @var array
     */
    protected $config;

    public function __construct()
    {
        $r = new \ReflectionObject($this);
        $this->path = dirname($r->getFileName());

        if (!$this->configDir) {
            $this->configDir = dirname($this->path) . '/config';
        }

        $this->loadConfiguration();
        $this->configureDatabase();
        $this->loadRoutes();

        $this->configureEnvironment();
        $this->configureTemplating();
    }

    /**
     * Load configuration.
     */
    protected function loadConfiguration()
    {
        if (file_exists("{$this->configDir}/config.php")) {
            $this->config = require "{$this->configDir}/config.php";

            // If no environment is set, assume production.
            if (!isset($this->config['environment'])) {
                $this->config['environment'] = 'production';
            }

            $_ENV['environment'] = $this->config['environment'];
        } else {
            throw new Exception("Unable to load config file from [{$this->configDir}]");
        }
    }

    /**
     * Configure database connection.
     */
    protected function configureDatabase()
    {
        if (isset($this->config['database'][$this->config['environment']])) {
            ConnectionManager::create($this->config['database'][$this->config['environment']]);
        }
    }

    /**
     * Load routes.
     */
    protected function loadRoutes()
    {
        if (file_exists("{$this->configDir}/routes.php")) {
            require "{$this->configDir}/routes.php";
        } else {
            throw new Exception("Unable to load routes file");
        }
    }

    /**
     * Load the environment configuration file.
     */
    protected function configureEnvironment()
    {
        if (isset($this->config['environment'])) {
            if (file_exists("{$this->configDir}/environment/{$this->config['environment']}.php")) {
                require "{$this->configDir}/environment/{$this->config['environment']}.php";
            }
        }
    }

    /**
     * Setup the view class.
     */
    protected function configureTemplating()
    {
        View::setEngine(new \Avalon\Templating\Engines\PhpEngine);
        View::addPath("{$this->path}/views");
    }

    /**
     * Process the request and return the response.
     *
     * @return \Avalon\Http\Response
     */
    public function process()
    {
        return Kernel::process();
    }

    /**
     * Process the request and send the response.
     *
     * @return \Avalon\Http\Response
     */
    public function run()
    {
        return $this->process()->send();
    }
}
