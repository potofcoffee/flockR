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


namespace Peregrinus\Flockr\Rota;


use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\CoreModule;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Services\HookService;
use Peregrinus\Flockr\Core\Services\SettingsService;
use Peregrinus\Flockr\Core\Settings\DoubleSelectSetting;
use Peregrinus\Flockr\Core\Settings\RichTextSetting;
use Peregrinus\Flockr\Core\Settings\SelectSetting;
use Peregrinus\Flockr\Core\Settings\Setting;
use Peregrinus\Flockr\Core\Settings\SettingsGroup;
use Peregrinus\Flockr\Core\Settings\SettingsPage;
use Peregrinus\Flockr\Core\Settings\ToggleSetting;
use Peregrinus\Flockr\Core\Utility\MenuUtility;
use Peregrinus\Flockr\Legacy\AbstractLegacyModule;
use Peregrinus\Flockr\Legacy\Services\LoginService;
use Peregrinus\Flockr\Rota\Exports\PdfLandscapeExport;
use Peregrinus\Flockr\Rota\Settings\EventFieldsSetting;

class RotaModule extends AbstractLegacyModule
{

    public function init()
    {

        $hooks = HookService::getInstance();

        // register the menu
        $hooks->addFilter('build_menu', [$this, 'registerMenu']);

        // register the sidebar
        $hooks->addFilter('build_sidebar', [$this, 'registerSidebar'], null, 3);

        // global settings
        $hooks->addFilter('build_global_settings', [$this, 'registerGlobalSettings']);

        // user preferences
        $hooks->addFilter('build_preferences', [$this, 'registerPreferences']);

        // exports
        $hooks->addFilter('rota_exports', [$this, 'registerRotaExports']);
    }

    public function registerMenu($menu)
    {
        global $ko_path, $smarty;
        global $ko_menu_akt, $access;
        global $my_submenu;

        $moduleKey = strtolower($this->getKey());
        $user = LoginService::getInstance()->getUserId();

        $all_rights = ko_get_access_all('rota_admin', '', $max_rights);
        if ($max_rights < 1) return FALSE;
        ko_get_access('daten');

        $action = ko_get_userpref($user, 'default_view_rota');
        if (!$action) {
            $action = ko_get_setting('default_view_rota');
        }
        if (isset($menu['rota'])) {
            $rotaMenu = $menu['rota']['menu'];
            $menu['rota'] = MenuUtility::topMenu($moduleKey);
            $menu['rota']['menu'] = $rotaMenu;
        } else {
            $menu['rota'] = MenuUtility::topMenu($moduleKey);
        }
        if (ko_get_userpref($user, 'modules_dropdown')) {
            // header
            $menu['rota']['menu'][] = MenuUtility::menuItem('rota', '', 'submenu_rota_title_rota');

            // schedule
            $menu['rota']['menu'][] = MenuUtility::menuItem('rota', 'schedule');

            // new team, list teams
            if ($max_rights > 4) {
                foreach (['new_team', 'list_teams'] as $action) {
                    $menu['rota']['menu'][] = MenuUtility::menuItem('rota', $action);
                }
            }

        }

        return $menu;
    }


    public function registerSidebar($sidebar, $moduleKey, $position)
    {
        if (($moduleKey == 'rota') && ($position == 'left')) {
            $submenu = HookService::getInstance()->applyFilters('build_menu', [])['rota'];
            $view = App::getInstance()->createView(new CoreModule(), 'Menu', 'sidebar');
            $view->assign('module', 'rota');
            $view->assign('menu', $submenu);
            $sidebar = $view->render('sidebar') . $sidebar;
        }
        return $sidebar;
    }

    /**
     * @param SettingsPage[] $settings All settings pages
     * @return SettingsPage[] All settings pages
     */
    public function registerGlobalSettings($settings)
    {
        global $access, $KOTA;

        \ko_get_access('rota');
        if (LoginService::getInstance()->isGuest() || ($access['rota']['MAX'] <= 4)) return $settings;



        // prepare roles
        \ko_get_grouproles($roles);
        foreach ($roles as $key => $role) {
            $roles[$key] = $role['name'];
        }



        $settings[] = new SettingsPage('rota', 'Dienstplan',
            HookService::getInstance()->applyFilters('rota_settings_general', [
                new SettingsGroup('rota_general', 'Dienstplan-Einstellungen',
                    HookService::getInstance()->applyFilters('rota_settings_rota_general', [
                            new ToggleSetting('rota_showroles', 'Bei der Gruppen-Auswahl für die Dienste auch die Rolle wählen?', Setting::SCOPE_GLOBAL),
                            new ToggleSetting('rota_manual_ordering', 'Manuelle der Dienste Sortierung zulassen', Setting::SCOPE_GLOBAL),
                            new SelectSetting('rota_teamrole', 'Gruppenrolle für Mitarbeiter', Setting::SCOPE_GLOBAL, $roles),
                            new SelectSetting('rota_leaderrole', 'Gruppenrolle für Teamleiter', Setting::SCOPE_GLOBAL, $roles),
                            new SelectSetting('rota_weekstart', 'Wochenstart für Dienstpläne', Setting::SCOPE_GLOBAL, [
                                0 => 'Montag',
                                1 => 'Dienstag',
                                2 => 'Mittwoch',
                                3 => 'Donnerstag',
                                -3 => 'Freitag',
                                -2 => 'Samstag',
                                -1 => 'Sonntag',
                            ]),
                            new ToggleSetting('rota_export_weekly_teams', 'Dienstwochen im Plan anzeigen', Setting::SCOPE_GLOBAL),

                        ]
                    )
                ),
                new SettingsGroup('rota_consensus', 'Konsensus',
                    HookService::getInstance()->applyFilters('core_admin_settings_rota_consensus', [
                            new EventFieldsSetting('consensus_eventfields', getLL('Anzuzeigende Felder'), Setting::SCOPE_GLOBAL),
                            new RichTextSetting('consensus_description', 'Beschreibung für Konsensus', Setting::SCOPE_GLOBAL),
                        ]
                    )
                )
            ])
        );

        return $settings;
    }

    /**
     * @param SettingsPage[] $settings All settings pages
     * @return SettingsPage[] All settings pages
     */
    public function registerPreferences($settings)
    {
        global $access;

        \ko_get_access('rota');
        if (LoginService::getInstance()->isGuest() || ($access['rota']['MAX'] < 2)) return $settings;

        $fontSizes = [];
        for ($i=7; $i<=20; $i++) {
            $fontSizes[$i] = $i;
        }

        $rotaSettings = [
            new SelectSetting('default_view_rota', getLL('rota_settings_default_view'), Setting::SCOPE_USER, [
                'schedule' => getLL('submenu_rota_schedule'),
                'list_teams' => getLL('submenu_rota_list_teams'),
            ]),
            new Setting('rota_delimiter', getLL('rota_settings_delimiter'), Setting::SCOPE_USER),
            new ToggleSetting('rota_markempty', getLL('rota_settings_markempty'), Setting::SCOPE_USER),
            new SelectSetting('rota_orderby', getLL('rota_settings_orderby'), Setting::SCOPE_USER, [
                'vorname' => getLL('kota_ko_leute_vorname'), 'nachname' => getLL('kota_ko_leute_nachname'),
            ]),
            new SelectSetting('rota_pdf_names', getLL('rota_settings_pdf_names'), Setting::SCOPE_USER, [
                1 => getLL('rota_settings_pdf_names_1'),
                2 => getLL('rota_settings_pdf_names_2'),
                3 => getLL('rota_settings_pdf_names_3'),
                4 => getLL('rota_settings_pdf_names_4'),
                5 => getLL('rota_settings_pdf_names_5'),
            ]),
            new ToggleSetting('rota_schedule_subgroup_members', getLL('rota_settings_schedule_subgroup_members'), Setting::SCOPE_USER),
            new SelectSetting('rota_pdf_fontsize', getLL('rota_settings_pdf_fontsize'), SelectSetting::SCOPE_USER, $fontSizes),
            new ToggleSetting('rota_pdf_use_colors', getLL('rota_settings_pdf_use_colors'), Setting::SCOPE_USER),
            new EventFieldsSetting('rota_eventfields', getLL('rota_settings_eventfields'), Setting::SCOPE_USER),
        ];


        if (LoginService::getInstance()->isLoggedIn() || ($access['rota']['MAX'] > 0)) {
            $settings[] = new SettingsPage('rota', 'Dienstplan',
                HookService::getInstance()->applyFilters('rota_preferences_general', [
                    new SettingsGroup('rota_general', 'Dienstplan-Einstellungen',
                        HookService::getInstance()->applyFilters('rota_preferences_rota_general', $rotaSettings)
                    )
                ])
            );
        }

        return $settings;
    }


    /**
     * Register all default rota exports
     * @param array $exports Registered exports from other sources
     * @return array Registered exports
     */
    public function registerRotaExports(array $exports) : array {
        $exports['pdfLandscapeExport'] = [
            'id' => 'pdfLandscapeExport',
            'name' => 'Als PDF-Datei exportieren',
            'icon' => 'fa fa-file-pdf-o',
            'hook' => [new PdfLandscapeExport(), 'export'],
        ];
        return $exports;
    }

}