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


use Peregrinus\Flockr\Core\Debugger;

class MenuService
{

    protected $permissionsService = null;

    public function __construct()
    {
        $this->setPermissionsService(new \Peregrinus\Flockr\Legacy\Services\PermissionsService());
    }

    public function getMenu()
    {
        $translationService = \Peregrinus\Flockr\Core\App::getInstance()->getTranslationService();

        $configService = \Peregrinus\Flockr\Legacy\Services\ConfigurationService::getInstance();
        $config = $configService->getConfig();

        $preferencesService = \Peregrinus\Flockr\Core\App::getInstance()->getPreferencesService();
        $userMenu = explode(',', $preferencesService->getUserPreferenceValue('menu_order'));
        if (!is_array($userMenu)) return [];
        $userMenu = array_merge($userMenu, array_diff($config['modules'], $userMenu));

        if (is_array($userMenu)) {
            $modulesService = new \Peregrinus\Flockr\Legacy\Services\ModulesService();

            $menuCtr = 0;
            foreach ($userMenu as $moduleName) {
                // check if module is enabled
                if (!in_array($moduleName, $config['modules'])) {
                    continue;
                }
                // skip certain modules
                if (in_array($moduleName, array('sms', 'kg', 'mailing')) || trim($moduleName) == '') {
                    continue;
                }
                // skip plugin menus
                if (substr($moduleName, 0, 3) == 'my_') {
                    continue;
                }
                // Tools menu only for root user
                if ($moduleName == 'tools' && \Peregrinus\Flockr\Core\App::getInstance()->getSecurityContext()->getUser()->getLogin() != 'root') {
                    continue;
                }

                // check if this module is installed an enabled for user
                if ($modulesService->isInstalled($moduleName)) {
                    // TODO: build menu
                    $menu[$menuCtr]['id'] = $moduleName;
                    $menu[$menuCtr]['name'] = $translationService->translate('module_' . $moduleName);
                    $defaultAction = $preferencesService->getPreferenceValue('default_view_' . $moduleName);

                    //Handle special links (e.g. webfolders)
                    if (substr($defaultAction, 0, 8) == 'SPECIAL_') {
                        switch (substr($defaultAction, 8)) {
                            case 'webfolder':
                                $menu[$menuCtr]['link'] = '';
                                $menu[$menuCtr]['link_param'] = 'FOLDER="' . FLOCKR_baseUrl . str_replace(FLOCKR_basePath,
                                        '',
                                        $config['WEBFOLDERS_BASE']) . '" style="behavior: url(#default#AnchorClick);"';
                                break;
                        }
                    } else {
                        $menu[$menuCtr]['link'] = FLOCKR_baseUrl . $moduleName . '/index.php?action=$defaultAction';
                    }

                    // build submenu
                    $subMenuMethod = 'getModuleSubMenuFor' . ucfirst($moduleName);
                    if (method_exists($this, $subMenuMethod)) {
                        $needSubMenus = $this->getAvailableSubMenus($config, $moduleName . '_dropdown');
                        $subMenu = $this->$subMenuMethod($needSubMenus);


                    }
                }
            }
        }
        //require_once FLOCKR_basePath.'menu.php';
        return $menu;
    }

    protected function getAvailableSubMenus($config, $type)
    {
        //$access has to stand here so it is available in the plugins which are included below with hook_include_sm()
        global $access;
        global $my_submenu;

        $subMenu['daten_left'] = array('termine', 'termingruppen', 'export', 'reminder');
        $subMenu['daten_right'] = array('filter', 'itemlist_termingruppen');
        $subMenu['daten'] = array_merge($subMenu['daten_left'], $subMenu['daten_right']);
        $subMenu['daten_dropdown'] = array('termine', 'termingruppen', 'reminder');

        $subMenu['leute_left'] = array('leute', 'schnellfilter', 'meine_liste', 'aktionen', 'kg');
        $subMenu['leute_right'] = array('filter', 'itemlist_spalten', 'itemlist_spalten_kg', 'itemlist_chart');
        $subMenu['leute'] = array_merge($subMenu['leute_left'], $subMenu['leute_right']);
        $subMenu['leute_dropdown'] = array('leute,kg');

        $subMenu['reservation_left'] = array('reservationen', 'objekte', 'export');
        $subMenu['reservation_right'] = array('filter', 'objektbeschreibungen', 'itemlist_objekte');
        $subMenu['reservation'] = array_merge($subMenu['reservation_left'], $subMenu['reservation_right']);
        $subMenu['reservation_dropdown'] = array('reservationen', 'objekte');

        $subMenu['rota_left'] = array('rota', 'itemlist_teams', 'itemlist_eventgroups');
        $subMenu['rota_right'] = array();
        $subMenu['rota'] = array_merge($subMenu['rota_left'], $subMenu['rota_right']);
        $subMenu['rota_dropdown'] = array('rota');

        $subMenu['admin_left'] = array('allgemein', 'logins', 'news', 'logs');
        $subMenu['admin_right'] = array('filter');
        $subMenu['admin'] = array_merge($subMenu['admin_left'], $subMenu['admin_right']);
        $subMenu['admin_dropdown'] = array('allgemein', 'logins', 'logs', 'news');

        if (ENABLE_FILESHARE) {
            $subMenu['fileshare_left'] = array('foldertree', 'shares', 'webfolders');
            $subMenu['fileshare_dropdown'] = array('shares', 'webfolders');
        } else {
            $subMenu['fileshare_left'] = array('webfolders');
            $subMenu['fileshare_dropdown'] = array('webfolders');
        }
        $subMenu['fileshare_right'] = array();
        $subMenu['fileshare'] = array_merge($subMenu['fileshare_left'], $subMenu['fileshare_right']);

        $subMenu['tools_left'] = array('submenus', 'leute-db', 'ldap', 'locallang', 'plugins', 'scheduler', 'typo3');
        $subMenu['tools_right'] = array();
        $subMenu['tools'] = array_merge($subMenu['tools_left'], $subMenu['tools_right']);
        $subMenu['tools_dropdown'] = array(
            'submenus',
            'leute-db',
            'ldap',
            'locallang',
            'plugins',
            'scheduler',
            'typo3'
        );

        $subMenu['tapes_left'] = array('tapes', 'series', 'groups');
        $subMenu['tapes_right'] = array('print', 'filter', 'settings');
        $subMenu['tapes'] = array_merge($subMenu['tapes_left'], $subMenu['tapes_right']);
        $subMenu['tapes_dropdown'] = array('tapes', 'series', 'groups');

        $subMenu['groups_left'] = array('groups', 'roles', 'export', 'rights', 'dffilter');
        $subMenu['groups_right'] = array();
        $subMenu['groups'] = array_merge($subMenu['groups_left'], $subMenu['groups_right']);
        $subMenu['groups_dropdown'] = array('groups', 'roles', 'rights');

        $subMenu['donations_left'] = array('donations', 'accounts', 'export');
        $subMenu['donations_right'] = array('filter', 'itemlist_accounts');
        $subMenu['donations'] = array_merge($subMenu['donations_left'], $subMenu['donations_right']);
        $subMenu['donations_dropdown'] = array('donations', 'accounts');

        $subMenu['tracking_left'] = array('trackings', 'filter', 'export');
        $subMenu['tracking_right'] = array('itemlist_trackinggroups');
        $subMenu['tracking'] = array_merge($subMenu['tracking_left'], $subMenu['tracking_right']);
        $subMenu['tracking_dropdown'] = array('trackings');

        $subMenu['projects_left'] = array('projects', 'filter', 'hosting', 'stats');
        $subMenu['projects_right'] = array();
        $subMenu['projects'] = array_merge($subMenu['projects_left'], $subMenu['projects_right']);
        $subMenu['projects_dropdown'] = array('projects', 'hosting');


        //HOOK: Include submenus from plugins
        //$hooks = hook_include_sm();
        //if(sizeof($hooks) > 0) foreach($hooks as $hook) include($hook);


        if (isset($config['GSM_SUBMENUS'])) {
            $gsm = $config['GSM_SUBMENUS'];
        } else {
            //Only show notes submenu if userpref is activated
            if (\Peregrinus\Flockr\Core\App::getInstance()
                    ->getPreferencesService()
                    ->getUserPreferenceValue('show_notes') == 1
            ) {
                $gsm = array('gsm_notizen');
            } else {
                $gsm = array();
            }
        }

        if ($subMenu[$type]) {
            if (substr($type, -6) == '_right' || substr($type, -9) == '_dropdown') {
                return $subMenu[$type];
            } else {
                return array_merge($subMenu[$type], $gsm);
            }
        } else {
            return array();
        }
    }

    /**
     * @param array $needSubMenus Needed subMenus
     * @return array Menu configuration
     */
    protected function getModuleSubMenuForDaten($needSubMenus)
    {
        $permissionsService = $this->getPermissionsService();
        $translationService = \Peregrinus\Flockr\Core\App::getInstance()
            ->getTranslationService();
        $accessLevel = $permissionsService->getAllAccessRights('daten_admin');
        $maxAccessLevel = $permissionsService->getAllAccessRights('daten_admin', null, true);
        foreach ($needSubMenus as $menu) {
            $found = false;
            $itemcounter = 0;
            switch ($menu) {

                case "termine":
                    $found = true;
                    $subMenu[$menucounter]["titel"] = $translationService->translate("submenu_daten_title_termine");

                    if ($max_rights > 1 && db_get_count('ko_eventgruppen', 'id', "AND `type` = '0'") > 0) {
                        $subMenu[$menucounter]["output"][$itemcounter] = ko_menuitem("daten", "neuer_termin");
                        $subMenu[$menucounter]["link"][$itemcounter++] = $ko_path . "daten/index.php?action=neuer_termin";
                    }
                    if ($max_rights > 0) {
                        $subMenu[$menucounter]["output"][$itemcounter] = ko_menuitem("daten", "all_events");
                        $subMenu[$menucounter]["link"][$itemcounter++] = $ko_path . "daten/index.php?action=all_events";
                        $subMenu[$menucounter]["output"][$itemcounter] = ko_menuitem("daten", "calendar");
                        $subMenu[$menucounter]["link"][$itemcounter++] = $ko_path . "daten/index.php?action=calendar";
                    }
                    if ($max_rights > 3 || ($max_rights > 1 && $_SESSION["ses_userid"] != ko_get_guest_id())) {
                        if ($max_rights > 3) {
                            if ($all_rights >= 4) {  //Moderator for all cals/groups
                                $where = ' AND 1=1 ';
                            } else {  //Only moderating several cals/groups
                                //Get Admin-rights if not already set
                                if (!isset($access['daten'])) {
                                    ko_get_access('daten');
                                }
                                $show_egs = array();
                                $egs = db_select_data('ko_eventgruppen', "WHERE `type` = '0'", '*');
                                foreach ($egs as $gid => $eg) {
                                    if ($access['daten'][$gid] > 3) {
                                        $show_egs[] = $eg['id'];
                                    }
                                }
                                $where = sizeof($show_egs) > 0 ? " AND `eventgruppen_id` IN ('" . implode("','",
                                        $show_egs) . "') " : ' AND 1=2 ';
                            }
                        } else {
                            //Apply filter for user_id for non-moderators
                            $where = " AND `_user_id` = '" . $_SESSION["ses_userid"] . "' ";
                        }
                        $num_mod = db_get_count("ko_event_mod", "id", $where);
                        $subMenu[$menucounter]["output"][$itemcounter] = ko_menuitem("daten",
                                "list_events_mod") . " ($num_mod)";
                        $subMenu[$menucounter]["link"][$itemcounter++] = $num_mod > 0 ? $ko_path . "daten/index.php?action=list_events_mod" : "";
                    }

                    if ($max_rights > 0 && $_SESSION['ses_userid'] != ko_get_guest_id()) {
                        $subMenu[$menucounter]['output'][$itemcounter] = ko_menuitem('daten', 'daten_settings');
                        $subMenu[$menucounter]['link'][$itemcounter++] = $ko_path . 'daten/index.php?action=daten_settings';

                        $subMenu[$menucounter]['output'][$itemcounter++] = '';
                        $subMenu[$menucounter]['output'][$itemcounter] = ko_menuitem('daten', 'ical_links');
                        $subMenu[$menucounter]['link'][$itemcounter++] = $ko_path . 'daten/index.php?action=daten_ical_links';
                    }
                    break;
            }
        }
        return [];
    }

    /**
     * @return \Peregrinus\Flockr\Legacy\Services\PermissionsService PermissionsService
     */
    public function getPermissionsService()
    {
        return $this->permissionsService;
    }

    /**
     * @param \Peregrinus\Flockr\Legacy\Services\PermissionsService $permissionsService PermissionsService
     */
    public function setPermissionsService(\Peregrinus\Flockr\Legacy\Services\PermissionsService $permissionsService)
    {
        $this->permissionsService = $permissionsService;
    }

    protected function getModuleSubMenuForReservation($needSubMenus)
    {
    }

    protected function getModuleSubMenuForLeute($needSubMenus)
    {
    }

    protected function getModuleSubMenuForGroups($needSubMenus)
    {
    }

    protected function getModuleSubMenuForRota($needSubMenus)
    {
    }

    protected function getModuleSubMenuForAdmin($needSubMenus)
    {
    }

    protected function getModuleSubMenuForTools($needSubMenus)
    {
    }
}