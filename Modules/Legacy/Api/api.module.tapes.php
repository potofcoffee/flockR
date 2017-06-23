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
 * MODUL-FUNKTIONEN  T A P E S                                                                                          *
 *                                                                                                                      *
 ************************************************************************************************************************/

function ko_get_tapes(&$tapes, $z_where = "", $z_limit = "")
{
    if ($_SESSION["sort_tapes"] && $_SESSION["sort_tapes_order"]) {
        $sort = " ORDER BY " . $_SESSION["sort_tapes"] . " " . $_SESSION["sort_tapes_order"];
    } else {
        $sort = "ORDER BY date DESC";
    }

    if ($z_where) {
        $z_where = "WHERE (ko_tapes_groups.id = ko_tapes.group_id) " . $z_where;
    } else {
        $z_where = "WHERE ko_tapes_groups.id = ko_tapes.group_id";
    }

    $query = "SELECT ko_tapes.*, ko_tapes_groups.name as group_name FROM `ko_tapes`, `ko_tapes_groups` $z_where $sort $z_limit";

    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        //Serie auslesen, falls eine definiert
        if ($row["serie_id"] > 0) {
            ko_get_tapeseries($serie, "AND `id` = '" . $row["serie_id"] . "'");
            $row["serie_name"] = $serie[$row["serie_id"]]["name"];
        }
        $tapes[$row["id"]] = $row;
    }
    return true;
}//ko_get_tapes()


function ko_get_tapeseries(&$series, $z_where = "", $z_limit = "")
{
    $series = db_select_data('ko_tapes_series', 'WHERE 1=1 ' . $z_where, '*', 'ORDER BY name ASC', $z_limit);
}//ko_get_tapeseries


function ko_get_tapegroups(&$groups, $z_where = "", $z_limit = "")
{
    $groups = db_select_data('ko_tapes_groups', $z_where, '*', 'ORDER BY name ASC', $z_limit);
}//ko_get_tapegroups()


function ko_get_preachers(&$preachers)
{
    $preachers = db_select_distinct('ko_tapes', 'preacher', 'ORDER BY preacher ASC');
}//ko_get_preachers()


/**
 * Falls ID gesetzt ist, kommt nur das Daten-Array zur�ck ansonsten kommen alle Layoute roh zur�ck
 */
function ko_get_tape_printlayout($id = "")
{
    if ($id != "") {
        $where = "WHERE `id` = '$id'";
    } else {
        $where = "";
    }

    $query = "SELECT * FROM `ko_tapes_printlayout` $where ORDER BY name ASC";
    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        if ($id != "") {
            $r = unserialize($row["data"]);
            $r["id"] = $row["id"];
            $r["name"] = $row["name"];
            $r["default"] = $row["default"];
        } else {
            $r[$row["id"]] = $row;
        }
    }
    return $r;
}//ko_get_tape_printlayout()

