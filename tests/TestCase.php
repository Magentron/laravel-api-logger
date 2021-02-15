<?php
/**
 * TestCase.php - Laravel base test case for Laravel API Logger (modified from spatie/laravel-http-logger)
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

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase as Orchestra;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Class TestCase
 *
 * @package Magentron\ApiLogger\Tests
 *
 * @codeCoverageIgnore
 */
class TestCase extends Orchestra
{
    /**
     * @var string
     */
    protected $uri = '/test-uri';

    /**
     * @var Container
     */
    protected $container;

    /**
     * TestCase constructor.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->fakeStorage();

        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    /**
     * Fake storage for while loading configs.
     */
    protected function fakeStorage()
    {
        $path        = realpath(__DIR__ . '/laravel-api-logger/');
        $pathStorage = $path . '/storage';

        $this->container = Container::getInstance();
        $this->container->instance('path.storage', $pathStorage);
    }

    protected function makeRequest(
        $method,
        $uri,
        $parameters = [],
        $cookies = [],
        $files = [],
        $server = [],
        $content = null
    ) {
        $files = array_merge($files, $this->extractFilesFromDataArray($parameters));

        return Request::createFromBase(
            SymfonyRequest::create(
                $this->prepareUrlForRequest($uri),
                $method,
                $parameters,
                $cookies,
                $files,
                array_replace($this->serverVariables, $server),
                $content
            )
        );
    }
}
