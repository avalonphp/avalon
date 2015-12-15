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

namespace Avalon\Database\Model;

/**
 * Base model class.
 *
 * @author Jack P.
 */
abstract class Base
{
    /**
     * Whether or not the model already exists in the database.
     *
     * @var bool
     */
    protected $_isNew;

    /**
     * Column data types to convert to/from.
     *
     * @var array
     */
    protected static $_dataTypes = [];

    /**
     * @param array $data  Model data.
     * @param bool  $isNew Whether or not it exists in the database.
     */
    public function __construct(array $data = [], $isNew = true)
    {
        $this->_isNew = $isNew;

        if ($isNew) {
            $this->set($data);
        } else {
            // Convert data from a safely storable format
            foreach (static::convertToDataTypes($data) as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Class Methods

    /**
     * Convert data from a safely storable format to actual values.
     *
     * @param array $data
     *
     * @return array
     */
    public static function convertToDataTypes(array $data)
    {
        foreach (static::$_dataTypes as $column => $type) {
            if (isset($data[$column])) {
                if ($type == 'json_array') {
                    $data[$column] = json_decode($data[$column], true);
                }
            }
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Instance Methods

    /**
     * Mass set model data.
     *
     * @param array $field
     * @param mixed $value
     */
    public function set($field, $value = null)
    {
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $this->set($key, $value);
            }
        } else {
            $this->{$field} = $value;
        }
    }

    /**
     * @return boolean
     */
    public function isNew()
    {
        return $this->_isNew;
    }
}
