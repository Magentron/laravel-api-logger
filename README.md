# Laravel API Logger

[![Laravel 5.7](https://img.shields.io/badge/Laravel-5.7-green.svg)](https://github.com/laravel/framework/tree/5.7)
[![Laravel 5.6](https://img.shields.io/badge/Laravel-5.6-green.svg)](https://github.com/laravel/framework/tree/5.6)
[![Laravel 5.5](https://img.shields.io/badge/Laravel-5.5-green.svg)](https://github.com/laravel/framework/tree/5.5)
[![Laravel 5.4](https://img.shields.io/badge/Laravel-5.4-green.svg)](https://github.com/laravel/framework/tree/5.4)

[Laravel](https://laravel.com/) component to log API requests and responses to file.

# Requirements

Perhaps it works with lesser versions as well, but this is untested.

- PHP 5.6 or above
- Laravel 5.4 or above

# Installation

Add package via composer:

    composer require magentron/laravel-api-logger

For Laravel version < 5.5, edit `config/app.php`, add the following to
the `providers` array:

    Magentron\ApiLogger\Providers\ServiceProvider::class,

# Usage

    TODO
	
	
# TODO

- Use Monolog or other log writers to support other destinations than file system
- Restrict logging to specific HTTP methods and/or URI's
- Specify HTTP status codes to log
- Support other, non-JSON API's 
- Write tests and gain 100% code coverage

# Author
 
[Jeroen Derks](https://www.phpfreelancer.nl), a.k.a [Magentron](https://github.com/Magentron)

# Inspiration

This project was inspired by the following projects:

- [Spatie HTTP Logger](https://github.com/spatie/laravel-http-logger)
- [Prettus HTTP request logger](https://github.com/prettus/laravel-request-logger)

# License

laravel-api-logger is free software: you can redistribute it and/or
modify it under the terms of the GNU General Public License as published
by the Free Software Foundation, either version 3 of the License, or (at
your option) any later version.

laravel-api-logger is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with laravel-api-logger.  If not, see <http://www.gnu.org/licenses/>.
