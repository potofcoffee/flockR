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
 * MODUL-FUNKTIONEN  G R O U P E S                                                                                      *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Liefert alle Gruppen
 */
function ko_get_groups(&$groups, $z_where = "", $z_limit = "", $sort_ = "")
{
    if (!$sort_) {
        $sort = ($_SESSION["sort_groups"]) ? "ORDER BY " . $_SESSION["sort_groups"] . " " . $_SESSION["sort_groups_order"] : "ORDER BY name ASC";
    } else {
        $sort = $sort_;
    }
    $groups = db_select_data('ko_groups', 'WHERE 1=1 ' . $z_where, '*', $sort, $z_limit);
}//ko_get_groups()


/**
 * Liefert alle Rollen
 */
function ko_get_grouproles(&$roles, $z_where = "", $z_limit = "")
{
    $sort = ($_SESSION["sort_grouproles"]) ? "ORDER BY " . $_SESSION["sort_grouproles"] . " " . $_SESSION["sort_grouproles_order"] : "ORDER BY name ASC";
    $roles = db_select_data('ko_grouproles', 'WHERE 1=1 ' . $z_where, '*', $sort, $z_limit);
}//ko_get_grouproles()


/**
 * Liefert alle IDs und Bezeichnungen f�r alle Rollen in einer Gruppe
 */
function ko_groups_get_group_id_names($gid, &$groups, &$roles, $do_roles = true)
{
    //Nicht aus Cache holen, sondern neu berechnen
    $values = $descs = $all_descs = array();

    //Gruppe
    $group = $groups[$gid];
    //Mutter-Gruppen
    $m = $group;
    $line = array("g" . $m["id"]);
    while ($m["pid"]) {
        $m = $groups[$m["pid"]];
        $line[] = "g" . $m["id"];
    }
    $line = array_reverse($line);

    //Gruppe selber
    if (!$group['maxcount'] || $group['count'] < $group['maxcount'] || $group['count_role']) {
        $values[] = implode(":", $line);
        $descs[] = $group['name'] . ($group['maxcount'] > 0 ? ' (' . $group['count'] . '/' . $group['maxcount'] . ')' : '');
    }
    //Alle Rollen
    if ($do_roles && $group["roles"] != "") {
        foreach (explode(",", $group["roles"]) as $role) {
            if (!$role) {
                continue;
            }
            //If maxcount is reached don't include this role in values/descs (left select of doubleselect)
            // But store in all_descs so it can be displayed in right select of assigned values
            if ($group['maxcount'] > 0 && $group['count'] >= $group['maxcount'] && $group['count_role'] == $role) {
                $v = implode(':', $line) . ':r' . $role;
                $all_descs[$v] = $group['name'] . ': ' . $roles[$role]['name'];
            } else {
                $values[] = implode(":", $line) . ":r" . $role;
                $descs[] = $group["name"] . ": " . $roles[$role]["name"];
            }
        }
    }

    return array($values, $descs, $all_descs);
}//ko_groups_get_group_id_names()


/**
 * Liefert einzelne Bestandteile eines Gruppen-Rollen-Strings
 */
function ko_groups_decode($all, $type, $limit = 0)
{
    global $all_groups;

    if (strlen($all) == 6) { //Einzelne Gruppen-ID �bergeben
        $mother_line = array();
        $base_group = $all;
    } else { //Sonst handelt es sich um ein g:000001:r000002 usw.
        $parts = explode(":", $all);
        $base_found = false;
        $mother_line = array();
        for ($i = (sizeof($parts) - 1); $i >= 0; $i--) {
            if (substr($parts[$i], 0, 1) == "r") {
                $rolle = substr($parts[$i], 1);
            } else {
                if (substr($parts[$i], 0, 1) == "g") {
                    if (!$base_found) {
                        $base_group = substr($parts[$i], 1);
                        $base_found = true;
                    } else {
                        $mother_line[] = substr($parts[$i], 1);
                        //Save the groupnames of the motherline aswell for the full path plus role
                        if ($type == "group_desc_full") {
                            if (isset($all_groups[substr($parts[$i], 1)])) {
                                $mother_line_names[] = $all_groups[substr($parts[$i], 1)]["name"];
                            } else {
                                ko_get_groups($group, "AND `id` = '" . substr($parts[$i], 1) . "'");
                                $mother_line_names[] = $group[substr($parts[$i], 1)]["name"];
                            }
                        }
                    }
                }
            }
        }
    }//if..else(strlen(all) == 6)

    switch ($type) {
        case "group_id":
            return $base_group;
            break;

        case "group":
            if (isset($all_groups[$base_group])) {
                return $all_groups[$base_group];
            } else {
                ko_get_groups($group, "AND `id` = '$base_group'");
                return $group[$base_group];
            }
            break;

        case "group_desc":
        case "group_desc_full":
            reset($mother_line_names);
            if (isset($all_groups[$base_group])) {
                $group[$base_group] = $all_groups[$base_group];
            } else {
                ko_get_groups($group, "AND `id` = '$base_group'");
            }
            if ($rolle) {
                ko_get_grouproles($role, "AND `id` = '$rolle'");
                if ($type == "group_desc_full") {
                    return implode(":",
                            array_reverse($mother_line_names)) . (sizeof($mother_line_names) > 0 ? ":" : "") . $group[$base_group]["name"] . ":" . $role[$rolle]["name"];
                } else {
                    return $group[$base_group]["name"] . ": " . $role[$rolle]["name"];
                }
            } else {
                if ($type == "group_desc_full") {
                    $value = implode(":",
                            array_reverse($mother_line_names)) . (sizeof($mother_line_names) > 0 ? ":" : "") . $group[$base_group]["name"];
                    if ($limit && strlen($value) > $limit) {
                        $limit = floor($limit / 2) - 2;
                        return substr($value, 0, $limit) . "[..]" . substr($value, -1 * $limit);
                    } else {
                        return $value;
                    }
                } else {
                    return $group[$base_group]["name"];
                }
            }
            break;

        case "group_description":
            if (isset($all_groups[$base_group])) {
                return $all_groups[$base_group]["description"];
            } else {
                ko_get_groups($group, "AND `id` = '$base_group'");
                return $group[$base_group]["description"];
            }
            break;

        case "role_id":
            return $rolle;
            break;

        case "role_desc":
            if (!$rolle) {
                return $group[$base_group]["name"];
            }
            ko_get_grouproles($role, "AND `id` = '$rolle'");
            return $role[$rolle]["name"];
            break;

        case "mother_line":
            return array_reverse($mother_line);
            break;


        case 'full_gid':
            if (!is_array($all_groups)) {
                ko_get_groups($all_groups);
            }
            if (sizeof($mother_line) == 0) {
                $mother_line = ko_groups_get_motherline($base_group, $all_groups);
            } else {
                $mother_line = array_reverse($mother_line);
            }
            $mids = array();
            foreach ($mother_line as $mg) {
                $mids[] = 'g' . $all_groups[$mg]['id'];
            }
            $full_id = (sizeof($mids) > 0 ? implode(':', $mids) . ':' : '') . 'g' . $base_group;
            if ($rolle) {
                $full_id .= ':r' . $rolle;
            }
            return $full_id;
            break;
    }
}//ko_groups_decode()


function ko_groups_get_motherline($gid, &$groups)
{
    if (!$gid) {
        return;
    }

    if (!is_array($groups)) {
        ko_get_groups($groups);
    }

    $mother_line = array();
    $group = $groups[$gid];
    while ($group["pid"]) {
        $pid = $group["pid"];
        $mother_line[] = $pid;
        $group = $groups[$pid];
        $gid = $pid;
    }
    return array_reverse($mother_line);
}//ko_groups_get_motherline()


/**
 * Erstellt Save-String gem�ss POST-Werten und Berechtigungen, damit nicht bearbeitbare nicht rausfliegen
 */
function ko_groups_get_savestring(&$value, $data, &$log, $_bisher = null, $apply_start_stop = true, $do_ezmlm = true)
{
    global $access;

    if (!ko_module_installed("groups")) {
        return;
    }
    ko_get_access('groups');

    //Behandlung der Gruppen
    //Einzeln hinzuf�gen oder l�schen, damit Rechte eingehalten werden. (Bestehende, nicht anzeigbare w�rden sonst gel�scht, da nicht in Formular)
    if (isset($_bisher)) {
        $bisher = explode(",", $_bisher);
    } else {
        ko_get_person_by_id($data["id"], $person);
        $bisher = explode(",", $person["groups"]);
    }
    $submited = explode(",", $value);
    $log = " ";
    //Neu eingetragene:
    $linkedGroups = array();
    foreach ($submited as $g) {
        if (!$g) {
            continue;
        }
        $group = ko_groups_decode($g, "group");
        //Don't work on timed groups
        if ($apply_start_stop && ($group["stop"] != "0000-00-00" && $group["stop"] <= strftime("%Y-%m-%d", time()))) {
            continue;
        }
        if ($apply_start_stop && ($group["start"] != "0000-00-00" && $group["start"] > strftime("%Y-%m-%d", time()))) {
            continue;
        }

        //Check for maxcount
        if ($group['maxcount'] > 0 && $group['count'] >= $group['maxcount'] && (!$group['count_role'] || $group['count_role'] == ko_groups_decode($g,
                    'role_id'))
        ) {
            continue;
        }

        if (!in_array($g, $bisher) && ($access['groups']['ALL'] > 1 || $access['groups'][$group['id']] > 1)) {
            $bisher[] = $g;
            $linkedGroupString = implode(',', ko_groups_get_linked_groups($g));
            if ($linkedGroupString != '' && !in_array($linkedGroupString, $bisher)) {
                $linkedGroups[] = $linkedGroupString;
            }
            $log .= "+" . ko_groups_decode($g, "group_desc") . ", ";
            //Check for new ezmlm subscription
            if ($do_ezmlm && defined("EXPORT2EZMLM") && EXPORT2EZMLM && $group["ezmlm_list"] != "") {
                if (!is_array($person)) {
                    ko_get_person_by_id($data["id"], $person);
                }
                ko_ezmlm_subscribe($group["ezmlm_list"], $group["ezmlm_moderator"], $person["email"]);
            }
        }
    }
    //Gel�schte:
    foreach ($bisher as $b_i => $b) {
        $group = ko_groups_decode($b, "group");
        //Falls col==MODULEgrp ist (also die Funktion aus multiedit heraus aufgerufen wird), nur gew�hlte Gruppe bearbeiten,
        //sonst fallen alle anderen Gruppen-Einteilungen raus
        if (substr($data["col"], 0, 9) == "MODULEgrp" && $group["id"] != substr($data["col"], 9)) {
            continue;
        }
        //Don't work on timed groups
        if ($apply_start_stop && ($group["stop"] != "0000-00-00" && $group["stop"] <= strftime("%Y-%m-%d", time()))) {
            continue;
        }
        if ($apply_start_stop && ($group["start"] != "0000-00-00" && $group["start"] > strftime("%Y-%m-%d", time()))) {
            continue;
        }

        if (($access['groups']['ALL'] > 1 || $access['groups'][$group['id']] > 1) && !in_array($b, $submited)) {
            unset($bisher[$b_i]);
            $log .= "-" . ko_groups_decode($b, "group_desc") . ", ";
            //Check for ezmlm subscription to cancel
            if ($do_ezmlm && defined("EXPORT2EZMLM") && EXPORT2EZMLM && $group["ezmlm_list"] != "") {
                if (!is_array($person)) {
                    ko_get_person_by_id($data["id"], $person);
                }
                ko_ezmlm_unsubscribe($group["ezmlm_list"], $group["ezmlm_moderator"], $person["email"]);
            }
        }
    }

    $linkedGroups = ko_groups_remove_spare_norole($linkedGroups);

    //get rid of empty entries
    $r = array();
    foreach (array_unique(array_merge($bisher, $linkedGroups)) as $v) {
        if (!$v) {
            continue;
        }
        $r[] = $v;
    }


    $value = implode(",", $r);
}//ko_groups_get_savestring()


function ko_groups_get_linked_groups($group, $result = array())
{
    $groupId = ko_groups_decode($group, 'group_id');
    $role = ko_groups_decode($group, 'role_id');

    $linkedGroupId = null;
    $res = db_select_data('ko_groups', 'where id = ' . $groupId, 'linked_group', '', '', true, true);
    if ($res['linked_group'] != '') {
        $linkedGroupId = $res['linked_group'];
    }
    if ($linkedGroupId === null) {
        return $result;
    }

    // prevent loops in recursion
    if (isset($result[$linkedGroupId])) {
        return $result;
    }

    $linkedGroup = db_select_data('ko_groups', 'where id = ' . $linkedGroupId, 'roles', '', '', true, true);
    if ($linkedGroup === null) {
        // TODO: maybe print info that linked group wasn't found
        return $result;
    }


    $linkedRolesString = $linkedGroup['roles'];
    $linkedRoles = explode(',', $linkedRolesString);
    $linkedRole = '';
    if (in_array($role, $linkedRoles)) {
        $linkedRole = $role;
    }

    $fullLinkedGroupId = ko_groups_decode($linkedGroupId, 'full_gid');
    $result[$linkedGroupId] = $fullLinkedGroupId . ($linkedRole == '' ? '' : ':r' . $linkedRole);

    $recursiveResult = ko_groups_get_linked_groups($fullLinkedGroupId . ($role == '' ? '' : ':r' . $role), $result);

    return $recursiveResult;

}//ko_groups_get_linked_groups

/**
 * Removes group assignments without role if the person is also assigned with a role. Applies only to groups with
 * exactly 1 role
 *
 * @param $groups
 * @return array
 */
function ko_groups_remove_spare_norole($groups)
{
    if ($groups == '' || sizeof($groups) == 0) {
        return array();
    }

    if (!(is_array($groups))) {
        $groups = explode(',', $groups);
    }

    sort($groups);

    $lastNoroleIndex = null;
    $lastNoroleKey = null;

    foreach ($groups as $k => $group) {
        $groupId = format_userinput(ko_groups_decode($group, 'group_id'), 'uint');
        if ($groupId == '' || $groupId == 0) {
            unset($groups[$k]);
            continue;
        }
        if (strpos($group, 'r') === false) {
            $roles = db_select_data('ko_groups', 'where id = ' . $groupId, 'roles', '', '', true, true);
            if ($roles !== null) {
                $roles = $roles['roles'];
                if (strpos($roles, ',') === false) {
                    $lastNoroleIndex = $k;
                    $lastNoroleGroup = $groupId;
                }
            }
        } else {
            if ($groupId == $lastNoroleGroup) {
                unset ($groups[$lastNoroleIndex]);
            }
        }
    }

    return array_merge($groups);
}//ko_groups_remove_spare_norole


/**
 * saves the datafields for a person
 */
function ko_groups_save_datafields($value, $data, &$log)
{
    global $all_groups, $access;

    if (!$all_groups) {
        ko_get_groups($all_groups);
    }

    $id = $data["id"];
    $current_groups = array();
    //save datafields of assigned groups
    foreach (explode(",", $data["groups"]) as $group_id) {
        if (!$group_id) {
            continue;
        }
        $gid = ko_groups_decode($group_id, "group_id");
        $current_groups[] = $gid;
        if ($access['groups']['ALL'] < 2 && $access['groups'][$gid] < 2) {
            continue;
        }
        //Don't touch groups with start or stop date set. Their values would be empty and so set to empty as they where not in the form
        if ($all_groups[$gid]["stop"] != "0000-00-00" && $all_groups[$gid]["stop"] < strftime("%Y-%m-%d", time())) {
            continue;
        }
        if ($all_groups[$gid]["start"] != "0000-00-00" && $all_groups[$gid]["start"] > strftime("%Y-%m-%d", time())) {
            continue;
        }

        if ($all_groups[$gid]["datafields"]) {
            $value_log = "";
            // go through all datafields
            foreach (explode(",", $all_groups[$gid]["datafields"]) as $fid) {
                // get current df value
                $old_df = db_select_data("ko_groups_datafields_data",
                    "WHERE `datafield_id` = '$fid' AND `person_id` = '$id' AND `group_id` = '$gid'");
                $old_df = array_shift($old_df);
                // only update and log changes if value has been changed
                if (isset($old_df["value"])) {
                    if ($old_df["value"] != $value[$gid][$fid]) {
                        db_update_data("ko_groups_datafields_data",
                            "WHERE `datafield_id` = '$fid' AND `person_id` = '$id' AND `group_id` = '$gid'",
                            array("value" => $value[$gid][$fid]));
                        $value_log .= $value[$gid][$fid] . ", ";
                    }
                } else {
                    db_insert_data("ko_groups_datafields_data", array(
                        "group_id" => $gid,
                        "person_id" => $id,
                        "datafield_id" => $fid,
                        "value" => $value[$gid][$fid]
                    ));
                    $value_log .= $value[$gid][$fid] . ", ";
                }
            }
            if ($value_log) {
                $log .= $all_groups[$gid]["name"] . ": " . $value_log;
            }
        }//if(group[datafields]
    }//foreach(data[groups] as group_id)

    //delete datafields for groups, not assigned anymore
    foreach (explode(",", $data["old_groups"]) as $old) {
        if (!$old) {
            continue;
        }
        $gid = ko_groups_decode($old, "group_id");
        //Don't touch groups with start or stop date set. These would not be in current_groups and so the datafields would be deleted
        if ($all_groups[$gid]["stop"] != "0000-00-00" && $all_groups[$gid]["stop"] < strftime("%Y-%m-%d", time())) {
            continue;
        }
        if ($all_groups[$gid]["start"] != "0000-00-00" && $all_groups[$gid]["start"] > strftime("%Y-%m-%d", time())) {
            continue;
        }
        if (!in_array($gid, $current_groups)) {
            db_delete_data("ko_groups_datafields_data", "WHERE `group_id` = '$gid' AND `person_id` = '$id'");
        }
    }
}//ko_groups_save_datafields()


/**
 * creates datafields-form for all groups a person is in
 */
function ko_groups_render_group_datafields($groups, $id, $values = false, $_options = array(), $do_dfs = array())
{
    global $all_groups, $ko_path;

    if (!$all_groups) {
        ko_get_groups($all_groups);
    }

    if (!is_array($groups)) {
        $full_groups = explode(",", $groups);
        $groups = null;
        foreach ($full_groups as $g) {
            $gid = ko_groups_decode($g, "group_id");
            $groups[] = array_merge($all_groups[$gid], array("desc_full" => ko_groups_decode($g, "group_desc_full")));
        }
    }

    //array_unique()
    $new_groups = array();
    $groups_id = array();
    foreach ($groups as $g) {
        if (!in_array($g["id"], $groups_id)) {
            $new_groups[] = $g;
            $groups_id[] = $g["id"];
        }
    }
    $groups = $new_groups;
    unset($groups_id);
    unset($new_groups);


    //get datafield values for this user for all groups
    if (!$values) {
        $fielddata = db_select_data("ko_groups_datafields_data", "WHERE `person_id` = '$id'", "*", "ORDER BY group_id");
        $values = null;
        foreach ($fielddata as $data) {
            $values[$data["group_id"]][$data["datafield_id"]] = $data["value"];
        }
    }

    $html = null;
    $df = 0;
    foreach ($groups as $group) {
        if (!$group["datafields"]) {
            continue;
        }

        if (!$_options["hide_title"]) {
            $html[$df]["title"] = $group["desc_full"];
        }
        foreach (explode(",", $group["datafields"]) as $fid) {
            //Only render given datafields if set
            if (sizeof($do_dfs) > 0 && !$do_dfs[$fid]) {
                continue;
            }

            $value = htmlspecialchars($values[$group["id"]][$fid], ENT_QUOTES, 'ISO-8859-1');

            //get datafield
            $field = db_select_data("ko_groups_datafields", "WHERE `id` = '$fid'", "*", "", "", true);
            if (!$field["id"]) {
                continue;
            }

            $html[$df]["content"] .= '<div style="font-size:9px; font-weight:700;">' . $field["description"] . ': </div>';

            if ($_options["add_leute_id"]) {
                $input_name = "group_datafields[$id][" . $group["id"] . "][$fid]";
            } else {
                if ($_options["koi"]) {
                    $input_name = $_options["koi"];
                } else {
                    $input_name = "group_datafields[" . $group["id"] . "][$fid]";
                }
            }

            switch ($field["type"]) {
                case "text":
                    $html[$df]["content"] .= '<input type="text" size="40" name="' . $input_name . '" value="' . $value . '" />';
                    break;

                case "textarea":
                    $html[$df]["content"] .= '<textarea cols="40" rows="5" name="' . $input_name . '">' . $value . '</textarea>';
                    break;

                case "checkbox":
                    $checked = $value ? 'checked="checked"' : "";
                    $html[$df]["content"] .= '<input type="checkbox" name="' . $input_name . '" value="1" ' . $checked . ' />';
                    break;

                case "select":
                    $options = unserialize($field["options"]);
                    if (sizeof($options) == 0) {
                        continue;
                    }

                    $html[$df]["content"] .= '<select name="' . $input_name . '" size="0">';
                    $html[$df]["content"] .= '<option value=""></option>';
                    foreach ($options as $o) {
                        $html[$df]["content"] .= '<option value="' . $o . '" ' . ($o == $value ? 'selected="selected"' : '') . '>' . $o . '</option>';
                    }
                    $html[$df]["content"] .= '</select>';
                    break;

                case "multiselect":
                    $options = unserialize($field["options"]);
                    if (sizeof($options) == 0) {
                        continue;
                    }

                    $html[$df]["content"] .= '<table><tr><td>';
                    $html[$df]["content"] .= '<input type="hidden" name="' . $input_name . '" value="' . $value . '" />';
                    $html[$df]["content"] .= '<input type="hidden" name="old_' . $input_name . '" value="' . $value . '" />';
                    $html[$df]["content"] .= '<select name="sel_ds1_' . $input_name . '" size="6" onclick="double_select_add(this.options[parseInt(this.selectedIndex)].text, this.options[parseInt(this.selectedIndex)].value, \'sel_ds2_' . $input_name . '\', \'' . $input_name . '\');">';
                    foreach ($options as $o) {
                        $html[$df]["content"] .= '<option value="' . $o . '">' . $o . '</option>';
                    }
                    $html[$df]["content"] .= '</select>';
                    $html[$df]["content"] .= '</td><td valign="top">';
                    $html[$df]["content"] .= '<img src="' . $ko_path . 'images/ds_top.gif" border="0" alt="up" onclick="double_select_move(\'' . $input_name . '\', \'top\');" /><br />';
                    $html[$df]["content"] .= '<img src="' . $ko_path . 'images/ds_up.gif" border="0" alt="up" onclick="double_select_move(\'' . $input_name . '\', \'up\');" /><br />';
                    $html[$df]["content"] .= '<img src="' . $ko_path . 'images/ds_down.gif" border="0" alt="up" onclick="double_select_move(\'' . $input_name . '\', \'down\');" /><br />';
                    $html[$df]["content"] .= '<img src="' . $ko_path . 'images/ds_bottom.gif" border="0" alt="up" onclick="double_select_move(\'' . $input_name . '\', \'bottom\');" /><br />';
                    $html[$df]["content"] .= '<img src="' . $ko_path . 'images/ds_del.gif" border="0" alt="up" onclick="double_select_move(\'' . $input_name . '\', \'del\');" />';
                    $html[$df]["content"] .= '</td><td>';
                    $html[$df]["content"] .= '<select name="sel_ds2_' . $input_name . '" size="6">';
                    foreach (explode(",", $value) as $v) {
                        if ($v) {
                            $html[$df]["content"] .= '<option value="' . $v . '">' . $v . '</option>';
                        }
                    }
                    $html[$df]["content"] .= '</select>';
                    $html[$df]["content"] .= '</td></tr></table>';
                    break;
            }
            $html[$df]["content"] .= "<br />";
        }
        $df++;
    }//foreach(datafields as group)

    $df_html = '<div id="datafields_form" name="datafields_form" class="df_content">';
    $df_html .= '<table width="100%" cellpadding="0" cellspacing="0">';
    $df_counter = 0;
    foreach ($html as $content) {
        if ($df_counter == 0) {
            $df_html .= '<tr>';
        }
        $df_html .= '<td width="50%" valign="top">';
        if ($content["title"]) {
            $df_html .= '<div class="df_header">' . $content["title"] . '</div>';
        }
        $df_html .= $content["content"] . '</td>';
        if ($df_counter == 0) {
            $df_counter = 1;
        } else {
            $df_counter = 0;
            $df_html .= '</tr>';
        }
    }
    if ($df_counter == 1) {
        $df_html .= '<td width="50%">&nbsp;</td></tr>';
    }
    $df_html .= '</table></div>';

    return $df_html;
}//ko_groups_render_group_datafields()


//Liefert alle Gruppen rekursiv
function ko_groups_get_recursive($z_where, $fullarrays = false, $start = 'NULL')
{
    $r = array();

    //Leaves finden
    $not_leaves = db_select_distinct("ko_groups", "pid");

    //Top-Level
    if ($start == 'NULL') {
        ko_get_groups($top, "AND `pid` IS NULL " . $z_where, "", "ORDER BY name ASC");
    } else {
        ko_get_groups($top, "AND `pid` = '$start' " . $z_where, "", "ORDER BY name ASC");
    }

    $level = 0;
    foreach ($top as $t) {
        if ($fullarrays) {
            $r[] = $t;
        } else {
            $r[] = array("id" => $t["id"], "name" => $t["name"]);
        }
        rec_groups($t, $r, $z_where, $not_leaves, $fullarrays);
    }//foreach(top)

    return $r;
}//ko_groups_get_recursive()


function rec_groups(&$t, &$r, $z_where = "", &$not_leaves, $fullarrays = false)
{
    if (!is_array($not_leaves)) {
        $not_leaves = db_select_distinct("ko_groups", "pid");
    }

    //Bei Bl�ttern sofort zur�ckgeben
    if (!in_array($t["id"], $not_leaves)) {
        return;
    }

    ko_get_groups($children, "AND `pid` = '" . $t["id"] . "' " . $z_where, "", "ORDER BY name ASC");

    foreach ($children as $c) {
        if ($fullarrays) {
            $r[] = $c;
        } else {
            $r[] = array("id" => $c["id"], "name" => $c["name"]);
        }
        rec_groups($c, $r, $z_where, $not_leaves, $fullarrays);
        unset($children[$c["id"]]);
    }
}//rec_groups()


/**
 * Liefert die WHERE-Bedingung f�r Gruppen gem�ss Option, ob abgelaufene Gruppen angezeigt werden d�rfen oder nicht
 */
function ko_get_groups_zwhere($forceAll = false)
{
    if ($forceAll || ko_get_userpref($_SESSION['ses_userid'], 'show_passed_groups') == 1) {
        $z_where = "";
    } else {
        $z_where = "AND ((`start` = '0000-00-00' OR `start` <= NOW()) AND (`stop` = '0000-00-00' OR `stop` > NOW()))";
    }
    return $z_where;
}//ko_get_groups_zwhere()


/**
 * Erstellt die JS-Eintr�ge f�r ein Selmenu f�r das Gruppen-Modul
 */
function ko_selmenu_generate_children_entries($top_id, $list_id, &$all_groups, &$all_roles, $show_all_types = false)
{
    global $access;
    global $counter, $list_counter, $level, $children;

    if (!is_array($access['groups'])) {
        ko_get_access('groups');
    }

    $level++;
    if (!$list_counter[$level]) {
        $list_counter[$level] = ($level * 1000000);
    }
    $group_list = array();

    if ($top_id == "NULL") {
        $groups = db_select_data("ko_groups", "WHERE `pid` IS NULL " . ko_get_groups_zwhere(), "*",
            "ORDER BY `name` ASC");
    } else {
        $groups = db_select_data("ko_groups", "WHERE `pid` = '$top_id' " . ko_get_groups_zwhere(), "*",
            "ORDER BY `name` ASC");
    }

    //Get an array of the number of children for all groups that have children
    if (!$children) {
        $children = db_select_data('ko_groups', 'WHERE 1=1 ' . ko_get_groups_zwhere(), '`pid`,COUNT(`id`) AS num',
            'GROUP BY `pid`');
    }

    foreach ($groups as $group) {
        if ($access['groups']['ALL'] > 0 || $access['groups'][$group['id']] > 0) {
            //Echter Eintrag
            //$mother_line = array_reverse(ko_groups_get_motherline($group["id"], $all_groups));
            //print 'addItem(1, '.$counter.', "g'.implode(":g", $mother_line).":g".$group["id"].'", "'.$descs[$i].'");'."\n";
            list($values, $descs) = ko_groups_get_group_id_names($group["id"], $all_groups, $all_roles,
                $do_roles = false);
            foreach ($values as $i => $value) {
                if ($show_all_types || $group["type"] != 1) {  //Platzhalter-Gruppen nicht ausgeben
                    print 'addItem(' . $list_id . ', ' . $counter . ', "' . $value . '", "' . $descs[$i] . '");' . "\n";
                    $group_list[] = $counter++;
                }
            }

            //Link auf Subliste mit allen Children dieser Gruppe
            if ($children[$group['id']]['num'] > 0) {
                ko_selmenu_generate_children_entries($group["id"], $list_id, $all_groups, $all_roles, $show_all_types);
                $level--;
                $group_list[] = $list_counter[($level + 1)]++;
            }

        }//if(g_view)
    }//foreach(groups)

    if ($top_id == "NULL") {
        print 'addTopList(' . $list_id . ', 0, "' . implode(", ", $group_list) . '");' . "\n";
    } else {
        //Muttergruppe f�r Sublisten-Namen holen
        if ($level == 2) {
            print 'addItem(' . $list_id . ', ' . $counter . ', "back", "---' . getLL("groups_list_up") . '---");' . "\n";
        } else {
            print 'addItem(' . $list_id . ', ' . $counter . ', "sub:' . $list_counter[($level - 1)] . '", "---' . getLL("groups_list_up") . '---");' . "\n";
        }
        $group_list = array_merge(array($counter++), $group_list);
        print 'addSubList(' . $list_id . ', ' . $list_counter[$level] . ', 0, "' . $all_groups[$top_id]["name"] . ' -->", "' . implode(", ",
                $group_list) . '");' . "\n";
    }
}//ko_selmenu_generate_children_entries()


function ko_update_grouprole_filter()
{
    //Rollen-Filter machen, der nur nach Rolle suchen l�sst
    $new_code = "<select name=\"var1\" size=\"0\">";
    $new_code .= '<option value="0"></option>';

    //Gruppen-Select
    ko_get_grouproles($roles);
    foreach ($roles as $r) {
        $new_code .= '<option value="r' . $r["id"] . '">' . $r["name"] . '</option>';
    }
    $new_code .= '</select>';

    db_update_data("ko_filter", "WHERE `typ`='leute' AND `name`='role'", array("code1" => $new_code));
}//ko_update_grouprole_filter()


function ko_get_group_count($id, $rid = '')
{
    $id = format_userinput($id, 'uint');
    if (!$id) {
        return 0;
    }

    if ($rid) {
        $rex = 'g' . $id . ':r' . $rid;
    } else {
        $rex = 'g' . $id . '(:r|,|$)';
    }
    $count = db_get_count('ko_leute', 'id', "AND `groups` REGEXP '$rex' AND deleted = '0' ");
    return $count;
}//ko_get_group_count()


function ko_update_group_count($id, $rid = '')
{
    $id = format_userinput($id, 'uint');
    if (!$id) {
        return 0;
    }

    db_update_data('ko_groups', "WHERE `id` = '$id'", array('count' => ko_get_group_count($id, $rid)));
}//ko_update_group_count()

