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

use ArrayAccess;

/**
 * This class is used to treat `$_GET`, `$_POST`, `$_REQUEST` and `$_COOKIE`
 * as objects.
 *
 * ParameterBag properties can also be accessed via the array interface like so:
 *     $myProperty = $parameterBag['myProperty'];
 *
 * @author Jack P.
 * @package Avalon\Http
 * @since 2.0.0
 */
class ParameterBag implements ArrayAccess
{
    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @param array $properties
     */
    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $property
     *
     * @return boolean
     */
    public function has($property)
    {
        return isset($this->properties[$property]);
    }

    /**
     * @param string $property
     * @param mixed  $value
     */
    public function set($property, $value = null)
    {
        // Check if we're trying to set multiple properties.
        if (is_array($property)) {
            foreach ($property as $prop => $value) {
                $this->set($prop, $value);
            }
        } else {
            $this->properties[$property] = $value;
        }
    }

    /**
     * @param string $property
     * @param mixed  $fallback
     *
     * @return mixed
     */
    public function get($property, $fallback = null, $placeInParameterBag = true)
    {
        // Check if the property exists otherwise return fallback value.
        if (isset($this->properties[$property])) {
            // If the property is an array, convert it to a ParameterBag object.
            if (is_array($this->properties[$property]) && $placeInParameterBag) {
                return $this->properties[$property] = new static($this->properties[$property]);
            } else {
                return $this->properties[$property];
            }
        } else {
            return $fallback;
        }
    }

    /**
     * Delete property.
     *
     * @param string $property
     */
    public function delete($property)
    {
        unset($this->properties[$property]);
    }

    /**
     * @param string $property
     *
     * @return boolean
     */
    public function offsetExists($property)
    {
        return $this->has($property);
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function offsetGet($property)
    {
        return $this->get($property);
    }

    /**
     * @param string $property
     * @param mixed  $value
     *
     * @return boolean
     */
    public function offsetSet($property, $value)
    {
        $this->set($property, $value);
    }

    /**
     * @param string $property
     */
    public function offsetUnset($property)
    {
        $this->delete($property);
    }
}
