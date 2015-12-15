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

/**
 * HTTP Request.
 *
 * @package Radium\Http
 * @author Jack P.
 * @since 0.1.0
 */
class Request
{
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND             = 302;
    const HTTP_SEE_OTHER         = 303;

    /**
     * @var Request
     */
    protected static $instance;

    /**
     * Holds the $_GET parameters.
     *
     * @var ParameterBag
     */
    public static $query;

    /**
     * Holds the $_POST parameters.
     *
     * @var ParameterBag
     */
    public static $post;

    /**
     * Custom request attributes.
     *
     * @var ParameterBag
     */
    public static $properties;

    /**
     * Holds the $_SERVER parameters.
     *
     * @var ParameterBag
     */
    public static $server;

    /**
     * Holds the request $_FILES
     *
     * @var array
     */
    public static $files;

    /**
     * Request headers.
     *
     * @var ParameterBag
     */
    public static $headers;

    /**
     * Request method.
     *
     * @var string
     */
    protected static $method;

    /**
     * @var string
     */
    protected static $pathInfo;

    /**
     * @var string
     */
    protected static $requestUri;

    /**
     * @var string
     */
    protected static $basePath;

    /**
     * @var string
     */
    protected static $baseUrl;

    /**
     * Sets up the request.
     */
    public function __construct()
    {
        static::$instance = $this;

        static::$query      = new ParameterBag($_GET);
        static::$post       = new ParameterBag($_POST);
        static::$properties = new ParameterBag();
        static::$server     = new ParameterBag($_SERVER);
        static::$files      = $_FILES; // Need to make a custom ParameterBag for this.
        static::$headers    = static::buildHeaderBag();

        static::$baseUrl    = static::prepareBaseUrl();
        static::$basePath   = static::prepareBasePath();
        static::$requestUri = static::prepareRequestUri();
        static::$pathInfo   = static::preparePathInfo();
    }

    /**
     * Separates the headers from `$_SERVER`.
     *
     * @return ParameterBag
     */
    protected static function buildHeaderBag()
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $key = substr($key, 5);
                $key = str_replace('_', ' ', strtolower($key));
                $key = str_replace(' ', '-', ucwords($key));

                $headers[$key] = $value;
            }
        }

        return new ParameterBag($headers);
    }

    /**
     * @return boolean
     */
    public static function matches($path)
    {
        return preg_match("#^{$path}$#", static::$pathInfo);
    }

    /**
     * @return string
     */
    public static function method()
    {
        if (!static::$method) {
            static::$method = $_SERVER['REQUEST_METHOD'];
        }

        return static::$method;
    }

    /**
     * @return string
     */
    public static function schemeAndHttpHost()
    {
        return static::scheme() . '://' . static::httpHost();
    }

    /**
     * @return bool
     */
    public static function isSecure()
    {
        $https = static::$server->get('HTTPS');
        return !empty($https) && strtolower($https) !== 'off';
    }

    /**
     * @return string
     */
    public static function scheme()
    {
        return static::isSecure() ? 'https' : 'http';
    }

    /**
     * @return string
     */
    public static function host()
    {
        if (!$host = static::$headers->get('Host')) {
            if (!$host = static::$server->get('SERVER_NAME')) {
                $host = static::$server->get('SERVER_ADDR', '');
            }
        }

        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host);

        return $host;
    }

    /**
     * @return integer
     */
    public static function port()
    {
        if ($host = static::$headers->get('Host')) {
            $pos = strpos($host, ':');
            if ($pos !== false) {
                return intval(substr($host, $pos + 1));
            }
        }

        return static::$server->get('SERVER_PORT');
    }

    /**
     * @return string
     */
    public static function httpHost()
    {
        $scheme = static::scheme();
        $host   = static::host();
        $port   = static::port();

        if (($scheme == 'http' && $port == 80) || ($scheme == 'https' && $port == 443)) {
            return $host;
        }

        return $host . ':' . $port;
    }

    /**
     * @return string
     */
    public static function basePath($append = null)
    {
        return static::$basePath . ($append ? '/' . ltrim($append, '/') : '');
    }

    /**
     * @return string
     */
    public static function pathInfo()
    {
        return static::$pathInfo;
    }

    /**
     * @return string
     */
    public static function requestUri()
    {
        return static::$requestUri;
    }

    /**
     * @return string
     */
    public static function scriptFilename()
    {
        return static::$server->get("SCRIPT_FILENAME");
    }

    /**
     * @param string  $url
     * @param integer $responseCode
     */
    public static function redirect($url, $responseCode = Request::HTTP_SEE_OTHER)
    {
        header("Location: {$url}", true, $responseCode);
        exit;
    }

    /**
     * Redirects to a path based on the base path.
     *
     * @param string  $url
     * @param integer $responseCode
     */
    public static function redirectTo($url, $responseCode = Request::HTTP_SEE_OTHER)
    {
        static::redirect(static::basePath($url), $responseCode);
    }

    /**
     * Builds a query string, including the question mark.
     *
     * @param array $data
     *
     * @return string
     */
    public static function buildQueryString(array $data = null, $urlEncode = true)
    {
        if ($data === null) {
            $data = static::$query;
        }

        $query = [];

        foreach ($data as $name => $value) {
            $query[] = "{$name}=" . ($urlEncode ? urlencode($value) : $value);
        }

        if (count($query)) {
            return '?' . implode('&', $query);
        }
    }

    /**
     * @return string
     */
    protected static function prepareBaseUrl()
    {
        $fileName = basename(static::$server->get('SCRIPT_FILENAME'));

        if ($fileName === basename(static::$server->get('SCRIPT_NAME'))) {
            $baseUrl = static::$server->get('SCRIPT_NAME');
        } elseif ($fileName === basename(static::$server->get('PHP_SELF'))) {
            $baseUrl = static::$server->get('PHP_SELF');
        } elseif ($fileName === basename(static::$server->get('ORIG_SCRIPT_NAME'))) {
            $baseUrl = static::$server->get('ORIG_SCRIPT_NAME');
        }

        if (strpos($baseUrl, '?') !== false) {
            $baseUrl = explode('?', $baseUrl)[0];
        }

        return rtrim(str_replace($fileName, '', $baseUrl), '/');
    }

    /**
     * @return string
     */
    protected static function prepareBasePath()
    {
        $fileName = basename(static::$server->get('SCRIPT_FILENAME'));
        $baseUrl  = static::$baseUrl;

        if (empty($baseUrl)) {
            return '';
        }

        if ($fileName === basename($baseUrl)) {
            $basePath = dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return rtrim($basePath, '/');
    }

    /**
     * @return string
     */
    public static function preparePathInfo()
    {
        $requestUri = static::$requestUri;
        $baseUrl    = static::$baseUrl;

        if ($baseUrl === null || $requestUri === null) {
            return '/';
        }

        $pathInfo = '/';

        // Remove the query string
        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        if (false === $pathInfo = substr($requestUri, strlen($baseUrl))) {
            return $requestUri;
        }

        return $pathInfo;
    }

    /**
     * @return string
     */
    protected static function prepareRequestUri()
    {
        $requestUri = '';

        // Microsoft IIS Rewrite Module
        if (static::$headers->has('X-Original-Url')) {
            $requestUri = static::$headers->get('X-Original-Url');
        }
        // IIS ISAPI_Rewrite
        elseif (static::$headers->has('X-Rewrite-Url')) {
            $requestUri = static::$headers->get('X-Rewrite-Url');
        }
        // IIS7 URL Rewrite
        elseif (static::$server->get('IIS_WasUrlRewritten') == '1' && static::$server->get('UNENCODED_URL') != '') {
            $requestUri = static::$server->get('UNENCODED_URL');
        }
        // HTTP proxy, request URI with scheme, host and port + the URL path
        elseif (static::$server->has('REQUEST_URI')) {
            $requestUri = static::$server->get('REQUEST_URI');
            $schemeAndHttpHost = static::schemeAndHttpHost();
            if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
            }
        }
        // IIS 5, PHP as CGI
        elseif (static::$server->has('ORIG_PATH_INFO')) {
            $requestUri = static::$server->get('ORIG_PATH_INFO');

            if (static::$queryString != '') {
                $requestUri .= '?' . static::$server->get('ORIG_PATH_INFO');
            }
        }

        static::$server->set('REQUEST_URI', $requestUri);
        return $requestUri;
    }

    /**
     * Get the instantiated request instance instead of creating a new one.
     *
     * @return Request
     */
    public static function getInstance()
    {
        return static::$instance;
    }
}
