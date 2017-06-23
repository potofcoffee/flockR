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
 * USER UND EINSTELLUNGEN                                                                                               *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Get the people id of the logged in id
 *
 * @return int id in ko_leute of the person assigned to the logged in user
 */
function ko_get_logged_in_id($id = "")
{
    $lid = $id ? $id : $_SESSION["ses_userid"];
    if (!$lid) {
        return false;
    }

    $row = db_select_data('ko_admin', "WHERE `id` = '$lid'", 'id,leute_id', '', '', true);
    if (is_array($row)) {
        return $row["leute_id"];
    } else {
        return "";
    }
}


/**
 * Returns the person assigned to the currently logged in user
 * If an admin email is set for this login, this will be returned as the email field for this person (if admin=TRUE)
 */
function ko_get_logged_in_person($id = '')
{
    global $LEUTE_EMAIL_FIELDS, $LEUTE_MOBILE_FIELDS;

    $lid = $id ? $id : $_SESSION['ses_userid'];
    if (!$lid) {
        return false;
    }

    $person = db_select_data("ko_admin AS a LEFT JOIN ko_leute as l ON a.leute_id = l.id",
        "WHERE a.id = '$lid' AND (a.disabled = '0' OR a.disabled = '')",
        "l.*, a.email AS admin_email",
        '', '', true);
    //Set email from one of the email fields
    if (sizeof($LEUTE_EMAIL_FIELDS) > 1) {
        ko_get_leute_email($person, $email);
        $person['email'] = array_shift($email);
    }
    //Set mobile from one of the mobile fields
    if (sizeof($LEUTE_MOBILE_FIELDS) > 1) {
        ko_get_leute_mobile($person, $mobile);
        $person['natel'] = array_shift($mobile);
    }
    //Overwrite person's email address with admin email from login
    if ($person['admin_email']) {
        $person['email'] = $person['admin_email'];
    }

    return $person;
}


/**
 * Get date and time of the last login for a given login
 *
 * @param int user id. $_SESSION["ses_userid"] is being used not given
 * @return datetime SQL datetime value of last login
 */
function ko_get_last_login($uid = "")
{
    $uid = $uid ? $uid : $_SESSION["ses_userid"];
    if (!$uid) {
        return false;
    }

    $row = db_select_data('ko_admin', "WHERE `id` = '" . $_SESSION['ses_userid'] . "'", 'id,last_login', '', '', true);
    if (is_array($row)) {
        return $row["last_login"];
    } else {
        return "";
    }
}//ko_get_last_login()


/**
 * Get the id of the special login ko_guest
 *
 * @return int user id of ko_guest
 */
function ko_get_guest_id()
{
    if ($GLOBALS["kOOL"]["guest_id"]) {
        return $GLOBALS["kOOL"]["guest_id"];
    }

    $row = db_select_data('ko_admin', "WHERE `login` = 'ko_guest'", 'id', '', '', true);
    if (is_array($row)) {
        $GLOBALS["kOOL"]["guest_id"] = $row["id"];
        return $row["id"];
    } else {
        return false;
    }
}


/**
 * Get the id of the special login root
 *
 * @return int user id of root
 */
function ko_get_root_id()
{
    if ($GLOBALS["kOOL"]["root_id"]) {
        return $GLOBALS["kOOL"]["root_id"];
    }

    $row = db_select_data('ko_admin', "WHERE `login` = 'root'", 'id', '', '', true);
    if (is_array($row)) {
        $GLOBALS["kOOL"]["root_id"] = $row["id"];
        return $row["id"];
    } else {
        return false;
    }
}


/*
 * Get a setting from ko_settings
 *
 * @param string Key to get setting for
 * @param boolean Set to true to force rereading setting from db
 * @return mixed Value for the specified key
 */
function ko_get_setting($key, $force = false)
{
    global $LEUTE_NO_FAMILY;
    //Get from cache

    if (!$force && isset($GLOBALS['kOOL']['ko_settings'][$key])) {
        $result = $GLOBALS['kOOL']['ko_settings'][$key];
    } else {
        $query = "SELECT `value` from `ko_settings` WHERE `key` = '$key' LIMIT 1";
        $result = mysql_query($query);
        $row = mysql_fetch_row($result);
        $result = $row[0];
    }


    if ($key == 'leute_col_name' && $LEUTE_NO_FAMILY) {
        $temp = unserialize($result);
        foreach (array('en', 'de', 'it', 'fr', 'nl') as $lan) {
            unset($temp[$lan]['famid']);
            unset($temp[$lan]['kinder']);
            unset($temp[$lan]['famfunction']);
        }
        $result = serialize($temp);
    }

    $GLOBALS["kOOL"]["ko_settings"][$key] = $result;
    return $result;
}//ko_get_setting()


/*
 * Stores a setting in ko_settings
 *
 * @param string Key of the setting to be stored
 * @param mixed Value to be stored
 * @return boolean True on succes, false on failure
 */
function ko_set_setting($key, $value)
{
    if (db_get_count('ko_settings', 'key', "AND `key` = '$key'") == 0) {
        db_insert_data('ko_settings', array('key' => $key, 'value' => format_userinput($value, 'text')));
    } else {
        db_update_data('ko_settings', "WHERE `key` = '$key'", array('value' => format_userinput($value, 'text')));
    }
    $GLOBALS['kOOL']['ko_settings'][$key] = $value;

    return true;
}//ko_set_setting()


/**
 * Get a user preference as stored in ko_userprefs
 *
 * @param int user id
 * @param string Key of user preference
 * @param string Type of user preference to get
 * @param string ORDER BY statement to pass to the db
 * @param boolean Set to true to have the userpref read from DB instead of from cache
 * @return mixed Value of user preference
 */
function ko_get_userpref($id, $key = "", $type = "", $order = "", $force = false)
{
    if ($type != "") {
        if ($key != "") {
            //Look up userpref in GLOBALS
            if (!$force && $id == $_SESSION["ses_userid"] && isset($GLOBALS["kOOL"]["ko_userprefs"]["TYPE@" . $type][$key])) {
                return array($GLOBALS["kOOL"]["ko_userprefs"]["TYPE@" . $type][$key]);
            }
            //Get it from DB if not set
            $query = "SELECT * FROM `ko_userprefs` WHERE `user_id` = '$id' AND `key` = '$key' AND `type` = '$type' $order";
        } else {
            //Look up userpref in GLOBALS
            if (!$force && $id == $_SESSION["ses_userid"] && is_array($GLOBALS["kOOL"]["ko_userprefs"]["TYPE@" . $type])) {
                return $GLOBALS["kOOL"]["ko_userprefs"]["TYPE@" . $type];
            }
            //Get it from DB if not set
            $query = "SELECT * FROM `ko_userprefs` WHERE `user_id` = '$id' AND `type` = '$type' $order";
        }
        $result = mysql_query($query);
        while ($row = mysql_fetch_assoc($result)) {
            $r[] = $row;
        }
        return $r;
    } else {
        //Look up userpref in GLOBALS
        if (!$force && $id == $_SESSION["ses_userid"] && isset($GLOBALS["kOOL"]["ko_userprefs"][$key])) {
            return $GLOBALS["kOOL"]["ko_userprefs"][$key];
        }
        //Get it from DB if not set
        $query = "SELECT * FROM `ko_userprefs` WHERE `user_id` = '$id' AND `key` = '$key' $order";
        if (defined(DEBUG_THIS_STATEMENT)) die ($query);
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
        return $row['value'];
    }
}//ko_get_userpref()


/**
 * Store a user preference in ko_userprefs
 *
 * @param int user id
 * @param string Key of user preference
 * @param mixed Value to be stored
 * @param string Type of user preference to store
 */
function ko_save_userpref($id, $key, $value, $type = "")
{
    $id = format_userinput($id, "int");
    $key = format_userinput($key, "text");
    $type = format_userinput($type, "alphanum+");

    //Store in db
    if (db_get_count('ko_userprefs', 'key', "AND `user_id`= '$id' AND `key` = '$key' AND `type` = '$type'") >= 1) {
        db_update_data('ko_userprefs', "WHERE `user_id` = '$id' AND `key` = '$key' AND `type` = '$type'",
            array('value' => $value));
    } else {  //...sonst neues einf�gen
        db_insert_data('ko_userprefs', array('user_id' => $id, 'type' => $type, 'key' => $key, 'value' => $value));
    }

    //Save in GLOBALS as well (but only for logged in user)
    if ($id == $_SESSION["ses_userid"]) {
        if ($type != "") {
            $GLOBALS["kOOL"]["ko_userprefs"]["TYPE@" . $type][$key] = array(
                "type" => $type,
                "key" => $key,
                "value" => $value
            );
        } else {
            $GLOBALS["kOOL"]["ko_userprefs"][$key] = $value;
        }
    }
}//ko_save_userpref()


/**
 * Delete a user preference
 *
 * @param int user id
 * @param string Key to be deleted
 * @param string Type of preference to be deleted
 */
function ko_delete_userpref($id, $key, $type = "")
{
    $id = format_userinput($id, "int");
    $key = format_userinput($key, "text");
    $type = format_userinput($type, "alphanum+");

    //Delete from DB
    db_delete_data('ko_userprefs', "WHERE `user_id` = '$id' AND `key` = '$key' AND `type` = '$type'");

    //Delete from cache
    if ($type != '') {
        unset($GLOBALS['kOOL']['ko_userprefs']['TYPE@' . $type][$key]);
    } else {
        unset($GLOBALS['kOOL']['ko_userprefs'][$key]);
    }
}//ko_delete_userpref()


/**
 * Checks whether a given user preference is set in ko_userprefs
 *
 * @param int user id
 * @param string Key to be checked for
 * @param string Type of preference to be checked for
 */
function ko_check_userpref($id, $key, $type = "")
{
    $id = format_userinput($id, "int");
    $key = format_userinput($key, "text");
    $type = format_userinput($type, "alphanum+");

    if ($type != "") {
        $query = "SELECT `key`, `value` FROM `ko_userprefs` WHERE `user_id` = '$id' AND `key` = '$key' AND `type` = '$type'";
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
    } else {
        $query = "SELECT `value` FROM `ko_userprefs` WHERE `user_id` = '$id' AND `key` = '$key'";
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
    }
    return (sizeof($row) >= 1);
}//ko_check_userpref()

