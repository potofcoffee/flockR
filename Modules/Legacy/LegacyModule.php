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


namespace Peregrinus\Flockr\Legacy;


use Peregrinus\Flockr\Core\Services\HookService;
use Peregrinus\Flockr\Core\Services\SettingsService;

class LegacyModule extends AbstractLegacyModule
{

    public function init() {
        $hooks = HookService::getInstance();
        $hooks->addFilter('build_menu', [$this, 'registerMenu'], 5);
    }

    public function registerMenu($menu) {
        global $MODULES;

        $settings = SettingsService::getInstance();
        $doDropdown = $settings->getUserPreference('modules_dropdown');

        $userModules = explode(",", $settings->getUserPreference('menu_order'));
        $userModules = array_merge($userModules, array_diff($MODULES, $userModules));

        $module = 0;
        foreach ($userModules as $module) {
            if (!in_array($module, $MODULES)) {
                continue;
            }
            if (in_array($module, array('sms', 'kg', 'mailing')) || trim($module) == '') {
                continue;
            }
            if (substr($module, 0, 3) == 'my_') {
                continue;
            }  //Don't show menus from plugins in main navigation (yet)
            if ($module == 'tools' && $_SESSION['ses_userid'] != ko_get_root_id()) {
                continue;
            }
            if (ko_module_installed($module)) {
                // check for modules already converter to new style ...
                if (!(in_array($module, ['rota']))) {
                    $menu[$module]["id"] = $module;
                    $menu[$module]["name"] = getLL("module_" . $module);
                    $action = ko_get_userpref($_SESSION["ses_userid"], "default_view_" . $module);
                    if (!$action) {
                        $action = ko_get_setting("default_view_" . $module);
                    }
                    //Handle special links (e.g. webfolders)
                    if (substr($action, 0, 8) == "SPECIAL_") {
                        switch (substr($action, 8)) {
                            case "webfolder":
                                $menu[$module]["link"] = "";
                                $menu[$module]["link_param"] = 'FOLDER="' . $BASE_URL . str_replace($BASE_PATH,
                                        "",
                                        $WEBFOLDERS_BASE) . '" style="behavior: url(#default#AnchorClick);"';
                                break;
                        }
                    } else {
                        $menu[$module]["link"] = $ko_path . $module . "/index.php?action=$action";
                    }

                    //Dropdown-Menu
                    if ($doDropdown == "ja") {
                        $sm = null;
                        //Get submenu-array
                        if (function_exists('submenu_' . $module)) {
                            eval(("\$dd_sm = submenu_" . $module . '("' . implode(",",
                                    ko_get_submenus(($module . "_dropdown"))) . '", "", "open", 3);'));
                            //Get open user-submenus in the right order
                            $user_sm = array_merge(explode(",",
                                ko_get_userpref($_SESSION["ses_userid"], "submenu_" . $module . "_left")),
                                explode(",",
                                    ko_get_userpref($_SESSION["ses_userid"], "submenu_" . $module . "_right")));
                            //Each entry is single submenu
                            foreach ($user_sm as $usm) {
                                $entry = null;
                                foreach ($dd_sm as $dd) {
                                    if ($usm == $dd["id"]) {
                                        $entry = $dd;
                                    }
                                }
                                if (!$entry) {
                                    continue;
                                }

                                $sm[] = array("name" => $entry["titel"], "link" => "");
                                //Each non-empty output-element ist one entry from the submenu with a corresponding link-entry
                                foreach ($entry["output"] as $e_i => $e) {
                                    if ($e) {
                                        $sm[] = array("name" => $e, "link" => $entry["link"][$e_i]);
                                    }
                                }
                            }
                            $menu[$module]["menu"] = $sm;
                        }
                    }//if(do_dropdown == "ja")
                }
            }//if(ko_module_installed(module)
        }//foreach(MODULES as m)


        return $menu;
    }

}