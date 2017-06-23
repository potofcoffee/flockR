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
 * MODUL-FUNKTIONEN   A D M I N                                                                                         *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Liefert alle Logins
 */
function ko_get_logins(&$l, $z_where = "", $z_limit = "", $sort_ = "")
{
    global $ko_menu_akt;

    if ($sort_ != "") {
        $sort = $sort_;
    } else {
        if ($ko_menu_akt == 'admin' && $_SESSION['sort_logins'] && $_SESSION['sort_logins_order']) {
            $sort = 'ORDER BY ' . $_SESSION['sort_logins'] . ' ' . $_SESSION['sort_logins_order'];
            if ($_SESSION['sort_logins'] != 'login') {
                $sort .= ', login ASC';
            }
        } else {
            $sort = "ORDER BY login ASC";
        }
    }


    //Treat special order columns
    if ($ko_menu_akt == 'admin' && substr($_SESSION['sort_logins'], 0, 6) == 'MODULE') {
        switch (substr($_SESSION['sort_logins'], 6)) {
            //Order by name of assigned person
            case 'leute_id':
                $l = db_select_data('ko_admin AS a LEFT JOIN ko_leute AS l ON a.leute_id = l.id', 'WHERE 1 ' . $z_where,
                    "a.id AS id, a.*, CONCAT_WS(' ', l.vorname, l.nachname) AS _name",
                    'ORDER BY _name ' . $_SESSION['sort_logins_order'] . ', login ASC', $z_limit);
                break;

            //Order by status (enabled/disabled) and by login name
            case 'disabled':
                $l = db_select_data('ko_admin', 'WHERE 1 ' . $z_where, '*, LENGTH(disabled) AS _len',
                    'ORDER BY _len ' . $_SESSION['sort_logins_order'] . ', login ASC', $z_limit);
                break;
        }
    } //No special ordering, so just get logins through SQL
    else {
        $l = db_select_data('ko_admin', 'WHERE 1=1 ' . $z_where, '*', $sort, $z_limit);
    }
}//ko_get_logins()


/**
 * Liefert ein einzelnes Login
 */
function ko_get_login($id, &$l)
{
    $l = db_select_data('ko_admin', "WHERE `id` = '$id'", '*', '', '', true);
}//ko_get_login()


/**
 * Liefert Etiketten-Einstellungen
 */
function ko_get_etiketten_vorlagen(&$v)
{
    $v = array();
    $query = "SELECT * FROM `ko_etiketten` WHERE `key` = 'name' ORDER BY `value`";
    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        $v[] = $row;
    }
}//ko_get_etiketten_vorlagen()


/**
 * Liefert einzelne Etiketten-Vorlagen-Werte
 */
function ko_get_etiketten_vorlage($id, &$v)
{
    $v = array();
    $query = "SELECT * FROM `ko_etiketten` WHERE `vorlage` = '$id'";
    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        $v[$row["key"]] = $row["value"];
    }
}//ko_get_etiketten_vorlagen()


/**
 * Speichert eine komplette Etiketten-Vorlage
 */
function ko_save_etiketten_vorlage($id, $values, $mode = "new")
{
    foreach ($values as $key => $value) {
        if ($mode == "new") {
            db_insert_data('ko_etiketten', array('vorlage' => $id, 'key' => $key, 'value' => $value));
        } else {
            if ($mode == "edit") {
                //Test if this key is already available and only update then, otherwise insert
                if (db_get_count('ko_etiketten', 'vorlage', "AND `vorlage` = '$id' AND `key` = '$key'") > 0) {
                    db_update_data('ko_etiketten', "WHERE `vorlage` = '$id' AND `key` = '$key'",
                        array('value' => $value));
                } else {
                    db_insert_data('ko_etiketten', array('vorlage' => $id, 'key' => $key, 'value' => $value));
                }
            }
        }
    }
}//ko_save_etiketten_vorlage()

