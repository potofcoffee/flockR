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

//Set user to ko_guest if none logged in yet
if ($db_connection && !in_array($ko_menu_akt,
        array('scheduler', 'install', 'get.php', 'post.php')) && !$_SESSION['ses_userid']
) {
    $_SESSION['ses_username'] = 'ko_guest';
    $_SESSION['ses_userid'] = ko_get_guest_id();

    //Log guest with IP address (but not form mailing cron job or cli)
    if (!in_array($ko_menu_akt,
            array('mailing', 'scheduler', 'get.php', 'post.php', 'ical')) && php_sapi_name() != 'cli'
    ) {
        ko_log('guest', 'ko_guest from ' . ko_get_user_ip());
    }

    //Redirect guest user upon it's first visit (unless script is called from cli)
    if (!in_array($ko_menu_akt, array(
            'mailing',
            'scheduler',
            'install',
            'get.php',
            'post.php',
            'ical',
            'carddav'
        )) && php_sapi_name() != 'cli'
    ) {
        ko_redirect_after_login();
    }
}
