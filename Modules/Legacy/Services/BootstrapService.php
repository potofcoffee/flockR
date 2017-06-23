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


namespace Peregrinus\Flockr\Legacy\Services;


class BootstrapService
{

    public function __construct() {}

    public function run() {
        $this->setPathConstants();
    }

    private function setPathConstants() {
        global $ko_menu_akt, $ko_path;

        define('KOOL_basePath', FLOCKR_basePath);
        define('KOOL_inc', KOOL_basePath.'inc/');
        define('KOOL_api', KOOL_inc.'api/');
        define('KOOL_pluginPath', KOOL_basePath.'plugins/');
        define('KOOL_configPath', KOOL_basePath.'config/');
        define('KOOL_langPath', KOOL_basePath.'locallang/');

    }

}