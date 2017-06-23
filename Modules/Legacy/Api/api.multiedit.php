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
 * M U L T I E D I T - F U N K T I O N E N                                                                              *
 *                                                                                                                      *
 ************************************************************************************************************************/
function ko_include_kota($tables = array())
{
    global $BASE_PATH, $KOTA, $ko_menu_akt, $access, $SMALLGROUPS_ROLES, $LOCAL_LANG;

    //Include KOTA function (once)
    include_once($BASE_PATH . 'inc/kotafcn.php');

    //Include KOTA table definitions for given tables
    $KOTA_TABLES = $tables;
    include($BASE_PATH . 'inc/kota.inc');

    //Apply access rights --> unset KOTA columns the current user has no access to
    foreach ($tables as $table) {
        $delcols = array();
        $cols = ko_access_get_kota_columns($_SESSION['ses_userid'], $table);
        if (sizeof($cols) > 0) {
            foreach ($KOTA[$table] as $k => $v) {
                if (substr($k, 0, 1) == '_') {
                    continue;
                }
                if (!in_array($k, $cols)) {
                    $delcols[] = $k;
                    unset($KOTA[$table][$k]);
                }
            }
            foreach ($KOTA[$table]['_listview'] as $lk => $lv) {
                if (in_array($lv['name'], $delcols)) {
                    unset($KOTA[$table]['_listview'][$lk]);
                }
            }
        }
    }
}//ko_include_kota()


/**
 * Generates a form for multiedit or a single entry
 *
 * Get information from KOTA to render a form for editing one or more entries
 *
 * @param string $table Table to edit
 * @param string $columns Comma separated list of columns to be edited (empty for all)
 * @param string $ids Comma separated list of ids to be edited. 0 for a new entry
 * @param string $order ORDER BY statement to be used if editing multiple entries
 * @param array $form_data Data for the rendering of the form (like title etc.)
 * @param boolean $return_only_group Renders form if set to false, only return group array otherwise which can be used to feed ko_formular.tmpl through smarty
 * @param string $_kota_type Specify kota type for a new entry
 */
function ko_multiedit_formular(
    $table,
    $columns = "",
    $ids = 0,
    $order = "",
    $form_data = "",
    $return_only_group = false,
    $_kota_type = ''
)
{
    global $smarty, $mysql_pass;
    global $KOTA, $js_calendar, $BASE_URL;

    //Columns used in SQL
    if ($columns == "") {  //not multiedit, so take all KOTA-columns
        $mode = "single";
        //Get columns from DB
        $_table_cols = db_get_columns($table);
        $table_cols = array();
        foreach ($_table_cols as $col) {
            $table_cols[] = $col['Field'];
        }
        foreach ($KOTA[$table] as $kota_col => $array) {
            if (substr($kota_col, 0, 1) == "_") {
                continue;
            }   // _multititle, _listview, _access
            if (!isset($array['form']) || $array['form']['ignore']) {
                continue;
            }         // ignore this column all together
            $columns[] = $kota_col;
        }

        foreach ($columns as $column) {
            if ($KOTA[$table][$column]["form"]["dontsave"]) {
                continue;
            }
            if (!in_array($column, $table_cols)) {
                continue;
            }
            $sel_columns[] = "`" . $column . "`";
        }
        //Add columns for other types
        if (isset($KOTA[$table]['_types']['field'])) {
            foreach ($KOTA[$table]['_types']['types'] as $type => $typedef) {
                if (!is_array($typedef['add_fields'])) {
                    continue;
                }
                foreach ($typedef['add_fields'] as $add_colid => $add_col) {
                    $sel_columns[] = "`" . $add_colid . "`";
                }
            }
        }
        $sel_columns = implode(',', array_unique($sel_columns));

        //Add help for the given table
        $smarty->assign("help", ko_get_help("kota", $table));
    } else {  //multiedit, only use given column(s)
        $mode = "multi";
        $showForAll = true;
        foreach ($columns as $c_i => $col) {
            if (substr($col, 0, 6) == "MODULE") {
                switch (substr($col, 6, 3)) {
                    case "grp":
                        $db_cols .= "`groups`,";
                        break;
                }
            } else {
                $db_cols .= "`" . $col . "`,";
            }
        }
        $sel_columns = $db_cols . implode(",", array_keys($KOTA[$table]["_multititle"]));

        //Add help for multiediting
        $smarty->assign("help", ko_get_help("kota", "multiedit"));
    }//if..else(!columns)

    //Add type column. Must be selected, so the record can be checked for it's type, which might change the form
    if (isset($KOTA[$table]['_types']['field'])) {
        $sel_columns .= ',' . $KOTA[$table]['_types']['field'];
    }

    //IDs of the records to be edited
    if ($ids == 0) {  //no multiedit, might be a form for a new entry, so don't ask for ids
        $ids = array(0);
        $new_entry = true;
        $sel_ids = 0;
        $row = array("id" => 0);
        foreach ($kota_cols as $kota_col) {
            $row[$kota_col] = "";
        }
    } else {  //multiedit, so ids to be edited must be given
        $new_entry = false;
        $sel_ids = "";
        if (is_array($ids)) {
            foreach ($ids as $id) {
                $sel_ids .= "'$id', ";
            }
            $sel_ids = substr($sel_ids, 0, -2);
        } else {
            $sel_ids = $ids;
            $ids = array($ids);
        }

        if (!$sel_ids) {
            return false;
        }
    }


    //Get data from DB
    $result = mysql_query("SELECT id,$sel_columns FROM `$table` WHERE `id` IN ($sel_ids) $order");

    //Start building the form
    $rowcounter = 0;
    $gc = 0;
    //Loop through all entries
    while ($new_entry || $showForAll || $row = mysql_fetch_assoc($result)) {
        $gc = $row['group'] ? getLL('kota_fieldset_' . $row['group']) : $gc;
        if ($new_entry) {  //Single edit form
            $group[$gc] = array();
        } else {
            if ($showForAll) {  //Add edit fields to be applied to all edited rows
                $group[$gc] = array(
                    "forAll" => true,
                    "titel" => getLL("multiedit_title_forAll"),
                    "state" => "closed",
                    "table" => $table
                );
                $row = array();
            } else {  //normal multiedit rows
                $titel = "";
                //Set title for each entry
                foreach ($KOTA[$table]["_multititle"] as $tc => $tc_fcn) {
                    $val = $row[$tc];
                    if ($tc_fcn != "") {
                        eval("\$val=" . str_replace("@VALUE@", $val, $tc_fcn) . ";");
                    }
                    $titel .= "$val ";
                }
                $group[$gc] = array("titel" => $titel, "state" => "open", "colspan" => 'colspan="2"');
            }
        }

        //Add columns if a certain kota type is given
        if ($mode == 'single' && isset($KOTA[$table]['_types']['field'])) {
            $kota_type = $_kota_type ? $_kota_type : $row[$KOTA[$table]['_types']['field']];
            if ($kota_type != $KOTA[$table]['_types']['default'] && sizeof($KOTA[$table]['_types']['types'][$kota_type]['add_fields']) > 0) {
                foreach ($KOTA[$table]['_types']['types'][$kota_type]['add_fields'] as $add_colid => $add_col) {
                    $KOTA[$table][$add_colid] = $add_col;
                    $columns[] = $add_colid;
                }
            }
        }

        //Alle zu bearbeitenden Spalten f�r diesen Datensatz
        $col_pos = 0;
        foreach ($columns as $col) {

            //Reset own_row
            $own_row = false;

            //Check if this column should show for this type (if types are defined for this KOTA table)
            if (isset($KOTA[$table]['_types']['field'])) {
                $kota_type = $_kota_type ? $_kota_type : $row[$KOTA[$table]['_types']['field']];
                if ($kota_type != $KOTA[$table]['_types']['default']) {
                    if (!in_array($col, $KOTA[$table]['_types']['types'][$kota_type]['use_fields'])
                        && !in_array($col, array_keys($KOTA[$table]['_types']['types'][$kota_type]['add_fields']))
                    ) {
                        //Unset ID for multiediting (this column may not be edited for this row)
                        if ($mode == 'multi') {
                            foreach ($ids as $ik => $iv) {
                                if ($iv == $row['id']) {
                                    unset($ids[$ik]);
                                }
                            }
                        }
                        //Unset column, so column check after submission will work correctly
                        foreach ($columns as $ck => $cv) {
                            if ($cv == $col) {
                                unset($columns[$ck]);
                            }
                        }
                        //And don't show input
                        continue;
                    }
                }
            }

            //Call a fill function to prefill this input
            if ($KOTA[$table][$col]['fill']) {
                $fcn = substr($KOTA[$table][$col]['fill'], 4);
                if (function_exists($fcn)) {
                    eval("$fcn(\$row, \$col);");
                }
            }

            $keep_name = $keep_name_PLUS = "";
            if ($showForAll) {
                $keep_name = "koi[$table][$col][forAll]";
                $keep_name_PLUS = "koi[$table][$col" . "_PLUS][forAll]";
            }
            $col = str_replace("`", "", $col);
            //Module bearbeiten, damit in row[col] �berhaupt etwas steht
            if (substr($col, 0, 6) == "MODULE") {
                switch (substr($col, 6, 3)) {
                    case "grp":
                        if (false === strpos($col, ':')) {
                            $g_value = array();
                            $gid = substr($col, 9);
                            foreach (explode(",", $row["groups"]) as $g) {
                                if (ko_groups_decode($g, "group_id") == $gid) {
                                    $g_value[] = $g;
                                }
                            }
                            $row[$col] = implode(",", $g_value);
                        } else {
                            $gid = substr($col, 9, 6);  //group id
                            $fid = substr($col, 16, 6); //datafield id
                            //only continue if person is assigned to this group
                            if (false !== strpos($row["groups"], "g" . $gid) || $showForAll) {
                                $koi_name = $keep_name ? $keep_name : "koi[ko_leute][$col][" . $row["id"] . "]";
                                $code = ko_groups_render_group_datafields($gid, $row["id"], false,
                                    array("koi" => $koi_name), array($fid => true));
                                $KOTA[$table][$col]["form"] = array(
                                    "desc" => getLL("groups_edit_datafield"),
                                    "type" => "html",
                                    "value" => $code
                                );
                            } else {
                                $KOTA[$table][$col]["form"] = array("desc" => "-");
                            }
                        }
                        break;
                }
            }//if(MODULE)
            $type = $KOTA[$table][$col]["form"]["type"];
            //If no type defined then don't output this form field (maybe only used in list view)
            if (!$type) {
                continue;
            }

            //Add description from LL
            if (!$KOTA[$table][$col]["form"]["desc"]) {
                $ll_value = getLL("kota_" . $table . "_" . $col);
                $KOTA[$table][$col]["form"]["desc"] = $ll_value ? $ll_value : $col;
            }
            $help = ko_get_help($module, 'kota.' . $table . '.' . $col);
            if ($help['show']) {
                $KOTA[$table][$col]['form']['help'] = $help['link'];
            }

            //Vorbehandlung f�r versch. Typen
            $do_1 = false;
            if ($type == "date") {  //no JS-DateSelect (used for multiedit)
                $type = "text";
            } else {
                if ($type == "jsdate" && is_object($js_calendar)) {  //use js-calendar
                    //$type = "jsdate";
                    //Prefill form for new entry with POST data
                    $post_date = $_POST['koi'][$table][$col][$row['id']];
                    if ($new_entry && $KOTA[$table][$col]['form']['prefill_new'] && $post_date != '' && $post_date == format_userinput($post_date,
                            'date')
                    ) {
                        $date = $_POST['koi'][$table][$col][$row['id']];
                    } else {
                        $date = $KOTA[$table][$col]["form"]["value"];
                        if (strlen($date) > 10) {
                            $date = "";
                        }  //if several jsdate inputs are used on one page, value still contains HTML from the last one
                    }
                    if (!$date && $row[$col] != "0000-00-00") {
                        $date = sql2datum($row[$col]);
                    }

                    if ($KOTA[$table][$col]['pre'] != '' && isset($row[$col])) {
                        $data = $row;
                        kota_process_data($table, $data, 'pre', $_log);
                        $date = $data[$col];
                    }

                    $name = $keep_name ? $keep_name : "koi[$table][$col][" . $row["id"] . "]";
                    //$KOTA[$table][$col]["form"]["value"] = $js_calendar->make_input_field(array(), array("name" => $name, "value" => $date));
                } else {
                    if ($type == 'multidateselect' && is_object($js_calendar)) {  //use js-calendar
                        $name = $keep_name ? $keep_name : "koi[$table][$col][" . $row['id'] . ']';
                        $onchange = "double_select_add(this.value, this.value, 'sel_ds2_$name', '$name');";
                        $KOTA[$table][$col]['form']['dateselect'] = $js_calendar->make_input_field(array(
                            'ifFormat' => '%Y-%m-%d',
                            'closeOnClick' => false,
                            'align' => 'Bl'
                        ), array('name' => 'txt_' . $name, 'onchange' => $onchange, 'size' => '10'));

                        if (!$new_entry) {  //add entries from db if edit. If new, kota_assign_values() assigns avalue/adescs/avalues - if needed
                            $KOTA[$table][$col]['form']['avalues'] = $KOTA[$table][$col]['form']['adescs'] = array();
                            foreach (explode(',', $row[$col]) as $v) {
                                $KOTA[$table][$col]['form']['avalues'][] = $v;
                                $KOTA[$table][$col]['form']['adescs'][] = $v;
                            }
                            $KOTA[$table][$col]['form']['avalue'] = $row[$col];
                        }
                    } else {
                        if ($type == "checkbox") {
                            $KOTA[$table][$col]["form"]["params"] = $row[$col] ? 'checked="checked"' : '';
                            $row[$col] = 1;  //F�r Checkboxen Value immer auf 1 setzen
                        } else {
                            if ($type == 'switch') {
                                if ($row[$col] == '') {
                                    $row[$col] = 0;
                                }
                            } else {
                                if ($type == "textplus") {
                                    //Create a second input field as text input (but only when editing a single entry and not for multiedit)
                                    if ($mode == "single") {
                                        $KOTA[$table][$col]["form"]['PLUS'] = true;
                                        $do_1 = true;
                                        $do_1_array = array(
                                            "desc" => getLL("form_textplus") . ":",
                                            "type" => "text",
                                            "name" => ($keep_name_PLUS ? $keep_name_PLUS : ("koi[" . $table . "][" . $col . "_PLUS][" . $row["id"] . "]")),
                                            "params" => $KOTA[$table][$col]["form"]["params_PLUS"],
                                        );
                                    }
                                    $type = "select";
                                    //set size of select to 0
                                    $KOTA[$table][$col]["form"]["params"] = 'size="0"';
                                    //get values for the select
                                    if (!$KOTA[$table][$col]["form"]["values"]) {
                                        $values = db_select_distinct($table, $col, "",
                                            $KOTA[$table][$col]['form']['where'],
                                            $KOTA[$table][$col]["form"]["select_case_sensitive"] ? true : false);
                                        if ($KOTA[$table][$col]['form']['PLUS_addempty']) {
                                            $values[] = '';
                                        }
                                        $KOTA[$table][$col]["form"]["values"] = $values;
                                        $KOTA[$table][$col]["form"]["descs"] = $values;
                                    }
                                } else {
                                    if ($type == 'textmultiplus') {
                                        if (!$KOTA[$table][$col]['form']['js_func_add']) {
                                            $KOTA[$table][$col]['form']['js_func_add'] = 'double_select_add';
                                        }
                                        //get values for the select
                                        if (!$KOTA[$table][$col]['form']['values']) {
                                            $values = kota_get_textmultiplus_values($table, $col);
                                            $KOTA[$table][$col]['form']['values'] = $values;
                                            $KOTA[$table][$col]['form']['descs'] = $values;
                                        }
                                        //Add active entries for edit
                                        if (!$new_entry) {
                                            $avalue = $row[$col];
                                            $KOTA[$table][$col]['form']['avalue'] = $avalue;
                                            if ($avalue != '') {
                                                $KOTA[$table][$col]['form']['adescs'] = explode(',', $avalue);
                                                $KOTA[$table][$col]['form']['avalues'] = explode(',', $avalue);
                                            }
                                        }
                                    } else {
                                        if ($type == "doubleselect") {
                                            if (!$KOTA[$table][$col]["form"]["js_func_add"]) {
                                                $KOTA[$table][$col]["form"]["js_func_add"] = "double_select_add";
                                            }
                                            if (!$new_entry) {  //add entries from db if edit. If new, kota_assign_values() assigns avalue/adescs/avalues - if needed
                                                $KOTA[$table][$col]["form"]["avalues"] = $KOTA[$table][$col]["form"]["adescs"] = array();
                                                $valuesi = array_flip($KOTA[$table][$col]["form"]["values"]);
                                                foreach (explode(",", $row[$col]) as $v) {
                                                    $KOTA[$table][$col]["form"]["avalues"][] = $v;
                                                    if ($KOTA[$table][$col]['form']['group_desc_full']) {
                                                        $KOTA[$table][$col]['form']['adescs'][] = ko_groups_decode(ko_groups_decode($v,
                                                            'full_gid'), 'group_desc_full');
                                                    } else {
                                                        //Use description from descs. If not set then fall back to all_descs.
                                                        // This can be used to include descs for values that can not be assigned anymore (like roles for groups which are full)
                                                        $KOTA[$table][$col]['form']['adescs'][] = $KOTA[$table][$col]['form']['descs'][$valuesi[$v]] ? $KOTA[$table][$col]['form']['descs'][$valuesi[$v]] : $KOTA[$table][$col]['form']['all_descs'][$v];
                                                    }
                                                }
                                                $KOTA[$table][$col]["form"]["avalue"] = $row[$col];
                                            }
                                        } else {
                                            if ($type == 'checkboxes') {
                                                if (!$new_entry) {  //add entries from db if edit. If new, kota_assign_values() assigns avalue/adescs/avalues - if needed
                                                    $KOTA[$table][$col]['form']['avalues'] = $KOTA[$table][$col]['form']['adescs'] = array();
                                                    $valuesi = array_flip($KOTA[$table][$col]['form']['values']);
                                                    foreach (explode(',', $row[$col]) as $v) {
                                                        $KOTA[$table][$col]['form']['avalues'][] = $v;
                                                    }
                                                    $KOTA[$table][$col]['form']['avalue'] = $row[$col];
                                                }
                                            } else {
                                                if ($type == "dyndoubleselect") {
                                                    $type = "doubleselect";
                                                    if (!$KOTA[$table][$col]["form"]["js_func_add"]) {
                                                        $KOTA[$table][$col]["form"]["js_func_add"] = "double_select_add";
                                                    }
                                                    $KOTA[$table][$col]["form"]["avalues"] = $KOTA[$table][$col]["form"]["adescs"] = array();

                                                    $values = $KOTA[$table][$col]["form"]["values"];
                                                    $descs = $KOTA[$table][$col]["form"]["descs"];
                                                    unset($KOTA[$table][$col]["form"]["values"]);
                                                    unset($KOTA[$table][$col]["form"]["descs"]);
                                                    if ($row[$col] || $KOTA[$table][$col]['form']['value']) { //Current value given
                                                        if (!$row[$col]) {
                                                            $row[$col] = $KOTA[$table][$col]['form']['value'];
                                                        }
                                                        foreach (explode(",", $row[$col]) as $v) {
                                                            $KOTA[$table][$col]["form"]["avalues"][] = $v;
                                                            $KOTA[$table][$col]["form"]["adescs"][] = $descs[$v];
                                                        }
                                                        $KOTA[$table][$col]["form"]["avalue"] = $row[$col];
                                                    }
                                                    //Build top level of select
                                                    foreach ($values as $vid => $value) {
                                                        $KOTA[$table][$col]["form"]["values"][] = $vid;
                                                        $suffix = is_array($value) ? "-->" : "";
                                                        $KOTA[$table][$col]["form"]["descs"][] = $descs[$vid] . $suffix;
                                                    }
                                                } else {
                                                    if ($type == "dynselect") {
                                                        //Only works for single edit (not multiedit) because KOTA[..][values] would be different for each multiedit item, which doesn't work
                                                        $type = "select";
                                                        if (!$KOTA[$table][$col]["form"]["_done"]) {
                                                            if ($showForAll) {  //First time when multiediting
                                                                list($values, $descs) = kota_convert_dynselect_select($KOTA[$table][$col]["form"]["values"],
                                                                    $KOTA[$table][$col]["form"]["descs"]);
                                                                $KOTA[$table][$col]["form"]["params"] = ' size="0"';  //Set size to 0 for multiedit
                                                                $KOTA[$table][$col]["form"]["values"] = $values;
                                                                $KOTA[$table][$col]["form"]["descs"] = $descs;
                                                                $KOTA[$table][$col]["form"]["_done"] = true;  //So this conversion is only done once for the forAll entry and then used in all
                                                            } else {  //Normal form, no multiediting
                                                                $values = $KOTA[$table][$col]["form"]["values"];
                                                                $descs = $KOTA[$table][$col]["form"]["descs"];
                                                                unset($KOTA[$table][$col]["form"]["values"]);
                                                                unset($KOTA[$table][$col]["form"]["descs"]);
                                                                if ($row[$col]) {  //Current value given
                                                                    $KOTA[$table][$col]["form"]["avalues"] = $KOTA[$table][$col]["form"]["adescs"] = array();
                                                                    $KOTA[$table][$col]["form"]["avalue"] = $row[$col];
                                                                    //If current value is not found on top level then go through all lower levels to find it and display this level
                                                                    if (!in_array($row[$col], array_keys($values))) {
                                                                        foreach ($values as $vid => $value) {
                                                                            if (substr($vid, 0, 1) != "i") {
                                                                                continue;
                                                                            }
                                                                            if (in_array($row[$col], $value)) {
                                                                                $values = array("i-" => "i-");
                                                                                //Add all values from this level
                                                                                foreach ($value as $v) {
                                                                                    $values[$v] = $v;
                                                                                }
                                                                                //Add link to go back up to the index
                                                                                $descs["i-"] = getLL("form_peopleselect_up");
                                                                                break;
                                                                            }
                                                                        }
                                                                    }//if(!in_array(row[col], values))
                                                                }//if(row[col])
                                                                //Build top level of select
                                                                foreach ($values as $vid => $value) {
                                                                    $KOTA[$table][$col]["form"]["values"][] = $vid;
                                                                    $suffix = is_array($value) ? "-->" : "";
                                                                    $KOTA[$table][$col]["form"]["descs"][] = $descs[$vid] . $suffix;
                                                                }
                                                            }//if..else(showForAll) (multiedit)
                                                        }//if(!KOTA[form][_done])
                                                    } else {
                                                        if ($type == 'peoplesearch') {
                                                            if ($row[$col] != '') {
                                                                $lids = explode(",", $row[$col]);
                                                                list($av, $ad) = kota_peopleselect($lids,
                                                                    $KOTA[$table][$col]['form']['sort']);
                                                                $KOTA[$table][$col]["form"]["avalues"] = $av;
                                                                $KOTA[$table][$col]["form"]["adescs"] = $ad;

                                                                $KOTA[$table][$col]["form"]["avalue"] = $row[$col];
                                                            }
                                                        } else {
                                                            if ($type == 'peoplefilter') {
                                                                ko_get_filters($_filters, 'leute', true);
                                                                $filters = array();
                                                                foreach ($_filters as $k => $f) {
                                                                    $filters[$k] = $f['name'];
                                                                }
                                                                $KOTA[$table][$col]['form']['filters'] = $filters;

                                                                $avalues = explode(',', $row[$col]);
                                                                $adescs = array();
                                                                $filterArray = kota_peoplefilter2filterarray($row[$col]);
                                                                foreach ($filterArray as $fk => $filter) {
                                                                    if (!is_numeric($fk)) {
                                                                        continue;
                                                                    }
                                                                    ko_get_filter_by_id($filter[0], $f);
                                                                    $text = $f['name'] . ': ';

                                                                    //Mark negative
                                                                    if ($filter[2]) {
                                                                        $text .= '!';
                                                                    }

                                                                    //Tabellen-Name, auf den dieser Filter am ehesten wirkt, auslesen/erraten:
                                                                    $fcol = array();
                                                                    for ($c = 1; $c < 4; $c++) {
                                                                        list($fcol[$c]) = explode(' ', $f['sql' . $c]);
                                                                    }
                                                                    $t1 = $t2 = '';
                                                                    for ($i = 0; $i <= sizeof($filter[1]); $i++) {
                                                                        $v = map_leute_daten($filter[1][$i],
                                                                            ($fcol[$i] ? $fcol[$i] : $fcol[1]), $t1,
                                                                            $t2, false, array('num' => $i));
                                                                        if ($v) {
                                                                            $text .= $v . ',';
                                                                        }
                                                                    }
                                                                    $text = substr($text, 0, -1);
                                                                    $adescs[] = $text;
                                                                }
                                                                $KOTA[$table][$col]['form']['avalue'] = $row[$col];
                                                                $KOTA[$table][$col]['form']['avalues'] = $avalues;
                                                                $KOTA[$table][$col]['form']['adescs'] = $adescs;
                                                            } else {
                                                                if ($type == 'foreign_table') {
                                                                    $own_row = true;

                                                                    if ($new_entry) {
                                                                        $pid = 'new' . md5(uniqid('', true));
                                                                    } else {
                                                                        $pid = $row['id'];
                                                                    }
                                                                    if (isset($KOTA[$table][$col]['form']['foreign_table_preset'])) {
                                                                        $presetSettings = $KOTA[$table][$col]['form']['foreign_table_preset'];
                                                                        $smarty->assign('ft_preset_table',
                                                                            $presetSettings['table']);
                                                                        $smarty->assign('ft_preset_join_value_local',
                                                                            $row[$presetSettings['join_column_local']]);
                                                                        $smarty->assign('ft_preset_join_column_foreign',
                                                                            $presetSettings['join_column_foreign']);
                                                                        $alertMessage = getLL($presetSettings['ll_no_join_value']);
                                                                        if ($alertMessage == '') {
                                                                            $alertMessage = getLL('form_ft_alert_no_join_value');
                                                                        }
                                                                        $smarty->assign('ft_alert_no_join_value',
                                                                            $alertMessage);
                                                                    }
                                                                    $smarty->assign('ft_pid', $pid);
                                                                    $smarty->assign('ft_field', $table . '.' . $col);
                                                                    $smarty->assign('ft_content',
                                                                        kota_ft_get_content($table . '.' . $col, $pid));
                                                                } else {
                                                                    if ($type == "file") {
                                                                        //Show thumb if possible
                                                                        if ($row[$col]) {
                                                                            $thumb = ko_pic_get_tooltip($row[$col], 40,
                                                                                200);
                                                                            if ($thumb) {
                                                                                $KOTA[$table][$col]['form']['special_value'] = $thumb;
                                                                                $KOTA[$table][$col]['form']['value'] = ' ';
                                                                            } else {
                                                                                $KOTA[$table][$col]['form']['value'] = $row[$col];
                                                                                $KOTA[$table][$col]['form']['special_value'] = '';
                                                                            }
                                                                        } else {
                                                                            //Reset KOTA entry otherwise previous entries fill later entries
                                                                            $KOTA[$table][$col]['form']['value'] = '';
                                                                            $KOTA[$table][$col]['form']['special_value'] = '';
                                                                        }
                                                                        //add delete checkbox for files
                                                                        $KOTA[$table][$col]["form"]["value2"] = getLL("delete");
                                                                        $KOTA[$table][$col]["form"]["name2"] = "koi[$table][$col" . "_DELETE][" . $row["id"] . "]";
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }//if..else(type==...)

            $rowcounter++;
            $col_pos = 0;

            //prefill_new: Prefill value for new
            if (in_array($type, array('select'))) {
                $post_date = $_POST['koi'][$table][$col][$row['id']];
                if ($new_entry && $KOTA[$table][$col]['form']['prefill_new'] && $_POST['koi'][$table][$col][$row['id']] != '') {
                    $KOTA[$table][$col]['form']['value'] = $_POST['koi'][$table][$col][$row['id']];
                }
            }

            // cf: group
            if ($KOTA)

                $group[$gc]["row"][$rowcounter]["inputs"][$col_pos] = $KOTA[$table][$col]["form"];
            $group[$gc]["row"][$rowcounter]["inputs"][$col_pos]["type"] = $type;
            $group[$gc]["row"][$rowcounter]["inputs"][$col_pos]["id"] = $col;
            if (!$KOTA[$table][$col]["form"]["value"]) {
                $val = $row[$col];
                if ($KOTA[$table][$col]["pre"] != "") {
                    $data = array($col => $val);
                    $group[$gc]['row'][$rowcounter]['inputs'][$col_pos]['ovalue'] = $val;
                    kota_process_data($table, $data, "pre", $_log);
                    $val = $data[$col];
                }
                $group[$gc]["row"][$rowcounter]["inputs"][$col_pos]["value"] = $val;
            }
            if ($keep_name) {
                $group[$gc]["row"][$rowcounter]["inputs"][$col_pos]["name"] = $keep_name;
            } else {
                $group[$gc]["row"][$rowcounter]["inputs"][$col_pos]["name"] = "koi[$table][$col][" . $row["id"] . "]";
            }

            //Zweite Spalte mit einer Eingabe erstellen
            if ($do_1) {
                $group[$gc]["row"][$rowcounter]["inputs"][1] = $do_1_array;
            }

            if ($col_pos == 1 || $do_1 || $KOTA[$table][$col]["form"]["new_row"]) {
                $rowcounter++;
                $col_pos = 0;
            } else {
                $col_pos = 1;
            }

        }//foreach(columns as col)
        $new_entry = false;
        $showForAll = false;
        $gc++;
    }//while(row)

    if ($return_only_group) {
        return $group;
    }

    //Remove columns marked with ignore_test and columns referencing a foreign table
    $new = array();
    foreach ($columns as $ci => $c) {
        if ($KOTA[$table][$c]['form']['ignore_test']) {
            continue;
        }
        if ($KOTA[$table][$c]['form']['type'] == 'foreign_table') {
            continue;
        }
        $new[] = $c;
    }
    $columns = $new;

    //Controll-Hash
    sort($columns);
    sort($ids);
    //print $mysql_pass.$table.implode(":", $columns).implode(":", $ids);
    $hash_code = md5(md5($mysql_pass . $table . implode(":", $columns) . implode(":", $ids)));
    $hash = $table . "@" . implode(",", $columns) . "@" . implode(",", $ids) . "@" . $hash_code;

    //Add legend
    $legend = getLL("kota_formlegend_" . $table);
    if ($legend) {
        $smarty->assign("tpl_legend", $legend);
        $smarty->assign("tpl_legend_icon", getLL("kota_formlegend_" . $table . "_icon"));
    }

    if ($kota_type) {
        $hidden_inputs[] = array('name' => 'kota_type', 'value' => $kota_type);
    }
    $smarty->assign('tpl_hidden_inputs', $hidden_inputs);

    $smarty->assign("tpl_titel", $form_data["title"] ? $form_data["title"] : getLL("multiedit_title"));
    $smarty->assign("tpl_submit_value", $form_data["submit_value"] ? $form_data["submit_value"] : getLL("save"));
    $smarty->assign("tpl_id", $hash);
    $smarty->assign("tpl_action", $form_data["action"] ? $form_data["action"] : "submit_multiedit");
    if ($form_data['action_as_new']) {
        $smarty->assign('tpl_submit_as_new',
            ($form_data['label_as_new'] ? $form_data['label_as_new'] : getLL('save_as_new')));
        $smarty->assign('tpl_action_as_new', $form_data['action_as_new']);
    }
    $smarty->assign("tpl_cancel", $form_data["cancel"]);
    $smarty->assign("tpl_groups", $group);
    $smarty->display("ko_formular.tpl");
}//ko_multiedit_formular()


/**
 * Get addresses from ko_leute for a KOTA field of type peoplesearch
 * @param array Array of currently selected IDs
 * @param boolean Set to true to return addresses ordered by name (default).
 * Set to false to return the addresses in the order of the given IDs
 * @return array Two arrays are returned holding the IDs and labels to be used as options for a select
 */
function kota_peopleselect($ids, $sort = true)
{
    $avalues = $adescs = array();

    //get people from db
    $order = $sort ? 'ORDER BY nachname,vorname ASC' : '';
    $_leute_rows = db_select_data("ko_leute", "WHERE `id` IN ('" . implode("','", $ids) . "')",
        "id,vorname,nachname,firm,department", $order);
    if (!$sort) {
        //Keep order of ids as given in array
        foreach ($ids as $id) {
            $leute_rows[$id] = $_leute_rows[$id];
        }
    } else {
        $leute_rows = $_leute_rows;
    }
    foreach ($leute_rows as $leute_row) {
        if ($leute_row["nachname"]) {
            $value = $leute_row["vorname"] . " " . $leute_row["nachname"];
        } else {
            if ($leute_row["firm"]) {
                $value = $leute_row["firm"] . " (" . $leute_row["department"] . ")";
            }
        }
        $avalues[] = $leute_row["id"];
        $adescs[] = $value;
    }

    return array($avalues, $adescs);
}//kota_peopleselect()

