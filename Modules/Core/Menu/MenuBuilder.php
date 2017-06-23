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


namespace Peregrinus\Flockr\Core\Menu;


class MenuBuilder
{
    private static $instance = null;

    public function __construct()
    {
    }

    /**
     * Get an instance of the menu builder
     * @return \Peregrinus\Flockr\Core\MenuBuilder Instance of MenuBuilder
     */
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the menu for all active modules
     * @return array Menu configuration
     */
    public function getMenuFromModules() {
        $menu = [];
        $activeModules = \Peregrinus\Flockr\Core\ModuleLoader::getInstance()->getActiveModuleClasses();
        \Peregrinus\Flockr\Core\Debugger::toFile($activeModules, 'activeModules');
        foreach ($activeModules as $module) {
            $menu = array_merge_recursive($module::getMenu(), $menu);
        }
        return $menu;
    }

    public function getMenu() {
        $items = [];

        // legacy menu
        $moduleLoader = \Peregrinus\Flockr\Core\ModuleLoader::getInstance();
        if ($moduleLoader->isPresent('legacy')) {
            $legacyMenuService = new \Peregrinus\Flockr\Legacy\Services\MenuService();
            $items = $legacyMenuService->getMenu();
        }

        // native flockr modules
        $items = array_merge_recursive($this->getMenuFromModules(), $items);

        \Peregrinus\Flockr\Core\Debugger::toFile($items, 'menu');

        $menu = [
            'items' => $items,
            'active' => [
                'module' => \Peregrinus\Flockr\Core\App::getInstance()->activeModule,
            ],
        ];

        return $menu;
    }

}