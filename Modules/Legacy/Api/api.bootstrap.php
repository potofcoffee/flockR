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

function ko_init()
{
    global $db_connection, $ko_menu_akt, $BASE_URL;

    //Return during installation, as no DB connection and/or no DB tables are present yet
    if (!$db_connection || $ko_menu_akt == "install") {
        return false;
    }
    //Allow post.php to be called without session or user
    if ($ko_menu_akt == 'post.php') {
        return;
    }

    unset($GLOBALS["kOOL"]);

    //Check for valid user (not disabled since login)
    if ($_SESSION['ses_userid'] != ko_get_guest_id()) {
        $ok = true;
        $uid = intval($_SESSION['ses_userid']);
        if (!$uid) {
            $ok = false;
        }

        $user = db_select_data('ko_admin', "WHERE `id` = '$uid'", '*', '', '', true);
        if (!$user['id'] || $user['id'] != $uid || $user['disabled'] != '') {
            $ok = false;
        }

        if (!$ok) {
            session_destroy();
            $_SESSION['ses_userid'] = ko_get_guest_id();
            $_SESSION['ses_username'] = 'ko_guest';
            header('Location: ' . $BASE_URL . 'index.php');
            return;
        }
    }

    //Read settings
    $settings = db_select_data("ko_settings", "WHERE 1", array("key", "value"));
    foreach ($settings as $s) {
        $GLOBALS["kOOL"]["ko_settings"][$s["key"]] = $s["value"];
    }

    //Read userprefs for logged in user
    $userprefs = null;
    //db_select_data does not work here, as this table doesn't contain an unique_id column
    $rows = db_select_data('ko_userprefs', "WHERE `user_id` = '" . $_SESSION['ses_userid'] . "'", '*',
        'ORDER BY `key` ASC', '', false, true);
    foreach ($rows as $row) {
        if ($row["type"] != "") {
            $userprefs["TYPE@" . $row["type"]][$row["key"]] = $row;
        } else {
            $userprefs[$row["key"]] = $row["value"];
        }
    }
    $GLOBALS["kOOL"]["ko_userprefs"] = $userprefs;

    //Set kota_filter if not in session yet
    if ($_SESSION['ses_userid'] != ko_get_guest_id() && ko_get_userpref($_SESSION['ses_userid'],
            'save_kota_filter') == 1 && !isset($_SESSION['kota_filter'])
    ) {
        $_SESSION['kota_filter'] = unserialize(ko_get_userpref($_SESSION['ses_userid'], 'kota_filter'));
    }

    //Get all help entries for the current module
    $helps = db_select_data('ko_help', "WHERE `module` IN ('$ko_menu_akt', 'kota')", '*');
    foreach ($helps as $help) {
        if ($help['type'] == '') {
            $GLOBALS['kOOL']['ko_help'][$help['language']]['_notype'] = $help;
        } else {
            $GLOBALS['kOOL']['ko_help'][$help['language']][$help['type']] = $help;
        }
    }

}//ko_init()

