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




//Get base_path from _SERVER if not set during first installation
if ($ko_menu_akt == "install" && !$BASE_PATH) {
    $bdir = str_replace("install", "", dirname($_SERVER['SCRIPT_NAME']));
    $droot = $_SERVER["DOCUMENT_ROOT"];
    if (substr($droot, -1) == "/") {
        $droot = substr($droot, 0, -1);
    }
    $BASE_PATH = $droot . $bdir;
}
if ($BASE_PATH != "" && substr($BASE_PATH, -1) != "/") {
    $BASE_PATH .= "/";
}

define ('KOOL_baseUrl', $BASE_PATH);