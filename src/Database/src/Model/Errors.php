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

use Avalon\Language;

/**
 * Errors model trait.
 *
 * @author Jack P.
 */
trait Errors
{
    /**
     * Returns the errors array.
     *
     * @return array
     */
    public function errors()
    {
        return $this->_errors;
    }

    /**
     * Check if a field has an error.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function hasError($field)
    {
        if (isset($this->_errors[$field])) {
            return true;
        }

        return false;
    }

    /**
     * Get error(s) for a field.
     *
     * @param string $field
     *
     * @return array|null
     */
    public function getError($field)
    {
        if (isset($this->_errors[$field])) {
            return $this->_errors[$field];
        }
    }

    /**
     * Get the translated error message(s) for a field.
     *
     * @param string $field
     *
     * @return array|null
     */
    public function getErrorMessage($field)
    {
        if (isset($this->_errors[$field])) {
            $messages = [];

            foreach ($this->_errors[$field] as $error) {
                $error['field'] = Language::translate($field);
                $messages[] = Language::translate($error['error'], $error);
            }

            return $messages;
        }
    }

    /**
     * Get translated error messages.
     *
     * @return array
     */
    public function getErrorMessages()
    {
        if (count($this->_errors)) {
            $messages = [];

            foreach ($this->_errors as $field => $errors) {
                foreach ($errors as $error) {
                    $error['field'] = Language::translate($field);
                    $messages[$field][] = Language::translate($error['error'], $error);
                }
            }

            return $messages;
        }
    }

    /**
     * Adds an error for the specified field.
     *
     * @param string $field
     * @param mixed  $data
     * @param string $index
     */
    public function addError($field, $data, $index = null)
    {
        if (!isset($this->_errors[$field])) {
            $this->_errors[$field] = [];
        }

        if (is_string($index)) {
            $this->_errors[$field][$index] = $data;
        } else {
            $this->_errors[$field][] = $data;
        }
    }

    /**
     * Adds a validation error for the specified field.
     *
     * @param string $field
     * @param mixed  $data
     * @param string $index
     */
    public function addValidationError($field, $data, $index = null)
    {
        if (isset($data['error'])) {
            $data['error'] = "errors.validations.{$data['error']}";
        }

        $this->addError($field, $data, $index);
    }
}
