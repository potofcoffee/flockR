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


namespace Peregrinus\Flockr\Core\Loaders;


use Peregrinus\Flockr\Core\ConfigurationManager;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Loaders\AbstractLoader;
use Peregrinus\Flockr\Core\PortalModules\AbstractPortalModule;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class PortalModuleLoader extends AbstractLoader
{

    protected static $folder = 'PortalModules';
    protected static $fileNamePattern = '*PortalModule.php';

    /**
     * Get a single PortalModule
     * @param string $file Path to file
     * @param AbstractModule $module Module object
     * @param path $file Path to folder
     * @return string PortalModule class
     */
    public static function loadSingle($file, $module, $path) {
        return substr($module->moduleInfo['ns'].'PortalModules\\'.pathinfo($file, PATHINFO_FILENAME), 1);
    }

    /**
     * Load all PortalModules for a specific user (and position)
     * @param int $user User id
     * @param string $position
     * @return AbstractPortalModule[] Available modules
     */
    public static function load($user, $position='main') {
        $portalModules = self::loadAll();
        $result = [];
        // 1. filter modules by their own data:
        foreach ($portalModules as $portalModuleClass) {
            /** @var AbstractPortalModule $portalModule */
            $portalModule = new $portalModuleClass();
            if ($portalModule->availableForUser($user)) {
                $result[$portalModule->getKey()] = $portalModule;
            }
        }
        // 2. filter from configuration (Configuration/Portal.yaml)
        $config = ConfigurationManager::getInstance()->getConfigurationSet('Portal');
        $section = (LoginService::getInstance()->isGuest($user) ? 'guest' : 'user');
        $result2 = [];
        foreach ($config['modules'][$section][$position] as $portalModuleKey) {
            if (isset($result[$portalModuleKey])) $result2[$portalModuleKey]=$result[$portalModuleKey];
        }

        /*
         * 3. filter by exclusive action command
         * This will allow a portal module that get's an action to occupy the complete space of its column
         */
        if (isset($_REQUEST['portalModule'])) {
            $result3 = [];
            foreach ($result2 as $key => $module) {
                if (isset($_REQUEST['portalModule'][$key])) {
                    if ($module->hasExclusiveActions()) {
                        $result3[$key] = $module;
                    }
                }
            }
            if (count($result3)) $result2 = $result3;
        }


        return $result2;
    }



}