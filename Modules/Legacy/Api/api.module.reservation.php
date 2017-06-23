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
 * MODUL-FUNKTIONEN   R E S E R V A T I O N                                                                             *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Liefert einzelne Reservation
 */
function ko_get_res_by_id($id, &$r)
{
    $r = db_select_data('ko_reservation', "WHERE `id` = '$id'");
}//ko_get_res_by_id()


/**
 * Liefert die Farbe eines Reservations-Items
 */
function ko_get_resitem_farbe($id)
{
    $row = db_select_data('ko_resitem', "WHERE `id` = '$id'", 'farbe', '', '', true);
    return $row['farbe'];
}


/**
 * Liefert alle Reservationen (normale oder zu moderierende) zu einem definierten Datum
 */
function ko_get_res_by_date($t = "", $m, $j, &$r, $show_all = true, $mode = "res", $z_where = "")
{
    global $access, $ko_menu_akt;

    //Reservationen oder Mod-Res
    if ($mode == "res") {
        $db_table = "ko_reservation";
    } else {
        if ($mode == "mod") {
            $db_table = "ko_reservation_mod";
        } else {
            return;
        }
    }

    $datum = $j . "-" . str_to_2($m) . "-" . ($t ? str_to_2($t) : "01");

    $where = "WHERE (`startdatum`<='$datum' AND `enddatum`>='$datum')";

    if ($mode == "res") {
        if ($ko_menu_akt == "reservation" && sizeof($_SESSION["show_items"]) == 0) {
            return false;
        }
        if (!$show_all) {
            $where .= " AND (`item_id` IN ('" . implode("', '", $_SESSION["show_items"]) . "'))";
        }//if(!show_all)
    } else {
        if ($mode == "mod") {
            if ($access['reservation']['ALL'] > 4) {
                $where .= '';
            } else {
                if ($access['reservation']['MAX'] > 4) {
                    $items = array();
                    foreach ($access['reservation'] as $k => $v) {
                        if (!intval($k) || $v < 5) {
                            continue;
                        }
                        $items[] = $k;
                    }
                    $where .= ' AND `item_id` IN (\'' . implode("','", $items) . "') ";
                } else {
                    $where .= ' AND 1=2 ';
                }
            }
        }
    }//if..else(mode==res)
    $r = db_select_data($db_table, $where . ' ' . $z_where, '*', 'ORDER BY `startdatum` ASC, `startzeit` ASC');
}//ko_get_res_by_date()


/**
 * Liefert alle normalen oder moderierten Reservationen
 */
function ko_get_reservationen(&$r, $z_where, $z_limit = '', $type = 'res', $z_sort = '')
{
    $r = array();

    //Sortierung
    if ($z_sort) {
        $sort = $z_sort;
    } else {
        if ($_SESSION["sort_item"] && $_SESSION["sort_item_order"]) {
            $sort = "ORDER BY " . ($_SESSION["sort_item"] == "item_id" ? "item_name" : $_SESSION["sort_item"]) . " " . $_SESSION["sort_item_order"];
        } else {
            $sort = 'ORDER BY startdatum,startzeit,item_name ASC';
        }
    }

    //Reservationen oder Mod-Res
    if ($type == "res") {
        $db_table = "ko_reservation";
    } else {
        if ($type == "mod") {
            $db_table = "ko_reservation_mod";
        } else {
            return;
        }
    }

    //WHERE anwenden, oder falls leer, eine FALSE-Bedingung einf�gen
    if ($z_where != "") {
        $z_where = "WHERE 1=1 " . $z_where;
    } else {
        $z_where = "WHERE 1=2";
    }  //Nichts anzeigen

    $query = "SELECT $db_table.*,ko_resitem.name AS item_name,ko_resitem.farbe AS item_farbe,ko_resitem.gruppen_id AS gruppen_id FROM $db_table LEFT JOIN ko_resitem ON $db_table.item_id = ko_resitem.id $z_where $sort $z_limit";
    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        $r[$row["id"]] = $row;
    }//while(row)
}//ko_get_reservationen()


/**
 * Liefert alle Res-Items einer Res-Gruppe
 */
function ko_get_resitems_by_group($g, &$r)
{
    $r = array();
    ko_get_resitems($r, "", "WHERE ko_resitem.gruppen_id = '$g'");
}//ko_get_resitems_by_group()


/**
 * Liefert ein einzelnes Res-Item
 */
function ko_get_resitem_by_id($id, &$r)
{
    $r = array();
    ko_get_resitems($r, "", "WHERE ko_resitem.id = '$id'");
}//ko_get_resitem_by_id()


/**
 * Liefert den Namen eines Res-Items
 */
function ko_get_resitem_name($id)
{
    $row = db_select_data('ko_resitem', "WHERE `id` = '$id'", 'name', '', '', true);
    return $row['name'];
}//ko_get_resitem_name()


/**
 * Liefert alle Resitems in sortierter Reihenfolge
 */
function ko_get_resitems(&$r, $z_limit = "", $z_where = "")
{
    $order = ($_SESSION["sort_group"]) ? (" ORDER BY " . ($_SESSION["sort_group"] == "gruppen_id" ? "gruppen_name" : $_SESSION["sort_group"]) . " " . $_SESSION["sort_group_order"]) : " ORDER BY name ASC ";
    $query = "SELECT ko_resitem.*,ko_resgruppen.name AS gruppen_name FROM ko_resitem LEFT JOIN ko_resgruppen ON ko_resitem.gruppen_id = ko_resgruppen.id $z_where $order $z_limit";
    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        $r[$row["id"]] = $row;
    }
}//ko_get_resitems()


/**
 * Liefert eine Liste aller oder einer einzelnen Res-Gruppen
 */
function ko_get_resgroups(&$r, $id = "")
{
    $where = $id != '' ? "WHERE `id` = '$id'" : 'WHERE 1=1';
    $r = db_select_data('ko_resgruppen', $where, '*', 'ORDER BY name ASC');
}//ko_get_resgroups()


/**
 * Liefert alle zu moderierenden Reservationen der angegebenen Resitems
 */
function ko_get_res_mod(&$r, $items, $user_id = "")
{
    $where = "";
    //Apply filter for items
    if (is_array($items)) {
        $where .= " AND (`item_id` IN ('" . implode("','", $items) . "')) ";
    }
    //Apply filter for user_id if given
    if ($user_id > 0 && $user_id != ko_get_guest_id()) {
        $where .= " AND (`user_id` = '$user_id') ";
    }

    $r = db_select_data("ko_reservation_mod", "WHERE 1=1 $where", "*");
}//ko_get_res_mod()


/**
 * Liefert eine einzelne zu moderierende Reservation
 */
function ko_get_res_mod_by_id(&$r, $id)
{
    $r = db_select_data('ko_reservation_mod', "WHERE `id` = '$id'");
}//ko_get_res_mod_by_id()


/**
 * Find moderators for a given res item
 * Uses ko_get_moderators_by_resgroup()
 */
function ko_get_moderators_by_resitem($item_id)
{
    //Find resgroup id
    $item = db_select_data("ko_resitem", "WHERE `id` = '$item_id'", "gruppen_id", "", "", true);
    $gid = $item["gruppen_id"];

    return ko_get_moderators_by_resgroup($gid);
}//ko_get_moderators_by_resitem()


/**
 * Find moderators for a given res group
 */
function ko_get_moderators_by_resgroup($gid)
{
    global $LEUTE_EMAIL_FIELDS;

    //email fields
    $email_fields = $where_email = '';
    foreach ($LEUTE_EMAIL_FIELDS as $field) {
        $email_fields .= 'l.' . $field . ' AS ' . $field . ', ';
        $where_email .= " l.$field != '' OR ";
    }
    $email_fields = substr($email_fields, 0, -2);

    //Get moderators for this resgroup
    $logins = db_select_data("ko_admin AS a LEFT JOIN ko_leute as l ON a.leute_id = l.id",
        "WHERE ($where_email a.email != '') AND (a.disabled = '0' OR a.disabled = '')",
        "a.id AS id, $email_fields, a.email AS admin_email, l.id AS leute_id, l.vorname AS vorname, l.nachname AS nachname, a.login as login");
    foreach ($logins as $login) {
        $all = ko_get_access_all('res_admin', $login['id'], $max);
        if ($max < 4) {
            continue;
        }
        $user_access = ko_get_access('reservation', $login['id'], true, true, 'login', false);
        if ($user_access['reservation']['grp' . $gid] < 5) {
            continue;
        }
        $mods[$login['id']] = $login;
    }
    $add_mods = array();
    foreach ($mods as $i => $mod) {
        //Use admin_email as set for the login in first priority
        if ($mod['admin_email']) {
            $mods[$i]['email'] = $mod['admin_email'];
        } else {
            //Get all email addresses for this person
            ko_get_leute_email($mod['leute_id'], $email);
            $mods[$i]['email'] = $email[0];
            //Create additional moderators for every email address to be used (if several are set in ko_leute_preferred_fields)
            if (sizeof($email) > 1) {
                for ($j = 1; $j < sizeof($email); $j++) {
                    $add_mods[$j] = $mod;
                    $add_mods[$j]['email'] = $email[$j];
                }
            }
        }
    }
    if (sizeof($add_mods) > 0) {
        $mods = array_merge($mods, $add_mods);
    }

    return $mods;
}//ko_get_moderators_by_resgroup()


/**
 * Get values to be used in smarty html_options to display res items in a select
 * Adds optgroups for res groups
 */
function kota_ko_reservation_item_id_dynselect(&$values, &$descs, $rights = 0, $_where = "")
{
    global $access;

    ko_get_access('reservation');

    $values = $descs = array();
    $groups = db_select_data("ko_resgruppen", "WHERE 1=1", "*", "ORDER BY `name` ASC");
    foreach ($groups as $gid => $group) {
        if ($access['reservation']['grp' . $gid] < $rights) {
            continue;
        }
        //Add group name (as optgroup)
        $descs["i" . $gid] = $group["name"];
        //Get items for this group
        $where = "WHERE `gruppen_id` = '$gid' ";
        $where .= $_where;
        $items = db_select_data("ko_resitem", $where, "*", "ORDER BY `name` ASC");
        foreach ($items as $iid => $item) {
            if ($access['reservation'][$iid] < $rights) {
                continue;
            }
            $values["i" . $gid][$iid] = $iid;
            $descs[$iid] = $item["name"];
        }
    }//foreach(groups)
}//kota_ko_reservation_item_id_dynselect()

