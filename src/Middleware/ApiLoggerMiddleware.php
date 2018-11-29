<?php
/**
 * ApiLoggerMiddleware.php - Laravel HTTP middleware for Laravel API Logger
 *
 * @author     Jeroen Derks <jeroen@derks.it>
 * @since      2018/Nov/28
 * @license    GPLv3 https://www.gnu.org/licenses/gpl.html
 * @copyright  Copyright (c) 2018 Jeroen Derks / Derks.IT
 * @url        https://github.com/Magentron/laravel-api-logger/
 *
 * This file is part of laravel-api-logger.
 *
 * laravel-api-logger is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or (at
 * your option) any later version.
 *
 * laravel-api-logger is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with laravel-api-logger.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Magentron\ApiLogger\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggerMiddleware
{
    /**
     * Should requests be logged?
     * @var boolean
     * @see config/api-logger.php
     */
    private $enabled;

    /**
     * Filename to log requests to.
     * @var string
     * @see config/api-logger.php
     */
    private $filename;

    /**
     * Pattern to hide in POST data, default: "'/((_token|password(_confirmation)?)=)([^&=]*)/'"
     * Regular expression of with everything except first match will be hidden.
     * @var string
     * @see config/api-logger.php
     */
    private $hidePattern;

    /**
     * Start time of script.
     * @var boolean
     * @see config/api-logger.php
     */
    private $start;

    /**
     * Type of log file rotation, default: "daily"
     * Supported values: daily, weekly, monthly, yearly.
     * @var string
     * @see config/api-logger.php
     */
    private $rotation;

    /**
     * Route prefix to log requests and responses for, default: "api.".
     * @var string
     * @see config/api-logger.php
     */
    private $routePrefix;

    /**
     * RequestsLogger constructor.
     * Load configuration.
     *
     */
    public function __construct()
    {
        $this->enabled     = config('api-logger.enabled',       true);
        $this->filename    = config('api-logger.filename',      storage_path('log/api.log'));
        $this->hidePattern = config('api-logger.hidePattern',   '/((_?token|password(_confirmation)?)=)([^&=]*)/');
        $this->rotation    = config('api-logger.rotation',      'daily');
        $this->routePrefix = config('api-logger.routePrefix',   'api.');
        $this->start       = LARAVEL_START;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $environment = App::environment();
        $force       = config('api-logger.force', false);

        // for testing do not rely on LARAVEL_START
        $isTesting = 'testing' === $environment;
        if ($isTesting || $force) {
            $this->start = microtime(true);
        }

        $response = $next($request);

        // do not log debugbar requests
        if (config('debugbar.enabled')) {
            $currentRoute = $request->route();
            if ($currentRoute && 'debugbar.' === substr($currentRoute->getName(), 0, 9)) {
                return $response;
            }
        }

        if ($this->enabled) {
            // only log processing time on non-production environments
            if ('production' !== $environment) {
                $response->header('X-ApiLogger-PT', round((microtime(true) - $this->start) * 1000));
            }

            // for testing do not rely on register shutdown
            if ($isTesting || $force) {
                $this->logRequest($request, $response);
            } else {
                register_shutdown_function(array($this, 'logRequest'), $request, $response);
            }
        }

        return $response;
    }

    /**
     * Log request to file.
     *
     * @param \Illuminate\Http\Request                   $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    public function logRequest(Request $request, Response $response)
    {
        // get current time
        $microtime = explode(' ', microtime());
        settype($microtime[1], 'integer');

        // get data to log
        $data = $this->getLogData($request, $response, $microtime);

        // set message to log
        $message = implode(' ', $data) . "\n";

        // get filename to use
        $filename = $this->getFilename($microtime[1]);

        // write log message
        error_log($message, 3, $filename);
    }

    /**
     * Get filename to use for log file.
     * @param  integer $time
     * @return string
     */
    public function getFilename($time)
    {
        $filename = $this->filename;
        $postfix  = $this->getFilenamePostfix($time);
        if (false !== $postfix)
        {
            $basename = basename($filename);
            $position = strrpos($basename, '.');
            if (false === $position) {
                $filename .= $postfix;
            } else {
                $position = strrpos($filename, '.');
                $filename = substr($filename, 0, $position) . $postfix . substr($filename, $position);
            }
        }

        return $filename;
    }

    /**
     * Retrieve log filename postfix based on rotation configuration.
     * @param  integer      $time
     * @return bool|string
     */
    public function getFilenamePostfix($time)
    {
        $postfix = false;

        switch ($this->rotation)
        {
            case 'daily':   $postfix = '-' . date('Y-m-d', $time); break;
            case 'weekly':  $postfix = '-' . date('Y-\wW', $time); break;
            case 'monthly': $postfix = '-' . date('Y-m',   $time); break;
            case 'yearly':  $postfix = '-' . date('Y',     $time); break;
        }

        return $postfix;
    }

    /**
     * Get data to log for request.
     * @param \Illuminate\Http\Request                   $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param array                                      $microtime
     * @return array
     */
    public function getLogData(Request $request, Response $response, array $microtime)
    {
        // get data to log
        $date        = date('Y/M/d H:i:s', $microtime[1]) . substr($microtime[0], 1, 4);
        $microtime   = $microtime[1] + $microtime[0];
        $userAgent   = $request->header('user_agent');
        $method      = $request->getMethod();
        $uri         = $request->getUri();
        $httpVersion = $request->getProtocolVersion();
        $content     = $request->getContent();
        $statusCode  = $response->getStatusCode();

        // set body according to status code, only for API routes and HTTP status codes 2xx, 403
        $body  = '<ignored>';
        $route = $request->route();

        if ($route && $this->routePrefix === substr($route->getName(), 0, strlen($this->routePrefix))) {
            if ('2' === substr((string) $statusCode, 0, 1) || 403 === $statusCode) {
                $body = $response->getContent();
                $body = '' == $body ? '-' : json_encode($body, JSON_UNESCAPED_SLASHES);
            }
        }

        // remove sensitive information from POST data
        if ($this->hidePattern) {
            $content = preg_replace($this->hidePattern, '$1******', $content  );
        }

        // prepare data to log
        $user = $request->user();
        $data = [
            'remote_addr'    => $request->ip() ?: '-',
            'remote_user'    => $user ? (isset($user->username) ? $user->username : $user->email) : (isset($_SERVER['REMOTE_USER']) ? "http:\"{$_SERVER['REMOTE_USER']}\"" : '-'),
            'date'           => "[{$date}]",
            'request'        => "\"{$method} {$uri} {$httpVersion}\"",
            'status'         => $statusCode,
            'content_length' => mb_strlen($body, '8bit'),
            'user_agent'     => $userAgent ? '"' . addslashes($userAgent) . '"' : '-',
            'response_time'  => sprintf('%0.1fms', ($microtime - $this->start) * 1000),
            'content'        => '' == $content ? '-' : json_encode($content, JSON_UNESCAPED_SLASHES),
            'body'           => $body,
        ];

        return $data;
    }
}
