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

use Avalon\Http\Response;

/**
 * JSON Response.
 *
 * @package Avalon\Http
 * @author Jack P.
 * @since 0.2.0
 */
class JsonResponse extends Response
{
    /**
     * Response content-type.
     */
    public $contentType = 'application/json';

    public function __construct($content = '', $status = 200)
    {
        if (!is_string($content)) {
            $content = json_encode($content);
        }

        parent::__construct($content, $status);
    }
}
