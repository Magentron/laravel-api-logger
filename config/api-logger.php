<?php
/**
 * api-logger.php - Laravel default configuration for Laravel API Logger
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

return [
    'enabled'       => env('API_LOGGER_ENABLED', true),
    'enablePt'      => env('API_LOGGER_ENABLE_PT', 'production' !== env('APP_ENV')),
    'filename'      => env('API_LOGGER_FILENAME', 'api.log'),
    'force'         => env('API_LOGGER_FORCE', false),
    'hidePattern'   => env('API_LOGGER_HIDE_PATTERN', '/((_?token|password(_confirmation)?)=)([^&=]*)/'),
    'rotation'      => env('API_LOGGER_ROTATION', 'daily'),   // possible values: single, daily, weekly, monthly, yearly
    'routePrefix'   => env('API_LOGGER_ROUTE_PREFIX', 'api.'),
];
