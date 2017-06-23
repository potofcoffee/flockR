<?php
/*
 * COMMUNIO
 * Multi-Purpose Church Administration Suite
 * http://github.com/VolksmissionFreudenstadt/communio
 *
 * Copyright (c) 2016+ Volksmission Freudenstadt, http://www.volksmission-freudenstadt.de
 * Author: Christoph Fischer, chris@toph.de
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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


namespace Peregrinus\Flockr\Core;


class ModuleLoader extends \Peregrinus\Flockr\Core\AbstractClass
{
    protected static $instance;

    /**
     * Get an instance of the module loader
     * @return \Peregrinus\Flockr\Core\ModuleLoader Module loader
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all modules as objects
     * @return array Modules
     */
    static public function getModules()
    {
        $mods = [];
        $tmp = glob(FLOCKR_basePath . 'Modules/*');
        foreach ($tmp as $mod) {
            $modName = pathinfo($mod, PATHINFO_FILENAME);
            $modClass = '\\Peregrinus\\Flockr\\' . $modName . '\\' . $modName . 'Module';
            if (class_exists($modClass)) {
                $mods[$modName] = new $modClass();
            }
        }
        return $mods;
    }

    /**
     * Get a list of all active modules by their class names
     * @param bool $includeCore Include core module?
     */
    public function getActiveModuleClasses($includeCore = false)
    {
        $confMan = \Peregrinus\Flockr\Core\ConfigurationManager::getInstance();
        $activeModules = $confMan->getConfigurationSet('Modules');
        \Peregrinus\Flockr\Core\Debugger::toFile($activeModules, 'activeModules2');
        $modules = [];
        foreach ($activeModules as $module => $active) {
            if ($active) {
                $modules[] = FLOCKR_NS.$module.'\\'.$module.'Module';
            }
        }
        if ($includeCore) $modules[] = FLOCKR_NS.'Core\\CoreModule';
        return $modules;
    }

    public function isInstalled($module) {
        return true;
    }

    public function isPresent($module) {
        $module = ucfirst($module);
        return file_exists(FLOCKR_basePath.'Modules/'.$module.'/'.$module.'Module.php');
    }


}