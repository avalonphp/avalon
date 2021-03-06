<?php
/*!
 * Avalon
 * Copyright 2011-2016 Jack P.
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

/**
 * HTTP Response.
 *
 * @package Avalon\Http
 * @author Jack P.
 * @since 0.2.0
 */
class Response
{
    /**
     * HTTP status code.
     */
    public $status = 200;

    /**
     * Response body.
     */
    public $body;

    /**
     * Response content-type.
     */
    public $contentType = 'text/html';

    /**
     * Response headers.
     */
    protected $headers = [];

    /**
     * Cookies to set.
     */
    protected $cookies = [];

    public function __construct($content = '', $status = 200)
    {
        $this->body = $content;
        $this->status = $status;
    }

    /**
     * Add cookie.
     *
     * @param string  $name
     * @param string  $value
     * @param integer $expires
     * @param string  $path
     *
     * @return Response
     */
    public function addCookie()
    {
        $this->cookies[] = func_get_args();

        return $this;
    }

    /**
     * Takes a file extension and sets the content-type.
     *
     * @param string $format
     *
     * @return Response
     */
    public function format($format)
    {
        switch ($format) {
            case 'html':
                $this->contentType = 'text/html';
                break;

            case 'json':
                $this->contentType = 'application/json';
                break;
        }

        return $this;
    }

    /**
     * Sets a response header.
     *
     * @param string $header
     * @param string $value
     *
     * @return Response
     */
    public function addHeader($header, $value, $replace = true)
    {
        $this->headers[] = [$header, $value, $replace];

        return $this;
    }

    /**
     * Add a flash message.
     *
     * @param string $name
     * @param string $message
     *
     * @return Response
     */
    public function addFlash($name, $message)
    {
        $_SESSION['flashMessages'][$name][] = $message;

        return $this;
    }

    /**
     * Sends the response to the browser.
     */
    public function send()
    {
        // Set response code
        http_response_code($this->status);

        // Set content-type
        header("Content-Type: {$this->contentType}");

        // Set cookies
        foreach ($this->cookies as $cookie) {
            call_user_func_array('setcookie', $cookie);
        }

        // Set headers
        foreach ($this->headers as $header) {
            header("{$header[0]}: {$header[1]}", $header[2]);
        }

        // Print the content
        print($this->body);
    }
}
