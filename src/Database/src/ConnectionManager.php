<?php
/*
 * Avalon
 * Copyright 2011-2015 Jack P.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Avalon\Database;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

/**
 * Database connection manager.
 *
 * @author Jack P.
 */
class ConnectionManager
{
    /**
     * Active connections.
     *
     * @param \Doctrine\DBAL\Connection[]
     */
    protected static $connections = [];

    /**
     * Create a database connection.
     *
     * @param array  $info
     * @param string $name Connection name.
     */
    public static function create($info, $name = 'default')
    {
        return static::$connections[$name] = DriverManager::getConnection($info);
    }

    /**
     * @param string $name Connection name.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public static function getConnection($name = 'default')
    {
        return static::$connections[$name];
    }
}
