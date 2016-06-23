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

use Avalon\Routing\Router;

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
     * Holds the $_COOKIE array.
     *
     * @var ParameterBag
     */
    public static $cookies;

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
    public static $method;

    /**
     * @var string
     */
    public static $pathInfo;

    /**
     * @var string
     */
    public static $requestUri;

    /**
     * @var string
     */
    public static $requestPath;

    /**
     * @var string
     */
    public static $basePath;

    /**
     * @var string
     */
    public static $baseUrl;

    public function __construct()
    {
        static::init();
    }

    /**
     * Sets up the request.
     */
    public static function init()
    {
        static::$query      = new ParameterBag($_GET);
        static::$post       = new ParameterBag($_POST);
        static::$properties = new ParameterBag;
        static::$server     = new ParameterBag($_SERVER);
        static::$cookies    = new ParameterBag($_COOKIE);
        static::$files      = $_FILES; // Need to make a custom ParameterBag for this.
        static::$headers    = static::buildHeaderBag();
        static::$method     = static::method();

        static::$baseUrl     = static::prepareBaseUrl();
        static::$basePath    = static::prepareBasePath();
        static::$requestUri  = static::prepareRequestUri();
        static::$pathInfo    = static::preparePathInfo();
    }

    /**
     * Reset the class variables to null.
     */
    public static function reset()
    {
        static::$query      = null;
        static::$post       = null;
        static::$properties = null;
        static::$server     = null;
        static::$cookies    = null;
        static::$files      = null;
        static::$headers    = null;
        static::$method     = null;

        static::$pathInfo    = null;
        static::$requestUri  = null;
        static::$requestPath = null;
        static::$basePath    = null;
        static::$baseUrl     = null;

    }

    /**
     * Check if there is a flash message, or messages, set.
     *
     * @param string $name
     *
     * @return boolean
     */
    public static function hasFlash($name)
    {
        return isset($_SESSION['flashMessages'][$name]);
    }

    /**
     * Get the flash message(s).
     *
     * @param string $name
     *
     * @return array
     */
    public static function getFlash($name)
    {
        $messages = $_SESSION['flashMessages'][$name];
        unset($_SESSION['flashMessages'][$name]);
        return $messages;
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
        foreach (Router::$tokens as $token => $value) {
            $path = str_replace("{{$token}}", $value, $path);
        }

        return preg_match("#^{$path}$#", static::$pathInfo);
    }

    /**
     * @return string
     */
    public static function method()
    {
        if (!static::$method) {
            if (static::$post->has('_method')) {
                static::$method = strtoupper(static::$post->get('_method'));
            } else {
                static::$method = $_SERVER['REQUEST_METHOD'];
            }
        }

        return static::$method;
    }

    /**
     * @return boolean
     */
    public static function isXhr()
    {
        return static::$headers->get('X-Requested-With') == 'XMLHttpRequest';
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

        return (int) static::$server->get('SERVER_PORT');
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
        if (!static::$basePath) {
            static::$basePath = static::prepareBasePath();
        }

        return static::$basePath . ($append ? '/' . ltrim($append, '/') : '');
    }

    /**
     * @return string
     */
    public static function baseUrl($append = null)
    {
        if (!static::$baseUrl) {
            static::$baseUrl = static::prepareBaseUrl();
        }

        return static::$baseUrl . ($append ? '/' . ltrim($append, '/') : '');
    }

    /**
     * @return string
     */
    public static function pathInfo()
    {
        if (!static::$pathInfo) {
            static::$pathInfo = static::preparePathInfo();
        }

        return static::$pathInfo;
    }

    /**
     * @return string
     */
    public static function requestUri()
    {
        if (!static::$requestUri) {
            static::$requestUri = static::prepareRequestUri();
        }

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
            if ($value !== null) {
                $query[] = "{$name}=" . ($urlEncode ? urlencode($value) : $value);
            } else {
                $query[] = $name;
            }
        }

        if (count($query)) {
            return implode('&', $query);
        }
    }

    // =========================================================================

    /*!
     * The following methods are derived from code of the Zend Framework (1.10dev - 2010-01-24)
     *
     * Code subject to the new BSD license (http://framework.zend.com/license/new-bsd).
     *
     * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
     */

    /*
     * Returns the prefix as encoded in the string when the string starts with
     * the given prefix, false otherwise.
     *
     * @param string $string The urlencoded string
     * @param string $prefix The prefix not encoded
     *
     * @return string|false The prefix as it is encoded in $string, or false
     */
    protected static function urlencodedPrefix($string, $prefix)
    {
        if (0 !== strpos(rawurldecode($string), $prefix)) {
            return false;
        }

        $len = strlen($prefix);

        if (preg_match(sprintf('#^(%%[[:xdigit:]]{2}|.){%d}#', $len), $string, $match)) {
            return $match[0];
        }

        return false;
    }

    protected static function prepareRequestUri()
    {
        $requestUri = '';

        if (static::$headers->has('X_ORIGINAL_URL')) {
            // IIS with Microsoft Rewrite Module
            $requestUri = static::$headers->get('X_ORIGINAL_URL');
            static::$headers->remove('X_ORIGINAL_URL');
            static::$server->remove('HTTP_X_ORIGINAL_URL');
            static::$server->remove('UNENCODED_URL');
            static::$server->remove('IIS_WasUrlRewritten');
        } elseif (static::$headers->has('X_REWRITE_URL')) {
            // IIS with ISAPI_Rewrite
            $requestUri = static::$headers->get('X_REWRITE_URL');
            static::$headers->remove('X_REWRITE_URL');
        } elseif (static::$server->get('IIS_WasUrlRewritten') == '1' && static::$server->get('UNENCODED_URL') != '') {
            // IIS7 with URL Rewrite: make sure we get the unencoded URL (double slash problem)
            $requestUri = static::$server->get('UNENCODED_URL');
            static::$server->remove('UNENCODED_URL');
            static::$server->remove('IIS_WasUrlRewritten');
        } elseif (static::$server->has('REQUEST_URI')) {
            $requestUri = static::$server->get('REQUEST_URI');
            // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path, only use URL path
            $schemeAndHttpHost = static::schemeAndHttpHost();
            if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
            }
        } elseif (static::$server->has('ORIG_PATH_INFO')) {
            // IIS 5.0, PHP as CGI
            $requestUri = static::$server->get('ORIG_PATH_INFO');
            if ('' != static::$server->get('QUERY_STRING')) {
                $requestUri .= '?'.static::$server->get('QUERY_STRING');
            }
            static::$server->remove('ORIG_PATH_INFO');
        }

        // normalize the request URI to ease creating sub-requests from this request
        static::$server->set('REQUEST_URI', $requestUri);

        return $requestUri;
    }

    /**
     * Prepares the base URL.
     *
     * @return string
     */
    protected static function prepareBaseUrl()
    {
        $filename = basename(static::$server->get('SCRIPT_FILENAME'));

        if (basename(static::$server->get('SCRIPT_NAME')) === $filename) {
            $baseUrl = static::$server->get('SCRIPT_NAME');
        } elseif (basename(static::$server->get('PHP_SELF')) === $filename) {
            $baseUrl = static::$server->get('PHP_SELF');
        } elseif (basename(static::$server->get('ORIG_SCRIPT_NAME')) === $filename) {
            $baseUrl = static::$server->get('ORIG_SCRIPT_NAME'); // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = static::$server->get('PHP_SELF', '');
            $file = static::$server->get('SCRIPT_FILENAME', '');
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/'.$seg.$baseUrl;
                ++$index;
            } while ($last > $index && (false !== $pos = strpos($path, $baseUrl)) && 0 != $pos);
        }

        // Does the baseUrl have anything in common with the request_uri?
        $requestUri = static::requestUri();

        if ($baseUrl && false !== $prefix = static::urlencodedPrefix($requestUri, $baseUrl)) {
            // full $baseUrl matches
            return $prefix;
        }

        if ($baseUrl && false !== $prefix = static::urlencodedPrefix($requestUri, rtrim(dirname($baseUrl), '/'.DIRECTORY_SEPARATOR).'/')) {
            // directory portion of $baseUrl matches
            return rtrim($prefix, '/'.DIRECTORY_SEPARATOR);
        }

        $truncatedRequestUri = $requestUri;
        if (false !== $pos = strpos($requestUri, '?')) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);
        if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) {
            // no match whatsoever; set it blank
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if (strlen($requestUri) >= strlen($baseUrl) && (false !== $pos = strpos($requestUri, $baseUrl)) && $pos !== 0) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return rtrim($baseUrl, '/'.DIRECTORY_SEPARATOR);
    }

    /**
     * Prepares the base path.
     *
     * @return string base path
     */
    protected static function prepareBasePath()
    {
        $filename = basename(static::$server->get('SCRIPT_FILENAME'));
        $baseUrl = static::baseUrl();
        if (empty($baseUrl)) {
            return '';
        }

        if (basename($baseUrl) === $filename) {
            $basePath = dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        if ('\\' === DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return rtrim($basePath, '/');
    }

    /**
     * Prepares the path info.
     *
     * @return string path info
     */
    protected static function preparePathInfo()
    {
        $baseUrl = static::baseUrl();

        if (null === ($requestUri = static::requestUri())) {
            return '/';
        }

        $pathInfo = '/';

        // Remove the query string from REQUEST_URI
        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        $pathInfo = substr($requestUri, strlen($baseUrl));
        if (null !== $baseUrl && (false === $pathInfo || '' === $pathInfo)) {
            // If substr() returns false then PATH_INFO is set to an empty string
            return '/';
        } elseif (null === $baseUrl) {
            return $requestUri;
        }

        return (string) $pathInfo;
    }
}
