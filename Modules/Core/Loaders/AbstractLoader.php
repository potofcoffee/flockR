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


use Peregrinus\Flockr\Core\AbstractModule;
use Peregrinus\Flockr\Core\Debugger;

class AbstractLoader
{
    /**
     * @var string $folder Folder name to look in (under module root, default: '')
     */
    protected static $folder = '';

    /**
     * @var string $fileNamePattern Pattern for file names to look for (default: '*')
     */
    protected static $fileNamePattern = '*';

    /**
     * @var bool $includeAbstract True if Abstract<Object> should be included (default: false)
     */
    protected static $includeAbstract = false;

    /**
     * @var bool $includeCore True if Core module is to be included (default: true)
     */
    protected static $includeCore = true;

    /**
     * Load all files this loader is responsible for
     * @return array Loader results
     */
    public static function loadAll() {
        $activeModules = \Peregrinus\Flockr\Core\ModuleLoader::getInstance()->getActiveModuleClasses(static::$includeCore);
        $result = [];
        foreach ($activeModules as $moduleClass) {
            $result = array_merge(static::loadForSingleModule($moduleClass), $result);
        }
        return $result;
    }

    /**
     * Load all files from a specific module
     * @param string $moduleClass Module class name
     * @return array Loader results
     */
    public static function loadForSingleModule($moduleClass) {
        $module = new $moduleClass();
        $result = [];
        $path = FLOCKR_basePath.'Modules/' . $module->moduleInfo['module'] . '/'.static::$folder.'/';
        if (file_exists($path)) {
            $result = array_merge(static::loadFromPath($path, $module), $result);
        }
        return $result;
    }

    /**
     * Load all files from a specific folder
     * @param string $path Path to folder
     * @param AbstractModule $module Module object
     */
    public static function loadFromPath($path, $module) {
        $result = [];
        $lookingFor = $path.static::$fileNamePattern;
        foreach (glob($lookingFor) as $file) {
            if ((static::$includeAbstract) || (substr(basename($file), 0, 8) !== 'Abstract')) {
                if ($singleResult = static::loadSingle($file, $module, $path)) $result[] = $singleResult;
            }
        }
        return $result;
    }

    /**
     * Load a single file
     * Needs to be overwritten by descendants
     * @abstract
     * @param string $file Path to file
     * @param AbstractModule $module Module object
     * @param path $file Path to folder
     * @return mixed Loader results
     */
    public static function loadSingle($file, $module, $path) {
        return $file;
    }


}