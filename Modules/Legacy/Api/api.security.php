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

/************************************************************************************************************************
 *                                                                                                                      *
 * MODULE UND BERECHTIGUNGEN                                                                                            *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Checks whether a module is installed for a user
 *
 * If no userid is given as argument the current user will be checked
 * that is stored in $_SESSION["ses_userid"].
 *
 * @param string id of module to check for
 * @param int userid of user to check. If not set, value in $_SESSION["ses_userid"] will be used
 * @return boolean True if module is available to user, false otherwise
 */
function ko_module_installed($m, $uid = "")
{
    if (defined("ALL_ACCESS")) {
        return true;
    }

    if ($uid) {
        ko_get_user_modules($uid, $modules);
    } else {
        ko_get_user_modules($_SESSION["ses_userid"], $modules);
    }
    if (in_array($m, $modules)) {
        return true;
    } else {
        return false;
    }
}//ko_module_installed()


/**
 * Get all modules a user is allowed to see
 *
 * @param int userid
 * @param array Contains the modules
 */
function ko_get_user_modules($uid, &$m)
{
    global $MODULES;

    //Get from cache
    if (isset($GLOBALS["kOOL"]["user_modules"][$uid])) {
        $m = $GLOBALS["kOOL"]["user_modules"][$uid];
        return;
    }

    if (defined("ALL_ACCESS")) {
        $m = $MODULES;
        return;
    }

    if (!$uid) {
        $uid = ko_get_guest_id();
    }

    $row = db_select_data("ko_admin", "WHERE `id` = '$uid'", "modules", "", "", true);
    $m = explode(",", $row["modules"]);

    $groups = ko_get_admingroups($uid);
    foreach ($groups as $group) {
        $row = db_select_data("ko_admingroups", "WHERE `id` = '" . $group["id"] . "'", "modules", "", "", true);
        $m = array_merge($m, explode(",", $row["modules"]));
    }
    $m = array_unique($m);

    //Store in cache and return
    $GLOBALS["kOOL"]["user_modules"][$uid] = $m;
}//ko_get_user_modules()


/**
 * Returns an array of admingroups.
 *
 * If the first argument is set to a user id then the admingroups are returned
 * this user is being assigned to. Otherwise all admingroups are returned.
 *
 * @param int userid
 * @return array admingroups
 */
function ko_get_admingroups($uid = "")
{
    //Get from cache
    if ($uid && isset($GLOBALS["kOOL"]["admingroups"][$uid])) {
        return $GLOBALS["kOOL"]["admingroups"][$uid];
    }

    $groups = array();
    //get all groups
    if ($uid == "") {
        $groups = db_select_data("ko_admingroups", "", "*", "ORDER BY name ASC");
    } //get groups for the specified account
    else {
        $row = db_select_data("ko_admin", "WHERE `id` = '$uid'", "admingroups", "", "", true);
        foreach (explode(",", $row["admingroups"]) as $groupid) {
            if (!$groupid) {
                continue;
            }
            $group = db_select_data("ko_admingroups", "WHERE `id` = '$groupid'", "*", "", "", true);
            $groups[$group["id"]] = $group;
        }
    }

    //Store in cache and return
    if ($uid) {
        $GLOBALS["kOOL"]["admingroups"][$uid] = $groups;
    }
    return $groups;
}//ko_get_admingroups()


/**
 * Get ALL-Rights
 */
function ko_get_access_all($col, $id = "", &$max = 0)
{
    $max = 0;
    if (defined('ALL_ACCESS')) {
        $id = ko_get_root_id();
    }
    if (!$id) {
        $id = $_SESSION['ses_userid'];
    }

    //Fake access rights for tools module for root user
    if ($col == 'tools' && $id == ko_get_root_id()) {
        $max = 4;
        return 4;
    }

    //Accept module name instead of col name as well
    if (substr($col, -6) != '_admin') {
        switch ($col) {
            case 'reservation':
                $col = 'res_admin';
                break;
            case 'admin':
                $col = 'admin';
                break;
            case 'daten':
                $col = 'event_admin';
                break;
            default:
                $col = $col . '_admin';
        }
    }

    if (isset($GLOBALS['kOOL']['admin_max'][$id][$col])) {
        $max = $GLOBALS['kOOL']['admin_max'][$id][$col];
    }
    if (isset($GLOBALS['kOOL']['admin_all'][$id][$col])) {
        return $GLOBALS['kOOL']['admin_all'][$id][$col];
    }

    $value = 0;
    //Check for settings for login
    $rights = db_select_data('ko_admin', "WHERE `id` = '$id'", '*', '', '', true);
    foreach (explode(',', $rights[$col]) as $r) {
        if (false === strpos($r, "@")) {
            $value = $r;
        }
        $max = max($max, substr($r, 0, 1));
    }
    //Check for settings for admingroups
    if ($rights["admingroups"]) {
        $admingroups = db_select_data("ko_admingroups",
            "WHERE `id` IN ('" . implode("','", explode(",", $rights["admingroups"])) . "')");
        foreach ($admingroups as $ag) {
            foreach (explode(",", $ag[$col]) as $r) {
                if (false === strpos($r, "@")) {
                    $value = max($value, $r);
                }
                $max = max($max, substr($r, 0, 1));
            }
            //Raise max rights for people module if a admin_filter is set for the given access level
            if ($col == 'leute_admin') {
                $glaf = unserialize($ag['leute_admin_filter']);
                if ($max < 3 && $glaf[3]) {
                    $max = 3;
                } else {
                    if ($max < 2 && $glaf[2]) {
                        $max = 2;
                    } else {
                        if ($max < 1 && $glaf[1]) {
                            $max = 1;
                        }
                    }
                }
            }
        }
    }

    //Raise max rights for people module if a admin_filter is set for the given access level
    if ($col == 'leute_admin') {
        $laf = unserialize($rights['leute_admin_filter']);
        if ($max < 3 && $laf[3]) {
            $max = 3;
        } else {
            if ($max < 2 && $laf[2]) {
                $max = 2;
            } else {
                if ($max < 1 && $laf[1]) {
                    $max = 1;
                }
            }
        }
    }

    if ($col == 'groups_admin') {
        if ($max < 4) {
            if (db_get_count('ko_groups', 'id', "AND `rights_del` REGEXP '(^|,)$id(,|$)'") > 0) {
                $max = 4;
            }
        }
        if ($max < 3) {
            if (db_get_count('ko_groups', 'id', "AND `rights_edit` REGEXP '(^|,)$id(,|$)'") > 0) {
                $max = 3;
            }
        }
        if ($max < 2) {
            if (db_get_count('ko_groups', 'id', "AND `rights_new` REGEXP '(^|,)$id(,|$)'") > 0) {
                $max = 2;
            }
        }
        if ($max < 1) {
            if (db_get_count('ko_groups', 'id', "AND `rights_view` REGEXP '(^|,)$id(,|$)'") > 0) {
                $max = 1;
            }
        }
    }

    $GLOBALS['kOOL']['admin_all'][$id][$col] = $value;
    $GLOBALS['kOOL']['admin_max'][$id][$col] = $max;

    return $value;
}//ko_get_access_all()


function ko_get_access(
    $module,
    $uid = '',
    $force = false,
    $apply_admingroups = true,
    $mode = 'login',
    $store_globally = true
)
{
    global $access, $MODULES, $FORCE_KO_ADMIN;

    //Temporary array to hold the access rights within this function
    $_access = array();

    if (!in_array($module, $MODULES)) {
        return false;
    }
    if ($uid == '') {
        $uid = $_SESSION['ses_userid'];
    }
    if (defined('ALL_ACCESS')) {
        $uid = ko_get_root_id();
    }

    //Only reread access rights if force is set
    if (is_array($access[$module]) && $uid == $_SESSION['ses_userid'] && !$force) {
        return true;
    }

    switch ($module) {
        case 'rota':
        case 'leute':
        case 'fileshare':
        case 'kg':
        case 'tapes':
        case 'groups':
        case 'donations':
        case 'tracking':
        case 'projects':
            $col = $module . '_admin';
            break;
        case 'reservation':
            $col = 'res_admin';
            break;
        case 'admin':
            $col = 'admin';
            break;
        case 'daten':
            $col = 'event_admin';
            break;
        case 'tools':
            if ($uid == ko_get_root_id()) {
                $access['tools'] = array('ALL' => 4, 'MAX' => 4);
            }
            return;
            break;
        default:
            if (in_array($module, $MODULES)) {
                $col = $module . '_admin';
            } else {
                return false;
            }
    }

    //get rights for user from db
    if ($mode == 'login') {
        $row = db_select_data('ko_admin', "WHERE `id` = '$uid'", '*', '', '', true);
    } else {
        $row = db_select_data('ko_admingroups', "WHERE `id` = '$uid'", '*', '', '', true);
    }
    $rights = explode(',', $row[$col]);
    foreach ($rights as $r) {
        if (trim($r) == '') {
            continue;
        }
        if (strpos($r, '@') === false) {  //No @ means ALL rights
            $_access[$module]['ALL'] = $r;
        } else {
            list($level, $id) = explode('@', $r);
            $_access[$module][$id] = max($_access[$module]['ALL'], $level);
        }
    }
    //Add access rights from admin groups
    if ($row['admingroups'] != '' && $apply_admingroups) {
        $groups = db_select_data('ko_admingroups',
            "WHERE `id` IN ('" . implode("','", explode(',', $row['admingroups'])) . "')");
        foreach ($groups as $group) {
            $rights_group = explode(',', $group[$col]);
            foreach ($rights_group as $r) {
                if (trim($r) == '') {
                    continue;
                }
                if (strpos($r, '@') === false) {  //No @ means ALL rights
                    $_access[$module]['ALL'] = max($r, $_access[$module]['ALL']);
                } else {
                    list($level, $id) = explode('@', $r);
                    $_access[$module][$id] = max($_access[$module]['ALL'], $_access[$module][$id], $level);
                }
            }
        }
    }//if(apply_admingroups)

    if (defined('ALL_ACCESS')) {
        $_access[$module]['ALL'] = 4;
        foreach ($_access[$module] as $id => $level) {
            $_access[$module][$id] = 4;
        }
    }


    switch ($module) {
        case 'daten':
            $_access[$module]['REMINDER'] = $row['event_reminder_rights'];
            $egs = db_select_data('ko_eventgruppen', 'WHERE 1=1');
            foreach ($egs as $eg) {
                if (ko_get_setting('daten_access_calendar') == 1 && $eg['calendar_id'] > 0) {
                    //Access rights set by calendars or event groups
                    if (isset($_access[$module]['cal' . $eg['calendar_id']])) {
                        $_access[$module][$eg['id']] = max($_access[$module]['cal' . $eg['calendar_id']],
                            $_access[$module]['ALL']);
                    } else {
                        $_access[$module][$eg['id']] = $_access[$module]['ALL'];
                        $_access[$module]['cal' . $eg['calendar_id']] = $_access[$module]['ALL'];
                    }
                } else {
                    //Access rights set exclusively by event groups
                    $_access[$module][$eg['id']] = max($_access[$module][$eg['id']], $_access[$module]['ALL']);
                    //Set cal access rights, as they are needed e.g. to fill the KOTA form to enter a new event
                    if ($eg['calendar_id'] > 0) {
                        $_access[$module]['cal' . $eg['calendar_id']] = max($_access[$module]['cal' . $eg['calendar_id']],
                            $_access[$module][$eg['id']]);
                    }
                }
                if ($apply_admingroups) {
                    $_access[$module]['REMINDER'] = max($_access[$module]['REMINDER'], $eg['event_reminder_rights']);
                }
            }
            break;


        case 'tapes':
            $tapegroups = db_select_data('ko_tapes_groups', 'WHERE 1=1');
            foreach ($tapegroups as $tapegroup) {
                $_access[$module][$tapegroup['id']] = max($_access[$module][$tapegroup['id']],
                    $_access[$module]['ALL']);
            }
            break;


        case 'tracking':
            $trackings = db_select_data('ko_tracking', 'WHERE 1=1');
            foreach ($trackings as $tracking) {
                $_access[$module][$tracking['id']] = max($_access[$module][$tracking['id']], $_access[$module]['ALL']);
            }
            break;


        case 'rota':
            $teams = db_select_data('ko_rota_teams', 'WHERE 1=1');
            foreach ($teams as $team) {
                $_access[$module][$team['id']] = max($_access[$module][$team['id']], $_access[$module]['ALL']);
            }
            break;


        case 'donations':
            $accounts = db_select_data('ko_donations_accounts', 'WHERE 1=1');
            foreach ($accounts as $account) {
                $_access[$module][$account['id']] = max($_access[$module][$account['id']], $_access[$module]['ALL']);
            }
            break;


        case 'reservation':
            $resgroups = db_select_data('ko_resgruppen', 'WHERE 1=1', '*', 'ORDER BY name ASC');
            foreach ($resgroups as $rg) {
                $_access[$module]['grp' . $rg['id']] = max($_access[$module]['ALL'],
                    $_access[$module]['grp' . $rg['id']]);
            }
            unset($resgroups);

            $items = db_select_data('ko_resitem', 'WHERE 1=1');
            foreach ($items as $item) {
                if (isset($_access[$module][$item['id']])) {
                    $_access[$module][$item['id']] = max($_access[$module]['ALL'], $_access[$module][$item['id']]);
                    $_access[$module]['grp' . $item['gruppen_id']] = max($_access[$module]['grp' . $item['gruppen_id']],
                        $_access[$module][$item['id']]);
                } else {
                    if (isset($_access[$module]['grp' . $item['gruppen_id']])) {
                        $_access[$module][$item['id']] = max($_access[$module]['ALL'],
                            $_access[$module]['grp' . $item['gruppen_id']]);
                    } else {
                        $_access[$module][$item['id']] = $_access[$module]['ALL'];
                        $_access[$module]['grp' . $item['gruppen_id']] = $_access[$module]['ALL'];
                    }
                }
            }
            unset($items);
            break;


        case 'leute':
            $rights = array();
            //Always include hidden addresses
            $orig_value = ko_get_setting('leute_hidden_mode');
            ko_set_setting('leute_hidden_mode', 0);
            for ($i = 3; $i > $_access[$module]['ALL']; $i--) {
                if (false !== apply_leute_filter('', $z_where, true, $i, $uid, true)) {
                    $leute = db_select_data('ko_leute', 'WHERE 1=1 ' . $z_where, 'id');
                    if (sizeof($leute) > 0) {
                        foreach ($leute as $id => $p) {
                            if (!isset($_access[$module][$id])) {
                                $_access[$module][$id] = $i;
                            }
                        }
                    } else {
                        //If no address found with this filter but filter is allowed, then set dummy entry so MAX will be set
                        $_access[$module][-1] = max($_access[$module][-1], $i);
                    }
                }
            }
            ko_set_setting('leute_hidden_mode', $orig_value);
            unset($leute);
            //GS will be added at the end (after MAX has been set)
            break;


        case 'groups':
            if ($_access[$module]['ALL'] < 4) {
                $not_leaves = db_select_distinct('ko_groups', 'pid');
            }

            $prefix = $mode == 'login' ? '' : 'g';

            $modes = array('', 'view', 'new', 'edit', 'del');
            for ($level = 4; $level > 0; $level--) {
                //Get access rights for single groups that are higher than the ALL rights
                if ($_access[$module]['ALL'] < $level) {
                    $where = "WHERE `rights_" . $modes[$level] . "` REGEXP '(^|,)" . $prefix . $uid . "(,|$)' ";
                    //Add access rights from admin groups
                    if ($mode == 'login' && $row['admingroups'] != '' && $apply_admingroups) {
                        $groups = db_select_data('ko_admingroups',
                            "WHERE `id` IN ('" . implode("','", explode(',', $row['admingroups'])) . "')");
                        foreach ($groups as $ag) {
                            $where .= " OR `rights_" . $modes[$level] . "` REGEXP '(^|,)g" . $ag['id'] . "(,|$)' ";
                        }
                    }

                    ${'grps' . $level} = db_select_data('ko_groups', $where, 'id');
                    if (sizeof(${'grps' . $level}) > 0) {
                        foreach (${'grps' . $level} as $grp) {
                            $_access[$module][$grp['id']] = max($_access[$module][$grp['id']], $level);
                            //Propagate rights to all children
                            $children = array();
                            rec_groups($grp, $children, '', $not_leaves);
                            foreach ($children as $c) {
                                $_access[$module][$c['id']] = max($_access[$module][$c['id']], $level);
                            }
                        }
                    }
                }
            }

            //Add view rights for all parent groups, so tree to the groups gets visible
            $top_groups = array_unique(array_merge(array_keys((array)$grps1), array_keys((array)$grps2),
                array_keys((array)$grps3), array_keys((array)$grps4)));
            unset($grps1);
            unset($grps2);
            unset($grps3);
            unset($grps4);
            if (sizeof($top_groups) > 0) {
                ko_get_groups($all_groups);
                foreach ($top_groups as $gid) {
                    $motherline = ko_groups_get_motherline($gid, $all_groups);
                    foreach ($motherline as $id) {
                        $_access[$module][$id] = max($_access[$module][$id], 1);
                    }
                }
            }
            break;

        case 'fileshare':
        case 'kg':
        case 'admin':
            //Nothing
            break;

        default:
            $module_groups = hook_access_get_groups($module);
            foreach ($module_groups as $module_group) {
                $_access[$module][$module_group['id']] = max($_access[$module][$module_group['id']],
                    $_access[$module]['ALL']);
            }
    }


    //Apply FORCE_KO_ADMIN
    if ($uid != ko_get_root_id()) {
        foreach ($MODULES as $mod) {
            if (is_array($FORCE_KO_ADMIN[$mod])) {
                foreach ($FORCE_KO_ADMIN[$mod] as $k => $v) {
                    $_access[$mod][$k] = $v;
                }
            }
        }
    }


    //Add max value
    $_access[$module]['MAX'] = max($_access[$module]);

    //Store max and all values in Cache for ko_get_access_all()
    $GLOBALS['kOOL']['admin_all'][$uid][$col] = $_access[$module]['ALL'];
    $GLOBALS['kOOL']['admin_max'][$uid][$col] = $_access[$module]['MAX'];

    //Only add it here after MAX has been set, so this value won't be considered for the MAX value
    if ($module == 'leute') {
        // Add right to moderate group subscriptions
        $gs = db_select_data('ko_admin', "WHERE `id` = '$uid'", 'leute_admin_gs', '', '', true);
        $_access[$module]['GS'] = $gs['leute_admin_gs'] == 1;

        //Check admingroups for GS setting
        if ($row['admingroups'] != '' && $apply_admingroups) {
            $groups = db_select_data('ko_admingroups',
                "WHERE `id` IN ('" . implode("','", explode(',', $row['admingroups'])) . "')");
            foreach ($groups as $group) {
                if ($group['leute_admin_gs'] == 1) {
                    $_access[$module]['GS'] = true;
                }
            }
        }//if(apply_admingroups)
    }

    //Usually the access rights will be stored in the global array $access
    if ($store_globally) {
        //Reset access rights before (re)building them
        unset($access[$module]);
        $access[$module] = $_access[$module];
        return;
    } //But when reading the access rights for another user it shouldn't overwrite one's own access rights
    else {
        return $_access;
    }
    unset($_access);
}//ko_get_access()


/**
 * Get columns the given login / admingroup has access to for a given KOTA table
 *
 * Reads data from ko_admin and/or ko_admingroups to find KOTA columns for the given table this user
 * has access to. Columns from admingroups and login are summed.
 * Used in ko_include_kota() to unset columns with no access to
 *
 * @param $loginid int ID of login or admingroup to be checked
 * @param $table string Name of DB table (as set in $KOTA)
 * @param $mode string Can be all to get summed access rights or login or admingroup to only get access
 *                     for the login/admingroup itself
 * @return $cols array If array is empty then user has access to all columns, otherwise only to the ones in the array
 */
function ko_access_get_kota_columns($loginid, $table, $mode = 'all')
{
    $cols = array();

    //TODO: Make configurable, but not in KOTA as this is not loaded
    $tables = array('ko_kleingruppen');
    if (!in_array($table, $tables)) {
        return $cols;
    }

    if ($mode == 'login' || $mode == 'all') {
        $row = db_select_data('ko_admin', "WHERE `id` = '$loginid'", 'kota_columns_' . $table, '', '', true);
    } else {
        if ($mode == 'admingroup') {
            $row = db_select_data('ko_admingroups', "WHERE `id` = '$loginid'", 'kota_columns_' . $table, '', '', true);
        }
    }
    if (trim($row['kota_columns_' . $table]) != '') {
        $cols = explode(',', $row['kota_columns_' . $table]);
    }

    if ($mode == 'all') {
        $admingroups = ko_get_admingroups($loginid);
        foreach ($admingroups as $group) {
            $row = db_select_data('ko_admingroups', "WHERE `id` = '" . $group['id'] . "'", 'kota_columns_' . $table, '',
                '', true);
            if (trim($row['kota_columns_' . $table]) != '') {
                $cols2 = explode(',', $row['kota_columns_' . $table]);
                $cols = array_merge($cols, $cols2);
            }
        }
    }
    $cols = array_unique($cols);
    foreach ($cols as $k => $v) {
        if (!$v) {
            unset($cols[$k]);
        }
    }

    return $cols;
}//ko_access_get_kota_columns()


/**
 * @param $reminder an array containing at least the 'cruser' of the reminder, or the reminderId
 * @return bool true, if the logged in user may access this reminder
 */
function ko_get_reminder_access($reminder)
{
    global $access;

    if (!isset($access['daten'])) {
        ko_get_access('daten');
    }

    if (!is_array($reminder)) {
        ko_get_reminders($reminder, 1, ' and `id` = ' . $reminder, '', '', true, true);
    }

    if ($access['daten']['REMINDER'] == 0) {
        return false;
    } else {
        if ($_SESSION['ses_userid'] == $reminder['cruser']) {
            return true;
        } else {
            if ($access['daten']['ALL'] >= 3) {
                return true;
            } else {
                return false;
            }
        }
    }
} // ko_get_reminder_access()


/**
 * Saves admin data in ko_admin
 *
 * Stores access rights for modules, password, available modules etc. in ko_admin
 *
 * @param string module id or other column to store data for
 * @param int user id or admingroup id to store the data for
 * @param string Value to be stored
 * @param string Stores the data for a login if set to "login", for an admingroup otherwise
 * @return True on success, false on failure
 */
function ko_save_admin($module, $uid, $string, $type = "login")
{
    global $MODULES;

    switch ($module) {
        case "daten":
            $col = "event_admin";
            break;
        case "leute":
            $col = "leute_admin";
            break;
        case "leute_filter":
            $col = "leute_admin_filter";
            break;
        case "leute_spalten":
            $col = "leute_admin_spalten";
            break;
        case "leute_groups":
            $col = "leute_admin_groups";
            break;
        case "leute_gs":
            $col = "leute_admin_gs";
            break;
        case "leute_assign":
            $col = "leute_admin_assign";
            break;
        case "reservation":
            $col = "res_admin";
            break;
        case "admin":
            $col = "admin";
            break;
        case 'rota':
            $col = 'rota_admin';
            break;
        case "fileshare":
            $col = "fileshare_admin";
            break;
        case "kg":
            $col = "kg_admin";
            break;
        case "tapes":
            $col = "tapes_admin";
            break;
        case "groups":
            $col = "groups_admin";
            break;
        case "donations":
            $col = "donations_admin";
            break;
        case "tracking":
            $col = "tracking_admin";
            break;
        case 'sms':
            $col = '';
            break;
        case 'mailing':
            $col = '';
            break;
        case 'tools':
            $col = '';
            break;
        case "projects":
            $col = "projects_admin";
            break;
        case "modules":
            $col = "modules";
            break;
        case "admingroups":
            $col = "admingroups";
            break;
        case "login":
            $col = "login";
            break;
        case "name":
            $col = "name";
            break;
        case "password":
            $col = "password";
            break;
        case "kota_columns_ko_kleingruppen":
            $col = "kota_columns_ko_kleingruppen";
            break;
        case "daten_force_global":
            $col = "event_force_global";
            break;
        case "daten_reminder_rights":
            $col = "event_reminder_rights";
            break;
        case "reservation_force_global":
            $col = "res_force_global";
            break;
        default:
            if (in_array($module, $MODULES)) {
                $col = $module . '_admin';
            } else {
                $col = "";
            }
    }//switch(module)

    if (!isset($uid)) {
        return false;
    }
    if ($col == "") {
        return false;
    }

    if ($type == "login") {
        db_update_data('ko_admin', "WHERE `id` = '$uid'", array($col => format_userinput($string, 'text')));
    } else {
        db_update_data('ko_admingroups', "WHERE `id` = '$uid'", array($col => format_userinput($string, 'text')));
    }

    //Unset cached value in GLOBALS[kOOL]
    if (isset($GLOBALS["kOOL"][$col][$uid][$type])) {
        unset($GLOBALS["kOOL"][$col][$uid][$type]);
    }

    return true;
}


/**
 * Checks whether the login should only see reservations/events specified by the global time filter
 *
 * @param string The module, either 'reservation' or 'daten'
 * @param int Login id
 * @return boolean
 */
function ko_get_force_global_time_filter($module, $id)
{

    $moduleMapper = array('reservation' => 'res', 'daten' => 'event');
    $module = $moduleMapper[$module];
    if (!$module) {
        return false;
    }

    $adminGroups = ko_get_admingroups($id);
    $admin = db_select_data('ko_admin', "where id = $id", $module . "_force_global", '', '', true, true);

    if ($admin[$module . '_force_global'] == 1) {
        return true;
    }
    foreach ($adminGroups as $ag) {
        if ($ag[$module . '_force_global'] == 1) {
            return true;
        }
    }
    return false;
}


/**
 * Returns the array of admin-filters for the given login.
 *
 * This array defines the global filter that is to be applied to ko_leute always for the given login.
 *
 * @param int user id or admingroup id
 * @param string Get filter for login if set to "login", for admingroup otherwise
 * @return array Filter to be applied
 */
function ko_get_leute_admin_filter($id, $mode = "login")
{
    //Get from cache
    if (isset($GLOBALS["kOOL"]["leute_admin_filter"][$id][$mode])) {
        return $GLOBALS["kOOL"]["leute_admin_filter"][$id][$mode];
    }

    if ($mode == "login") {
        $row = db_select_data("ko_admin", "WHERE `id` = '$id'", "leute_admin_filter", "", "", true);
    } else {
        if ($mode == "admingroup") {
            $row = db_select_data("ko_admingroups", "WHERE `id` = '$id'", "leute_admin_filter", "", "", true);
        }
    }

    //Store in cache and return
    $r = unserialize($row["leute_admin_filter"]);

    //For backwards compatibility: If no value was set, then use name as value (which is still the case for filter presets)
    if (is_array($r)) {
        foreach ($r as $k => $v) {
            if (isset($r[$k]['name']) && !isset($r[$k]['value'])) {
                $r[$k]['value'] = $r[$k]['name'];
            }
        }
    }

    $GLOBALS["kOOL"]["leute_admin_filter"][$id][$mode] = $r;
    return $r;
}//ko_get_leute_admin_filter()


/**
 * Returns the columns of ko_leute, for which the user has [view] and [edit] rights.
 *
 * Remark: Remember special cols like groups, smallgroups.
 * They are not included here, but retrieved from the corresponding module-rights
 *
 * @param int user id or admingroup id
 * @param string Get filter for login if set to "login", for admingroup otherwise
 * @param int address id to check access for
 * @return array Array with view and edit rights or FALSE if no limitations exist
 */
function ko_get_leute_admin_spalten($userid, $mode = "login", $pid = 0)
{
    global $FORCE_KO_ADMIN, $LEUTE_ADMIN_SPALTEN_CONDITION;

    //Get from cache
    if (isset($GLOBALS["kOOL"]["leute_admin_spalten"][$userid][$mode][$pid])) {
        return $GLOBALS["kOOL"]["leute_admin_spalten"][$userid][$mode][$pid];
    }

    //>0 means editing. -1 means new address (from ko_leute_mod and add_person)
    if (($pid > 0 || $pid == -1) && is_array($LEUTE_ADMIN_SPALTEN_CONDITION)) {
        $lasc = $LEUTE_ADMIN_SPALTEN_CONDITION[$userid];
        if (!is_array($lasc)) {
            $lasc = $LEUTE_ADMIN_SPALTEN_CONDITION['ALL'];
        }
        if (is_array($lasc)) {
            $p = db_select_data('ko_leute', "WHERE `id` = '$pid'", '*', '', '', true);
            foreach (explode(',', $lasc['dontapply']) as $_col) {
                if (substr($_col, 0, 1) == '!') {
                    $_col = substr($_col, 1);
                    if (!$p[$_col]) {
                        return false;
                    }
                } else {
                    if ($p[$_col]) {
                        return false;
                    }
                }
            }
        }
    }

    if ($mode == "login" || $mode == "all") {
        $cols = db_select_data("ko_admin", "WHERE `id` = '$userid'", "leute_admin_spalten", "", "", true);
        $return = unserialize($cols["leute_admin_spalten"]);
    } else {
        if ($mode == "admingroup") {
            $cols = db_select_data("ko_admingroups", "WHERE `id` = '$userid'", "leute_admin_spalten", "", "", true);
            $return = unserialize($cols["leute_admin_spalten"]);
        }
    }

    if ($mode == "all") {
        $admingroups = ko_get_admingroups($userid);
        foreach ($admingroups as $group) {
            $group_cols = db_select_data("ko_admingroups", "WHERE `id` = '" . $group["id"] . "'", "leute_admin_spalten",
                "", "", true);
            $cols = unserialize($group_cols["leute_admin_spalten"]);
            if (sizeof($cols) > 0) {
                $return = array_merge_recursive((array)$return, (array)$cols);
            }
        }
    }

    //Unset empty entries
    if (is_array($return)) {
        foreach ($return as $k => $v) {
            if (!$v) {
                unset($return[$k]);
            }
        }
    } else {
        //If not an array, then probably just '0', which means all columns may be displayed
        $return = false;
    }

    //Return FALSE if no cols were found, so all will get displayed
    if (sizeof($return) == 0) {
        $return = false;
    }

    //Check for forced access rights
    if (isset($FORCE_KO_ADMIN["leute_admin_spalten"])) {
        $return = unserialize($FORCE_KO_ADMIN["leute_admin_spalten"]);
    }

    //Propagate view rights to edit rights if edit is not set at all
    if (is_array($return['view']) && !$return['edit']) {
        $return['edit'] = $return['view'];
    }

    //Store in cache and return
    $GLOBALS["kOOL"]["leute_admin_spalten"][$userid][$mode][$pid] = $return;
    return $return;
}//ko_get_leute_admin_spalten()


function ko_get_leute_admin_groups($userid, $mode = 'login')
{
    $r = false;

    //Get from cache
    if (isset($GLOBALS['kOOL']['leute_admin_groups'][$userid][$mode])) {
        return $GLOBALS['kOOL']['leute_admin_groups'][$userid][$mode];
    }

    if ($mode == 'login' || $mode == 'all') {
        $groups = db_select_data('ko_admin', "WHERE `id` = '$userid'", 'leute_admin_groups', '', '', true);
        $r[] = $groups['leute_admin_groups'];
    } else {
        if ($mode == 'admingroup') {
            $groups = db_select_data('ko_admingroups', "WHERE `id` = '$userid'", 'leute_admin_groups', '', '', true);
            $r[] = $groups['leute_admin_groups'];
        }
    }

    if ($mode == 'all') {
        $admingroups = ko_get_admingroups($userid);
        foreach ($admingroups as $group) {
            $r[] = $group['leute_admin_groups'];
        }
    }

    //Unset empty entries
    if (is_array($r)) {
        foreach ($r as $k => $v) {
            if (!$v) {
                unset($r[$k]);
            }
        }
    }

    //Store in cache
    $GLOBALS['kOOL']['leute_admin_groups'][$userid][$mode] = $r;

    if (sizeof($r) > 0) {
        return $r;
    } else {
        return false;
    }
}//ko_get_leute_admin_groups()


function ko_get_leute_admin_assign($userid, $mode = 'login')
{
    $r = false;

    //Get from cache
    if (isset($GLOBALS['kOOL']['leute_admin_assign'][$userid][$mode])) {
        return $GLOBALS['kOOL']['leute_admin_assign'][$userid][$mode];
    }

    if ($mode == 'login' || $mode == 'all') {
        $assign = db_select_data('ko_admin', "WHERE `id` = '$userid'", 'leute_admin_assign', '', '', true);
        $r[] = $assign['leute_admin_assign'];
    } else {
        if ($mode == 'admingroup') {
            $assign = db_select_data('ko_admingroups', "WHERE `id` = '$userid'", 'leute_admin_assign', '', '', true);
            $r[] = $assign['leute_admin_assign'];
        }
    }

    if ($mode == 'all') {
        $admingroups = ko_get_admingroups($userid);
        foreach ($admingroups as $group) {
            $r[] = $group['leute_admin_assign'];
        }
    }

    //Unset empty entries
    if (is_array($r)) {
        foreach ($r as $k => $v) {
            if (!$v) {
                unset($r[$k]);
            }
        }
    }

    //Only allow if admin_group is set
    if (false === ko_get_leute_admin_groups($userid, $mode)) {
        $r = false;
    }

    //Store in cache
    $GLOBALS['kOOL']['leute_admin_assign'][$userid][$mode] = $r;

    if (sizeof($r) > 0) {
        return $r;
    } else {
        return false;
    }
}//ko_get_leute_admin_assign()
