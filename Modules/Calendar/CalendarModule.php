<?php

namespace Peregrinus\Flockr\Calendar;

use Peregrinus\Flockr\Core\Services\HookService;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class CalendarModule extends \Peregrinus\Flockr\Core\AbstractModule {
    protected $permittedActions = [
        'Event' => 'list,show',
        'Settings' => 'dialog,submit',
    ];

    public function init() {

        $hooks = HookService::getInstance();

        // register the menu
        $hooks->addFilter('build_menu', [$this, 'registerMenu'],11);

    }

    public function registerMenu($menu) {
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
                $menu['daten']['menu'][] = [
                    'name' => 'Export',
                ];
                $menu['daten']['menu'][] = [
                    'name' => 'Jahreskalender',
                    'link' => 'calendar/export/year'
                ];
            }
        }


        return $menu;
    }

}