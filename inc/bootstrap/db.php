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

//Connect to the database
//$db_conn = mysql_connect($mysql_server, $mysql_user, $mysql_pass);
$db_conn = mysql_connect(
    $database['host'],
    $database['user'],
    $database['pass']
);
//Test the connection
$db_connection = $db_conn !== false;
mysql_select_db($database['name']);
mysql_query("SET SESSION sql_mode = ''");

//Set client-server connection to latin1
if ($ko_menu_akt != 'install') {
    mysql_query('SET NAMES utf8');
    mysql_set_charset('utf8',$db_conn);
}



//No DB-connection and not the install-tool is running
if (!$db_connection && $ko_menu_akt != "install") {
    print '<div align="center" style="font-weight:900;color:red;">';
    print getLL("error_no_db_1") . "<br /><br />";
    print getLL("error_no_db_2");
    print '</div>';
    print '<ul>';
    print '<li>' . getLL("error_no_db_reason_1") . '</li>';
    print '<li>' . getLL("error_no_db_reason_2") . '</li>';
    print '<li>' . getLL("error_no_db_reason_3") . '</li>';
    print '</ul>';
    exit;
}

