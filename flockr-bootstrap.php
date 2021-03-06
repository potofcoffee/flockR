<?php
/*
 * FLOCKR
 * Multi-Purpose Church Administration Suite
 * http://github.com/potofcoffee/flockr
 * http://flockr.org
 *
 * Copyright (c) 2016+ Christoph Fischer (chris@toph.de)
 *
 * Parts copyright 2003-2015 Renzo Lauper, renzo@churchtool.org
 * FlockR is a fork from the kOOL project (www.churchtool.org). kOOL is available
 * under the terms of the GNU General Public License (see below).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// define some constants
define('FLOCKR_version', '0.0.1');
define('FLOCKR_software', 'FlockR ' . FLOCKR_version);

define('FLOCKR_debug', true);
define('FLOCKR_basePath', __DIR__ . '/');


require_once(FLOCKR_basePath.'vendor/autoload.php');
//\php_error\reportErrors();


define('FLOCKR_uploadPath', FLOCKR_basePath . 'Temp/Uploads/');
define('FLOCKR_NS', 'Peregrinus\\Flockr\\');
define('FLOCKR_baseUrl',
    (($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http')
    . '://' . $_SERVER['SERVER_NAME'] . dirname(str_replace(__DIR__, '', __FILE__)));

// error handling stuff:
if (FLOCKR_debug) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
} else {
    error_reporting(E_ERROR);
}

