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


use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\Menu\MenuBuilder;
use Peregrinus\Flockr\Core\Services\HookService;
use Peregrinus\Flockr\Core\Settings\SelectSetting;
use Peregrinus\Flockr\Core\Settings\Setting;
use Peregrinus\Flockr\Core\Settings\SettingsGroup;
use Peregrinus\Flockr\Core\Settings\SettingsPage;
use Peregrinus\Flockr\Core\Settings\ToggleSetting;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class CoreModule extends AbstractModule
{


    public function init()
    {
        HookService::getInstance()->addFilter('build_menu', [$this, 'registerMenu'], 0);
        HookService::getInstance()->addFilter('build_menu', [$this, 'registerMenuForNewModules']);
        HookService::getInstance()->addFilter('build_top_right_menu', [$this, 'registerTopRightMenu'], 1000);
        HookService::getInstance()->addFilter('build_user_menu', [$this, 'registerUserMenu'], 0);
        HookService::getInstance()->addFilter('build_global_settings', [$this, 'registerGlobalSettings']);

        HookService::getInstance()->addFilter('build_preferences', [$this, 'registerPreferences']);

    }

    public function registerMenu($menu) {
        $menu['home'] = [
            'name' => '<span class="fa fa-home"></span>',
            'link' => '',
        ];
        return $menu;
    }

    public function registerMenuForNewModules($menu)
    {
        return array_merge($menu, MenuBuilder::getInstance()->getMenuFromModules());
    }

    public function registerTopRightMenu($menu) {
        $settingsMenu = HookService::getInstance()->applyFilters('build_settings_menu', []);
        if (count($settingsMenu)) {
            $menu['settings'] = [
                'name' => '<span class="fa fa-cog"></span>',
                'link' => 'core/admin/settings',
                'menu' => array_merge([
                    'adminSettings' => [
                        'name' => 'Einstellungen',
                        'link' => 'core/admin/settings',
                    ]
                ], $settingsMenu),
            ];
        } else {
            $menu['settings'] = [
                'name' => '<span class="fa fa-cog"></span>',
                'link' => 'core/admin/settings',
            ];
        }
        return $menu;
    }

    public function registerUserMenu($menu) {
        $menu['preferences'] = [
            'name' => '<span class="fa fa-cog"></span> Meine Einstellungen',
            'link' => 'core/admin/preferences',
        ];
        return $menu;
    }

    public function registerGlobalSettings($settings)
    {
        $settings[] = new SettingsPage('general', 'Allgemeine Einstellungen',
            HookService::getInstance()->applyFilters('core_admin_settings_general', [
                    new SettingsGroup('contact', 'Kontaktdaten',
                        HookService::getInstance()->applyFilters('core_admin_settings_general_contact', [
                            new Setting('info_name', 'Name der Organisation', Setting::SCOPE_GLOBAL),
                            new Setting('info_address', 'Adresse', Setting::SCOPE_GLOBAL),
                            new Setting('info_zip', 'PLZ', Setting::SCOPE_GLOBAL),
                            new Setting('info_city', 'Ort', Setting::SCOPE_GLOBAL),
                            new Setting('info_phone', 'Telefon', Setting::SCOPE_GLOBAL),
                            new Setting('info_url', 'Website', Setting::SCOPE_GLOBAL),
                            new Setting('info_email', 'E-Mailadresse', Setting::SCOPE_GLOBAL),
                        ])
                    ),
                    new SettingsGroup('user_rights', 'Benutzeroptionen',
                        HookService::getInstance()->applyFilters('core_admin_settings_general_user_rights', [
                            new ToggleSetting('login_edit_person', 'Benutzer kann eigene Daten bearbeiten', Setting::SCOPE_GLOBAL),
                            new ToggleSetting('change_password', 'Benutzer darf eigenes Passwort ändern', Setting::SCOPE_GLOBAL),
                        ])
                    ),
                    new SettingsGroup('excel_export', 'Excel-Export',
                        HookService::getInstance()->applyFilters('core_admin_settings_general_excel_export', [
                            new Setting('xls_default_font', 'Schriftart für Excel-Tabellen', Setting::SCOPE_GLOBAL),
                            new Setting('xls_title_font', 'Schriftart für Titel in Excel-Tabellen', Setting::SCOPE_GLOBAL),
                            new ToggleSetting('xls_title_bold', 'Fette Schrift in Titeln', Setting::SCOPE_GLOBAL),
                            new SelectSetting('xls_title_color', 'Farbe für Titel', Setting::SCOPE_GLOBAL, [
                                'blue' => 'Blau',
                                'black' => 'Schwarz',
                                'cyan' => 'Cyan',
                                'brown' => 'Braun',
                                'magenta' => 'Magenta',
                                'grey' => 'Grau',
                                'green' => 'Grün',
                                'orange' => 'Orange',
                                'purple' => 'Lila',
                                'red' => 'Rot',
                                'yellow' => 'Gelb'
                            ])
                        ])
                    )
                ]
            )
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
        if (LoginService::getInstance()->isGuest()) return $settings;

        $settings[] = new SettingsPage('user', 'Benutzer',
            HookService::getInstance()->applyFilters('core_preferences_user', [
                new SettingsGroup('user_account', 'Mein Konto',
                    HookService::getInstance()->applyFilters('core_preferences_user_account', [
                        new Setting('password', 'Passwort', Setting::SCOPE_USER),
                    ])
                )
            ])
        );

        return $settings;
    }


}