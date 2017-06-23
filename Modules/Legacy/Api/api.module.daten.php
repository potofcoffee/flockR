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
 * MODUL-FUNKTIONEN   D A T E N                                                                                         *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Get all calendars
 */
function ko_get_event_calendar(&$r, $id = "", $type = "")
{
    $z_where = "WHERE 1=1 ";
    if ($id) {
        $z_where .= " AND `id` = '$id' ";
    }
    if ($type) {
        $z_where .= " AND `type` = '$type' ";
    }

    $r = db_select_data('ko_event_calendar', $z_where, '*', 'ORDER BY name ASC');
}//ko_get_event_calendar()

/**
 * Liefert alle Eventgruppen
 */
function ko_get_eventgruppen(&$grp, $z_limit = "", $z_where = "")
{
    $order = ($_SESSION["sort_tg"]) ? " ORDER BY " . $_SESSION["sort_tg"] . " " . $_SESSION["sort_tg_order"] : " ORDER BY name ASC ";
    $grp = db_select_data('ko_eventgruppen', "WHERE 1=1 $z_where", '*', $order, $z_limit);
}//ko_get_eventgruppen()


/**
 * Liefert einzelne Eventgruppe
 */
function ko_get_eventgruppe_by_id($gid, &$grp)
{
    $grp = db_select_data('ko_eventgruppen', "WHERE `id` = '$gid'", '*', '', 'LIMIT 1', true);
}//ko_get_eventgruppe_by_id()


/**
 * Liefert die Farbe, die einer Eventgruppe zugewiesen ist
 */
function ko_get_eventgruppen_farbe($id)
{
    $row = db_select_data('ko_eventgruppen', "WHERE `id` = '$id'", 'farbe', '', '', true);
    return $row['farbe'];
}//ko_get_eventgruppen_farbe()


/**
 * Liefert den Namen, der einer Eventgruppe zugewiesen ist
 */
function ko_get_eventgruppen_name($id)
{
    $row = db_select_data('ko_eventgruppen', "WHERE `id` = '$id'", 'name', '', '', true);
    return $row['name'];
}//ko_get_eventgruppen_name()


/**
 * Liefert die Reservations-Items, die einer Eventgruppe zugeordnet sind
 */
function ko_get_eventgruppen_resitems($id)
{
    $row = db_select_data('ko_eventgruppen', "WHERE `id` = '$id'", 'resitems', '', '', true);
    return $row['resitems'];
}//ko_get_eventgruppen_resitems()


/**
 * Liefert einzelnen Event
 */
function ko_get_event_by_id($id, &$e)
{
    $e = db_select_data('ko_event', "WHERE `id` = '$id'", '*', '', '', true);
}//ko_get_event_by_id()


/**
 * Liefert alle Events
 */
function ko_get_events(&$e, $z_where = '', $z_limit = '', $table = 'ko_event', $z_sort = '')
{
    $e = array();

    //Replace ko_event in filter with table name
    if ($table != 'ko_event') {
        $z_where = str_replace('ko_event', $table, $z_where);
    }

    if ($z_sort) {
        $order = $z_sort;
    } else {
        $order = $_SESSION["sort_events"] ? " ORDER BY " . ($_SESSION["sort_events"] == "eventgruppen_id" ? "eventgruppen_name" : $_SESSION["sort_events"]) . " " . $_SESSION["sort_events_order"] . ",startzeit " . $_SESSION["sort_events_order"] : " ORDER BY startdatum ASC,startzeit ASC";
    }
    $e = db_select_data($table . ' LEFT JOIN ko_eventgruppen ON ' . $table . '.eventgruppen_id = ko_eventgruppen.id',
        'WHERE 1=1 ' . $z_where,
        $table . '.id AS id, ' . $table . '.*, ko_eventgruppen.name AS eventgruppen_name, ko_eventgruppen.farbe AS eventgruppen_farbe, ko_eventgruppen.res_combined AS res_combined, ko_eventgruppen.type AS eg_type',
        $order, $z_limit);

    //Add color dynamically
    ko_set_event_color($e);
}//ko_get_events()


/**
 * gets all reminders off type $mode and according to where clause
 *
 * @param $result contains the result i.e. the reminders
 * @param string $mode either 1 = event or later 2 = leute
 * @param string $z_where MYSQL where clause, starting with AND or OR
 * @param string $z_limit MYSQL limit clause, starting with LIMIT
 * @param string $z_sort MYSQL order clause, starting with ORDER BY
 */
function ko_get_reminders(
    &$result,
    $mode = null,
    $z_where = '',
    $z_limit = '',
    $z_sort = '',
    $single = false,
    $noIndex = false
)
{
    $where = 'where 1 = 1 ';
    $where .= ($mode === null) ? '' : "and `type` = " . $mode . " ";
    $result = db_select_data('ko_reminder', $where . $z_where, "*", $z_sort, $z_limit, $single, $noIndex);
} // ko_get_reminders


/**
 * Apply event color for each event individually if $EVENT_COLOR is set.
 *
 * @param array &$_events : Array with one event or several passed by reference. eventgruppen_farbe will be set for each event
 */
function ko_set_event_color(&$_events)
{
    global $EVENT_COLOR;

    if (!is_array($EVENT_COLOR) || sizeof($EVENT_COLOR) <= 0) {
        return;
    }

    if (isset($_events['id'])) {
        $events = array($_events['id'] => $_events);
        $single = true;
    } else {
        $events = $_events;
        $single = false;
    }
    foreach ($events as $k => $event) {
        $color = $EVENT_COLOR['map'][$event[$EVENT_COLOR['field']]];
        if ($color) {
            $events[$k]['eventgruppen_farbe'] = $color;
        }
    }

    if ($single) {
        $_events = array_shift($events);
    } else {
        $_events = $events;
    }
}//ko_set_event_color()


/**
 * Liefert alle zu moderierenden Events
 */
function ko_get_events_mod(&$e, $z_where = "", $z_limit = "")
{
    ko_get_events($e, $z_where, $z_limit, 'ko_event_mod');
}//ko_get_events_mod()


/**
 * Liefert alle Events an einem Datum
 */
function ko_get_events_by_date($t = "", $m, $j, &$r, $z_where = "", $table = "ko_event")
{
    $datum = $j . "-" . str_to_2($m) . "-" . ($t ? str_to_2($t) : "01");

    //Replace ko_event in filter with table name
    if ($table != 'ko_event') {
        $z_where = str_replace('ko_event', $table, $z_where);
    }

    $r = db_select_data($table . ' LEFT JOIN ko_eventgruppen ON ko_event.eventgruppen_id = ko_eventgruppen.id',
        "WHERE (`startdatum` <= '$datum' AND `enddatum` >= '$datum') $z_where",
        'ko_event.id AS id, ko_event.*, ko_eventgruppen.name AS eventgruppen_name',
        'ORDER BY `startdatum` ASC, `startzeit` ASC');
}//ko_get_events_by_date()


function kota_ko_event_eventgruppen_id_dynselect(&$values, &$descs, $rights = 0, $_where = "")
{
    global $access;

    if (!isset($access['daten'])) {
        ko_get_access('daten');
    }

    $values = $descs = array();
    $cals = db_select_data("ko_event_calendar", "WHERE 1=1", "*", "ORDER BY `name` ASC");
    foreach ($cals as $cid => $cal) {
        if ($access['daten']['cal' . $cid] < $rights) {
            continue;
        }
        //Add cal name (as optgroup)
        $descs["i" . $cid] = $cal["name"];
        //Get groups for this cal (only show groups with type=0 (kOOL) but not imported event groups (type>0))
        $where = "WHERE `calendar_id` = '$cid' AND `type` = '0' ";
        $where .= $_where;
        $groups = db_select_data("ko_eventgruppen", $where, "*", "ORDER BY `name` ASC");
        foreach ($groups as $gid => $group) {
            if ($access['daten'][$gid] < $rights) {
                continue;
            }
            $values["i" . $cid][$gid] = $gid;
            $descs[$gid] = $group["name"];
        }
    }//foreach(cals)
    //Add all event groups without calendars
    $groups = db_select_data("ko_eventgruppen", "WHERE `calendar_id` = '0' AND `type` = '0'", "*",
        "ORDER BY `name` ASC");
    foreach ($groups as $gid => $group) {
        if ($access['daten'][$gid] < $rights) {
            continue;
        }
        $values[$gid] = $gid;
        $descs[$gid] = $group["name"];
    }
}//kota_ko_event_eventgruppen_id_dynselect()


/**
 * Find moderators for a given event group
 */
function ko_get_moderators_by_eventgroup($gid)
{
    global $LEUTE_EMAIL_FIELDS;

    //email fields
    $email_fields = $where_email = '';
    foreach ($LEUTE_EMAIL_FIELDS as $field) {
        $email_fields .= 'l.' . $field . ' AS ' . $field . ', ';
        $where_email .= " l.$field != '' OR ";
    }
    $email_fields = substr($email_fields, 0, -2);

    //Get moderators for this event group
    $logins = db_select_data("ko_admin AS a LEFT JOIN ko_leute as l ON a.leute_id = l.id",
        "WHERE ($where_email a.email != '') AND (a.disabled = '0' OR a.disabled = '')",
        "a.id AS id, $email_fields, a.email AS admin_email, l.id AS leute_id");
    foreach ($logins as $login) {
        $all = ko_get_access_all('daten', $login['id'], $max);
        if ($max < 4) {
            continue;
        }
        $user_access = ko_get_access('daten', $login['id'], true, true, 'login', false);
        if ($user_access['daten'][$gid] < 4) {
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
}//ko_get_moderators_by_eventgroup()


