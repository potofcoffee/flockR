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
 * MODUL-FUNKTIONEN   L E U T E                                                                                         *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Liefert eine Liste alle vorkommenden L�nder in der Personen-Daten
 */
function ko_get_all_countries(&$c)
{
    $c = db_select_distinct('ko_leute', 'land', '', "WHERE `deleted` = '0'");
}//ko_get_all_countries()


/**
 * Liefert Personen-Daten
 */
function ko_get_leute(&$p, $z_where = "", $z_limit = "", $z_cols = "", $z_sort = "", $apply_version = true)
{
    global $ko_menu_akt;

    //only apply sorting if not a MODULE-column is to be sorted
    if (is_string($z_sort) && !empty($z_sort)) {
        $sort = $z_sort;
    } else {
        if (is_array($_SESSION["sort_leute"]) && is_array($_SESSION["sort_leute_order"])) {
            $sort_add = array();
            foreach ($_SESSION["sort_leute"] as $i => $col) {
                if (substr($col, 0, 6) != "MODULE") {
                    $sort_add[] = $col . " " . $_SESSION["sort_leute_order"][$i];
                }
            }
            if (!in_array("nachname", $_SESSION["sort_leute"])) {
                $sort_add[] = "nachname ASC";
            }
            if (!in_array("vorname", $_SESSION["sort_leute"])) {
                $sort_add[] = "vorname ASC";
            }
            $sort = "ORDER BY " . implode(", ", $sort_add);
        } else {
            $sort = "";
        }
    }

    //Decide on the columns to get
    if ($z_cols != "") {
        $cols = $z_cols;
    } else {
        $cols = "*";
    }

    //Unset z_limit if an old version is to be retrieved
    $limit = false;
    if ($_SESSION["leute_version"] && $z_limit && $apply_version) {
        list($limit_start, $limit) = explode(", ", str_replace("LIMIT ", "", $z_limit));
        $z_limit = "";
    }

    //Perform query
    $p = array();
    $count = 0;
    $rows = db_select_data('ko_leute', 'WHERE 1=1 ' . $z_where, $cols, $sort, $z_limit);
    $num = sizeof($rows);
    foreach ($rows as $row) {
        //Get old version of person if set
        if ($_SESSION["leute_version"] && $apply_version) {
            //Apply limit manually if z_limit was set but old version is displayed
            if ($limit && ($count < $limit_start || $count >= $limit)) {
                continue;
            }
            //Don't show records with crdate greater than the given date. Display all those with no crdate (backwards compatibilty and safer)
            if (strtotime($row["crdate"]) > strtotime($_SESSION["leute_version"] . " 23:59:59")) {
                $num--;
                continue;
            }
            //Get old version
            $old = ko_leute_get_version($_SESSION["leute_version"], $row["id"]);
            $hid = strpos(ko_get_leute_hidden_sql(), "hidden = '0'");
            if ($old["deleted"] == 1 || ($hid && $old["hidden"] == 1)) {
                //Don't display old version that used to be deleted or hidden when hidden entries are to be invisible
                $num--;
                continue;
            } else {
                if (isset($old["id"])) {  //old entry found
                    $p[$row["id"]] = $old;
                } else {
                    if ($row["deleted"] == 0) {  //no old entry so display current if not deleted
                        $p[$row["id"]] = $row;
                    } else {
                        //Don't display currently deleted entries with no old version
                        $num--;
                        continue;
                    }
                }
            }
        } //Normal case, so just store current entry as it is in ko_leute
        else {
            $p[$row["id"]] = $row;
        }
    }
    return $num;
}//ko_get_leute()


function ko_manual_sorting($cols)
{
    if ($_SESSION["leute_version"]) {
        return true;
    } else {
        $manual_columns = array('smallgroups', 'famid', 'famfunction');
        foreach ($cols as $col) {
            if (in_array($col, $manual_columns) || substr($col, 0, 6) == "MODULE") {
                return true;
            }
        }
    }
    return false;
}//ko_manual_sorting()


/**
 * Liefert Familien-Daten zu Familien-ID
 */
function ko_get_familie($id)
{
    if (!is_numeric($id)) {
        return false;
    }

    $fam = db_select_data('ko_familie', "WHERE `famid` = '$id'", '*', '', 'LIMIT 1', true);
    ko_add_fam_id($fam);

    return $fam;
}//ko_get_familie()


/**
 * F�gt eine Familien-ID bestehend aus Nachname, Ort und Vornamen zur �bergebenen Familie
 */
function ko_add_fam_id(&$fam, $_members = "")
{
    global $COLS_LEUTE_UND_FAMILIE, $FAMFUNCTION_SORT_ORDER;

    $max_len = 12;

    if ($_members) {
        $members = $_members;
        $num_members = sizeof($members);
    } else {
        $num_members = ko_get_personen_by_familie($fam["famid"], $members);
    }
    //order members by Father, Mother, Kids
    $new_members = array();
    foreach ($members as $i => $member) {
        $sortKey = $FAMFUNCTION_SORT_ORDER[$member['famfunction']] ? $FAMFUNCTION_SORT_ORDER[$member['famfunction']] : 10;
        $sort_members[$sortKey][$i] = $member['geburtsdatum'] . $member['vorname'];
    }
    if (sizeof($sort_members[1]) > 0) {
        asort($sort_members[1]);   //Man
        foreach ($sort_members[1] as $k => $v) {
            $new_members[] = $members[$k];
        }
    }
    if (sizeof($sort_members[2]) > 0) {
        asort($sort_members[2]);   //Woman
        foreach ($sort_members[2] as $k => $v) {
            $new_members[] = $members[$k];
        }
    }
    if (sizeof($sort_members[3]) > 0) {
        asort($sort_members[3]);   //Children
        foreach ($sort_members[3] as $k => $v) {
            $new_members[] = $members[$k];
        }
    }
    if (sizeof($sort_members[10]) > 0) {
        asort($sort_members[10]);  //No famfunction defined
        foreach ($sort_members[10] as $k => $v) {
            $new_members[] = $members[$k];
        }
    }
    $members = $new_members;
    reset($members);

    //lastname
    if (in_array("nachname", $COLS_LEUTE_UND_FAMILIE)) {
        //normal families, with lastname as a family field
        $famlastname = trim($fam['nachname']);
    } else {
        //mixed families with different lastnames
        $famnames = array();
        foreach ($members as $member) {
            if (!in_array(trim($member['nachname']), $famnames)) {
                $famnames[] = trim($member['nachname']);
            }
        }
        $famlastname = implode(getLL('family_lastname_link'), $famnames);
    }
    //city
    if ($fam["ort"]) {
        $famcity = strlen($fam["ort"]) > $max_len ? substr($fam["ort"], 0, $max_len) . ".." : $fam["ort"];
        $famcity = getLL("from") . " " . $famcity;
    }
    //single members of the family
    $fammembers = "";
    if ($num_members > 0) {
        foreach ($members as $p) {
            $fammembers .= strtoupper(substr($p["vorname"], 0, 1)) . ",";
        }
        $fammembers = "(" . substr($fammembers, 0, -1) . ")";
    }//if(num_members>0)

    //put it all together into the new fam-id
    $fam["id"] = $famlastname . " " . $famcity . " " . $fammembers;
    //save lastname in family, even if lastname is not a family field. This makes the export work with lastnames
    if (!in_array("nachname", $COLS_LEUTE_UND_FAMILIE)) {
        $fam["nachname"] = $famlastname;
    }
}//ko_add_fam_id()


/**
 * Liefert alle Familien
 * inkl. ID
 */
function ko_get_familien(&$fam)
{
    global $ko_menu_akt;

    $fam = array();

    //Get all families
    $query = "SELECT * FROM ko_familie ORDER BY nachname ASC";
    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        $fam[$row["famid"]] = $row;
    }

    //Get all family members once, so they don't have to be retrieved inside the loop
    $members = array();
    $deleted = ($ko_menu_akt == "leute" && ko_get_userpref($_SESSION["ses_userid"],
            "leute_show_deleted") == 1) ? " AND `deleted` = '1' " : " AND `deleted` = '0' ";
    $deleted .= ko_get_leute_hidden_sql();
    $result = mysql_query("SELECT * FROM ko_leute WHERE `famid` != '0' $deleted ORDER BY `famid` ASC");
    while ($row = mysql_fetch_assoc($result)) {
        $members[$row["famid"]][] = $row;
    }

    //Add family ID
    foreach ($fam as $i => $f) {
        ko_add_fam_id($fam[$i], $members[$f["famid"]]);
        $sort[$fam[$i]["id"]] = $fam[$i]["famid"];
    }//foreach(fam)

    //sort them by famid which is constructed by all the lastnames
    ksort($sort, SORT_LOCALE_STRING);
    $return = null;
    foreach ($sort as $famid) {
        $return[$famid] = $fam[$famid];
    }
    $fam = $return;
}//ko_get_familien()


/**
 * Aktualisiert eine Familie mit den �bergebenen Fam-Daten
 * und aktualisiert alle Member
 */
function ko_update_familie($famid, $fam_data, $leute_id = "")
{
    global $FAMILIE_EXCLUDE;
    $data = array();
    $fam_cols = db_get_columns("ko_familie");
    foreach ($fam_cols as $col_) {
        $col = $col_["Field"];
        if (in_array($col, $FAMILIE_EXCLUDE)) {
            continue;
        }
        if (!isset($fam_data[$col])) {
            continue;
        }

        $data[$col] = $fam_data[$col];
    }//foreach(fam_cols as col)
    if (sizeof($data) == 0) {
        return;
    }

    //Familien-Daten aktualisieren
    db_update_data('ko_familie', "WHERE `famid` = '$famid'", $data);

    //Alle Familien-Mitglieder aktualisieren
    ko_update_leute_in_familie($famid, $changes = true, $leute_id);
}//ko_update_familie()


/**
 * Aktualisiert alle Member mit den Angaben aus der Familie
 * Only store changes to ko_leute_changes if second argument is set. This gets set in ko_update_familie()
 * The third argument defines the id of the person being saved, as no entry to ko_leute_changes must be saved (this is done in submit_edit_person in leute/index.php
 */
function ko_update_leute_in_familie($famid, $do_changes = false, $leute_id = "")
{
    global $COLS_LEUTE_UND_FAMILIE;

    if (!is_numeric($famid) || $famid <= 0) {
        return false;
    }
    $fam_data = ko_get_familie($famid);

    $data = array();
    foreach ($COLS_LEUTE_UND_FAMILIE as $col) {
        $data[$col] = $fam_data[$col];
    }

    //Kinder-Feld
    $num_kids = ko_get_personen_by_familie($famid, $members, "child");

    $do_ldap = ko_do_ldap();
    if ($do_ldap) {
        $ldap = ko_ldap_connect();
    }

    //Daten aller Family-Members aktualisieren
    ko_get_personen_by_familie($famid, $members);
    foreach ($members as $m) {
        //store version
        if ($do_changes && $leute_id != $m["id"]) {
            ko_save_leute_changes($m["id"]);
        }
        //Update according to fam data
        if ($m["famfunction"] == "husband" || $m["famfunction"] == "wife") {
            $data['kinder'] = $num_kids;
        } else {
            $data['kinder'] = '0';
        }
        db_update_data('ko_leute', "WHERE `id` = '" . $m['id'] . "'", $data);

        //Update LDAP for each member
        if (ko_do_ldap() && $m['id'] != $leute_id) {
            ko_ldap_add_person($ldap, $m, $m['id'], ko_ldap_check_person($ldap, $m['id']));
        }
    }

    if ($do_ldap) {
        ko_ldap_close($ldap);
    }
}//ko_update_leute_in_familie()


/**
 * Liefert einzelne Person
 */
function ko_get_person_by_id($id, &$p, $show_deleted = false)
{
    global $ko_menu_akt;

    $p = array();
    if (!is_numeric($id)) {
        return false;
    }

    if (!$show_deleted) {
        $deleted = ($ko_menu_akt == "leute" && ko_get_userpref($_SESSION["ses_userid"],
                "leute_show_deleted") == 1) ? " AND `deleted` = '1' " : " AND `deleted` = '0' ";
    }

    $p = db_select_data('ko_leute', "WHERE `id` = '$id' $deleted", '*', '', '', true);
}//ko_get_person_by_id()


/**
 * Changes the address fields of the given address record according to the given rectype
 *
 * @param array $p Address record from ko_leute
 * @param string $force_rectype Specify the rectype that should be applied. If none is given, this persons default rectype ($p[rectype]) will be used
 * @param array $addp Will hold additional addresses if rectype uses reference to other addresses, which might be more than one
 * @returns array $p Returns the address with the applied changes to the fields defined for this rectype
 */
function ko_apply_rectype($p, $force_rectype = '', &$addp)
{
    global $RECTYPES;

    if (!is_array($p)) {
        return $p;
    }

    $target_rectype = $force_rectype != '' ? $force_rectype : $p['rectype'];
    $addp = array();

    if ($target_rectype && is_array($RECTYPES[$target_rectype])) {
        foreach ($RECTYPES[$target_rectype] as $pcol => $newcol) {
            if (!isset($p[$pcol])) {
                continue;
            }
            if (false === strpos($newcol, ':')) {
                $p[$pcol] = $p[$newcol];
            } else {
                list($table, $field) = explode(':', $newcol);
                switch ($table) {
                    //Use data from smallgroup this person is assigned to
                    case 'ko_kleingruppen':
                        list($sgs) = explode(',', $p['smallgroups']);
                        if (!$sgs) {
                            continue;
                        }
                        list($sgid, $sgrole) = explode(':', $sgs);
                        $sg = ko_get_smallgroup_by_id($sgid);
                        if (isset($sg[$field])) {
                            $p[$pcol] = $sg[$field];
                        }
                        break;

                    //Use columns from another address in ko_leute. Other address defined in $field
                    case 'ko_leute':
                        if (!$p[$field]) {
                            continue;
                        }
                        $persons = db_select_data('ko_leute',
                            "WHERE `id` IN (" . $p[$field] . ") AND `deleted` = '0' AND `hidden` = '0'");
                        if (sizeof($persons) < 1) {
                            continue;
                        }
                        $first = true;
                        foreach ($persons as $person) {
                            //Prevent circular dependency
                            if ($person['id'] == $p['id']) {
                                continue;
                            }

                            $person = ko_apply_rectype($person, $force_rectype);
                            //If multiple addresses are returned apply first changes to original $p...
                            if ($first) {
                                $p[$pcol] = $person[$pcol];
                                $first = false;
                            } //...and store the remaining changes in $addp
                            else {
                                if (!is_array($addp[$person['id']])) {
                                    $addp[$person['id']] = $p;
                                }
                                $addp[$person['id']][$pcol] = $person[$pcol];
                            }
                        }
                        break;

                    default:
                        continue;
                }
            }
        }
    }

    return $p;
}//ko_apply_rectype()


/**
 * Get the name belonging to a person's id
 */
function ko_get_person_name($id, $format = "vorname nachname")
{
    ko_get_person_by_id($id, $p);
    return strtr($format, $p);
}//ko_get_person_name()


/**
 * Liefert alle Personen einer Familie
 */
function ko_get_personen_by_familie($famid, &$p, $function = "")
{
    if (!is_numeric($famid) || $famid <= 0) {
        return false;
    }
    $p = array();
    $z_where = '';

    if ((!is_array($function) && $function != '') || (is_array($function) && sizeof($function) > 0)) {
        if (!is_array($function)) {
            $function = array($function);
        }
        $fam_functions = db_get_enums('ko_leute', 'famfunction');
        foreach ($function as $fi => $f) {
            if ($f == '') {
                continue;
            }
            if (!in_array($f, $fam_functions)) {
                unset($function[$fi]);
            }
        }
        if (sizeof($function) > 0) {
            $z_where = " AND `famfunction` IN ('" . implode("','", $function) . "') ";
        }
    }

    $p = db_select_data('ko_leute', "WHERE `famid` = '$famid' $z_where AND `deleted` = '0' AND `hidden` = '0'", '*',
        'ORDER BY famfunction DESC');

    return sizeof($p);
}//ko_get_personen_by_familie()


/**
 * Liefert eine Liste aller (oder wenn id definiert ist nur diesen Eintrag) zu moderierenden Mutationen (aus Tabelle ko_leute_mod)
 */
function ko_get_mod_leute(&$r, $id = "")
{
    $r = array();
    $z_where = "WHERE `_leute_id` <> '0' AND `_group_id` = ''";  //don't show web-group-subscriptions
    $z_where .= ($id != "") ? " AND `_id`='$id'" : "";
    $query = "SELECT * FROM `ko_leute_mod` $z_where ORDER BY _crdate DESC";
    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        $r[$row["_id"]] = $row;
    }
}//ko_get_mod_leute()


/**
 * Liefert eine Liste aller (oder wenn id definiert ist nur diesen Eintrag) zu moderierenden Gruppen-Anmeldungen (aus Tabelle ko_leute_mod)
 */
function ko_get_groupsubscriptions(&$r, $gsid = '', $uid = '', $gid = '')
{
    global $access;

    // Group rights if uid is given
    if ($uid > 0) {
        ko_get_access('groups');
    }

    // Get subscriptions
    $r = array();
    $z_where = "WHERE `_group_id` != ''";  //don't show address changes
    if ($gsid != '') {
        $z_where .= " AND `_id` = '$gsid'";
    }
    if ($gid != '') {
        $z_where .= " AND `_group_id` LIKE '%g$gid%'";
    }
    $query = "SELECT * FROM `ko_leute_mod` $z_where ORDER BY _crdate DESC";
    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        if ($uid) {
            // Only display subscriptions to groups the given user has level 2 access to
            if ($access['groups']['ALL'] > 1 || $access['groups'][ko_groups_decode($row['_group_id'],
                    'group_id')] > 1
            ) {
                $r[$row["_id"]] = $row;
            }
        } else {
            // Return them all if no userid is given
            $r[$row["_id"]] = $row;
        }
    }
}//ko_get_groupsubscriptions()


/**
 * Apply filter given in setting 'birthday_filter' and return SQL
 * To be attached to SQL to get birthday list
 */
function ko_get_birthday_filter()
{
    $filter = unserialize(ko_get_userpref($_SESSION['ses_userid'], 'birthday_filter'));
    if (!$filter['value']) {
        return '';
    } else {
        apply_leute_filter(unserialize($filter['value']), $z_where);
        return ' ' . $z_where;
    }
}//ko_get_birthday_filter()


/**
 * Liefert die Spaltennamen der ko_leute-DB
 * Zus�tzlich werden noch Module-Spaltennamen (wie z.B. Gruppen) hinzugef�gt
 * mode kann view oder edit sein, jenachdem f�r welchen Modus die Spalten gem�ss ko_admin.leute_admin_spalten verlangt sind
 *   (bei all wird auf den Vergleich mit allowed_cols verzichtet)
 */
function ko_get_leute_col_name(
    $groups_hierarchie = false,
    $add_group_datafields = false,
    $mode = "view",
    $force = false,
    &$rawgdata = ''
)
{
    global $access;
    global $LEUTE_NO_FAMILY;

    if (!isset($access['kg'])) {
        ko_get_access('kg');
    }
    if (!isset($access['groups'])) {
        ko_get_access('groups');
    }

    $r_all = unserialize(ko_get_setting("leute_col_name", true));
    $r = $r_all[$_SESSION["lang"]];

    //exclude not allowed cols, if set
    $allowed_cols = ko_get_leute_admin_spalten($_SESSION["ses_userid"], "all");
    $always_allowed = array();
    $do_groups = ko_module_installed('groups', $_SESSION['ses_userid']) && $access['groups']['MAX'] > 0;
    $do_smallgroups = ko_module_installed('kg', $_SESSION['ses_userid']) && $access['kg']['MAX'] > 0;
    if ($do_groups) {
        $always_allowed[] = 'groups';
    }
    if ($do_smallgroups) {
        $always_allowed[] = 'smallgroups';
    }

    //Unset not allowed columns
    if ($mode != "all") {
        if (is_array($allowed_cols[$mode]) && sizeof($allowed_cols[$mode]) > 0) {
            foreach ($r as $i => $v) {
                if (in_array($i, $always_allowed)) {
                    continue;
                }
                if (!in_array($i, $allowed_cols[$mode])) {
                    unset($r[$i]);
                }
            }
        } else {
            if (!$do_groups && in_array('groups', array_keys($r))) {
                foreach ($r as $i => $v) {
                    if ($i == 'groups') {
                        unset($r[$i]);
                    }
                }
            }
            if (!$do_smallgroups && in_array('smallgroups', array_keys($r))) {
                foreach ($r as $i => $v) {
                    if ($i == 'smallgroups') {
                        unset($r[$i]);
                    }
                }
            }
        }
    }

    //Family columns (father, mother)
    $famok = true;
    if ($mode != 'all' && is_array($allowed_cols[$mode]) && sizeof($allowed_cols[$mode]) > 0) {
        if (!in_array('famid', $allowed_cols[$mode])) {
            $famok = false;
        }
    }
    if ($famok && !$LEUTE_NO_FAMILY) {
        $r['MODULEfamid_husband'] = getLL('kota_ko_leute__famid_father');
        $r['MODULEfamid_wife'] = getLL('kota_ko_leute__famid_mother');
        $r['MODULEfamid_famlastname'] = getLL('kota_ko_leute__famid_famlastname');
    }

    //Remove empty entries
    foreach ($r as $k => $v) {
        if (!$v) {
            unset($r[$k]);
        }
    }

    //Allow plugins to add columns
    hook_leute_add_column($r);

    //Add small group columns
    if (ko_module_installed('kg') && ko_get_userpref($_SESSION['ses_userid'], 'leute_kg_as_cols') == 1) {
        $kg_cols = db_get_columns('ko_kleingruppen');
        foreach ($kg_cols as $col) {
            if (in_array($col['Field'], array('id'))) {
                continue;
            }
            $ll = getLL('kota_listview_ko_kleingruppen_' . $col['Field']);
            $ll = $ll ? $ll : $col['Field'];
            $r['MODULEkg' . $col['Field']] = getLL('kg_shortname') . ': ' . $ll;
        }
    }


    //Add groups
    if (ko_module_installed('groups') || $force) {
        if ($add_group_datafields) {
            $all_datafields = db_select_data('ko_groups_datafields', "WHERE 1");
        }

        $rawgdata = array();
        $groups = ko_groups_get_recursive(ko_get_groups_zwhere());
        ko_get_groups($all_groups);
        foreach ($groups as $group) {
            if ($access['groups']['ALL'] < 1 && $access['groups'][$group['id']] < 1 && !$force) {
                continue;
            }
            $name = strlen($group['name']) > ITEMLIST_LENGTH_MAX ? substr($group['name'], 0,
                    ITEMLIST_LENGTH_MAX) . '..' : $group['name'];
            if ($groups_hierarchie) {
                $ml = ko_groups_get_motherline($group['id'], $all_groups);
                $depth = sizeof($ml);
                for ($i = 0; $i < $depth; $i++) {
                    $name = '&nbsp;&nbsp;' . $name;
                }
            }
            $rawgdata[$group['id']] = array(
                'id' => $group['id'],
                'name' => $group['name'],
                'depth' => $depth,
                'pid' => array_pop($ml)
            );
            $r['MODULEgrp' . $group['id']] = $name;
            //add datafields for this group if needed
            if ($add_group_datafields && $all_groups[$group['id']]['datafields']) {
                foreach (explode(',', $all_groups[$group['id']]['datafields']) as $fid) {
                    $field = $all_datafields[$fid];
                    if (!$field['id']) {
                        continue;
                    }
                    $name = $field['description'];
                    if ($groups_hierarchie) {
                        for ($i = 0; $i <= $depth; $i++) {
                            $name = '&nbsp;&nbsp;' . $name;
                        }
                    }
                    $r['MODULEgrp' . $group['id'] . ':' . $fid] = $name;
                    $rawgdata[$group['id']]['df'][] = $field;
                }
            }
        }
    }


    //Tracking
    if (ko_module_installed('tracking') || $force) {
        if (!is_array($access['tracking'])) {
            ko_get_access('tracking');
        }
        $groups = db_select_data('ko_tracking_groups', "WHERE 1", '*', 'ORDER BY name ASC');
        array_unshift($groups, array('id' => '0', 'name' => getLL('tracking_itemlist_no_group')));
        $filters = db_select_data('ko_userprefs',
            'WHERE `type` = \'tracking_filterpreset\' and (`user_id` = ' . $_SESSION['ses_userid'] . ' or `user_id` = -1)',
            '`id`,`key`,`value`');
        foreach ($groups as $group) {
            $trackings = db_select_data('ko_tracking', "WHERE `group_id` = '" . $group['id'] . "'", '*',
                'ORDER BY name ASC');
            foreach ($trackings as $tracking) {
                if ($access['tracking'][$tracking['id']] < 1 && $access['tracking']['ALL'] < 1) {
                    continue;
                }
                $r['MODULEtracking' . $tracking['id']] = getLL('tracking_listtitle_short') . ' ' . $tracking['name'];
                foreach ($filters as $filter) {
                    $r['MODULEtracking' . $tracking['id'] . 'f' . $filter['id']] = getLL('tracking_listtitle_short') . ' ' . $tracking['name'] . ' ' . $filter['key'];
                }
            }
        }
    }

    //Donations
    if (ko_module_installed('donations') || $force) {
        if (!is_array($access['donations'])) {
            ko_get_access('donations');
        }
        if ($access['donations']['MAX'] > 0) {
            $years = db_select_distinct('ko_donations', 'YEAR(`date`)', 'ORDER BY `date` DESC');
            foreach ($years as $year) {
                $r['MODULEdonations' . $year] = getLL('donations_listtitle_short') . ' ' . $year;
                $accounts = db_select_data('ko_donations_accounts', "WHERE 1=1", '*', 'ORDER BY name ASC');
                foreach ($accounts as $account) {
                    if ($access['donations'][$account['id']] < 1 && $access['donations']['ALL'] < 1) {
                        continue;
                    }
                    $r['MODULEdonations' . $year . $account['id']] = getLL('donations_listtitle_short') . ' ' . $year . ' ' . $account['name'];
                }
            }
        }
    }

    return $r;
}//ko_get_leute_col_name()


function ko_get_family_col_name()
{
    $r_all = unserialize(ko_get_setting("familie_col_name"));
    $r = $r_all[$_SESSION["lang"]];

    return $r;
}//ko_get_family_col_name()


/**
 * Wendet die Leute-Filter an und gibt SQL-WHERE-Clause zur�ck
 * Ebenfalls verwendet, um Admin-Filter f�r Berechtigungen anzuwenden
 * Muss in ko.inc stehen (und nicht in leute/inc/leute.inc), damit ko_get_admin() immer Zugriff darauf hat --> z.B. f�r Dropdown-Men�s
 */
function apply_leute_filter(
    $filter,
    &$where_code,
    $add_admin_filter = true,
    $admin_filter_level = '',
    $_login_id = '',
    $includeAll = false
)
{
    global $ko_menu_akt;

    //Set login_id if given as parameter (needed from mailing.php because ses_userid is not set there)
    if ($_login_id != '') {
        $login_id = $_login_id;
    } else {
        $login_id = $_SESSION['ses_userid'];
    }

    //Innerhalb einer Filtergruppe werden die Filter mit OR verkn�pft
    $where_code = "";
    $q = array();
    if (is_array($filter)) {

        //Move addchildren filter to the end, so it will be applied as the last filter
        $new = array();
        $last = false;
        foreach ($filter as $f_i => $f) {
            ko_get_filter_by_id($f[0], $f_);
            if (in_array($f_['_name'], array('addchildren', 'addparents'))) {
                $last = $f;
            } else {
                $new[$f_i] = $f;
            }
        }
        $filter = $new;
        if ($last) {
            $filter[] = $last;
        }

        //Loop through all filters and build SQL
        $filter_sql = array();
        foreach ($filter as $f_i => $f) {
            if (!is_numeric($f_i)) {
                continue;
            }

            ko_get_filter_by_id($f[0], $f_);
            $f_typ = $f_['_name'];


            //Gruppen-, Rollen- und FilterVorlagen-Filter finden
            if (in_array($f_["_name"], array('group', 'role', 'filterpreset', 'rota', 'donation', 'grp data'))) {
                $link = $filter["link"] == "or" ? " OR " : " AND";
            } else {
                $link = "OR";
            }

            $f_sql = "";
            for ($i = 1; $i <= sizeof($f[1]); $i++) {
                $f_sql_part = "";
                //Nur Leeres Argument erlauben, wenn es das einzige in diesem Filter ist
                if (sizeof($f[1]) == 1 || $f[1][$i] != "" || $f_["dbcol"] == "ko_groups_datafields_data.value") {

                    //In jeder Zeile alle Werte VAR[1-3] ersetzen
                    $trans = array(
                        "[VAR1]" => format_userinput($f[1][1], "text"),
                        "[VAR2]" => format_userinput($f[1][2], "text"),
                        "[VAR3]" => format_userinput($f[1][3], "text")
                    );
                    //Add regex escaping
                    if (false !== strpos($f_["sql$i"], 'REGEXP')) {
                        foreach ($trans as $k => $v) {
                            $trans[$k] = str_replace(array('(', ')'), array('\\\\(', '\\\\)'), $v);
                        }
                    }

                    $f_sql_part .= strtr($f_["sql$i"], $trans) . " AND ";

                    //Leere Suchstrings gehen nur mit LIKE und nicht mit REGEXP!
                    if ($f[1][$i] == "") {
                        $f_sql_part = str_replace("REGEXP", "LIKE", $f_sql_part);
                    }
                }

                if (trim($f_sql_part) != "AND") {
                    $f_sql .= $f_sql_part;
                }
            }


            //Alle AND's am Schluss entfernen (mehrere m�glich!)
            while (substr(rtrim($f_sql), -4) == " AND") {
                $f_sql = substr(rtrim($f_sql), 0, -4);
            }

            //Handle group filter if old groups should not be displayed
            if ($f_["_name"] == "group" && !ko_get_userpref($_SESSION['ses_userid'], 'show_passed_groups')) {
                ko_get_groups($all_groups);
                $not_leaves = db_select_distinct("ko_groups", "pid");
                $_gid = substr($f[1][1], 1);
                ko_get_groups($top, "AND `id` = '$_gid'");
                //Get subgroups of current group (if any) and exclude all with expired start and/or stop date
                $z_where = "AND ((`start` != '0000-00-00' AND `start` > NOW()) OR (`stop` != '0000-00-00' AND `stop` < NOW()))";
                rec_groups($top[$_gid], $children, $z_where, $not_leaves);
                foreach ($children as $child) {
                    //Get full id for child
                    $motherline = ko_groups_get_motherline($child["id"], $all_groups);
                    $mids = array();
                    foreach ($motherline as $mg) {
                        $mids[] = "g" . $all_groups[$mg]["id"];
                    }
                    $full_id = (sizeof($mids) > 0 ? implode(":", $mids) . ":" : "") . "g" . $child["id"];
                    //Exclude children with expired start and/or stop date
                    $f_sql .= ' AND `groups` NOT REGEXP ' . "'$full_id' ";
                }
            }
            //Add children filter
            if ($f_['_name'] == 'addchildren') {
                //Clear all other filters if checkbox "only children" has been ticked
                if ($f[1][3] == 'true') {
                    $q = array();
                }
                //Apply all filters except for this one and get all famids for the filtered people
                $cf = $filter;
                unset($cf[$f_i]);
                apply_leute_filter($cf, $cwhere, $add_admin_filter, $admin_filter_level, $login_id, $includeAll);
                $families = db_select_data('ko_leute',
                    'WHERE famid > 0 AND famfunction IN (\'husband\', \'wife\') ' . $cwhere, 'id,famid');
                if (sizeof($families) == 0) {
                    $f_sql = '';
                } else {
                    $famids = array();
                    foreach ($families as $fam) {
                        $famids[] = $fam['famid'];
                    }
                    $f_sql = " `famid` IN ('" . implode("','",
                            array_unique($famids)) . "') AND `famfunction` = 'child' " . ($f_sql ? "AND " . $f_sql : '');
                }
            }
            //Add parents filter
            if ($f_['_name'] == 'addparents') {
                //Clear all other filters if checkbox "only parents" has been ticked
                if ($f[1][1] == 'true') {
                    $q = array();
                }
                //Apply all filters except for this one and get all famids for the filtered people
                $cf = $filter;
                unset($cf[$f_i]);
                apply_leute_filter($cf, $cwhere, $add_admin_filter, $admin_filter_level, $login_id, $includeAll);
                $families = db_select_data('ko_leute', 'WHERE famid > 0 AND famfunction = \'child\' ' . $cwhere,
                    'id,famid');
                if (sizeof($families) == 0) {
                    $f_sql = '';
                } else {
                    $famids = array();
                    foreach ($families as $fam) {
                        $famids[] = $fam['famid'];
                    }
                    $f_sql = " `famid` IN ('" . implode("','",
                            array_unique($famids)) . "') AND `famfunction` IN ('husband', 'wife') ";
                }
            }
            //Find possible duplicates
            if ($f_['_name'] == 'duplicates') {
                $where = 'WHERE 1 ';
                //Get leute_admin_filter
                apply_leute_filter(array(), $dwhere, $add_admin_filter, $admin_filter_level, $login_id, $includeAll);
                //Field to test for
                $fields = explode('-', $f[1][1]);
                foreach ($fields as $field) {
                    $where .= ' AND (`' . $field . '` != \'\' AND `' . $field . '` != \'0000-00-00\') ' . $dwhere;
                }
                $all = db_select_data('ko_leute', $where, '*');

                //Build test string for all persons
                $test = array();
                foreach ($all as $person) {
                    $value = array();
                    foreach ($fields as $field) {
                        $value[] = strtolower($person[$field]);
                    }
                    $test[$person['id']] = implode('#', $value);
                }
                unset($all);
                //Find dups (only one is left in $dups)
                $dups = array_unique(array_diff_assoc($test, array_unique($test)));
                //Add the removed entries which are the doubles to the ones in $dups
                if (sizeof($dups) > 0) {
                    $ids = array_keys($dups);
                    foreach ($test as $tid => $t) {
                        if (in_array($t, $dups)) {
                            $ids[] = $tid;
                        }
                    }
                    $ids = array_unique($ids);
                    $f_sql = " `id` IN ('" . implode("','", $ids) . "') ";
                } else {
                    $f_sql = ' 1=2 ';
                }
            }

            //find special filters for other db tables
            list($db_table, $db_col) = explode(".", $f_["dbcol"]);
            if ($db_table && $db_col) {
                //special group datafields filter
                if ($db_table == "ko_groups_datafields_data") {
                    $rows = db_select_data("ko_groups_datafields_data", "WHERE $f_sql", "person_id");
                    $ids = null;
                    foreach ($rows as $row) {
                        if (!$row["person_id"]) {
                            continue;
                        }
                        $ids[] = "'" . $row["person_id"] . "'";
                    }
                    if (is_array($ids)) {
                        $f_sql = "`id` IN (" . implode(",", $ids) . ")";
                    } else {
                        $f_sql = " 1=2 ";
                    }
                } //special small group filters
                else {
                    if ($db_table == "ko_kleingruppen") {
                        $rows = db_select_data("ko_kleingruppen", "WHERE $f_sql", "id");
                        $ids = null;
                        foreach ($rows as $row) {
                            if (!$row["id"]) {
                                continue;
                            }
                            $ids[] = $row["id"];
                        }
                        if (is_array($ids)) {
                            $kg_sql = array();
                            foreach ($ids as $id) {
                                $kg_sql[] = "`smallgroups` REGEXP '(^|,)$id($|,|:)'";
                            }
                            $f_sql = implode(" OR ", $kg_sql);
                        } else {
                            $f_sql = " 1=2 ";
                        }
                    } //special rota filter
                    else {
                        if ($db_table == 'ko_rota_schedulling') {
                            //If no SQL given so far (should contain SQL for selected eventID), then don't display anything. Only happens if no event was selected
                            if (!$f_sql) {
                                $f_sql = ' 1=2 ';
                            } else {
                                //Add year-week to find scheduling from weekly teams (Dienstwochen)
                                $event = db_select_data('ko_event', "WHERE `id` = '" . $f[1][1] . "'", '*', '', '',
                                    true);
                                $f_sql = "( $f_sql OR `event_id` = '" . date('Y-W',
                                        strtotime($event['startdatum'])) . "') ";
                            }

                            //Check for selected rota team preset
                            if ($f[1][2] != '') {
                                if (substr($f[1][2], 0, 3) == '@G@') {
                                    $value = ko_get_userpref('-1', substr($f[1][2], 3), 'rota_itemset');
                                } else {
                                    $value = ko_get_userpref($_SESSION['ses_userid'], $f[1][2], 'rota_itemset');
                                }
                                //Check for team ID of a single team
                                if (!$value && intval($f[1][2]) > 0) {
                                    $team = db_select_data('ko_rota_teams', "WHERE `id` = '" . intval($f[1][2]) . "'",
                                        '*', '', '', true);
                                    if ($team['id'] > 0 && $team['id'] == intval($f[1][2])) {
                                        $rota_teams = array($team['id']);
                                    }
                                } else {
                                    $rota_teams = explode(',', $value[0]['value']);
                                    $rota_teams = array_unique($rota_teams);
                                }
                                foreach ($rota_teams as $k => $v) {
                                    if (!$v || !intval($v)) {
                                        unset($rota_teams[$k]);
                                    }
                                }
                                if (sizeof($rota_teams) > 0) {
                                    $f_sql .= " AND `team_id` IN (" . implode(',', $rota_teams) . ") ";
                                } else {
                                    //No team selected in this preset so don't show anything
                                    $f_sql .= " AND 1=2 ";
                                }
                            }

                            $rows = db_select_data('ko_rota_schedulling', 'WHERE ' . $f_sql, 'schedule');
                            $ids = array();
                            $gids = array();
                            foreach ($rows as $row) {
                                if (!$row['schedule']) {
                                    continue;
                                }
                                foreach (explode(',', $row['schedule']) as $pid) {
                                    //Group ID
                                    if (strlen($pid) == 7 && substr($pid, 0, 1) == 'g') {
                                        $gids[] = $pid;
                                    } else {
                                        //Person ID
                                        if (!$pid || format_userinput($pid, 'uint') != $pid) {
                                            continue;
                                        }
                                        $ids[] = $pid;
                                    }
                                }
                            }
                            foreach ($ids as $key => $value) {
                                if (!$value) {
                                    unset($ids[$key]);
                                }
                            }
                            $ids = array_unique($ids);
                            foreach ($gids as $key => $value) {
                                if (!$value) {
                                    unset($gids[$key]);
                                }
                            }
                            $gids = array_unique($gids);
                            if (sizeof($ids) > 0 || sizeof($gids) > 0) {
                                $f_sql = '';
                                if (sizeof($ids) > 0) {
                                    $f_sql = '`id` IN (' . implode(',', $ids) . ')';
                                }
                                if (sizeof($gids) > 0) {
                                    foreach ($gids as $gid) {
                                        $f_sql .= $f_sql != '' ? " OR `groups` LIKE '%$gid%' " : " `groups` LIKE '%$gid%' ";
                                    }
                                }
                            } else {
                                $f_sql = ' 1=2 ';
                            }
                        } //Apply another filter preset
                        else {
                            if ($db_table == "ko_filter") {
                                if (substr($f_sql, 0, 3) == '@G@') {
                                    $preset = ko_get_userpref('-1', substr($f_sql, 3), 'filterset');
                                } else {
                                    $preset = ko_get_userpref($login_id, $f_sql, 'filterset');
                                }
                                if ($preset[0]["key"]) {  //preset found
                                    //Get filter and convert it into a WHERE clause
                                    apply_leute_filter(unserialize($preset[0]['value']), $filter_where,
                                        $add_admin_filter, $admin_filter_level, $login_id, $includeAll);
                                    //Get ids of people fitting this condition
                                    $rows = db_select_data("ko_leute", "WHERE 1=1 " . $filter_where, "id");
                                    //Convert it into an id-list for an IN () statement
                                    $ids = null;
                                    foreach ($rows as $row) {
                                        if (!$row["id"]) {
                                            continue;
                                        }
                                        $ids[] = $row["id"];
                                    }
                                    if (sizeof($ids) > 0) {
                                        $f_sql = "`id` IN (" . implode(",", $ids) . ")";
                                    } else {  //No ids found, so add a false condition
                                        $f_sql = " 1=2 ";
                                    }
                                } else {  //no preset found
                                    $f_sql = "";
                                }
                            } //Apply filter for a given year of donations made
                            else {
                                if ($db_table == 'ko_donations') {
                                    $rows = db_select_distinct('ko_donations', 'person', "",
                                        "WHERE `person` != '' AND `promise` = '0' AND " . $f_sql);
                                    $ids = array();
                                    foreach ($rows as $id) {
                                        if (false !== strpos($id,
                                                ',')
                                        ) {  //find entries with multiple persons assigned to one donation
                                            $ids = array_merge($ids, explode(',', format_userinput($id, 'intlist')));
                                        } else {
                                            $ids[] = intval($id);
                                        }
                                    }
                                    $ids = array_unique($ids);
                                    foreach ($ids as $key => $value) {
                                        if (!$value) {
                                            unset($ids[$key]);
                                        }
                                    }
                                    if (sizeof($ids) > 0) {
                                        $f_sql = '`id` IN (' . implode(',', $ids) . ')';
                                    } else {
                                        $f_sql = ' 1=2 ';
                                    }
                                } else {
                                    if ($db_table == 'ko_admin') {
                                        if (false !== strpos($f_sql, '_all')) {
                                            $f_sql = '1';
                                        }
                                        if ($_SESSION['ses_userid'] != ko_get_root_id()) {
                                            $f_sql .= " AND `id` != '" . ko_get_root_id() . "'";
                                        }
                                        $f_sql .= " AND `id` != '" . ko_get_guest_id() . "' ";
                                        $rows = db_select_data($db_table, 'WHERE ' . $f_sql, 'leute_id');
                                        $ids = array();
                                        foreach ($rows as $row) {
                                            if (!$row['leute_id']) {
                                                continue;
                                            }
                                            $ids[] = format_userinput($row['leute_id'], 'uint');
                                        }
                                        foreach ($ids as $key => $value) {
                                            if (!$value) {
                                                unset($ids[$key]);
                                            }
                                        }
                                        $ids = array_unique($ids);
                                        if (sizeof($ids) > 0) {
                                            $f_sql = '`id` IN (' . implode(',', $ids) . ')';
                                        } else {
                                            $f_sql = ' 1=2 ';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }//if(db_table && db_col)


            if (trim($f_sql) != "") {
                if ($f[2]) {  //Negativ
                    $q[$f_typ] .= " ( !($f_sql) ) $link ";
                    $filter_sql[$f_i] = "!($f_sql)";
                } else {
                    $q[$f_typ] .= " ( $f_sql ) $link ";
                    $filter_sql[$f_i] = $f_sql;
                }
            }
        }//foreach(filter)
    }//if(is_array(filter))

    //Einzelne Filter-Gruppen mit AND verbinden und letztes OR l�schen
    $done_adv_link = false;
    if ($filter['use_link_adv'] === true && $filter['link_adv'] != '') {
        //Replace all numbers with {{d}}
        $link_adv = preg_replace('/(\d+)/', '{{$1}}', $filter['link_adv']);

        //Prepare mapping array for all applied filters
        $filter_map = array();
        foreach ($filter_sql as $k => $v) {
            if (!is_numeric($k)) {
                continue;
            }
            $filter_map['{{' . $k . '}}'] = '(' . $v . ')';
        }

        //Replace OR/AND from current language
        $link_adv = str_replace(array(getLL('filter_OR'), getLL('filter_AND')), array('OR', 'AND'),
            strtoupper($link_adv));

        //Remove not allowed characters
        $allowed = array(
            '0',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            '{',
            '}',
            'O',
            'R',
            'A',
            'N',
            'D',
            '(',
            ')',
            ' ',
            '!'
        );
        $new_link_adv = '';
        for ($i = 0; $i < strlen($link_adv); $i++) {
            if (in_array(substr($link_adv, $i, 1), $allowed)) {
                $new_link_adv .= substr($link_adv, $i, 1);
            }
        }
        $link_adv = $new_link_adv;

        $where_code = str_replace(array_keys($filter_map), array_values($filter_map), $link_adv);

        //Check for valid SQL
        $result = mysql_query('SELECT `id` FROM `ko_leute` WHERE ' . $where_code);
        if (false === $result) {
            $where_code = '';
            $_SESSION['filter']['use_link_adv'] = false;
        } else {
            $done_adv_link = true;
        }
    }

    //Apply regular link (OR/AND) if no adv_link is set, or if advanced caused SQL error
    if (!$done_adv_link) {
        $link = $filter["link"] == "or" ? " OR " : " AND ";
        if (sizeof($q) > 0) {
            foreach ($q as $type => $q_) {
                if (trim(substr($q_, 0, -4)) != "") {
                    $q_ = " ( " . substr($q_, 0, -4) . " ) ";
                    //Use the link for all filters except for addchildren, which is always added with OR
                    $where_code .= ($type == 'addchildren' || $type == 'addparents' ? ' OR ' : $link) . $q_;
                }
            }
        }
        $where_code = substr($where_code, 4);  //Erstes OR l�schen
    }

    //Admin-Filter anwenden
    if ($add_admin_filter) {
        //add all filters from applied admingroups first
        $add_rights = array();
        $admingroups = ko_get_admingroups($login_id);
        foreach ($admingroups as $ag) {
            $add_rights[] = "admingroup:" . $ag["id"];
        }
        $add_rights[] = "login";
        foreach ($add_rights as $type) {
            if (substr($type, 0, 10) == "admingroup") {
                list($type, $use_id) = explode(":", $type);
            } else {
                $use_id = $login_id;
            }
            $laf = ko_get_leute_admin_filter($use_id, $type);
            if (sizeof($laf) > 0) {
                if ($admin_filter_level != "") {
                    //apply only given level (if set) for ko_get_admin()
                    if (isset($laf[$admin_filter_level]["filter"])) {
                        apply_leute_filter($laf[$admin_filter_level]["filter"], $where, false, '', $login_id,
                            $includeAll);
                        if (trim(substr($where, 4)) != "") {
                            $admin_code .= "( " . substr($where, 4) . " ) OR ";
                        }
                    }
                } else {
                    //apply all levels for read access
                    for ($i = 1; $i < 4; $i++) {
                        if (!isset($laf[$i]["filter"])) {
                            continue;
                        }
                        apply_leute_filter($laf[$i]["filter"], $where, false, '', $login_id, $includeAll);
                        if (trim(substr($where, 4)) != "") {
                            $admin_code .= "( " . substr($where, 4) . " ) OR ";
                        }
                    }//for(i=1..3)
                }
            }//if(sizeof(laf))
        }
        if ($admin_code != "") {
            $admin_code = substr($admin_code, 0, -3);
        }
    }//if(add_admin_filter)

    if (trim($where_code) != "") {
        if (trim($admin_code) != "") {
            $where_code = " AND ($where_code) AND ($admin_code) ";
        } else {
            $where_code = " AND $where_code ";
        }
    } else {
        if ($admin_code != "") {
            $where_code = " AND $admin_code ";
        }
    }

    //Check if hidden filter is applied. If yes then don't apply "hidden=0" below
    $hiddenfilter = db_select_data('ko_filter', "WHERE `name` = 'hidden'", '*', '', '', true);
    $hidden_is_set = false;
    foreach ($filter as $f) {
        if ($f[0] == $hiddenfilter['id']) {
            $hidden_is_set = true;
        }
    }

    //deleted ausblenden
    if ($includeAll) {
        $deleted = '';
    } else {
        $deleted = ($ko_menu_akt == "leute" && ko_get_userpref($login_id, "leute_show_deleted") == 1)
            ? " AND `deleted` = '1' "
            : " AND `deleted` = '0' ";
        //retrieve deleted if old version is to be displayed.
        //They are eliminated later (in ko_get_leute()), if they have been deleted in the desired version.
        $deleted = $_SESSION["leute_version"] ? "" : $deleted;
    }
    //Add SQL for hidden records
    if (!$hidden_is_set) {
        $deleted .= ko_get_leute_hidden_sql();
    }

    if ($where_code) {
        $where_code = " AND ( " . substr($where_code, 5) . " ) " . $deleted;
        return true;
    } else {
        $where_code = $deleted;
        return false;
    }
}//apply_leute_filter()


function ko_get_leute_hidden_sql()
{
    $sql = "";

    switch (ko_get_setting("leute_hidden_mode")) {
        case 0:  //Show always
            break;
        case 1:  //Hide always
            $sql = " AND hidden = '0' ";
            break;
        case 2:  //Let user decide
            if (ko_get_userpref($_SESSION["ses_userid"], "leute_show_hidden") == 0) {
                $sql = " AND hidden = '0' ";
            }
            break;
    }

    return $sql;
}//get_leute_hidden_sql()


/**
 * Liefert Formular zu einzelnem Filter
 * F�r Ajax und Submenu.inc
 */
function ko_get_leute_filter_form($fid, $showButtons = true)
{
    $code = "";

    ko_get_filter_by_id($fid, $f);

    $code_filter = array();
    for ($i = 1; $i <= $f["numvars"]; $i++) {
        $code_filter[] = getLL("filter_" . $f["var$i"]) ? getLL("filter_" . $f["var$i"]) : $f["var$i"];
        $code_filter[] .= $f["code$i"];
    }

    foreach ($code_filter as $c) {
        $code .= $c . "<br />";
    }


    $code_filter_zusatz = "";
    if ($f["allow_neg"]) {
        $code_filter_zusatz .= '<input type="checkbox" name="filter_negativ" id="filter_neg" /><label for="filter_neg">' . getLL('filter_negativ') . '</label>';
    } else {
        $code_filter_zusatz .= '<input type="hidden" name="filter_negativ" id="filter_neg" value="0" />';
    }

    if ($showButtons) {
        $code_filter_zusatz .= '<p align="center">';
        $code_filter_zusatz .= '<input type="button" value="' . getLL("filter_add") . '" name="submit_filter" onclick="javascript:do_submit_filter(\'leutefilter\', \'' . session_id() . '\');" />';
        $code_filter_zusatz .= '&nbsp;&nbsp;';
        $code_filter_zusatz .= '<input type="button" value="' . getLL("filter_replace") . '" name="submit_filter_new" onclick="javascript:do_submit_filter(\'leutefilternew\', \'' . session_id() . '\');" />';
        $code_filter_zusatz .= '</p>';
    }


    $code .= $code_filter_zusatz;
    return ('<div name="filter_form" class="filter-form">' . $code . '</div>');
}//ko_get_leute_filter_form()


/**
 * Gibt formatierte Personendaten zur�ck
 * data enth�lt den Wert aus der DB
 * col ist die DB-Spalte
 * p ist eine Referenz auf den ganzen Personendatensatz
 * all_datafields ist ein Array aller Datenfelder
 * forceDatafields: Damit werden die Datenfelder miteinbezogen, auch wenn userpref nicht gesetzt ist. (Z.B. von TYPO3-Ext kool_leute)
 * options: array with options
 */
function map_leute_daten($data, $col, &$p, &$all_datafields, $forceDatafields = false, $_options)
{
    global $DATETIME, $KOTA;
    global $all_groups;
    global $access, $ko_path;
    global $LEUTE_EMAIL_FIELDS, $LEUTE_MOBILE_FIELDS;

    if (!is_array($all_groups)) {
        ko_get_groups($all_groups);
    }

    //Datenbank-Spalten-Info holen, falls es keine Modul-Spalte ist (die nicht direkt als Leute-Spalte gespeichert ist)
    if (substr($col, 0, 6) != "MODULE") {
        if (!$data && substr($col, 0, 1) != "_") {
            return "";
        }
        $db_col = db_get_columns("ko_leute", $col);
    }

    //Call KOTA list function if set
    if (substr($KOTA['ko_leute'][$col]['list'], 0, 4) == 'FCN:') {
        $fcn = substr($KOTA['ko_leute'][$col]['list'], 4);
        if (function_exists($fcn) && $fcn != 'kota_map_leute_daten') {
            $kota_data = array('table' => 'ko_leute', 'col' => $col, 'id' => $p['id'], 'dataset' => $p);
            eval("$fcn(\$data, \$kota_data);");
            return $data;
        }
    }


    if ($col == "groups") {  //Used for group filters and the groups-column in the excel export (the HTML view is created in leute.inc)
        $value = null;
        if (substr($data, 0, 1) == "r" || substr($data, 0, 2) == ":r") {  //Rolle
            ko_get_grouproles($role, "AND `id` = '" . substr($data, (strpos($data, "r") + 1)) . "'");
            return $role[substr($data, (strpos($data, "r") + 1))]["name"];
        } else {  //Gruppe(n)
            if (!isset($access['groups'])) {
                ko_get_access('groups');
            }
            foreach (explode(',', $data) as $g) {
                $gid = ko_groups_decode($g, 'group_id');
                if ($g
                    && ($access['groups']['ALL'] > 0 || $access['groups'][$gid] > 0)
                    && (ko_get_userpref($_SESSION['ses_userid'],
                            'show_passed_groups') == 1 || ($all_groups[$gid]['start'] <= date('Y-m-d') && ($all_groups[$gid]['stop'] == '0000-00-00' || $all_groups[$gid]['stop'] > date('Y-m-d'))))
                ) {
                    $value[] = ko_groups_decode($g, 'group_desc_full');
                }
            }
            sort($value);
            return implode(", \n", $value);
        }
    } else {
        if ($col == "datafield_id") {  //Angewandte Gruppen-Datenfelder-Filter sch�n darstellen
            if (strlen($data) == 6 && format_userinput($data, "uint") == $data) {
                $df = db_select_data("ko_groups_datafields", "WHERE `id` = '$data'", "*", "", "", true);
                return $df["description"];
            } else {
                return $data;
            }
        } else {
            if ($db_col[0]["Type"] == "date") {  //Datums-Typen von SQL umformatieren
                if ($data == "0000-00-00") {
                    return "";
                }
                return strftime($DATETIME["dmY"], strtotime($data));
            } else {
                if (substr($db_col[0]["Type"], 0, 4) == "enum") {  //Find ll values for enum
                    $ll_value = getLL('kota_ko_leute_' . $col . '_' . $data);
                    return ($ll_value ? $ll_value : $data);
                } else {
                    if ($col == 'smallgroups') {  //Smallgroups
                        return ko_kgliste($data);
                    } else {
                        if ($col == "famid") {
                            $fam = ko_get_familie($data);
                            return $fam["id"] . " " . getLL('kota_ko_leute_famfunction_short_' . $p['famfunction']);
                        } else {
                            if (substr($db_col[0]["Type"], 0, 7) == "tinyint") {  //Treat tinyint as checkbox
                                return ($data ? getLL("yes") : getLL("no"));
                            } else {
                                if (substr($col, 0, 1) == "_") {  //Children export columns
                                    if (!$p["famid"] || $p["famfunction"] != "child") {
                                        return "";
                                    }
                                    switch ($col) {
                                        case "_father":
                                        case "_mother":
                                            $func = $col == "_father" ? "husband" : "wife";
                                            $d = db_select_data("ko_leute",
                                                "WHERE `famid` = '" . $p["famid"] . "' AND `famfunction` = '$func' AND `deleted` = '0'",
                                                "*", "", "", true);
                                            return $d["vorname"] . " " . $d["nachname"];
                                            break;  //father
                                        default:
                                            if (in_array(substr($col, 0, 8), array("_father_", "_mother_"))) {
                                                $p_col = substr($col, 8);
                                                $fam_function = substr($col, 0,
                                                    7) == "_father" ? "'husband'" : "'wife'";
                                            } else {
                                                $p_col = substr($col, 1);
                                                $fam_function = "'husband', 'wife'";
                                            }
                                            $d = db_select_data("ko_leute",
                                                "WHERE `famid` = '" . $p["famid"] . "' AND `famfunction` IN ($fam_function) AND `$p_col` != '' AND `deleted` = '0'",
                                                "*", "ORDER BY famfunction ASC");
                                            foreach ($d as $e) {
                                                if (sizeof($LEUTE_EMAIL_FIELDS) > 1 && in_array($p_col,
                                                        $LEUTE_EMAIL_FIELDS)
                                                ) {
                                                    ko_get_leute_email($e, $email);
                                                    if ($email[0]) {
                                                        return $email[0];
                                                    }
                                                } else {
                                                    if (sizeof($LEUTE_MOBILE_FIELDS) > 1 && in_array($p_col,
                                                            $LEUTE_MOBILE_FIELDS)
                                                    ) {
                                                        ko_get_leute_mobile($e, $mobile);
                                                        if ($mobile[0]) {
                                                            return $mobile[0];
                                                        }
                                                    } else {
                                                        if ($e[$p_col]) {
                                                            return $e[$p_col];
                                                        }
                                                    }
                                                }
                                            }
                                            return "";
                                    }
                                } else {
                                    if (substr($col, 0, 6) == "MODULE") {

                                        //Gruppen-Modul: Rolle in entsprechender Gruppe anzeigen
                                        if (substr($col, 6, 3) == 'grp') {
                                            //Only group given, datafields have :
                                            if (false === strpos($col, ':')) {
                                                $gid = substr($col, 9);
                                                $value = array();
                                                $data = $p["groups"];
                                                foreach (explode(",", $data) as $group) {
                                                    //Don't display groups with start or stop date if settings show_passed_groups is not set.
                                                    $_gid = ko_groups_decode($group, "group_id");
                                                    $stop = false;
                                                    if (!ko_get_userpref($_SESSION['ses_userid'],
                                                        'show_passed_groups')
                                                    ) {
                                                        $motherline = array_merge(array($_gid),
                                                            ko_groups_get_motherline($_gid, $all_groups));
                                                        foreach ($motherline as $mg) {
                                                            if ($all_groups[$mg]["stop"] != "0000-00-00" && time() > strtotime($all_groups[$mg]["stop"])) {
                                                                $stop = true;
                                                            }
                                                            if ($all_groups[$mg]["start"] != "0000-00-00" && time() < strtotime($all_groups[$mg]["start"])) {
                                                                $stop = true;
                                                            }
                                                        }
                                                    }
                                                    if ($stop) {
                                                        continue;
                                                    }
                                                    if ($gid == $_gid) {  //Assigned to this group, and not one of the subgroups
                                                        $v = ko_groups_decode($group, "role_desc");
                                                        $value[] = $v ? ko_html($v) : 'x';
                                                    } else {
                                                        if (in_array($gid, ko_groups_decode($group,
                                                            "mother_line"))) {  //Check for assignement to a subgroup
                                                            $value[] = '<a href="#" onclick="' . "sendReq('../leute/inc/ajax.php', 'action,id,state,sesid', 'itemlist,MODULEgrp" . $_gid . ",switch," . session_id() . "', do_element);return false;" . '">&rsaquo;&thinsp;' . ko_html(ko_groups_decode($group,
                                                                    "group_desc")) . "</a>";
                                                        }
                                                    }
                                                }
                                                return implode(",<br />\n", $value);
                                            } else {
                                                //output datafields of this group
                                                list($_col, $dfid) = explode(':', $col);
                                                $gid = substr($_col, 9);
                                                $value = array();
                                                if ($all_groups[$gid]['datafields']) {
                                                    $group_dfs = explode(',', $all_groups[$gid]['datafields']);
                                                    //check for valid datafield
                                                    if (!isset($all_datafields[$dfid]) || !in_array($dfid,
                                                            $group_dfs)
                                                    ) {
                                                        return '';
                                                    }

                                                    //Get datafield value (versioning handled in function)
                                                    $value = ko_get_datafield_data($gid, $dfid, $p['id'],
                                                        $_SESSION['leute_version'], $all_datafields, $all_groups);
                                                    if ($value['typ'] == 'checkbox') {
                                                        return $value['value'] == '1' ? ko_html(getLL('yes')) : ko_html(getLL('no'));
                                                    } else {
                                                        return ko_html($value['value']);
                                                    }
                                                }
                                            }
                                        } else {
                                            if (substr($col, 6, 2) == 'kg') {
                                                $sg_col = substr($col, 8);
                                                $value = array();
                                                foreach (explode(',', $p['smallgroups']) as $sgid) {
                                                    $id = substr($sgid, 0, 4);
                                                    if (!$id) {
                                                        continue;
                                                    }
                                                    $sg = ko_get_smallgroup_by_id($id);

                                                    if (isset($sg[$sg_col]) && $sg[$sg_col] != '') {
                                                        $data = array($sg_col => $sg[$sg_col]);
                                                        kota_process_data('ko_kleingruppen', $data, 'list', $log);
                                                        $value[] = strip_tags($data[$sg_col]);
                                                    }
                                                    //Store empty value if option firstOnly is set.
                                                    // Otherwise the numbering is not the same for all fields which can lead to a mix of values from different small groups
                                                    else {
                                                        if ($_options['MODULEkg_firstOnly']) {
                                                            $value[] = '';
                                                        }
                                                    }

                                                    /*
				if($sg_col == 'picture') {
					$value[] = ko_pic_get_tooltip(str_replace('my_images/', '', $sg[$sg_col]), 25, 200, 'm', 'l');
				} else {
					if(isset($sg[$sg_col]) && $sg[$sg_col] != '') $value[] = $sg[$sg_col];
				}
				*/
                                                }
                                                if ($_options['MODULEkg_firstOnly']) {
                                                    return $value[0];
                                                } else {
                                                    $value = array_unique($value);
                                                    return implode(', ', $value);
                                                }
                                            } else {
                                                if (substr($col, 6, 8) == 'tracking') {  //Tracking column
                                                    $tfid = substr($col, 14);
                                                    if (!$tfid) {
                                                        return '';
                                                    }

                                                    $delimiterPosition = strpos($tfid, 'f');
                                                    if ($delimiterPosition == false) {
                                                        $tid = (int)$tfid;
                                                    } else {
                                                        $tid = substr($tfid, 0, $delimiterPosition);
                                                        $fid = substr($tfid, $delimiterPosition + 1);
                                                    }

                                                    if (!$tid) {
                                                        return '';
                                                    }

                                                    $db_filter = '';
                                                    if ($fid) {
                                                        $filter = db_select_data('ko_userprefs',
                                                            'WHERE `id` = ' . format_userinput($fid, 'uint'), '`value`',
                                                            '', '', true, true);
                                                        list($start, $stop) = explode(',', $filter['value']);
                                                        $db_filter = ' AND `date` >= \'' . $start . '\' AND `date` <= \'' . $stop . '\'';
                                                    }

                                                    $tracking = db_select_data('ko_tracking', "WHERE `id` = '$tid'",
                                                        '*', '', '', true);
                                                    if (!$tracking['id'] || $tracking['id'] != $tid) {
                                                        return '';
                                                    }
                                                    $value = '';
                                                    switch ($tracking['mode']) {
                                                        case 'type':
                                                        case 'typecheck':
                                                            $values = array();
                                                            $entries = db_select_data('ko_tracking_entries',
                                                                "WHERE `tid` = '$tid' AND `lid` = '" . $p['id'] . "'" . $db_filter);
                                                            foreach ($entries as $e) {
                                                                $values[$e['type']] += (float)$e['value'];
                                                            }
                                                            $v = array();
                                                            foreach (explode("\n", $tracking['types']) as $type) {
                                                                $type = trim($type);
                                                                if (!$values[$type]) {
                                                                    continue;
                                                                }
                                                                $v[] = $values[$type] . 'x' . $type;
                                                            }
                                                            $value = implode(', ', $v);
                                                            break;

                                                        case 'simple':
                                                            $value = db_get_count('ko_tracking_entries', 'value',
                                                                "AND `tid` = '$tid' AND `lid` = '" . $p['id'] . "'" . $db_filter);
                                                            break;

                                                        case 'value':
                                                            $sum = db_select_data('ko_tracking_entries',
                                                                "WHERE `tid` = '$tid' AND `lid` = '" . $p['id'] . "'" . $db_filter,
                                                                'id, SUM(`value`) as sum', '', '', true);
                                                            $value = $sum['sum'];
                                                            break;

                                                        case 'valueNonNum':
                                                            $values = array();
                                                            $entries = db_select_data('ko_tracking_entries',
                                                                "WHERE `tid` = '$tid' AND `lid` = '" . $p['id'] . "'" . $db_filter);
                                                            foreach ($entries as $e) {
                                                                $values[$e['value']] += 1;
                                                            }
                                                            ksort($values);
                                                            $v = array();
                                                            foreach ($values as $type => $value) {
                                                                if (!$type || !$value) {
                                                                    continue;
                                                                }
                                                                $v[] = $value . 'x' . $type;
                                                            }
                                                            $value = implode(', ', $v);
                                                            break;
                                                    }
                                                    //Add link to edit tracking for this person
                                                    $url = $ko_path . 'tracking/index.php?action=enter_tracking&id=' . $tracking['id'] . '#tp' . $p['id'];
                                                    $value = '<a href="' . $url . '" title="' . getLL('leute_show_tracking_for_person') . '">' . $value . '</a>';
                                                    return $value;
                                                } else {
                                                    if (substr($col, 6, 6) == 'plugin') {  //Column added by a plugin
                                                        $fcn = 'my_leute_column_map_' . substr($col, 12);
                                                        if (function_exists($fcn)) {
                                                            eval("\$value = $fcn(\$data, \$col, \$p);");
                                                            return $value;
                                                        }
                                                    } else {
                                                        if (substr($col, 6, 5) == 'famid') {  //Family columns
                                                            if ($p['famid'] > 0) {
                                                                if ($col == 'MODULEfamid_famlastname') {
                                                                    $family = ko_get_familie(intval($p['famid']));
                                                                    $value = '';
                                                                    if ($family['famanrede'] != '') {
                                                                        $value .= $family['famanrede'];
                                                                    }
                                                                    if ($family['famfirstname'] != '') {
                                                                        $value .= ($value != '' ? ', ' : '') . $family['famfirstname'];
                                                                    }
                                                                    if ($family['famlastname'] != '') {
                                                                        $value .= ($value != '' ? ', ' : '') . $family['famlastname'];
                                                                    }
                                                                    return $value;
                                                                } else {
                                                                    if ($p['famfunction'] == 'child') {
                                                                        $famfunction = substr($col, 12);
                                                                        if (!in_array($famfunction,
                                                                            array('husband', 'wife'))
                                                                        ) {
                                                                            return '';
                                                                        }

                                                                        $rel = db_select_data('ko_leute',
                                                                            "WHERE `famid` = '" . intval($p['famid']) . "' AND `famfunction` = '$famfunction' AND `deleted` = '0' AND `hidden` = '0'",
                                                                            '*', '', '', true);
                                                                        if ($rel['id']) {
                                                                            $kotadata = array(
                                                                                'col' => 'id',
                                                                                'dataset' => array('id' => $rel['id'])
                                                                            );
                                                                            kota_listview_people($value, $kotadata,
                                                                                true);
                                                                            return $value;
                                                                        } else {
                                                                            return '';
                                                                        }
                                                                    } else {
                                                                        return '';
                                                                    }
                                                                }
                                                            } else {
                                                                return '';
                                                            }
                                                        } //Donations
                                                        else {
                                                            if (substr($col, 6, 9) == 'donations') {
                                                                $year = format_userinput(substr($col, 15, 4), 'uint');
                                                                $account_id = format_userinput(substr($col, 19),
                                                                    'uint');
                                                                if (!$year || $year < 1900 || $year > 3000) {
                                                                    return '';
                                                                }

                                                                $datefield = ko_get_userpref($_SESSION['ses_userid'],
                                                                    'donations_date_field');
                                                                if (!$datefield) {
                                                                    $datefield = 'date';
                                                                }
                                                                $where = " WHERE `person` = '" . $p['id'] . "' AND YEAR(`$datefield`) = '$year' AND `promise` = '0' ";
                                                                if ($account_id > 0) {
                                                                    $where .= " AND `account` = '$account_id'";
                                                                }

                                                                $amount = db_select_data('ko_donations', $where,
                                                                    'SUM(`amount`) AS total_amount', '', '', true);
                                                                if ($amount['total_amount'] > 0) {
                                                                    return number_format($amount['total_amount'], 2,
                                                                        '.', "'");
                                                                } else {
                                                                    return '';
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if ($col == "`event_id`") {  //Used for rota special filter
                                            if ($_options['num'] == 1) {  //first variable is eventID
                                                $event = db_select_data('ko_event', "WHERE `id` = '$data'", '*', '', '',
                                                    true);
                                                $group = db_select_data('ko_eventgruppen',
                                                    "WHERE `id` = '" . $event['eventgruppen_id'] . "'", '*', '', '',
                                                    true);
                                                return strftime($DATETIME['dmy'],
                                                        strtotime($event['startdatum'])) . ': ' . $group['name'];
                                            } else {
                                                if ($_options['num'] == 2) {  //Second variable is team preset or teamID
                                                    if (intval($data) != 0 && intval($data) == $data) {  //TeamID if Integer
                                                        $team = db_select_data('ko_rota_teams',
                                                            "WHERE `id` = '" . intval($data) . "'", '*', '', '', true);
                                                        return $team['name'];
                                                    } else {  //Rota teams preset otherwise
                                                        return $data;
                                                    }
                                                }
                                            }
                                        } else {
                                            if ($col == '`account`') {  //Used for donation special filter
                                                $account = db_select_data('ko_donations_accounts',
                                                    'WHERE `id` = \'' . $data . '\'', '*', '', '', true);
                                                return ($account['number'] ? $account['number'] : $account['name']);
                                            } else {
                                                if ($col == 'plz') {  //For special filter 'plz IN (...)' with long list of zip codes (pfimi bern)
                                                    return strlen($data) > 20 ? substr($data, 0, 20) . '..' : $data;
                                                } else {
                                                    if ($db_col[0]['Type'] == 'tinytext') {  //Picture
                                                        return ko_pic_get_tooltip($data, 25, 200, 'm', 'l', true);
                                                    } else {
                                                        if ($col == '`admingroups`') {  //Used for special filter 'logins'
                                                            if ($data == '_all') {
                                                                return getLL('all');
                                                            } else {
                                                                $admingroup = db_select_data('ko_admingroups',
                                                                    "WHERE `id` = '" . (int)$data . "'", '*', '', '',
                                                                    true);
                                                                return $admingroup['name'];
                                                            }
                                                        } else {
                                                            if ($col == 'wochentag') {  //Find ll values for ko_kleingruppen.wochentag
                                                                $ll_value = getLL('kota_ko_kleingruppen_' . $col . '_' . $data);
                                                                return ($ll_value ? $ll_value : $data);
                                                            } else {  //Den Rest wie gehabt ausgeben
                                                                $ll_value = getLL('kota_ko_leute_' . $col . '_' . $data);
                                                                return ($ll_value ? $ll_value : $data);
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
    }

}//map_leute_daten()


/**
 * Get email address for a given person
 * Uses preferred email address as defined in ko_leute_preferred_fields.
 * If no preferred use the first one according to $LEUTE_EMAIL_FIELDS
 */
function ko_get_leute_email($p, &$email)
{
    global $LEUTE_EMAIL_FIELDS;

    $email = array();

    if (!is_array($p)) {
        ko_get_person_by_id($p, $person);
        $p = $person;
    }

    //Get preferred email field from ko_leute_preferred_fields
    $email_fields = db_select_data('ko_leute_preferred_fields', "WHERE `type` = 'email' AND `lid` = '" . $p['id'] . "'",
        '*');

    //First try to use email fields as defined in ko_leute_preferred_fields
    foreach ($email_fields as $row) {
        if (check_email($p[$row['field']])) {
            $email[] = $p[$row['field']];
        }
    }
    //If none have been found use first email field in order given in LEUTE_EMAIL_FIELDS
    if (sizeof($email) == 0) {
        foreach ($LEUTE_EMAIL_FIELDS as $field) {
            if (check_email($p[$field])) {
                $email[] = $p[$field];
                break;
            }
        }
    }

    //Return status: TRUE if at least one address has been found
    return sizeof($email) > 0;
}//ko_get_leute_email()


/**
 * Get mobile number for a given person
 * Uses preferred mobile number as defined in ko_leute_preferred_fields.
 * If no preferred use the first one according to $LEUTE_MOBILE_FIELDS
 */
function ko_get_leute_mobile($p, &$mobile)
{
    global $LEUTE_MOBILE_FIELDS;

    $mobile = array();

    if (!is_array($p)) {
        ko_get_person_by_id($p, $person);
        $p = $person;
    }

    //Get preferred mobile field from ko_leute_preferred_fields
    $mobile_fields = db_select_data('ko_leute_preferred_fields',
        "WHERE `type` = 'mobile' AND `lid` = '" . $p['id'] . "'", '*');

    //First try to use mobile fields as defined in ko_leute_preferred_fields
    foreach ($mobile_fields as $row) {
        if ($p[$row['field']]) {
            $mobile[] = $p[$row['field']];
        }
    }
    //If none have been found use first mobile field in order given in LEUTE_MOBILE_FIELDS
    if (sizeof($mobile) == 0) {
        foreach ($LEUTE_MOBILE_FIELDS as $field) {
            if ($p[$field]) {
                $mobile[] = $p[$field];
                break;
            }
        }
    }

    //Return status: TRUE if at least one number has been found
    return sizeof($mobile) > 0;
}//ko_get_leute_mobile()


/**
 * Get values from db table ko_leute_preferred_fields as an array
 * First index is the person's id, second index is the type (email, mobile)
 */
function ko_get_preferred_fields($type = '')
{
    $preferred_fields = array();

    if ($type) {
        $where = "WHERE `type` = '$type'";
    } else {
        $where = 'WHERE 1';
    }

    $rows = db_select_data('ko_leute_preferred_fields', $where, '*');
    foreach ($rows as $row) {
        $preferred_fields[$row['lid']][$row['type']][] = $row['field'];
    }
    return $preferred_fields;
}//ko_get_preferred_fields()


/**
 * Store an old state of an edited person record in database for versioning
 * @params $id ID of person's dataset
 * @params $data array holding the current data for this record, which will be stored serialized
 * " @params $df array holding all datafield data for this user. Will be fetched from db if empty
 * @params $uid int ID of kOOL login to assign this change to, usually ses_userid
 */
function ko_save_leute_changes($id, $data = '', $df = '', $uid = '')
{
    if (!$id) {
        return false;
    }

    $uid = intval($uid) > 0 ? intval($uid) : $_SESSION['ses_userid'];

    //Don't allow two changes by the same user within one second
    // (might happen when editing a person belonging to a family. Then the family members get updated as well)
    $same = db_get_count("ko_leute_changes", "id",
        "AND `leute_id` = '$id' AND `user_id` = '$uid' AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`date`) <= 1)");
    if ($same > 0) {
        return false;
    }

    //Get person from db if not given
    if (!is_array($data)) {
        ko_get_person_by_id($id, $data);
        if ($data["id"] != $id) {
            return false;
        }
    }

    //Get datafields from db if not given
    if (!is_array($df)) {
        $df = ko_get_datafields($id);
        if (!is_array($df)) {
            $df = array();
        }
    }

    $store = array(
        "date" => date("Y-m-d H:i:s"),
        'user_id' => $uid,
        "leute_id" => $id,
        "changes" => serialize($data),
        "df" => serialize($df),
    );
    db_insert_data("ko_leute_changes", $store);
}//ko_save_leute_changes()


/**
 * Get an old version for a given person
 * @param date version, Set date, for which to return the address data (YYYY-MM-DD). The first version after this date will be returned
 * @param int id, ID of the person for which to return the version
 * @return array, Array of address data for the given timestamp
 */
function ko_leute_get_version($version, $id)
{
    if (!$id || !$version) {
        return;
    }

    $old = db_select_data("ko_leute_changes", "WHERE `leute_id` = '$id' AND `date` > '$version 23:59:59'", "*",
        "ORDER BY `date` ASC", "LIMIT 0, 1", true);

    $row = unserialize($old["changes"]);
    return $row;
}//ko_leute_get_version()


/**
 * Get the value for a given group datafield
 * @param int gid, Group id
 * @param int fid, Datafield id
 * @param int pid, Person id
 * @param date version, Optional date for which to get the group datafield value
 * @param array all_datafields, Array with all group datafields given by reference
 * @param array all_groups, Array with all groups given by reference
 */
function ko_get_datafield_data($gid, $fid, $pid, $version = "", &$all_datafields, &$all_groups)
{
    //Return old version
    if ($version != "") {
        $value = db_select_data("ko_groups_datafields_data AS dfd LEFT JOIN ko_groups_datafields AS df ON dfd.datafield_id = df.id",
            "WHERE dfd.datafield_id = '$fid' AND dfd.person_id = '$pid' AND dfd.group_id = '$gid'",
            "dfd.value AS value, df.type as typ, dfd.id as dfd_id", "", "", true);

        //Get old version
        $old = db_select_data("ko_leute_changes", "WHERE `leute_id` = '$pid' AND `date` > '$version 23:59:59'", "*",
            "ORDER BY `date` ASC", "LIMIT 0, 1", true);
        //If an old version has been found display the old value
        //Otherwise display the current value because there is no older version available
        if (isset($old["df"])) {
            $df_old = unserialize($old["df"]);

            //Set back to old value if set
            if (isset($df_old[$value["dfd_id"]])) {
                $value["value"] = $df_old[$value["dfd_id"]]["value"];
            } else {
                $value["value"] = "";
            }
        }
    } //Just get current version
    else {
        $value = db_select_data("ko_groups_datafields_data LEFT JOIN ko_groups_datafields ON ko_groups_datafields_data.datafield_id = ko_groups_datafields.id",
            "WHERE ko_groups_datafields_data.datafield_id = '$fid' AND ko_groups_datafields_data.person_id = '$pid' AND ko_groups_datafields_data.group_id = '$gid'",
            "ko_groups_datafields.id AS id, ko_groups_datafields_data.value AS value, ko_groups_datafields.type as typ, ko_groups_datafields.description as description",
            "", "", true);
    }

    return $value;
}//ko_get_datafield_data()


/**
 * Get all group datafields for the given person
 * @param int pid, Person's id to get all datafields for
 * @return array df, Array with all datafields for this person
 */
function ko_get_datafields($pid)
{
    $df = db_select_data("ko_groups_datafields_data", "WHERE `person_id` = '$pid' AND `deleted` = '0'", "*",
        "ORDER BY `group_id` ASC");
    return (!$df ? array() : $df);
}//ko_get_datafields()


function ko_get_fast_filter()
{
    global $FAST_FILTER_IDS;

    $fast_filter = explode(',', ko_get_userpref($_SESSION['ses_userid'], 'leute_fast_filter'));
    foreach ($fast_filter as $k => $v) {
        if (!$v) {
            unset($fast_filter[$k]);
        }
    }

    if (sizeof($fast_filter) == 0) {
        $fast_filter = $FAST_FILTER_IDS;
    }

    return $fast_filter;
}//ko_get_leute_fast_filter()


