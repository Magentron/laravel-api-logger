<?php
/**
 * ServerProvider.php - Laravel service provider for Laravel API Logger
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

namespace Magentron\ApiLogger\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Class ServiceProvider
 *
 * @package Magentron\ApiLogger\Providers
 */
class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->publishes([
            realpath(__DIR__ . '/../../config/api-logger.php') => config_path('api-logger.php'),
        ], 'config');
    }
}
