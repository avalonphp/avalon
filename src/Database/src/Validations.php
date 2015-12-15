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

use Avalon\Database\Model\Base as BaseModel;

/**
 * Validations class.
 *
 * @author Jack P.
 */
class Validations
{
    /**
     * Runs the validations for passed Model and field.
     *
     * @param BaseModel $model
     * @param string    $field
     * @param array     $validations
     */
    public static function run(BaseModel $model, $field, $validations)
    {
        // Run validations
        foreach ($validations as $validation => $options) {
            // Is this a validation without any options?
            if (is_numeric($validation)) {
                $validation = $options;
            }

            $data = $data = call_user_func_array(
                [get_called_class(), $validation],
                [$model, $field, $options]
            );

            if ($data and $data !== null) {
                if (is_string($data)) {
                    $data = ['error' => $data];
                }

                $data = $data + [
                    'field' => $field,
                    'validation' => $validation
                ];

                $model->addValidationError($field, $data, $validation);
            }
        }
    }

    /**
     * Checks if the field is unique.
     *
     * @param BaseModel $model
     * @param string    $field
     */
    private static function unique(BaseModel $model, $field)
    {
        $value = $model->{$field};

        if ($value === null) {
            $value = '';
        }

        $row = $model::find($field, $value);
        if ($row && $row->id != $model->id) {
            return 'already_in_use';
        }
    }

    /**
     * Checks if the field is set.
     *
     * @param BaseModel $model
     * @param string    $field
     */
    private static function required(BaseModel $model, $field)
    {
        if (!isset($model->{$field}) || ($model->{$field} === '')) {
            return 'required';
        }
    }

    /**
     * Check two fields to check if they match.
     *
     * @param  BaseModel $model
     * @param  string    $field
     * @param  string    $confirmField
     *
     * @return array|null
     */
    private static function confirm(BaseModel $model, $field, $options)
    {
        $options = (array) $options;

        $options = $options + ['onlyNew' => false];

        if (!isset($options['field'])) {
            $options['field'] = "{$field}_confirmation";
        }

        $confirmField = $options['field'];

        if ($options['onlyNew'] === true && $model->isNew()) {
            if (!isset($model->{$confirmField}) || $model->{$field} !== $model->{$confirmField}) {
                return [
                    'error' => "fields_dont_match",
                    'field' => $field
                ];
            }
        }
    }

    /**
     * Checks if the field is an email address.
     *
     * @param BaseModel $model
     * @param string    $field
     */
    private static function email(BaseModel $model, $field)
    {
        if (!filter_var($model->{$field}, FILTER_VALIDATE_EMAIL)) {
            return 'must_be_email';
        }
    }

    /**
     * Validates the minimum length of the field.
     *
     * @param BaseModel $model
     * @param string    $field
     */
    private static function minLength(BaseModel $model, $field, $minLength)
    {
        if (strlen($model->{$field}) < $minLength) {
            return [
                'error'     => "field_too_short",
                'minLength' => $minLength
            ];
        }
    }

    /**
     * Validates the maximum length of the field.
     *
     * @param BaseModel $model
     * @param string    $field
     */
    private static function maxLength(BaseModel $model, $field, $maxLength)
    {
        if (strlen($model->{$field}) > $maxLength) {
            return [
                'error'     => "field_too_long",
                'maxLength' => $maxLength
            ];
        }
    }

    /**
     * Checks if the field is numeric.
     *
     * @param BaseModel $model
     * @param string    $field
     */
    private static function numeric(BaseModel $model, $field)
    {
        if (!is_numeric($model->{$field})) {
            return 'must_be_numeric';
        }
    }
}
