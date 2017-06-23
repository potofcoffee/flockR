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
 * MODUL-FUNKTIONEN   K L E I N G R U P P E N                                                                           *
 *                                                                                                                      *
 ************************************************************************************************************************/

function ko_get_kleingruppen(&$kg, $z_limit = '', $id = '', $_z_where = '')
{
    global $SMALLGROUPS_ROLES, $SMALLGROUPS_ROLES_FOR_NUM;

    $kg = array();

    //Limit anwenden, falls gesetzt
    if ($z_limit == '0' || $z_limit == 'LIMIT 0' || trim($z_limit) == '') {
        $z_limit = '';
    }

    //Nur einzelne Kleingruppe holen, falls id gesetzt
    if ($id) {
        $z_where = ' WHERE `id` IN (';
        foreach (explode(',', $id) as $i) {
            $z_where .= "'" . $i . "',";
        }
        $z_where = substr($z_where, 0, -1) . ') ';
    } else {
        if ($_z_where != '') {
            $z_where = $_z_where;
        } else {
            $z_where = '';
        }
    }


    if (is_string($_SESSION['sort_kg']) && !in_array($_SESSION['sort_kg'],
            array('anz_leute', 'kg_leiter')) && substr($_SESSION['sort_kg'], 0, 6) != 'MODULE'
    ) {
        $sort_col = $_SESSION['sort_kg'];
        $order = 'ORDER BY `' . $_SESSION['sort_kg'] . '` ' . $_SESSION['sort_kg_order'];
    } else {
        $sort_col = substr($_SESSION['sort_kg'], 0, 6) == 'MODULE' ? substr($_SESSION['sort_kg'], 6) : 'name';
        $order = 'ORDER BY name ASC';
    }


    //Prepare kg members and leaders
    $num_people = array();
    $kg_data = array();
    $kg_members = db_select_data('ko_leute', "WHERE `smallgroups` != '' AND `deleted` = '0'", 'id,smallgroups');
    foreach ($kg_members as $member) {
        $his_kgs = array();
        foreach (explode(',', $member['smallgroups']) as $kgid) {
            if (!$kgid) {
                continue;
            }
            list($_kg, $role) = explode(':', $kgid);
            $kg_data[$role][$_kg][] = $member['id'];
            if (in_array($role, $SMALLGROUPS_ROLES_FOR_NUM)) {
                $his_kgs[] = $_kg;
            }
        }
        //Build sums for number of members for each small group
        $his_kgs = array_unique($his_kgs);
        foreach ($his_kgs as $kgid) {
            $num_people[$kgid] += 1;
        }
    }

    //Get all small groups
    $rows = db_select_data('ko_kleingruppen', $z_where, '*', $order, $z_limit);
    $sort = array();
    foreach ($rows as $row) {
        //Add a column for each role
        foreach ($SMALLGROUPS_ROLES as $role) {
            $row['role_' . $role] = is_array($kg_data[$role][$row['id']]) ? implode(',',
                $kg_data[$role][$row['id']]) : '';
        }
        $row['anz_leute'] = $num_people[$row['id']];

        $kg[$row['id']] = $row;
        $sort[$row['id']] = $row[$sort_col];
    }

    //Manually sort for MODULE column
    if (substr($_SESSION['sort_kg'], 0, 6) == 'MODULE') {
        if ($_SESSION['sort_kg_order'] == 'ASC') {
            asort($sort);
        }
        if ($_SESSION['sort_kg_order'] == 'ASC') {
            arsort($sort);
        }
        $new = array();
        foreach ($sort as $k => $v) {
            $new[$k] = $kg[$k];
        }
        $kg = $new;
    }
}//ko_get_kleingruppen()


function ko_get_smallgroup_by_id($id)
{
    if (isset($GLOBALS['kOOL']['smallgroups'][$id])) {
        return $GLOBALS['kOOL']['smallgroups'][$id];
    }

    $id = format_userinput($id, 'uint');
    $sg = db_select_data('ko_kleingruppen', "WHERE `id` = '$id'", '*', '', '', true);
    $GLOBALS['kOOL']['smallgroups'][$id] = $sg;;
    return $sg;
}//ko_get_smallgroup_by_id()


function ko_kgliste($data)
{
    $r = '';

    //One char is the smallgroup role set in a filter
    if (strlen($data) == 1) {
        return getLL('kg_roles_' . $data);
    }

    if (!isset($GLOBALS["kOOL"]["ko_kleingruppen"])) {
        $GLOBALS["kOOL"]["ko_kleingruppen"] = db_select_data("ko_kleingruppen", "", "*", "ORDER BY name ASC");
    }

    foreach (explode(",", $data) as $id) {
        list($kgid, $role) = explode(':', $id);
        $kgs[] = $GLOBALS["kOOL"]["ko_kleingruppen"][$kgid]["name"] . ($role != '' ? ': ' . getLL('kg_roles_' . $role) : '');
    }
    $r = implode("; ", $kgs);
    return $r;
}//ko_kgliste()


/**
 * Get a list of small groups the given login is assigned to
 *
 * @param $uid Login id (defaults to _SESSION['ses_userid'])
 */
function kg_get_users_kgid($uid = '')
{
    $r = array();
    $uid = $uid ? $uid : $_SESSION['ses_userid'];

    $p = ko_get_logged_in_person($uid);
    foreach (explode(',', $p['smallgroups']) as $kgid) {
        if (!$kgid) {
            continue;
        }
        $r[] = format_userinput(substr($kgid, 0, 4), 'uint');
    }
    return $r;
}//kg_get_users_kgid()

