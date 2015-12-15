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

use Doctrine\Common\Inflector\Inflector as DoctrineInflector;

/**
 * Database related inflectors, based on Doctrine's Inflector.
 */
class Inflector
{
    /**
     * Turn the string into table name format.
     *
     * @param string $string
     *
     * @return string
     */
    public static function tableise($string)
    {
        return DoctrineInflector::pluralize(DoctrineInflector::tableize($string));
    }

    /**
     * Turn the string into a model class name.
     *
     * @param string $string
     *
     * @return string
     */
    public static function modelise($string)
    {
        return DoctrineInflector::singularize(DoctrineInflector::classify($string));
    }

    /**
     * Turn the string into a foreign key.
     *
     * @param string $string
     *
     * @return string
     */
    public static function foreignKey($string)
    {
        return DoctrineInflector::singularize(static::underscore($string)) . "_id";
    }

    /**
     * Turn the string into `under_score` format.
     *
     * @param string $string
     *
     * @return string
     */
    public static function underscore($string)
    {
        return strtolower(preg_replace(
            '/([A-Z]+)([A-Z])/',
            '\1_\2',
            preg_replace('/([a-z\d])([A-Z])/', '\1_\2', $string)
        ));
    }
}
