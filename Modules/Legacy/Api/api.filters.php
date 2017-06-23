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
 * FILTER                                                                                                               *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Returns a single filter from ko_filter
 *
 * @param int Filter id
 * @param array Filter
 */
function ko_get_filter_by_id($id, &$f)
{
    ko_get_filters($all_filters, "leute", true);
    $f = $all_filters[$id];
}//ko_get_filter_by_id()


/**
 * Get filters by type (e.g. type="leute")
 *
 * @param array Filters
 * @param string Type of filters to get
 * @param boolean Get all filter if true, if false only get the allowed filters for the logged in user
 */
function ko_get_filters(&$f, $typ, $get_all = false, $order = 'name')
{
    global $LEUTE_NO_FAMILY;

    if ($order == 'name' && isset($GLOBALS['kOOL']['ko_filter'][$typ][($get_all ? 'all' : 'notall')])) {
        $f = $GLOBALS['kOOL']['ko_filter'][$typ][($get_all ? 'all' : 'notall')];
        return;
    }

    $map_sort_groups = array(
        'person' => 1,
        'com' => 2,
        'status' => 3,
        'family' => 4,
        'groups' => 5,
        'smallgroup' => 6,
        'misc' => 7
    );

    //Prepare the filters, that are not to be display because this user is not allowed to view this column
    $allowed_cols = ko_get_leute_admin_spalten($_SESSION["ses_userid"], "all");
    if (is_array($allowed_cols["view"]) && sizeof($allowed_cols["view"]) > 0 && ko_module_installed("groups",
            $_SESSION["ses_userid"])
    ) {
        $allowed_cols["view"] = array_merge($allowed_cols["view"], array("groups", "roles"));
    }
    //Add column event, which is used for the rota filter
    if (is_array($allowed_cols['view']) && sizeof($allowed_cols['view']) > 0 && ko_module_installed('rota',
            $_SESSION['ses_userid'])
    ) {
        $allowed_cols['view'] = array_merge($allowed_cols['view'], array('event'));
    }

    $f = $_f = array();
    $orderby = $order == 'name' ? 'ORDER BY `name` ASC' : 'ORDER BY `group` ASC, `name` ASC';
    $rows = db_select_data('ko_filter', "WHERE `typ` = '$typ'", '*', $orderby);
    foreach ($rows as $row) {
        if (!$get_all) {
            if ($row['name'] == 'donation' && !ko_module_installed('donations', $_SESSION['ses_userid'])) {
                continue;
            }
            if ($row['name'] == 'logins' && ko_get_access_all('admin') < 5) {
                continue;
            }
            if ($row['name'] == 'duplicates') {  //Only show duplicates filter if access level allows editing and deleting
                ko_get_access_all('leute', $_SESSION['ses_userid'], $max_leute);
                if ($max_leute < 3) {
                    continue;
                }
            }
            //Filters for the small group module
            if ((in_array($row['name'], array('smallgroup', 'smallgrouproles')) || substr($row['dbcol'], 0,
                        strpos($row['dbcol'], '.')) == 'ko_kleingruppen') && !ko_module_installed('kg')
            ) {
                continue;
            }

            //Filters for the rota module
            if (substr($row['dbcol'], 0,
                    strpos($row['dbcol'], '.')) == 'ko_rota_schedulling' && !ko_module_installed('rota')
            ) {
                continue;
            }

            //Don't return filters for columns, that are not allowed
            if (is_array($allowed_cols["view"]) && sizeof($allowed_cols["view"]) > 0) {
                $ok = false;

                //Get DB column from the column ko_filter.dbcol
                if ($row['dbcol'] != '' && false === strpos($row['dbcol'], '.')) {
                    $dbcol = $row['dbcol'];
                    //Check for allowed column
                    if (in_array($dbcol, $allowed_cols['view'])) {
                        $ok = true;
                    }
                } else {
                    $ok = true;
                }

                if (!$ok) {
                    continue;
                }
            }
        }//if(!get_all)

        //special filters for other tables
        for ($i = 1; $i < 4; $i++) {
            if (substr($row["code$i"], 0, 4) == "FCN:") {
                $fcn = substr($row["code$i"], 4);
                if (strpos($fcn, ":")) {  //Find parameters given along with the function name (e.g. used for enum_ll)
                    $params = explode(":", $fcn);
                    $fcn = $params[0];
                    if (function_exists($fcn)) {
                        eval("$fcn(\$code, \$params);");
                    }
                } else {
                    if (function_exists($fcn)) {
                        eval("$fcn(\$code);");
                    }
                }
                $row["code$i"] = $code;
            }
        }

        //Locallang-values if set
        $ll_name = getLL("filter_" . $row["name"]);
        foreach (array("var1", "var2", "var3") as $var) {
            $ll_var = getLL("filter_" . $var . "_" . $row["name"]);
            $row[$var] = $ll_var ? $ll_var : ($ll_name ? $ll_name : $row[$var]);
        }
        $row["_name"] = $row["name"];  //Keep name as in db table for comparisons
        $row["name"] = $ll_name ? $ll_name : $row["name"];

        //If no group is defined, set it to misc
        if ($row['group'] == '') {
            $row['group'] = 'misc';
        }

        $_f[$row["id"]] = $row;

        //prepare for ll sorting
        $filter_sort[$row['id']] = $order == 'name' ? $row['name'] : $map_sort_groups[$row['group']] . $row['name'];
    }

    //Sort filters by the localized name
    asort($filter_sort);
    foreach ($filter_sort as $id => $name) {
        $f[$id] = $_f[$id];
    }

    // unset family filter option if $LEUTE_NO_FAMILY is set to true
    if ($LEUTE_NO_FAMILY) {
        foreach ($f as $k => $ff) {
            if ($ff['group'] == 'family') {
                unset($f[$k]);
            }
        }
    }

    $GLOBALS['kOOL']['ko_filter'][$typ][($get_all ? 'all' : 'notall')] = $f;
}//ko_get_filters()


/**
 * Tries to find the column a filter is applied to
 *
 * Will be obsolete soon, after storing this information in a new db column in ko_filter
 */
function ko_get_filter_column($sql)
{
    $remove = explode(",",
        "(,),1,2,3,4,5,6,7,8,9,0,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,-,>,<,=,+,*,/,[,],',`, ");
    while (strlen($sql) > 0 && in_array(substr($sql, 0, 1), $remove)) {
        $sql = substr($sql, 1);
    }

    $keep = explode(",", "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,_,1,2,3,4,5,6,7,8,9,0");
    for ($i = 0; $i < strlen($sql); $i++) {
        if (!in_array(substr($sql, $i, 1), $keep)) {
            $sql = substr($sql, 0, $i);
        }
    }
    return $sql;
}//ko_get_filter_column()


/**
 * Generate a special filter for group datafields
 *
 * This function is called by the filter for group datafields by a FCN: definition in the SQL column
 * It generates the necessary code for the filter dynamically
 *
 * @param string HTML code for the filter
 */
function ko_specialfilter_groupdatafields(&$code)
{
    //Only get groups with datafields set and exclude expired groups according to userpref
    $where = "WHERE `datafields` != '' ";
    if (ko_get_userpref($_SESSION['ses_userid'], 'show_passed_groups') != 1) {
        $where .= "AND (`start` < CURDATE() AND (`stop` = '0000-00-00' OR `stop` > CURDATE()))";
    }
    $df_groups = db_select_data('ko_groups', $where);


    $code = '<select name="var1" size="0" onchange="sendReq(' . "'../groups/inc/ajax.php', 'action,dfid,sesid', 'groupdatafieldsfilter,'+this.options[this.selectedIndex].value+'," . session_id() . "', do_element);" . '">';
    $code .= '<option value=""></option>';

    //get reusable first
    $dfs = db_select_data("ko_groups_datafields", "WHERE `reusable` = '1' AND `preset` = '0'", "*",
        "ORDER BY description ASC");
    foreach ($dfs as $df) {
        $code .= '<option value="' . $df['id'] . '" title="' . $df['description'] . '">' . ko_html($df['description']) . '</option>';
    }


    //add group specific afterwards
    $dfs = db_select_data("ko_groups_datafields", "WHERE `reusable` = '0' AND `preset` = '0'", "*",
        "ORDER BY description ASC");
    foreach ($dfs as $df) {
        //find first group, this datafield is used in and use this a description
        $group_name = "";
        foreach ($df_groups as $group) {
            if (strstr($group["datafields"], $df["id"])) {
                $group_name = $group["name"];
                break;
            }
        }
        //Don't display unused datafields
        if (!$group_name) {
            continue;
        }

        $code .= '<option value="' . $df['id'] . '" title="' . $df['description'] . ' (' . $group_name . ')">' . ko_html($df['description']) . ' (' . $group_name . ')</option>';
    }
    $code .= '</select>';
}//ko_specialfilter_groupdatafields()


/**
 * Generate a special filter for smallgroup regions
 *
 * This function is called by the filter for smallgroup regions by a FCN: definition in the SQL column
 * It generates the necessary code for the filter dynamically
 *
 * @param string HTML code for the filter
 */
function ko_specialfilter_kleingruppen_region(&$code)
{
    $code = '<select name="var1" size="0"><option value=""></option>';
    $rows = db_select_distinct("ko_kleingruppen", "region", "", "", true);
    foreach ($rows as $row) {
        if (!$row) {
            continue;
        }
        $code .= '<option value="' . $row . '" title="' . $row . '">' . ko_html($row) . '</option>';
    }
    $code .= '</select>';
}//ko_spcialfilter_kleingruppen_region()


/**
 * Generate a special filter for smallgroup types
 *
 * This function is called by the filter for smallgroup types by a FCN: definition in the SQL column
 * It generates the necessary code for the filter dynamically
 *
 * @param string HTML code for the filter
 */
function ko_specialfilter_kleingruppen_type(&$code)
{
    $code = '<select name="var1" size="0"><option value=""></option>';
    $rows = db_select_distinct("ko_kleingruppen", "type", "", "", true);
    foreach ($rows as $row) {
        if (!$row) {
            continue;
        }
        $code .= '<option value="' . $row . '" title="' . $row . '">' . ko_html($row) . '</option>';
    }
    $code .= '</select>';
}//ko_spcialfilter_kleingruppen_type()


/**
 * Generate a special filter for the family role of ko_leute
 *
 * @param string HTML code for the filter
 */
function ko_specialfilter_enum_ll(&$code, $params)
{
    //Parse parameters (0 is function name)
    $table = $params[1];
    $col = $params[2];

    $code = '<select name="var1" size="0"><option value=""></option>';
    $rows = db_get_enums_ll($table, $col);
    foreach ($rows as $key => $value) {
        if (!$key) {
            continue;
        }
        $code .= '<option value="' . $key . '" title="' . $value . '">' . $value . '</option>';
    }
    $code .= '</select>';
}//ko_spcialfilter_enum_ll()


/**
 * Rota filter: Show all events with rota schedulling for the user to select one.
 * The applied filter will then show people scheduled in this event.
 */
function ko_specialfilter_rota(&$code)
{
    global $DATETIME;

    ko_get_eventgruppen($grps);

    $code = '<select name="var1" size="0">';
    $events = db_select_data("ko_event", "WHERE `rota` IN (1,2) AND `startdatum` > NOW()", "*",
        "ORDER BY startdatum ASC, eventgruppen_id ASC", "LIMIT 0,30");
    foreach ($events as $event) {
        $value = strftime($DATETIME["dmy"], strtotime($event["startdatum"]));
        $value .= ": " . $grps[$event["eventgruppen_id"]]["name"];
        $code .= '<option value="' . $event["id"] . '" title="' . $value . '">' . $value . '</option>';
    }
    $code .= '</select>';
}//ko_specialfilter_rota()


/**
 * Rota filter: Show a list of alle team presets for the user to select one.
 * The applied filter will only show people scheduled in the given event and in one of these teams.
 */
function ko_specialfilter_rota_teams(&$code)
{
    $code = '<select name="var2" size="0"><option value="">' . getLL('all') . '</option>';

    //Get all presets
    $itemset = array_merge((array)ko_get_userpref('-1', '', 'rota_itemset', 'ORDER by `key` ASC'),
        (array)ko_get_userpref($_SESSION['ses_userid'], '', 'rota_itemset', 'ORDER by `key` ASC'));
    foreach ($itemset as $i) {
        $value = $i['user_id'] == '-1' ? '@G@' . $i['key'] : $i['key'];
        $desc = $i['user_id'] == '-1' ? getLL('itemlist_global_short') . ' ' . $i['key'] : $i['key'];
        $code .= '<option value="' . $value . '" title="' . $desc . '">"' . $desc . '"</option>';
    }

    //Add all teams
    $orderCol = ko_get_setting('rota_manual_ordering') ? 'sort' : 'name';
    $teams = db_select_data('ko_rota_teams', 'WHERE 1', '*', 'ORDER BY `' . $orderCol . '` ASC');
    if (sizeof($itemset) > 0 && sizeof($teams) > 0) {
        $code .= '<option value="" disabled="disabled">-- ' . strtoupper(getLL('rota_teams_list_title')) . ' --</option>';
    }
    if (sizeof($teams) > 0) {
        foreach ($teams as $team) {
            $code .= '<option value="' . $team['id'] . '" title="' . $team['name'] . '">' . $team['name'] . '</option>';
        }
    }

    $code .= '</select>';
}//ko_specialfilter_rota_teams()


function ko_specialfilter_filterpreset(&$code)
{
    $filterset = array_merge((array)ko_get_userpref('-1', '', 'filterset', 'ORDER BY `key` ASC'),
        (array)ko_get_userpref($_SESSION['ses_userid'], '', 'filterset', 'ORDER BY `key` ASC'));

    $code = '<select name="var1" size="0">';
    foreach ($filterset as $f) {
        $value = $f['user_id'] == '-1' ? '@G@' . $f['key'] : $f['key'];
        $desc = $f['user_id'] == '-1' ? getLL('itemlist_global_short') . ' ' . $f['key'] : $f['key'];
        $code .= '<option value="' . $value . '" title="' . $desc . '">' . $desc . '</option>';
    }
    $code .= '</select>';
}//ko_specialfilter_filterpreset()


function ko_specialfilter_crdate(&$code, $params)
{
    foreach ($_SESSION["filter"] as $i => $f) {
        if (!is_numeric($i)) {
            continue;
        }
        $filter = db_select_data("ko_filter", "WHERE `id` = '" . $f[0] . "'", "*", "", "", true);
        if ($filter["name"] == "crdate") {
            $value1 = $f[1][1];
            $value2 = $f[1][2];
        }
    }

    if ($params[1] == 1) {
        $value1 = $value1 ? $value1 : "0000-00-00";
        $code = '<input type="text" name="var1" size="12" maxlength="10" onkeydown="if ((event.which == 13) || (event.keyCode == 13)) { this.form.submit_filter.click(); return false;} else return true;" value="' . $value1 . '" />';
    } else {
        if ($params[1] == 2) {
            $value2 = $value2 ? $value2 : strftime("%Y-%m-%d", time());
            $code = '<input type="text" name="var2" size="12" maxlength="10" onkeydown="if ((event.which == 13) || (event.keyCode == 13)) { this.form.submit_filter.click(); return false;} else return true;" value="' . $value2 . '" />';
        }
    }

}//ko_specialfilter_crdate()


function ko_specialfilter_donation(&$code)
{
    $code = '<select name="var1" size="0">';
    $rows = db_select_distinct('ko_donations', 'YEAR(date)', '', "WHERE `promise` = '0'");
    foreach ($rows as $row) {
        if (!$row) {
            continue;
        }
        $code .= '<option value="' . $row . '" title="' . $row . '">' . ko_html($row) . '</option>';
    }
    $code .= '</select>';
}//ko_spcialfilter_donation()


function ko_specialfilter_donation_account(&$code)
{
    $code = '<select name="var2" size="0"><option value=""></option>';
    $rows = db_select_data('ko_donations_accounts', 'WHERE 1=1', '*');
    foreach ($rows as $row) {
        $code .= '<option value="' . $row['id'] . '" title="' . ko_html($row['number'] . ' ' . $row['name']) . '">' . ko_html($row['number'] . ' ' . $row['name']) . '</option>';
    }
    $code .= '</select>';
}//ko_spcialfilter_donation_account()


function ko_specialfilter_smallgrouproles(&$code)
{
    global $SMALLGROUPS_ROLES;

    $code = '<select name="var1" size="0">';
    foreach ($SMALLGROUPS_ROLES as $role) {
        if (!$role) {
            continue;
        }
        $code .= '<option value="' . $role . '" title="' . getLL('kg_roles_' . $role) . '">' . getLL('kg_roles_' . $role) . '</option>';
    }
    $code .= '</select>';
}//ko_spcialfilter_smallgrouproles()


function ko_specialfilter_duplicates(&$code)
{
    $code = '<select name="var1" size="0">';
    $fields = array(
        'vorname-nachname',
        'vorname-email',
        'vorname-adresse',
        'vorname-geburtsdatum',
        'natel-geburtsdatum',
        'firm-plz',
        'firm-ort',
        'firm',
        'email'
    );
    foreach ($fields as $field) {
        $code .= '<option value="' . $field . '" title="' . getLL('leute_duplicates_' . $field) . '">' . getLL('leute_duplicates_' . $field) . '</option>';
    }
    $code .= '</select>';
}//ko_spcialfilter_duplicates()


function ko_specialfilter_logins(&$code)
{
    $code = '<select name="var1" size="0">';
    $code .= '<option value="_all">' . getLL('all') . '</option>';
    $groups = db_select_data('ko_admingroups', "WHERE 1=1", '*');
    foreach ($groups as $group) {
        if (!$group['id']) {
            continue;
        }
        $code .= '<option value="' . $group['id'] . '" title="' . $group['name'] . '">' . ko_html($group['name']) . '</option>';
    }
    $code .= '</select>';
}//ko_spcialfilter_logins()

