<?php
/**
 * ApiLoggerMiddlewareTest.php - Laravel HTTP middleware test for Laravel API Logger
 *
 * @author     Jeroen Derks <jeroen@derks.it>
 * @since      2018/Nov/28
 * @license    GPLv3 https://www.gnu.org/licenses/gpl.html
 * @copyright  Copyright (c) 2018-2021 Jeroen Derks / Derks.IT
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

namespace Magentron\ApiLogger\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Magentron\ApiLogger\Middleware\ApiLoggerMiddleware;
use Magentron\ApiLogger\Providers\ServiceProvider;

class ApiLoggerMiddlewareTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        Config::set(include __DIR__ . '/../config/api-logger.php');

        $serviceProvider = new ServiceProvider($this->app);
        $serviceProvider->boot();
    }

    public function testGetFilename()
    {
        foreach (['/dev/null', storage_path('test/api_log'), storage_path('test/api.log')] as $filename) {
            Config::set('api-logger.filename', $filename);

            $filenameLength = strlen($filename);

            foreach (['single', 'daily', 'weekly', 'monthly', 'yearly'] as $rotation) {
                Config::set('api-logger.rotation', $rotation);

                // setup expectations
                $expectedFormat  = false;
                $expectedPostfix = false;

                switch ($rotation) {
                    case 'daily':
                        $expectedFormat = '-Y-m-d';
                        break;
                    case 'weekly':
                        $expectedFormat = '-Y-\wW';
                        break;
                    case 'monthly':
                        $expectedFormat = '-Y-m';
                        break;
                    case 'yearly':
                        $expectedFormat = '-Y';
                        break;
                }

                $microtime = microtime(true);
                if ($expectedFormat) {
                    $expectedPostfix = date($expectedFormat, $microtime);
                }

                // check filename postfix
                $requestsLogger = new ApiLoggerMiddleware();
                $postfix        = $requestsLogger->getFilenamePostfix($microtime);

                $this->assertEquals($expectedPostfix, $postfix, $rotation);

                // get filename
                $result       = $requestsLogger->getFilename(time());
                $resultLength = strlen($result);

                // check filename
                $message = "{$filename}({$filenameLength}) - {$rotation} - {$result}({$resultLength})";
                if ($expectedFormat) {
                    $dirnameLength = strlen(dirname($filename));
                    $position = strpos(basename($filename), '.');
                    if (false === $position) {
                        $postfix = substr($result, $filenameLength);
                    } else {
                        $postfix = substr($result, $dirnameLength + 1 + $position, $resultLength - $filenameLength);
                    }
                    $this->assertEquals($expectedPostfix, $postfix, "postfix - {$message}");
                } else {
                    $this->assertEquals($filename, $result, $message);
                }
            }
        }
    }

    public function testLogging()
    {
        $filename = sys_get_temp_dir() . '/' . str_replace('\\', '_', __CLASS__) . '-api.log';

        $enabled  = Config::get('api-logger.enabled');
        $rotation = Config::get('api-logger.rotation');

        Config::set('api-logger.filename', $filename);
        Config::set('api-logger.enabled', true);
        Config::set('api-logger.rotation', false);
        try {
            $requestsLogger = new ApiLoggerMiddleware();

            foreach (['post', 'put', 'patch', 'delete'] as $method) {
                $request = $this->makeRequest($method, $this->uri);

                $request->setRouteResolver(function () use ($method, $request) {
                    $name  = 'put' === $method ? 'api.put' : "web.{$method}";
                    $route = new Route($method, $this->uri, []);
                    $route->name($name);
                    return $route->bind($request);
                });

                $requestsLogger->handle($request, function () use ($method, $request) {
                    return new JsonResponse(['method' => $method]);
                });

                $size     = 'put' === $method ? 22 : 9;
                $method   = strtoupper($method);
                $expected = "] \"{$method} http://localhost/test-uri HTTP/1.1\" 200 {$size} \"Symfony";
                $lastLine = exec('tail -n 1 ' . escapeshellarg($filename));
                $this->assertContains($expected, $lastLine);
            }
        } finally {
            Config::set('api-logger.enabled', $enabled);
            Config::set('api-logger.rotation', $rotation);

            @unlink($filename);
        }
    }
}
