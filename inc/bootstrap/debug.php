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

if (defined('DEBUG') && DEBUG) {
    //start output with: if(defined('DEBUG') && DEBUG) $profiler->display($DEBUG_db);

    require($ko_path . 'pqp/classes/PhpQuickProfiler.php');
    $debugMode = true;
    $profiler = new PhpQuickProfiler(PhpQuickProfiler::getMicroTime(), 'web_test/pqp/');

    class pqp_db
    {
        var $queryCount = 0;
        var $queries = array();

        function query($sql)
        {
            return mysql_query($sql);
        }
    }

    ;
    $DEBUG_db = new pqp_db;

    define('DEBUG_SELECT', true);
    define('DEBUG_UPDATE', true);
    define('DEBUG_INSERT', true);
    define('DEBUG_DELETE', true);
}
