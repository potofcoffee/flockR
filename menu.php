<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2003-2015 Renzo Lauper (renzo@churchtool.org)
 *  All rights reserved
 *
 *  This script is part of the kOOL project. The kOOL project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  kOOL is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

$view = \Peregrinus\Flockr\Core\App::getInstance()->createView(\Peregrinus\Flockr\Core\CoreModule::getInstance(), 'Menu', 'top');

//Show login fields if not logged in yet
if (\Peregrinus\Flockr\Legacy\Services\LoginService::getInstance()->isGuest()) {
    $view->assign('isLoggedIn', 0);
    $view->assign('login', [
            'text' =>
                [
                    'username' => getLL('login_username'),
                    'password' => getLL('login_password'),
                    'login' => getLL('login'),
                ]
        ]
    );

} //Otherwise show logout link
else {
    $view->assign('isLoggedIn', 1);
    $view->assign('logout',
        [
            'link' => $ko_path . 'index.php?action=logout',
            'text' => getLL('login_logout'),
            'user' => \Peregrinus\Flockr\Legacy\Services\LoginService::getInstance()->getUser(),
        ]
    );
    $do_guest = false;
}

include($ko_path . "header.php");


$menu = [];

// FLOCKR: menu integration via hook "build_menu"
$menu = \Peregrinus\Flockr\Core\Services\HookService::getInstance()->applyFilters('build_menu', $menu);
\Peregrinus\Flockr\Core\Debugger::toFile($menu, 'menu');

$userMenu = \Peregrinus\Flockr\Core\Services\HookService::getInstance()->applyFilters('build_user_menu', []);
$rightMenu = \Peregrinus\Flockr\Core\Services\HookService::getInstance()->applyFilters('build_top_right_menu', []);

$view->assign("activeMenu", $ko_menu_akt);
$view->assign('menu', $menu);
$view->assign('userMenu', $userMenu);
$view->assign('rightMenu', $rightMenu);
if (!defined('DO_NOT_ECHO_MENU')) {
    echo $view->render('top');
} else {
    $renderedMenu = $view->render('top');
}


