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


namespace Peregrinus\Flockr\Agenda;


use Peregrinus\Flockr\Core\AbstractModule;

use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Services\HookService;
use Peregrinus\Flockr\Core\Utility\MenuUtility;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class AgendaModule extends AbstractModule
{

    public function init()
    {

        $hooks = HookService::getInstance();

        // register the menu
//        $hooks->addFilter('build_menu', [$this, 'registerMenu']);

        // register the sidebar
        $hooks->addFilter('build_sidebar', [$this, 'registerSidebar'], 11, 3);
    }

    public function registerMenu($menu)
    {
        global $ko_path, $smarty;
        global $ko_menu_akt, $access;
        global $my_submenu;

        $user = LoginService::getInstance()->getUserId();

        $all_rights = ko_get_access_all('rota_admin', '', $max_rights);
        if ($max_rights < 1) return FALSE;
        ko_get_access('daten');

        if (ko_get_userpref($user, 'modules_dropdown')) {
            // settings
            if (LoginService::getInstance()->isLoggedIn()) {
                $menu['rota']['menu'][] = [
                    'name' => 'Gottesdienstplan',
                    'link' => FLOCKR_baseUrl.'agenda/service/list'
                ];
            }
        }


        return $menu;
    }


    public function registerSidebar($sidebar, $moduleKey, $position)
    {
        if (($moduleKey == 'rota') && ($position == 'left')) {
        }
        return $sidebar;
    }
}