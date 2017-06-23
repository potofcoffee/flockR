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


namespace Peregrinus\Flockr\Core\Utility;


use Peregrinus\Flockr\Legacy\Services\LoginService;

class MenuUtility
{

    /**
     * Build a top menu item
     * @param string $moduleKey Module key
     * @return array Menu
     */
    public static function topMenu($moduleKey)
    {
        $action = ko_get_userpref(LoginService::getInstance()->getUserId(), 'default_view_'.$moduleKey);
        if (!$action) {
            $action = ko_get_setting('default_view_'.$moduleKey);
        }
        return [
            'id' => $moduleKey,
            'name' => getLL('module_'.$moduleKey),
            'link' => $moduleKey.'/index.php?action='.$action,
            'menu' => [],
        ];
    }

    /**
     * Build a single menu item
     * @param string $moduleKey Module key
     * @param string $action Action
     * @param string $alternateTitle Alternate title (LL key)
     * @return array Menu
     */
    public static function menuItem ($moduleKey, $action, $alternateTitle) {
        return [
            'name' => ($alternateTitle ? getLL($alternateTitle) : ko_menuitem($moduleKey, $action)),
            'link' => ($action ? $moduleKey.'/index.php?action='.$action : ''),
        ];
    }
}