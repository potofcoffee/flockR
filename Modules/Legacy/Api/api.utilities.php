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
 * Util-FUNKTIONEN                                                                                                      *
 *                                                                                                                      *
 ************************************************************************************************************************/


/**
 * Get help entry from db (ko_help) for the given module and type
 *
 * @param $module string: Module this help is for
 * @param $type string: Type of help to display
 * @param $ttparams array: Parameters for tooltip (if text help): Assoziative array. Possible keys: w for width, pv for vertical position (t, m, b), ph for horizontal position (l, c, r)
 * @return array: show = TRUE, link: HTML code to include which shows the help icon with link or tooltip
 */
function ko_get_help($module, $type, $ttparams = array())
{
    global $ko_path;

    //Map kOOL languages to TYPO3-Language uids
    $map_lang = array("en" => 0, "de" => 1, "nl" => 2);

    if ($type == '') {
        $type = '_notype';
    }
    $help = false;

    //Get help entry from cache
    if (isset($GLOBALS['kOOL']['ko_help'][$_SESSION['lang']][$type])) {
        $help = $GLOBALS['kOOL']['ko_help'][$_SESSION['lang']][$type];
    } else {
        $help = $GLOBALS['kOOL']['ko_help']['en'][$type];
    }
    if (!$help["id"]) {
        return false;
    }


    //Help text given in DB - display as tooltip
    if ($help["text"]) {
        $text = str_replace("\r", "", str_replace("\n", "", nl2br(ko_html($help["text"]))));
        $link = '<span onmouseover="tooltip.show(\'' . $text . '\', \'' . $ttparams['w'] . '\', \'' . $ttparams['pv'] . '\', \'' . $ttparams['ph'] . '\');" onmouseout="tooltip.hide();"><img src="' . $ko_path . 'images/icon_help.png" border="0" alt="help" /></span>';
    } //Create link to online documentation
    else {
        if (!$help["t3_page"]) {
            return false;
        }
        $href = 'http://www.churchtool.org/?id=' . $help["t3_page"];
        $href .= '&L=' . $map_lang[$help["language"]];
        if ($help["t3_content"]) {
            $href .= "#c" . $help["t3_content"];
        }

        $link = '<a href="' . $href . '" target="_blank"><img src="' . $ko_path . 'images/icon_help.png" border="0" alt="help" title="' . getLL("help_link_title") . '" /></a>';
    }

    return array("show" => true, "link" => $link);
}//ko_get_help()


function ko_leute_sort(&$data, $sort_col, $sort_order, $dont_apply_limit = false, $forceDatafields = false)
{
    global $all_groups, $FAMFUNCTION_SORT_ORDER, $access;

    //Check for columns which don't need second sorting as they can be sorted by MySQL directly (see ko_get_leute)
    if (!is_array($sort_col)) {
        $sort_col = array($sort_col);
    }
    if (!is_array($sort_order)) {
        $sort_order = array($sort_order);
    }
    if (!ko_manual_sorting($sort_col)) {
        return $data;
    }

    //get all datafields (used in map_leute_daten)
    $all_datafields = db_select_data("ko_groups_datafields", "WHERE 1=1", "*");

    //build sort-array
    foreach ($data as $i => $v) {
        foreach ($sort_col as $col) {
            if (!$col) {
                continue;
            }

            $col_value = null;
            $map_col = $col;  //Used for map_leute_daten()

            //Sort by birthday instead of age (only used from tx_koolleute_pi1)
            if ($col == "MODULEgeburtsdatum") {
                $map_col = "geburtsdatum";
            }

            if (!$col_value) {
                $col_value = map_leute_daten($v[$map_col], $map_col, $v, $all_datafields, $forceDatafields);
            }

            switch ($col) {
                case "MODULEgeburtsdatum":  //Order by month and day
                    if ($v[$map_col] == '0000-00-00') {
                        $col_value = 0;
                    }  //Would map to 01011970 which would be wrong
                    else {
                        $col_value = strftime('%m%d%Y', strtotime($v[$map_col]));
                    }
                    break;
                case 'geburtsdatum':  //Order by year (age) (Needed, as mapped value $col_value has already been transformed with sql_datum in map_leute_daten()
                    $col_value = strftime('%Y%m%d', strtotime($v[$map_col]));
                    break;
                case 'famid':
                    //Use the full family name without the fam function for sorting, so families with same names in the same city still don't get mixed
                    $col_value = substr($col_value, 0, strpos($col_value, ')'));
                    break;
                case 'famfunction':
                    if (isset($FAMFUNCTION_SORT_ORDER[$v[$col]])) {
                        $col_value = $FAMFUNCTION_SORT_ORDER[$v[$col]];
                    } else {
                        $col_value = 9;
                    }  //Add entires with no famfunction at the end
                    break;
            }

            //Build sort arrays for array_multisort()
            ${"sort_" . str_replace(':', '_', $col)}[$i] = strtolower($col_value);
        }
    }
    foreach ($sort_col as $i => $col) {
        $sort[] = '$sort_' . str_replace(':', '_', $col) . ', SORT_' . strtoupper($sort_order[$i]);
    }
    eval('array_multisort(' . implode(", ", $sort) . ', $data);');

    if (!$dont_apply_limit) {
        $data = array_slice($data, ($_SESSION["show_start"] - 1), $_SESSION["show_limit"]);
    }

    //Correct array index (numeric indizes get rearranged by array_multisort())
    foreach ($data as $key => $value) {
        $r[$value["id"]] = $value;
    }
    return $r;
}//ko_leute_sort()


function ko_get_map_links($data)
{
    $code = "";
    $hooks = hook_get_by_type("leute");
    if (sizeof($hooks) > 0) {
        foreach ($hooks as $hook) {
            if (function_exists("my_map_" . $hook)) {
                $code .= call_user_func("my_map_" . $hook, $data);
            }
        }
    }

    return $code;
}//ko_get_map_links()


function ko_check_fm_for_user($fm_id, $uid)
{
    global $FRONTMODULES;

    $allow = false;
    $fm = $FRONTMODULES[$fm_id];

    ko_get_user_modules($uid, $user_modules);

    if (!$fm["modul"]) {  //no module needed, to display this FM
        $allow = true;
    } else {  //One of the given modules must be installed for this FM
        foreach (explode(",", $fm["modul"]) as $m) {
            if (in_array($m, $user_modules)) {
                $allow = true;
            }
        }
    }
    return $allow;
}//ko_check_fm_for_user()

/**
 * Check, whether LDAP ist active for this kOOL
 */
function ko_do_ldap()
{
    global $ldap_enabled, $ldap_dn;

    $do_ldap = ($ldap_enabled && trim($ldap_dn) != "");
    return $do_ldap;
}//ko_do_ldap()


/**
 * Connect and bind to the LDAP-Server
 */
function ko_ldap_connect()
{
    global $ldap_server, $ldap_admin, $ldap_login_dn, $ldap_admin_pw;

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    $ldap = ldap_connect($ldap_server);
    if ($ldap) {
        //Bind (Login)
        $r = ldap_bind($ldap, ('cn=' . $ldap_admin . ',' . $ldap_login_dn), $ldap_admin_pw);

        //Error handling for ldap operations
        if ($r === false) {
            ko_log('ldap_error',
                'LDAP bind (' . ldap_errno($ldap) . '): ' . ldap_error($ldap) . ': cn=' . $ldap_admin . ',' . $ldap_login_dn);
            return false;
        } else {
            return $ldap;
        }
    }
    return false;
}//ko_ldap_connect()


/**
 * Disconnect from the LDAP-Server
 */
function ko_ldap_close(&$ldap)
{
    ldap_close($ldap);
}//ko_ldap_close()


/**
 * LDAP: Add Person Entry
 */
function ko_ldap_add_person(&$ldap, $person, $uid, $edit = false)
{
    global $ldap_dn, $LDAP_ATTRIB, $LDAP_SCHEMA;

    if (!$ldap) {
        return false;
    }
    if (!$uid) {
        return false;
    }

    //Add id
    $person['id'] = $uid;

    //Map all person values to LDAP attributes
    $ldap_entry = array();
    foreach ($LDAP_ATTRIB as $pkey => $lkey) {
        //Handle array parameters, where one kOOL field is matched to several LDAP fields
        if (!is_array($lkey)) {
            $lkey = array($lkey);
        }
        foreach ($lkey as $lkey2) {
            if ($ldap_entry[$lkey2] != '' || is_array($ldap_entry[$lkey2])) {
                if ($person[$pkey] != '') {  //If several kOOL columns end up in one ldap field, then store them as array
                    if (is_array($ldap_entry[$lkey2])) {
                        $ldap_entry[$lkey2][] = $person[$pkey];  //Add new entry
                    } else {
                        $ldap_entry[$lkey2] = array(
                            $ldap_entry[$lkey2],
                            $person[$pkey]
                        );  //Convert current value in new array
                    }
                }
            } else {
                $ldap_entry[$lkey2] = $person[$pkey];
            }
        }
    }
    //Use preferred email and mobile
    ko_get_leute_email($person, $email);
    $ldap_entry['mail'] = $email[0];
    ko_get_leute_mobile($person, $mobile);
    $ldap_entry['mobile'] = $mobile[0];

    //Add cn and uid
    $ldap_entry['cn'] = $ldap_entry['givenName'] . ' ' . $ldap_entry['sn'];
    $ldap_entry['uid'] = $uid;

    //Data vorbehandeln, damit es mit z.B. Umlauten keine Probleme gibt.
    foreach ($ldap_entry as $i => $d) {
        if (!$i) {
            unset($ldap_entry[$i]);
        }  //Unset entries with no key
        else {
            if (is_array($d)) {  //Multiple values for one key are stored as array
                foreach ($d as $dk => $dv) {
                    $ldap_entry[$i][$dk] = utf8_encode($dv);
                }
            } else {
                if (!$edit && trim($d) == '') {
                    unset($ldap_entry[$i]);
                }  //Unset empty values if a new LDAP entry is to be made
                else {
                    if ($edit && trim($d) == '') {
                        $ldap_entry[$i] = array();
                    }  //Set empty values to array(), so they get deleted in LDAP (when editing)
                    else {
                        $ldap_entry[$i] = utf8_encode($d);
                    }
                }
            }
        }  //Encode normal values with UTF-8
    }
    //ObjectClass inetOrgPerson requires sn
    if (!isset($ldap_entry['sn'])) {
        $ldap_entry['sn'] = ' ';
    }

    $ldap_entry['objectclass'] = $LDAP_SCHEMA;

    if ($edit) {
        $r = ldap_modify($ldap, ('uid=' . $uid . ',' . $ldap_dn), $ldap_entry);

        //Try to add new entry on error
        if ($r === false) {
            foreach ($ldap_entry as $i => $d) {
                if ((!is_array($d) && trim($d) == '') || (is_array($d) && sizeof($d) == 0)) {
                    unset($ldap_entry[$i]);
                }
            }
            $r = ldap_add($ldap, ('uid=' . $uid . ',' . $ldap_dn), $ldap_entry);
        }
    } else {
        $r = ldap_add($ldap, ('uid=' . $uid . ',' . $ldap_dn), $ldap_entry);
    }

    //Error handling for ldap operations
    if ($r === false) {
        ko_log('ldap_error',
            'LDAP add_person (' . ldap_errno($ldap) . '): ' . ldap_error($ldap) . ': ' . print_r($ldap_entry, true));
    }

    return $r;
}//ko_ldap_add_person()


/**
 * LDAP: Delete Person Entry
 */
function ko_ldap_del_person(&$ldap, $id)
{
    global $ldap_dn;

    if ($ldap) {
        $r = ldap_delete($ldap, ("uid=" . $id . "," . $ldap_dn));

        //Error handling for ldap operations
        if ($r === false) {
            ko_log('ldap_error', 'LDAP del_person (' . ldap_errno($ldap) . '): ' . ldap_error($ldap) . ': id ' . $id);
        }

        return $r;
    }
    return false;
}//ko_ldap_del_person()


/**
 * LDAP: Check for a person
 */
function ko_ldap_check_person(&$ldap, $id)
{
    global $ldap_dn;

    if ($ldap) {
        $result = ldap_search($ldap, $ldap_dn, "uid=" . $id);
        $num = ldap_count_entries($ldap, $result);

        if ($num >= 1) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}//ko_ldap_check_person()


/**
 * LDAP: Check for a login
 */
function ko_ldap_check_login(&$ldap, $cn)
{
    global $ldap_login_dn;

    if ($ldap) {
        $result = ldap_search($ldap, $ldap_login_dn, 'cn=' . $cn);
        $num = ldap_count_entries($ldap, $result);

        if ($num >= 1) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}//ko_ldap_check_login()


/**
 * LDAP: Add Login Entry
 */
function ko_ldap_add_login(&$ldap, $data)
{
    global $ldap_login_dn, $LDAP_SCHEMA;

    if ($ldap) {
        //Data vorbehandeln, damit es mit z.B. Umlauten keine Probleme gibt.
        foreach ($data as $i => $d) {
            if ($d == "") {
                $data[$i] = array();
            } else {
                if ($i == "userPassword") {
                    $data[$i] = '{md5}' . base64_encode(pack('H*', $d));
                } else {
                    $data[$i] = utf8_encode($d);
                }
            }
        }
        $data['objectclass'] = $LDAP_SCHEMA;

        $r = ldap_add($ldap, ('cn=' . $data["cn"] . ',' . $ldap_login_dn), $data);

        //Error handling for ldap operations
        if ($r === false) {
            ko_log('ldap_error',
                'LDAP add_login (' . ldap_errno($ldap) . '): ' . ldap_error($ldap) . ': ' . print_r($data, true));
        }

        return $r;
    }
    return false;
}//ko_ldap_add_login()


/**
 * LDAP: Delete Login Entry
 */
function ko_ldap_del_login(&$ldap, $cn)
{
    global $ldap_login_dn;

    if ($ldap) {
        $r = ldap_delete($ldap, ('cn=' . $cn . ',' . $ldap_login_dn));

        //Error handling for ldap operations
        if ($r === false) {
            ko_log('ldap_error', 'LDAP del_login (' . ldap_errno($ldap) . '): ' . ldap_error($ldap) . ': cn ' . $cn);
        }

        return $r;
    }
    return false;
}//ko_ldap_del_login()


/**
 * Speichert SMS-Balance von ClickaTell-Account in DB (Caching)
 */
function set_cache_sms_balance($balance)
{
    db_update_data('ko_settings', "WHERE `key` = 'cache_sms_balance'", array('value' => $balance));;
}


/**
 * Holt den gecachten SMS-Balance-Wert
 */
function get_cache_sms_balance()
{
    $query = "SELECT `value` FROM `ko_settings` WHERE `key` = 'cache_sms_balance'";
    $result = mysql_query($query);
    $value = mysql_fetch_assoc($result);
    return $value["value"];
}


/**
 * Send SMS message using aspsms.net
 */
function send_aspsms($recipients, $text, $from, &$num, &$credits)
{
    global $SMS_PARAMETER, $BASE_PATH;

    require_once($BASE_PATH . 'inc/aspsms.php');

    //Sender ID
    $originator = 'kOOL';  //Default value
    $sender_ids = explode(',', ko_get_setting('sms_sender_ids'));
    if (sizeof($sender_ids) > 0) {  //Check for sender_ids
        if (in_array($from, $sender_ids)) {
            $originator = $from;
        }
    }

    $sent = array();

    $sms = new SMS($SMS_PARAMETER['user'], $SMS_PARAMETER['pass']);
    $sms->setOriginator($originator);
    foreach ($recipients as $r) {
        if (!check_natel($r)) {
            continue;
        }
        $sms->addRecipient($r);
        $sent[] = $r;
    }
    $sms->setContent($text);
    $error = $sms->sendSMS();

    if ($error != 1) {
        $error_message = $sms->getErrorDescription();
        $my_error_txt = $error . ': ' . $error_message;
        koNotifier::Instance()->addTextError($my_error_txt);
        return false;
    }
    $num = sizeof($sent);
    $credits = $sms->getCreditsUsed();
    $log_message = format_userinput(strtr($text, array("\n" => ' ', "\r" => '')), 'text') . ' - ' . implode(', ',
            $sent) . ' - ' . $num . '/' . $num . ' - 0 - ' . $credits;
    ko_log('sms_sent', $log_message);

    set_cache_sms_balance($sms->showCredits());

    return true;
}//send_aspsms()


/**
 * Sendet SMS-Mitteilung
 */
function send_sms(
    $recipients,
    $text,
    $from,
    $climsgid,
    $msg_type,
    &$success,
    &$done,
    &$problems,
    &$charges,
    &$error_message
)
{
    global $SMS_PARAMETER, $BASE_PATH;

    require($BASE_PATH . "inc/Clickatell.php");
    set_time_limit(0);

    //Text
    $sms_message["text"] = $text;

    //Sender ID
    $sms_message["from"] = "kOOL";  //Default value
    $sender_ids = explode(',', ko_get_setting('sms_sender_ids'));
    if (sizeof($sender_ids) > 0) {  //Check for sender_id
        if (in_array($from, $sender_ids)) {
            $sms_message['from'] = $from;
        }
    }

    //Client-Message-ID
    $sms_message["climsgid"] = $climsgid;

    //Message-Type
    $sms_message["msg_type"] = $msg_type;


    $done = $success = $charges = 0;
    $problems = "";
    $sms = new SMS_Clickatell;
    $sms->init($SMS_PARAMETER);
    $log_message = "";


    if ($sms->auth($error_message)) {
        foreach ($recipients as $e) {
            if (check_natel($e)) {
                $sms_message["to"] = $e;
                $status = $sms->sendmsg($sms_message);

                //Get Api-Msg-ID and store it
                $temp = explode(" ", $status[1]);
                $apimsgid = $temp[0];

                //Get Status and Charge of sent SMS
                $charge = $sms->getmsgcharge($apimsgid);     //Array ( [0] => 1.5 [1] => 003 )
                //002: Queued, 003: Sent, 004: Received, 008: OK
                if (in_array($charge[1], array("002", "003", "004", "008"))) {
                    $success++;
                } else {
                    $problems .= $e . ", ";
                }
                $charges += $charge[0];
                $done++;
                $log_message .= $e . ", ";
            }//if(check_natel(e))
        }//foreach(empfaenger as e)

        //Neue Balance speichern
        set_cache_sms_balance($sms->getbalance());

        $log_message = format_userinput(strtr($sms_message['text'], array("\n" => ' ', "\r" => '')),
                'text') . ' - ' . substr($log_message, 0, -2) . " - $success/$done - " . substr($problems, 0,
                -2) . " - $charges";
        ko_log("sms_sent", $log_message);

        return true;
    }//if(sms->auth())
    else {
        return false;
    }
}//send_sms()


/**
 * ezmlm mailinglist management
 */
function ko_ezmlm_subscribe($list, $moderator, $email)
{
    if ($list == "" || $moderator == "" || !check_email($email)) {
        return false;
    }
    ko_send_mail($moderator, str_replace("@", "-subscribe-" . str_replace("@", "=", $email) . "@", $list), ' ', ' ');
    //ko_send_email(str_replace("@", "-subscribe-".str_replace("@", "=", $email)."@", $list), " ", " ", array("From" => $moderator));
}//ko_ezmlm_subscribe()

function ko_ezmlm_unsubscribe($list, $moderator, $email)
{
    if ($list == "" || $moderator == "" || !check_email($email)) {
        return false;
    }
    ko_send_mail($moderator, str_replace("@", "-unsubscribe-" . str_replace("@", "=", $email) . "@", $list), ' ', ' ');
    //ko_send_email(str_replace("@", "-unsubscribe-".str_replace("@", "=", $email)."@", $list), " ", " ", array("From" => $moderator));
}//ko_ezmlm_unsubscribe()


/**
 * Find contrast color for given background color.
 * Based on YIQ color space
 * Found on http://24ways.org/2010/calculating-color-contrast/
 */
function ko_get_contrast_color($hexcolor, $dark = '#000000', $light = '#FFFFFF')
{
    $r = hexdec(substr($hexcolor, 0, 2));
    $g = hexdec(substr($hexcolor, 2, 2));
    $b = hexdec(substr($hexcolor, 4, 2));
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? $dark : $light;

    //$sum3 = hexdec(substr($hexcolor, 0, 2)) + 1.6*hexdec(substr($hexcolor, 2, 2)) + hexdec(substr($hexcolor, 4, 2));
    //return ($sum3 > 3*127 || $hexcolor == '') ? $dark : $light;

    //$sum3 = hexdec(substr($hexcolor, 0, 2)) + hexdec(substr($hexcolor, 2, 2)) + hexdec(substr($hexcolor, 4, 2));
    //return ($sum3 > 3*0x000088 || $hexcolor == "") ? $dark : $light;
}


function ko_scheduler_set_next_call($task)
{
    global $ko_path;

    if (!is_array($task)) {
        $task = db_select_data('ko_scheduler_tasks', "WHERE `id` = '" . intval($task) . "'", '*', '', '', true);
    }
    if (!$task['crontime']) {
        return false;
    }

    if ($task['status'] == 0) {
        db_update_data('ko_scheduler_tasks', "WHERE `id` = '" . $task['id'] . "'",
            array('next_call' => '0000-00-00 00:00:00'));
    } else {
        require_once($ko_path . 'inc/cron.php');
        try {
            $cron = Cron\CronExpression::factory($task['crontime']);
            $next_call = $cron->getNextRunDate()->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            //Disable task
            db_update_data('ko_scheduler_tasks', "WHERE `id` = '" . $task['id'] . "'",
                array('next_call' => '0000-00-00 00:00:00', 'status' => '0'));
            //Return error
            return 8;
        }

        if ($next_call && $next_call != '0000-00-00 00:00:00') {
            db_update_data('ko_scheduler_tasks', "WHERE `id` = '" . $task['id'] . "'",
                array('next_call' => $next_call));
        }
    }

}//ko_scheduler_set_next_call()


/**
 * Scheduler task: Delete old files in download folder
 */
function ko_task_delete_old_downloads()
{
    global $ko_path;

    $deadline = 60 * 60 * 24;
    clearstatcache();

    $dirs = array(
        $ko_path . 'download/pdf/',
        $ko_path . 'download/dp/',
        $ko_path . 'download/excel/',
        $ko_path . 'download/word/',
        $ko_path . 'download/'
    );


    //Delete old files
    foreach ($dirs as $dir) {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if (!is_file($dir . $file)) {
                continue;
            }  //Only check files and ignore dirs and links
            if (substr($file, 0, 1) == '.') {
                continue;
            }  //Ignore hidden files and ./..
            if ($file == 'index.php') {
                continue;
            }  //Ignore index.php files

            $stat = stat($dir . $file);
            if ((time() - $stat['mtime']) > $deadline) {
                unlink($dir . $file);
            }
        }
        closedir($dh);
    }
}//ko_task_delete_old_downloads()


/**
 * Scheduler task: Import/update events for event groups with iCal import URL
 */
function ko_task_import_events_ical()
{
    global $ko_path, $BASE_PATH;

    require_once($ko_path . 'daten/inc/daten.inc');

    //Get event groups to be imported
    $egs = db_select_data('ko_eventgruppen', "WHERE `type` = '3' AND `ical_url` != ''");
    if (sizeof($egs) == 0) {
        return;
    }

    foreach ($egs as $eg) {
        //Apply update interval
        $update = $eg['update'] ? $eg['update'] : 60;
        if ((strtotime($eg['last_update']) + 60 * $update) > time()) {
            continue;
        }

        db_update_data('ko_eventgruppen', "WHERE `id` = '" . $eg['id'] . "'",
            array('last_update' => date('Y-m-d H:i:s')));
        ko_daten_import_ical($eg);
    }

    //Find and remove multiple entries
    $doubles = db_select_data('ko_event', "WHERE `import_id` != ''", "*, COUNT(id) AS num",
        'GROUP BY import_id ORDER BY num DESC');
    $double_import_ids = array();
    foreach ($doubles as $double) {
        if ($double['num'] < 2) {
            continue;
        }
        $double_import_ids[] = $double['import_id'];
    }
    unset($doubles);
    foreach ($double_import_ids as $ii) {
        if (!$ii) {
            continue;
        }
        $lowest = db_select_data('ko_event', "WHERE `import_id` = '$ii'", 'id', 'ORDER BY `id` ASC', 'LIMIT 0,1', true);
        db_delete_data('ko_event', "WHERE `import_id` = '$ii' AND `id` != '" . $lowest['id'] . "'");
    }

}//ko_task_import_events_ical()


/**
 * Scheduler task: Send reminder emails
 */
function ko_task_reminder($reminderId = null)
{

    $eventPatterns = array();
    $eventReplacements = array();

    if ($reminderId === null) {
        $reminders = db_select_data('ko_reminder', 'where `status` = 1');
    } else {
        $reminders = db_select_data('ko_reminder', 'where `id` = ' . $reminderId);
    }
    foreach ($reminders as $reminder) {

        $filter = $reminder['filter'];
        $type = substr($filter, 0, 4);
        $value = substr($filter, 4);
        if (trim($value) == '' || trim($type) == '') {
            continue;
        }
        switch ($type) {
            case 'LEPR':
                // TODO: implement leute functionality
                break;
            case 'EVGR': // event group id
                $zWhere = ' AND `ko_event`.`eventgruppen_id` = ' . $value;
                break;
            case 'EVID': // event id
                $zWhere = ' AND `ko_event`.`id` = ' . $value;
                break;
            case 'CALE': // calendar id
                $egs = db_select_data('ko_eventgruppen', 'where `calendar_id` = ' . $value, 'id');
                $egsString = implode(',', array_keys($egs));
                $zWhere = (trim($egsString) == '' ? '' : ' AND `ko_event`.`eventgruppen_id` in (' . $egsString . ')');
                break;
            case 'EGPR': // event group preset
                if (substr($value, 0, 4) == '[G] ') {
                    $egIdsString = ko_get_userpref('-1', substr($value, 4), 'daten_itemset');
                } else {
                    $egIdsString = ko_get_userpref($_SESSION['ses_userid'], $value, 'daten_itemset');
                }

                if ($egIdsString === null) {
                    $events = null; // TODO: maybe add warning that preset was not found
                } else {
                    $egIdsString = $egIdsString[0]['value'];
                    $zWhere = (trim($egIdsString) == '') ? '' : ' AND `ko_event`.`eventgruppen_id` in (' . $egIdsString . ')';
                }

                break;
            default:
                continue;
        }


        if ($reminder['type'] == 1) {


            // Set db query filter that selects those events which correspond to the deadline (and all 'overdue' events by 23 hours)
            $deadline = $reminder['deadline'];
            if ($reminderId !== null) {
                $limit = ' limit 1';
                if ($deadline >= 0) {
                    $order = ' order by enddatum asc, endzeit asc';
                    $timeFilterEvents = " AND TIMESTAMPDIFF(HOUR,CONCAT(CONCAT(`ko_event`.`enddatum`, ' '), `ko_event`.`endzeit`), NOW()) <= " . ($deadline + 24);
                } else {
                    $order = ' order by startdatum asc, startzeit asc';
                    $timeFilterEvents = " AND TIMESTAMPDIFF(HOUR,CONCAT(CONCAT(`ko_event`.`startdatum`, ' '), `ko_event`.`startzeit`),NOW()) <= " . ($deadline + 24);
                }
            } else {
                $limit = '';
                $order = '';
                if ($deadline >= 0) {
                    $timeFilterEvents = " AND TIMESTAMPDIFF(HOUR,CONCAT(CONCAT(`ko_event`.`enddatum`, ' '), `ko_event`.`endzeit`), NOW()) >= " . $deadline . " AND TIMESTAMPDIFF(HOUR,CONCAT(CONCAT(`ko_event`.`enddatum`, ' '), `ko_event`.`endzeit`), NOW()) <= " . ($deadline + 23);
                } else {
                    $timeFilterEvents = " AND TIMESTAMPDIFF(HOUR,CONCAT(CONCAT(`ko_event`.`startdatum`, ' '), `ko_event`.`startzeit`),NOW()) >= " . $deadline . " AND TIMESTAMPDIFF(HOUR,CONCAT(CONCAT(`ko_event`.`startdatum`, ' '), `ko_event`.`startzeit`),NOW()) <= " . ($deadline + 23);
                }
            }

            $events = db_select_data('ko_event', 'where 1=1 ' . $zWhere . $timeFilterEvents, "*", $order, $limit);


            // No reminders to send for this reminder entry
            if ($events === null) {
                if ($reminderId !== null) {
                    koNotifier::Instance()->addError(11);
                }
                continue;
            }

            $recipientsByKool = array();
            $recipientsByAddress = array();
            if ($reminderId === null) {
                // Kick events for which the reminder has already been sent
                $eventIds = array_keys($events);
                $zWhere = ' AND `reminder_id` = ' . $reminder['id'];
                $zWhere .= (sizeof($eventIds) == 0 ? '' : ' AND `event_id` in (' . implode(',', $eventIds) . ')');
                $alreadyHandledEvents = db_select_data('ko_reminder_mapping', 'where 1=1 ' . $zWhere, 'event_id');
                foreach ($alreadyHandledEvents as $k => $alreadyHandledEvent) {
                    unset($events[$k]);
                }

                // Process recipients
                $recipientsFromDBMails = explode(',', $reminder['recipients_mails']);
                $recipientsFromDBGroups = explode(',', $reminder['recipients_groups']);
                $recipientsFromDBLeute = explode(',', $reminder['recipients_leute']);

                foreach ($recipientsFromDBMails as $recipientFromDBMail) {
                    if (!check_email($recipientFromDBMail)) {
                        continue;
                    }
                    $recipientsByAddress[] = $recipientFromDBMail;
                }
                foreach ($recipientsFromDBGroups as $recipientFromDBGroup) {
                    if (!$recipientFromDBGroup || strlen($recipientFromDBGroup) != 7) {
                        continue;
                    }

                    $res = db_select_data('ko_leute', "where `groups` like '%" . $recipientFromDBGroup . "%'");
                    foreach ($res as $person) {
                        $recipientsByKool[$person['id']] = $person;
                    }
                }
                foreach ($recipientsFromDBLeute as $recipientsFromDBPerson) {
                    if (!intval($recipientsFromDBPerson)) {
                        continue;
                    }

                    $person = null;
                    ko_get_person_by_id($recipientsFromDBPerson, $person);
                    $recipientsByKool[$person['id']] = $person;
                }
            } else {
                $user = ko_get_logged_in_person();
                $user['id'] = ko_get_logged_in_id();
                $recipientsByKool[$user['id']] = $user;
            }


            $text = '<body>' . $reminder['text'] . '</body>';
            $subject = $reminder['subject'];

            // Get placeholders for recipients
            foreach ($recipientsByKool as $recipientByKool) {
                if (!isset($personPatterns[$recipientByKool['id']])) {
                    $personPlaceholders = ko_placeholders_leute_array($recipientByKool);
                    $personPatterns[$recipientByKool['id']] = array();
                    $personReplacements[$recipientByKool['id']] = array();
                    foreach ($personPlaceholders as $k => $personPlaceholder) {
                        $personPatterns[$recipientByKool['id']][] = '/' . $k . '/';
                        $personReplacements[$recipientByKool['id']][] = $personPlaceholder;
                    }
                }
            }

            // Get placeholders for events
            foreach ($events as $event) {
                if (!isset($eventPatterns[$event['id']])) {
                    $eventPlaceholders = ko_placeholders_event_array($event);
                    $eventPatterns[$event['id']] = array();
                    $eventReplacements[$event['id']] = array();
                    foreach ($eventPlaceholders as $k => $eventPlaceholder) {
                        $eventPatterns[$event['id']][] = '/' . $k . '/';
                        $eventReplacements[$event['id']][] = $eventPlaceholder;
                    }
                }
            }


            // Send mails
            $done = array();
            $failed = array();
            $textWithoutPlaceholders = preg_replace('/###.*###/', '', $text);
            $subjectWithoutPlaceholders = preg_replace('/###.*###/', '', $subject);
            foreach ($events as $event) {
                foreach ($recipientsByKool as $recipientByKool) {
                    $replacedSubject = preg_replace($eventPatterns[$event['id']], $eventReplacements[$event['id']],
                        $subject);
                    $replacedSubject = preg_replace($personPatterns[$recipientByKool['id']],
                        $personReplacements[$recipientByKool['id']], $replacedSubject);

                    $replacedText = preg_replace($eventPatterns[$event['id']], $eventReplacements[$event['id']], $text);
                    $replacedText = preg_replace($personPatterns[$recipientByKool['id']],
                        $personReplacements[$recipientByKool['id']], $replacedText);

                    if ($reminder['action'] == 'email') {
                        $mailAddresses = null;
                        ko_get_leute_email($recipientByKool, $mailAddresses);
                        foreach ($mailAddresses as $mailAddress) {
                            $lip = ko_get_logged_in_person();
                            $result = ko_send_html_mail($lip['email'], $mailAddress, $replacedSubject, $replacedText);
                            if ($result) {
                                $done[$mailAddress] = $recipientByKool['nachname'] . ' ' . $recipientByKool['vorname'] . ' (' . $recipientByKool['id'] . '):' . $mailAddress;
                            } else {
                                $failed[$mailAddress] = $recipientByKool['nachname'] . ' ' . $recipientByKool['vorname'] . ' (' . $recipientByKool['id'] . '):' . $mailAddress;
                            }
                        }
                    } else {
                        if ($reminder['action'] == 'sms') {
                            // TODO: add implementation
                        }
                    }
                }
                foreach ($recipientsByAddress as $recipientByAddress) {
                    if ($reminder['action'] == 'email') {
                        if (array_key_exists($recipientByAddress, $done)) {
                            continue;
                        }
                        $lip = ko_get_logged_in_person();
                        $result = ko_send_html_mail($lip['email'], $recipientByAddress, $subjectWithoutPlaceholders,
                            $textWithoutPlaceholders);
                        if ($result) {
                            $done[$recipientByAddress] = $recipientByAddress;
                        } else {
                            $failed[$recipientByAddress] = $recipientByAddress;
                        }
                    } else {
                        if ($reminder['action'] == 'sms') {
                            // TODO: add implementation
                        }
                    }
                }

                if ($reminderId === null) {
                    // Insert entry into ko_reminder_mapping, so the reminder won't be sent again
                    db_insert_data('ko_reminder_mapping', array(
                        'reminder_id' => $reminder['id'],
                        'event_id' => $event['id'],
                        'crdate' => date('Y-m-d H:i:s')
                    ));
                }
            }

            // Log
            ko_log('send_reminders',
                'sent the following reminders' . ($reminderId === null ? '' : ' (testmail)') . ' :: reminder: ' . $reminder['id'] . '; events: ' . implode(',',
                    array_keys($events)) . '; people success: ' . implode(',',
                    $done) . '; people failed: ' . implode(',', $failed),
                '; subject: ' . $subject . '; text: ' . $text);
        } else {
            if ($reminder['type'] == 2) {
                // TODO: implement leute functionality
            }
        }
    }

    return $done;

}//ko_task_reminder()


/**
 * @param $person
 * @return array
 */
function ko_placeholders_leute_array($person, $prefixPerson = 'r_', $prefixUser = 's_', $tag = '###')
{
    global $DATETIME;

    $map = array();

    //Address fields of a person
    foreach ($person as $k => $v) {
        $map[$tag . $prefixPerson . strtolower($k) . $tag] = $v;
    }

    // Salutations
    $geschlechtMap = array('Herr' => 'm', 'Frau' => 'w');
    $vorname = trim($person['vorname']);
    $nachname = trim($person['nachname']);
    $geschlecht = $person['geschlecht'] != '' ? $person['geschlecht'] : $geschlechtMap[$person['anrede']];
    $map[$tag . $prefixPerson . '_salutation_formal_name' . $tag] = getLL('mailing_salutation_formal_' . ($nachname != '' ? $geschlecht : '')) . ($nachname == '' ? '' : ' ' . $nachname);
    $map[$tag . $prefixPerson . '_salutation_name' . $tag] = getLL('mailing_salutation_' . ($vorname != '' ? $geschlecht : '')) . ($vorname == '' ? '' : ' ' . $vorname);

    //Salutation
    $map[$tag . $prefixPerson . '_salutation' . $tag] = getLL('mailing_salutation_' . $person['geschlecht']);
    $map[$tag . $prefixPerson . '_salutation_formal' . $tag] = getLL('mailing_salutation_formal_' . $person['geschlecht']);


    //Add current date
    $map[$tag . 'date' . $tag] = strftime($DATETIME['dMY'], time());
    $map[$tag . 'date_dmY' . $tag] = strftime($DATETIME['dmY'], time());

    //Add contact fields (from general settings)
    $contact_fields = array('name', 'address', 'zip', 'city', 'phone', 'url', 'email');
    foreach ($contact_fields as $field) {
        $map[$tag . 'contact_' . strtolower($field) . $tag] = ko_get_setting('info_' . $field);
    }

    //Add sender fields of current user
    $sender = ko_get_logged_in_person();
    foreach ($sender as $k => $v) {
        $map[$tag . $prefixUser . strtolower($k) . $tag] = $v;
    }

    return $map;
}//ko_placeholders_leute_array()


/**
 * @param $event
 * @return array
 */
function ko_placeholders_event_array($event, $prefix = 'e_', $tag = '###')
{
    global $DATETIME;

    $map = array();

    //Fields of event
    foreach ($event as $k => $v) {
        $map[$tag . $prefix . strtolower($k) . $tag] = $v;
    }

    $startDatetime = strtotime($map[$tag . $prefix . 'startdatum' . $tag] . ' ' . $map[$tag . $prefix . 'startzeit' . $tag]);
    $endDatetime = strtotime($map[$tag . $prefix . 'enddatum' . $tag] . ' ' . $map[$tag . $prefix . 'endzeit' . $tag]);

    $map[$tag . $prefix . 'startdatum' . $tag] = strftime($DATETIME['dMY'], $startDatetime);
    $map[$tag . $prefix . 'enddatum' . $tag] = strftime($DATETIME['dMY'], $endDatetime);
    $map[$tag . $prefix . 'startzeit' . $tag] = strftime("%H:%M", $startDatetime);
    $map[$tag . $prefix . 'endzeit' . $tag] = strftime("%H:%M", $endDatetime);
    if (trim($event['eventgruppen_id']) != '') {
        $eventGroupName = db_select_data('ko_eventgruppen', 'where id = ' . $event['eventgruppen_id'], 'name', '', '',
            true, true);
        $map[$tag . $prefix . 'eventgruppe' . $tag] = $eventGroupName['name'];
    }

    return $map;
}//ko_placeholders_event_array()


/*
 * Scheduler task: process mails which were sent to groups ...
 */
function ko_task_mailing()
{
    global $ko_path;

    require_once($ko_path . 'mailing.php');
    ko_mailing_main();
}//ko_task_mailing()


/**
 * �berpr�ft und korrigiert ein Datum
 * Basiert auf PEAR-Klasse zur �berpr�fung der Richtigkeit des Datums
 */
function check_datum(&$d)
{
    $d = format_userinput($d, "date");

    get_heute($tag, $monat, $jahr);

    //Trennzeichen testen (. oder ,)
    $date_ = explode(".", $d);
    $date__ = explode(",", $d);
    $date___ = explode("-", $d);
    if (sizeof($date_) >= 2) {
        $date = $date_;
    } else {
        if (sizeof($date__) >= 2) {
            $date = $date__;
        } else {
            if (sizeof($date___) >= 2) {  //SQL-Datum annehmen
                $date = $date___;
                $temp = $date[0];
                $date[0] = $date[2];
                $date[2] = $temp;
            }
        }
    }

    //Angaben ohne Jahr erlauben, dann einfach aktuelles Jahr einf�gen
    if (sizeof($date) == 2) {
        $date[2] = $jahr;
    }
    if ($date[2] == "") {
        $date[2] = $jahr;
    }  //Falls noch kein Jahr gefunden, dann einfach auf aktuelles setzen

    //Jahr auf vier Stellen erg�nzen, falls n�tig (immer 20XX verwenden)
    if (strlen($date[2]) == 2) {
        $date[2] = (int)("20" . $date[2]);
    } else {
        if (strlen($date[2]) == 1) {
            $date[2] = (int)("200" . $date[2]);
        }
    }

    $d = strftime('%d.%m.%Y', mktime(1, 1, 1, $date[1], $date[0], $date[2]));
    return ($date[0] > 0 && $date[1] > 0 && $date[2] > 0);
}//check_datum()


/**
 * �berpr�ft eine Zeit auf syntaktische Richtigkeit
 */
function check_zeit(&$z)
{
    $z = format_userinput($z, "date");

    $z_1 = explode(":", $z);
    $z_2 = explode(".", $z);
    if (sizeof($z_1) == 2) {
        $z_ = $z_1;
    } else {
        if (sizeof($z_2) == 2) {
            $z_ = $z_2;
        } else {
            $z_ = explode(":", ($z . ":00"));
        }
    }

    $z = implode(":", $z_);
    if ($z_ != "" && $z_[0] >= 0 && $z_[0] <= 24 && $z_[1] >= 0 && $z_[1] <= 60) {
        return true;
    } else {
        return false;
    }
}//check_zeit()


/**
 * �berpr�ft auf syntaktisch korrekte Emailadresse
 */
function check_email($email)
{
    $email = trim($email);
    if (strpos($email, ' ') !== false) {
        return false;
    }
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}//check_email()


/**
 * Formatiert eine Natelnummer ins internationale Format f�r clickatell
 */
function check_natel(&$natel)
{
    if (trim($natel) == "") {
        return false;
    }

    $natel = format_userinput($natel, "uint");

    //Ignore invalid numbers (e.g. strings)
    if ($natel == '') {
        return false;
    }
    //Check for min/max length for a reasonable mobile number
    if (strlen($natel) < 9 OR strlen($natel) > 18) {
        return false;
    }

    if (substr($natel, 0, 2) == '00') {  //Area code given as 00XY
        $natel = substr($natel, 2);
    } else {
        if (substr($natel, 0, 1) == "0") {
            $natel = ko_get_setting("sms_country_code") . substr($natel, 1);
        }
    }
    if ($natel) {
        return true;
    } else {
        return false;
    }
}//check_natel()


/**
 * F�gt einem String eine "0" vorne hinzu, falls der String nur 1 Zeichen enth�lt
 */
function str_to_2($s)
{
    while (strlen($s) < 2) {
        $s = "0" . $s;
    }
    return $s;
}


function zerofill($s, $l)
{
    while (strlen($s) < $l) {
        $s = '0' . $s;
    }
    return $s;
}


/**
 * Wandelt die angegebene Zeit in eine SQL-Zeit um
 */
function sql_zeit($z)
{
    if ($z != '') {
        $z = str_replace(array('.', ',', ' '), array(':', ':', ''), $z);
        $z_1 = explode(':', $z);
        switch (sizeof($z_1)) {
            case 1:
                $r = $z . ':00';
                break;
            case 2:
                $r = $z;
                break;
            case 3:
                $r = substr($z, 0, -3);
                break;
        }
    } else {
        $r = '';
    }
    if ($r == '00:00') {
        $r = '';
    }
    return format_userinput($r, 'date');
}//sql_zeit()


/**
 * Wandelt das angegebene Datum in ein SQL-Datum um
 */
function sql_datum($d)
{
    //Testen, ob Datum schon im SQL-Format ist:
    $temp = explode("-", $d);
    if (sizeof($temp) == 3) {
        return format_userinput($d, "date");
    }

    if ($d != "") {
        $date = explode(".", $d);
        $r = $date[2] . "-" . $date[1] . "-" . $date[0];
    } else {
        $r = "";
    }
    return format_userinput($r, "date");
}//sql_datum()


/**
 * Wandelt ein SQL-Datum ins Format TG.MT.JAHR um
 */
function sql2datum($s)
{
    if ($s == "" || $s == "0000-00-00") {
        return "";
    }
    $s_ = explode("-", $s);
    if (sizeof($s_) == 3) {
        $r = $s_[2] . "." . $s_[1] . "." . $s_[0];
        return $r;
    } else {
        return $s;
    }
}//sql2datum()


function sql2datetime($s)
{
    global $DATETIME;

    if ($s == '' || $s == '0000-00-00 00:00:00') {
        return '';
    }
    $ts = strtotime($s);
    if ($ts > 0) {
        return strftime($DATETIME['dmY'] . ' %H:%M', $ts);
    } else {
        return $s;
    }
}//sql2datum()


/**
 * Converts an SQL date (YYYY-MM-DD) into a unix timestamp
 */
function sql2timestamp($s)
{
    if ($s == "" || $s == "0000-00-00") {
        return "";
    } else {
        return strtotime($s);
    }
}//sql2timestamp()


/**
 * Wandelt ein SQL-DateTime ins Format TG.MT.JAHR hh:mm:ss um
 */
function sqldatetime2datum($s)
{
    if ($s == "" || $s == "0000-00-00 00:00:00") {
        return "";
    }
    $temp = explode(" ", $s);
    $date = $temp[0];
    $time = $temp[1];

    $s_ = explode("-", $date);
    if (sizeof($s_) == 3) {
        $r = $s_[2] . "." . $s_[1] . "." . $s_[0];
        return $r . " " . $time;
    } else {
        return $s;
    }
}//sqldatetime2datum()


/**
 * Addiert zu einem angegebenen Datum s Monate (inkl. �berlauf-Check)
 */
function addmonth(&$m, &$y, $s)
{
    $m = (int)$m;
    $y = (int)$y;
    $s = (int)$s;

    $m += $s;
    while ($m < 1) {
        $m += 12;
        $y--;
    }
    while ($m > 12) {
        $m -= 12;
        $y++;
    }
}//addmonth()


/**
 * Liefert Tag, Monat und Jahr des aktuellen Datums
 */
function get_heute(&$t, &$m, &$j)
{
    $heute = getdate(time());
    $t = $heute["mday"];
    $m = $heute["mon"];
    $j = $heute["year"];
}


/**
 * Formaitert eine Emailadresse f�r die anzeige im Web
 */
function format_email($m)
{
    return strtr($m, array('@' => ' (at) ', '.' => ' (dot) '));
}//format_email()


/**
 * Entfernt alle m�glichen "gef�hrlichen" Zeichen aus User-Strings (z.B. bei der Speicherung von Filter-Vorlagen)
 */
function format_userinput($s, $type, $enforce = false, $length = 0, $replace = array(), $add_own = "")
{
    if ($replace['umlaute']) {
        $s = strtr($s, array(
            '�' => 'a',
            '�' => 'o',
            '�' => 'u',
            '�' => 'e',
            '�' => 'e',
            '�' => 'a',
            '�' => 'A',
            '�' => 'O',
            '�' => 'U'
        ));
    }

    if ($replace['singlequote'] || $replace["allquotes"]) {
        $s = strtr($s, array("'" => '', '`' => ''));
    }
    if ($replace["doublequote"] || $replace["allquotes"]) {
        $s = str_replace('"', "", $s);
    }
    if ($replace["backquote"] || $replace["allquotes"]) {
        $s = str_replace("`", "", $s);
    }

    //Bei falscher L�nge abbrechen
    if ($length != 0) {
        if (substr($length, 0, 1) == "=") {  //Falls exakte L�nge verlangt...
            if (strlen($s) != $length) {
                if ($enforce) {
                    $s = "";
                    return false;
                } else {
                    $s = substr($s, 0, $length);
                }
            }
        } else {  //...sonst auf maximale L�nge pr�fen
            if (strlen($s) > $length) {
                if ($enforce) {
                    $s = "";
                    return false;
                } else {
                    $s = substr($s, 0, $length);
                }
            }
        }
    }//if(length)

    //Type testen
    switch ($type) {
        case "uint":
            $allowed = "1234567890";
            break;

        case "int":
            $allowed = "-1234567890";
            break;

        case "int@":
            $allowed = "1234567890@";
            break;

        case "intlist":
            $allowed = "1234567890,";
            break;

        case "alphanumlist":
            $allowed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_-,:;";
            break;

        case "float":
            $allowed = "-1234567890.";
            break;

        case "alphanum":
            $allowed = "abcdefghijklmnopqrstuvwxyz���ABCDEFGHIJKLMNOPQRSTUVWXYZ�����������1234567890";
            break;

        case "alphanum+":
            $allowed = "abcdefghijklmnopqrstuvwxyz���ABCDEFGHIJKLMNOPQRSTUVWXYZ�����������1234567890+-_";
            break;

        case "alphanum++":
            $allowed = "abcdefghijklmnopqrstuvwxyz���ABCDEFGHIJKLMNOPQRSTUVWXYZ�����������1234567890+-_ ";
            break;

        case "email":
            $allowed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.+-_@&";
            break;

        case "dir":
            $allowed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ�������1234567890-_ ";
            break;

        case "js":
            $allowed = "abcdefghijklmnopqrstuvwxyz���ABCDEFGHIJKLMNOPQRSTUVWXYZ����������1234567890-_?+&�@.;:/'() ";
            break;

        case "alpha":
            $allowed = "abcdefghijklmnopqrstuvwxyz���ABCDEFGHIJKLMNOPQRSTUVWXYZ�����������";
            break;

        case "alpha+":
            $allowed = "abcdefghijklmnopqrstuvwxyz���ABCDEFGHIJKLMNOPQRSTUVWXYZ�����������+-_";
            break;

        case "alpha++":
            $allowed = "abcdefghijklmnopqrstuvwxyz���ABCDEFGHIJKLMNOPQRSTUVWXYZ�����������+-_ ";
            break;

        case "date":
            $allowed = "1234567890-.: ";
            break;

        case 'group_role':
            $allowed = "gr1234567890:,";
            break;

        case "text":
            //Falls schon ein addslashes angewendet worden ist, diese erst wieder entfernen.
            $s = str_replace("\\", "", $s);
            $new = addslashes($s);
            return $new;
            break;

        case "all":
            return true;
            break;
    }//switch(type)

    if ($add_own) {
        $allowed .= $add_own;
    }

    $new = "";
    for ($i = 0; $i < strlen($s); $i++) {
        if (false !== strstr($allowed, substr($s, $i, 1))) {
            $new .= substr($s, $i, 1);
        } else {
            if ($enforce) {
                return false;  //Bei ung�ltigen Zeichen nur abbrechen, wenn enforce true ist.
            }
        }
    }
    return $new;
}


/**
 * Formatiert Sonderzeichen in ihre HTML-Entsprechungen
 * Damit soll XSS in Formularen und sonst verhindert werden
 */
function ko_html($string)
{
    return strtr($string, array(
            '&' => "&amp;",
            "'" => "&lsquo;",
            '"' => "&quot;",
            '>' => "&gt;",
            '<' => "&lt;",
            '�' => "&ouml;",
            '�' => "&auml;",
            '�' => "&uuml;",
            '�' => "&Ouml;",
            '�' => "&Auml;",
            '�' => "&Uuml;",
        )
    );
}//ko_html()


/**
 * Wendet ko_html-Funktion zweimal an. Z.B. f�r Overlib-Code
 */
function ko_html2($string)
{
    $r = ko_html(ko_html($string));
    return $r;
}


function ko_unhtml($string)
{
    return strtr($string, array(
            "&amp;" => "&",
            "&lsquo;" => "'",
            "&quot;" => '"',
            "&gt;" => '>',
            "&lt;" => '<',
            "&ouml;" => '�',
            "&auml;" => '�',
            "&uuml;" => '�',
            "&Ouml;" => '�',
            "&Auml;" => '�',
            "&Uuml;" => '�',
            "&rsaquo;" => '',
            "&thinsp;" => '',
            "&nbsp;" => ' ',
        )
    );
}//ko_unhtml()


/**
 * Escapes a string in a way it can be decoded again using JavaScript's unescape()
 * Found on http://php.net/manual/de/function.urlencode.php
 */
function ko_js_escape($in)
{
    $out = '';
    for ($i = 0; $i < strlen($in); $i++) {
        $hex = dechex(ord($in[$i]));
        if ($hex == '') {
            $out = $out . urlencode($in[$i]);
        } else {
            $out = $out . '%' . ((strlen($hex) == 1) ? ('0' . strtoupper($hex)) : (strtoupper($hex)));
        }
    }
    $out = str_replace('+', '%20', $out);
    $out = str_replace('_', '%5F', $out);
    $out = str_replace('.', '%2E', $out);
    $out = str_replace('-', '%2D', $out);
    return $out;
}//ko_js_escape()


function ko_js_save($s)
{
    return str_replace("\r", '', str_replace("\n", '', nl2br(ko_html($s))));
}


/**
 * Bereitet einen Text f�r ein Email auf, indem jeder Zeile schliessende \n entfernt werden
 */
function ko_emailtext($input)
{
    $lines = explode("\n", $input);
    $text = "";
    foreach ($lines as $l) {
        $text .= rtrim($l) . "\n";
    }
    return $text;
}//ko_emailtext()


/**
 * Trimmt jedes Element eines Arrays
 */
function array_trim($arr)
{
    unset($result);
    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            $result[$key] = array_trim($value);
        } else {
            $result[$key] = trim($value);
        }
    }
    return $result;
}


/**
 * Erwartet Array eines Datums mit den Eintr�gen 0=>Tag, 1=>Monat, 2=>Jahr (4-stellig)
 * Gibt einen Code in der Form JJJJMMTT zur�ck
 * Geeignet f�r Int-Vergleiche von Daten
 */
function date2code($d)
{
    $return = $d[2] . str_to_2($d[1]) . str_to_2($d[0]);
    return $return;
}

function code2date($d)
{
    $r[2] = substr($d, 0, 4);
    $r[1] = substr($d, 4, 2);
    $r[0] = substr($d, 6, 2);
    return $r;
}


/**
 * Erwartet Datum als .-getrennter String, einen Modus (tag, woche, monat) und ein Inkrement
 * Gibt Datum als Array zur�ck (0=>Tag, 1=>Monat, 2=>Jahr)
 */
function add2date($datum, $mode, $inc, $sqlformat = false)
{
    if ($sqlformat) {
        $d[0] = substr($datum, 8, 2);
        $d[1] = substr($datum, 5, 2);
        $d[2] = substr($datum, 0, 4);
    } else {
        if (is_array($datum)) {
            $d = $datum;
        } else {
            $d = explode('.', $datum);
        }
    }

    switch ($mode) {
        case 'tag':
        case 'day':
            $d[0] = (int)$d[0] + (int)$inc;
            break;
        case 'woche':
        case 'week':
            $d[0] = (int)$d[0] + (7 * (int)$inc);
            break;
        case 'monat':
        case 'month':
            $d[1] = (int)$d[1] + (int)$inc;
            break;
    }//switch(mode)

    //Ueberl�ufe korrigieren
    if ($sqlformat) {
        $r = strftime('%Y-%m-%d', mktime(1, 1, 1, $d[1], $d[0], $d[2]));
    } else {
        $r_ = strftime('%d.%m.%Y', mktime(1, 1, 1, $d[1], $d[0], $d[2]));
        $r = explode('.', $r_);
    }
    return $r;
}


function date_get_days_between_dates($d1, $d2)
{
    $c1 = str_replace('-', '', $d1);
    $c2 = str_replace('-', '', $d2);

    $diff = 0;
    while ($c1 < $c2) {
        $d1 = add2date($d1, 'day', 1, true);
        $c1 = str_replace('-', '', $d1);
        $diff++;
    }
    return $diff;
}


function add2time($time, $inc)
{
    list($hour, $minute) = explode(':', $time);
    $new = 60 * (int)$hour + (int)$minute + (int)$inc;
    return intval($new / 60) . ':' . ($new % 60);
}//add2time()


/**
 * Liefert den ersten Montag vor dem �bergebenen Datum (YYYY-MM-DD)
 */
function date_find_last_monday($date)
{
    $wd = date("w", strtotime($date));
    if ($wd == 0) {
        $wd = 7;
    }
    $r = add2date($date, "tag", (-1 * ($wd - 1)), true);
    return $r;
}//date_find_last_monday()


function date_find_next_monday($date)
{
    $wd = date('w', strtotime($date));
    if ($wd == 0) {
        $wd = 7;
    }
    $r = add2date($date, 'day', ($wd - 1), true);
    return $r;
}//date_find_last_monday()


/**
 * Liefert den n�chsten Sonntag nach dem �bergebenen Datum (YYYY-MM-DD)
 */
function date_find_next_sunday($date)
{
    $wd = date("w", strtotime($date));
    if ($wd == 0) {
        $wd = 7;
    }
    $r = add2date($date, "tag", (7 - $wd), true);
    return $r;
}//date_find_next_sunday()


function date_convert_timezone($date_str, $tz, $date_format = 'Ymd\THis\Z')
{
    $time = strtotime($date_str);
    $strCurrentTZ = date_default_timezone_get();
    date_default_timezone_set($tz);
    $ret = date($date_format, $time);
    date_default_timezone_set($strCurrentTZ);
    return $ret;
}//date_convert_timezone()


function ko_calendar_mwselect($mode)
{
    global $DATETIME;

    $r = '';

    if ($mode == 'month' || $mode == 'resourceMonth') {
        $r .= '<select name="sel_mwselect" size="1" onchange="val=this.options[this.selectedIndex].value; sendReq(\'inc/ajax.php\', \'action,ymd\', \'fcsetdate,\'+val); $(\'#ko_calendar\').fullCalendar(\'gotoDate\', val.substring(0,4), (val.substring(5,7)-1), val.substring(8));">';
        $today = date('Y-m-') . min(28, date('d'));
        $view_stamp = mktime(1, 1, 1, $_SESSION['cal_monat'], $_SESSION['cal_tag'], $_SESSION['cal_jahr']);
        $cur_month = date('m-Y');

        //Dynamically adjust select size
        $start = min(-12, floor(($view_stamp - time()) / (3600 * 24 * 30) - 12));
        $stop = max(25, floor(($view_stamp - time()) / (3600 * 24 * 30) + 12));

        if ($_SESSION['ses_userid'] == ko_get_guest_id()) {
            if (defined('GUEST_MWSELECT_MONTH_START')) {
                $start = GUEST_MWSELECT_MONTH_START;
            }
            if (defined('GUEST_MWSELECT_MONTH_STOP')) {
                $stop = GUEST_MWSELECT_MONTH_STOP;
            }
        }

        for ($i = $start; $i < $stop; $i++) {
            $temp = strtotime(add2date($today, 'monat', $i, true));
            $label = strftime('%m-%Y', $temp);
            $value = strftime('%Y-%m-%d', $temp);
            $sel = $label == strftime('%m-%Y', $view_stamp) ? 'selected="selected"' : '';
            //Mark today
            $mark = $label == $cur_month ? 'style="background: #c0c0c0; font-weight: bold;"' : '';
            $r .= '<option value="' . $value . '" ' . $sel . ' ' . $mark . '>' . $label . '</option>';
        }
        $r .= '</select>';
    } else {
        if ($mode == 'agendaWeek' || $mode == 'resourceWeek') {
            $r .= '<select name="sel_mwselect" size="1" onchange="val=this.options[this.selectedIndex].value; sendReq(\'inc/ajax.php\', \'action,ymd\', \'fcsetdate,\'+val); $(\'#ko_calendar\').fullCalendar(\'gotoDate\', val.substring(0,4), (val.substring(5,7)-1), val.substring(8));">';
            $today = date('Y-m-d');
            $view_stamp = mktime(1, 1, 1, $_SESSION['cal_monat'], $_SESSION['cal_tag'], $_SESSION['cal_jahr']);
            $cur_week = strftime('%V-%G', time());

            //Dynamically adjust select size
            $start = min(-52, floor(($view_stamp - time()) / (3600 * 24 * 7) - 10));
            $stop = max(105, floor(($view_stamp - time()) / (3600 * 24 * 7) + 10));

            if ($_SESSION['ses_userid'] == ko_get_guest_id()) {
                if (defined('GUEST_MWSELECT_WEEK_START')) {
                    $start = GUEST_MWSELECT_WEEK_START;
                }
                if (defined('GUEST_MWSELECT_WEEK_STOP')) {
                    $stop = GUEST_MWSELECT_WEEK_STOP;
                }
            }

            for ($i = $start; $i < $stop; $i++) {
                $temp = strtotime(add2date($today, 'woche', $i, true));
                $label = strftime('%V-%G', $temp);
                $value = strftime('%Y-%m-%d', $temp);
                $sel = $label == strftime('%V-%G', $view_stamp) ? 'selected="selected"' : '';
                //Mark today
                $mark = $label == $cur_week ? 'style="background: #c0c0c0; font-weight: bold;"' : '';
                $r .= '<option value="' . $value . '" ' . $sel . ' ' . $mark . '>' . $label . '</option>';
            }
            $r .= '</select>';
        } else {
            if ($mode == 'agendaDay' || $mode == 'resourceDay') {
                $r .= '<select name="sel_mwselect" size="1" onchange="val=this.options[this.selectedIndex].value; sendReq(\'inc/ajax.php\', \'action,ymd\', \'fcsetdate,\'+val); $(\'#ko_calendar\').fullCalendar(\'gotoDate\', val.substring(0,4), (val.substring(5,7)-1), val.substring(8));">';
                $today = date('Y-m-d');
                $view_stamp = mktime(1, 1, 1, $_SESSION['cal_monat'], $_SESSION['cal_tag'], $_SESSION['cal_jahr']);
                $cur_day = strftime($DATETIME['ddmy'], time());

                //Dynamically adjust select size
                $start = min(-60, floor(($view_stamp - time()) / (3600 * 24) - 30));
                $stop = max(365, floor(($view_stamp - time()) / (3600 * 24) + 30));

                for ($i = $start; $i < $stop; $i++) {
                    $temp = strtotime(add2date($today, 'tag', $i, true));
                    $label = strftime($DATETIME['ddmy'], $temp);
                    $value = strftime('%Y-%m-%d', $temp);
                    $sel = $label == strftime($DATETIME['ddmy'], $view_stamp) ? 'selected="selected"' : '';
                    //Mark sundays
                    if (strftime('%w', $temp) == 0) {
                        $mark = 'style="background: #e6e6e6;"';
                    } //Mark today
                    else {
                        if ($label == $cur_day) {
                            $mark = 'style="background: #c0c0c0; font-weight: bold;"';
                        } else {
                            $mark = '';
                        }
                    }
                    $r .= '<option value="' . $value . '" ' . $sel . ' ' . $mark . '>' . $label . '</option>';
                }
                $r .= '</select>';
            }
        }
    }

    return $r;
}//ko_calendar_mwselect()


/**
 * Liefert wiederholte Termine nach verschiedenen Modi. (f�r Reservationen und Termine verwendet)
 * d1 und d2 sind Start- und Enddatum des einzelnen Anlasses (wiederholte mehrt�gige Anl�sse sind m�glich)
 * repeat_mode enth�lt den Modus der Wiederholung (keine, taeglich, wochentlich, monatlich1, monatlich2)
 * bis_monat und bis_jahr stellen das Ende der Wiederholung dar (inkl.)
 */
function ko_get_wiederholung(
    $d1,
    $d2,
    $repeat_mode,
    $inc,
    $bis_tag,
    $bis_monat,
    $bis_jahr,
    &$r,
    $max_repeats = '',
    $holiday_eg = 0
)
{
    //Resultat-Array leeren
    $r = array();
    $num_repeats = 1;

    $d1 = format_userinput($d1, "date");
    $d2 = format_userinput($d2, "date");
    $repeat_mode = format_userinput($repeat_mode, "alphanum");
    $bis_tag = format_userinput($bis_tag, "uint");
    $bis_monat = format_userinput($bis_monat, "uint");
    $bis_jahr = format_userinput($bis_jahr, "uint");
    if (!$inc) {
        $inc = 1;
    }

    //Datum vorbereiten und Dauer f�r ein mehrt�giges Ereignis berechnen
    $d1e = explode(".", $d1);
    $sd_string = date2code($d1e);
    if ($d2 != "") {
        $d2e = explode(".", $d2);
        $ed_string = date2code($d2e);
        $d_diff = (int)($sd_string - $ed_string);
    } else {
        $d_diff = 0;
    }

    //Enddatum in Code umwandeln
    if ($max_repeats == "") {  //Keine Anzahl Wiederholungen angegeben --> Enddatum verwenden
        $max_repeats = 1000;
        $until_string = date2code(array($bis_tag, $bis_monat, $bis_jahr));
    } else {  //Sonst Anzahl Wiederholungen verwenden und Datum in ferne Zukunft legen
        $until_string = date2code(array("31", "12", "3000"));
    }

    switch ($repeat_mode) {
        case "taeglich":
        case "woechentlich":
        case "monatlich2":  //Immer am gleichen Datum

            //Inkrement wird vor dem Aufruf richtig gesetzt, also muss nur noch definiert werden, was inkrementiert werden soll
            if ($repeat_mode == "taeglich") {
                $add_mode = "tag";
            } else {
                if ($repeat_mode == "woechentlich") {
                    $add_mode = "woche";
                } else {
                    if ($repeat_mode == "monatlich2") {
                        $add_mode = "monat";
                    }
                }
            }

            $r[] = $d1;
            $r[] = $d2;
            $new_code1 = date2code(add2date($d1, $add_mode, $inc));
            $new_code2 = ($d_diff == 0) ? "" : date2code(add2date($d2, $add_mode, $inc));
            while ($new_code1 <= $until_string && $num_repeats < $max_repeats) {
                $num_repeats++;

                $code1 = code2date($new_code1);
                $r[] = $code1[0] . "." . $code1[1] . "." . $code1[2];
                $code2 = code2date($new_code2);
                if ($code2[0] != "") {
                    $r[] = $code2[0] . "." . $code2[1] . "." . $code2[2];
                } else {
                    $r[] = "";
                }

                $new_code1 = date2code(add2date(code2date($new_code1), $add_mode, $inc));
                $new_code2 = ($d_diff == 0) ? "" : date2code(add2date(code2date($new_code2), $add_mode, $inc));

            }//while(new_code1 < until_string)
            break;

        case "monatlich1":  //z.B. "Jeden 3. Montag"
            $nr_ = explode("@", $inc);
            $nr = $nr_[0];
            $tag = $nr_[1];

            // check if nr means every last xyz of month
            $everyLast = false;
            if ($nr == 6) {
                $nr = 5;
                $everyLast = true;
            }

            $erster = $d1e;
            $erster[0] = 1;
            $new_code = date2code($erster);

            while ($new_code <= $until_string && $num_repeats <= $max_repeats) {
                $num_repeats++;
                $found = false;
                while (!$found && $erster[0] < 8) {
                    $wochentag = strftime("%w", mktime(1, 1, 1, $erster[1], $erster[0], $erster[2]));
                    if ($wochentag == $tag) {
                        $found = true;
                    } else {
                        $erster[0] += 1;
                    }
                }
                $neues_datum = add2date($erster, "tag", ($nr - 1) * 7);

                // in case of 'every 5. xyz', check whether the current month has 5 xyz
                if ($nr < 5 || $neues_datum[0] > 28) {
                    $r[] = $neues_datum[0] . "." . $neues_datum[1] . "." . $neues_datum[2];
                    $neues_datum2 = add2date($neues_datum, "tag", $d_diff);
                    $r[] = ($d_diff > 0) ? ($neues_datum2[0] . "." . $neues_datum2[1] . "." . $neues_datum2[2]) : "";
                } // in case of 'every last xyz' and that a month doesn't have 5 xyz, enter event at 4. xyz of month
                else {
                    if ($everyLast) {
                        $neues_datum = add2date($neues_datum, "tag", -7);
                        $r[] = $neues_datum[0] . "." . $neues_datum[1] . "." . $neues_datum[2];
                        $neues_datum2 = add2date($neues_datum, "tag", $d_diff);
                        $r[] = ($d_diff > 0) ? ($neues_datum2[0] . "." . $neues_datum2[1] . "." . $neues_datum2[2]) : "";
                    }
                }

                $erster[0] = 1;
                $erster = add2date($erster, "monat", 1);
                $new_code = date2code($erster);

            }//while(new_code < until_string)

            break;

        default:  //case 'keine'
            $r[] = $d1;
            $r[] = $d2;
    }//switch(repeat_mode)


    //Exclude repetition dates that collide with holiday eventgroup
    if ($holiday_eg > 0) {
        $first = $r[0];
        $min = substr($first, -4) . '-' . substr($first, 3, 2) . '-' . substr($first, 0, 2);
        $last = $r[sizeof($r) - 1] ? $r[sizeof($r) - 1] : $r[sizeof($r) - 2];
        $max = substr($last, -4) . '-' . substr($last, 3, 2) . '-' . substr($last, 0, 2);
        $holidays = db_select_data('ko_event',
            "WHERE `eventgruppen_id` = '$holiday_eg' AND `enddatum` >= '$min' AND `startdatum` <= '$max'");
        $holiday_days = array();
        foreach ($holidays as $day) {
            $start = $day['startdatum'];
            $stop = $day['enddatum'];
            while (str_replace('-', '', $stop) >= str_replace('-', '', $start)) {
                $holiday_days[] = strftime('%d.%m.%Y', strtotime($start));
                $start = add2date($start, 'day', 1, true);
            }
        }
        for ($i = 0; $i < sizeof($r); $i += 2) {
            $dstart = substr($r[$i], -4) . '-' . substr($r[$i], 3, 2) . '-' . substr($r[$i], 0, 2);
            if ($r[$i + 1] == '') {
                $dstop = $dstart;
            } else {
                $dstop = substr($r[$i + 1], -4) . '-' . substr($r[$i + 1], 3, 2) . '-' . substr($r[$i + 1], 0, 2);
            }
            $del = false;
            while (str_replace('-', '', $dstart) <= str_replace('-', '', $dstop)) {
                if (in_array(strftime('%d.%m.%Y', strtotime($dstart)), $holiday_days)) {
                    $del = true;
                }
                $dstart = add2date($dstart, 'day', 1, true);
            }
            if ($del) {
                $del_keys[] = $i;
                $del_keys[] = $i + 1;
            }
        }
        foreach ($del_keys as $k) {
            unset($r[$k]);
        }
        //Reset indizes
        array_values($r);
    }

    return true;
}//ko_get_wiederholung()


function ko_get_new_serie_id($table)
{
    $max1 = db_select_data("ko_" . $table, "", "MAX(`serie_id`) as max", "", "", true);
    $max2 = db_select_data("ko_" . $table . "_mod", "", "MAX(`serie_id`) as max", "", "", true);
    $max = max($max1["max"], $max2["max"]);
    return ($max + 1);
}//ko_get_new_serie_id()


/**
 * Erstellt einen Log-Eintrag zu definierten Typ. Timestamp und UserID werden automatisch eingef�gt
 */
function ko_log($type, $msg)
{
    global $EMAIL_LOG_TYPES, $BASE_URL;

    //Create db entry
    $type = format_userinput($type, 'alphanum+', false, 0, array(), '@');
    db_insert_data('ko_log', array(
        'type' => $type,
        'comment' => mysql_real_escape_string($msg),
        'user_id' => $_SESSION['ses_userid'],
        'date' => date('Y-m-d H:i:s'),
        'session_id' => session_id(),
        'request_data' => print_r($_REQUEST, true),
    ));

    //Send email notification if activated for given type
    if (is_array($EMAIL_LOG_TYPES) && in_array($type, $EMAIL_LOG_TYPES) && defined('WARRANTY_EMAIL')) {
        $subject = 'kOOL: ' . $type . ' (on ' . $BASE_URL . ')';

        $from = ko_get_setting('info_email');
        if (!$from) {
            $from = WARRANTY_EMAIL;
        }

        $msg .= "\n\n- GET:\n" . print_r($_GET, true);
        $msg .= "\n\n- POST:\n" . print_r($_POST, true);
        $msg .= "\n\n- SESSION:\n" . print_r($_SESSION, true);
        $msg .= "\n\n- SERVER:\n" . print_r($_SERVER, true);

        ko_send_mail($from, WARRANTY_EMAIL, $subject, $msg);
        //ko_send_email(WARRANTY_EMAIL, $subject, $msg, array('From' => $from));
    }
}//ko_log()


/**
 * Erstellt Log-Meldung anhang zwei �bergebener Arrays, und gibt die Differenzen an
 */
function ko_log_diff($type, $data, $old = array())
{
    $msg = "";
    foreach ($data as $key => $value) {
        if ($old[$key] != $value) {
            $msg .= "$key: " . $old[$key] . " --> " . $value . ", ";
        }
    }
    if (isset($old["id"])) {
        $msg = "id: " . $old["id"] . ", " . $msg;
    }
    ko_log($type, substr($msg, 0, -2));
}//ko_log_diff()


/**
 * Erstellt Log-Meldung, falls in einem Modul ein behandelter Error auftritt.
 * Dient der Verfolgbarkeit von User-Meldungen, wenn sie einen Error erhalten.
 */
function ko_error_log($module, $error, $error_txt, $action)
{
    $log_message = "$module Error $error: '$error_txt' - Action: $action - ";
    $log_message .= "User: " . $_SESSION["ses_username"] . " (" . $_SESSION["ses_userid"] . ") - ";
    $log_message .= "POST: (" . var_export($_POST, true) . ")";
    $log_message .= " - GET: (" . var_export($_GET, true) . ")";

    ko_log("error", $log_message);
}//ko_error_log()


/**
 * Liefert einen einzelnen Logeintrag
 */
function ko_get_log(&$logs, $z_where = "", $z_limit = "")
{
    if ($_SESSION["sort_logs"] && $_SESSION["sort_logs_order"]) {
        $sort = " ORDER BY " . $_SESSION["sort_logs"] . " " . $_SESSION["sort_logs_order"] . ' , id DESC';
    } else {
        $sort = " id DESC ";
    }

    $logs = db_select_data('ko_log', 'WHERE 1=1 ' . $z_where, '*', $sort, $z_limit);
}//ko_get_log()


/**
 * Versucht, die IP, des aktuellen Users zu ermitteln
 */
function ko_get_user_ip()
{
    if (isset($HTTP_X_FORWARDED_FOR) && $HTTP_X_FORWARDED_FOR != null) {  //Bei Proxy
        $ip = $HTTP_X_FORWARDED_FOR;
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}//ko_get_user_ip()


/**
 *
 */
function ko_menuitem($module, $show)
{
    global $ko_menu_akt;

    $pre = '<b>';
    $post = '</b>';

    $ll_item = getLL('submenu_' . $module . '_' . $show);
    //Mark active entry
    if ($_SESSION['show'] == $show && $ko_menu_akt == $module) {
        return $pre . $ll_item . $post;
    } else {
        return $ll_item;
    }
}//ko_menuitem()


function ko_get_filename($file_name)
{
    $newfile = basename($file_name);
    if (strpos($newfile, '\\') !== false) {
        $tmp = preg_split("[\\\]", $newfile);
        $newfile = $tmp[count($tmp) - 1];
        return ($newfile);
    } else {
        return ($file_name);
    }
}


function ko_returnfile($file_, $path_ = "download/pdf/", $filename_ = "")
{
    $file_ = basename(format_userinput($file_, "alphanum+", false, 0, array(), "."));
    $file = $path_ . $file_;
    $filename = $filename_ ? $filename_ : $file_;

    $fp = @fopen($file, "r");
    if (!$fp) {
        header("HTTP/1.0 404 Not Found");
        print "Not found!";
        return false;
    }

    if (isset($_SERVER["HTTP_USER_AGENT"]) && strpos($_SERVER["HTTP_USER_AGENT"], "MSIE")) {
        // IE cannot download from sessions without a cache
        header("Cache-Control: public");

        // q316431 - Don't set no-cache when over HTTPS
        if (!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
            header("Pragma: no-cache");
        }
    } else {
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
    }

    $mime = exec("/usr/bin/file -bin " . $file . " 2>/dev/null");
    if ($mime == "") {
        $mime = "application/octet-stream";
    }
    header("Content-Type: " . $mime);

    // Inline text files, don't separatly save them
    $ext = substr($file, -3);
    if ($ext != "txt") {
        header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    }

    header("Content-Length: " . filesize($file));
    header("Content-Description: kOOL");
    fpassthru($fp);
    fclose($fp);
    exit;
}//ko_returnfile()


function dirsize($path)
{
    global $ko_path;

    $old_path = getcwd();
    if (!is_dir($ko_path . "/" . $path)) {
        return -1;
    }
    $size = trim(shell_exec("cd \"" . $ko_path . "/" . $path . "\"; du -sb; cd \"" . $old_path . "\";"),
        "\x00..\x2F\x3A..\xFF");

    return $size;
}


/**
 * Include all necessary CSS files
 * Called from module/index.php
 * Returns HTML string to be included in <head>
 */
function ko_include_css()
{
    global $ko_path, $PLUGINS;

    //Basic CSS files
    $r = '<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="' . $ko_path . 'Modules/Core/Resources/Public/Styles/startmin.css" />
<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
<link rel="stylesheet" type="text/css" href="' . $ko_path . 'lib/datepicker/bootstrap-datepicker3.min.css" />
<link rel="stylesheet" type="text/css" href="' . $ko_path . 'Modules/Core/Resources/Public/Styles/FlockrFont.css" />
<link rel="stylesheet" type="text/css" href="' . $ko_path . 'Modules/Core/Resources/Public/Styles/Flockr.css?' . filemtime($ko_path . 'Modules/Core/Resources/Public/Styles/Flockr.css') . '" />
<link rel="stylesheet" type="text/css" media="print" href="' . $ko_path . 'print.css?' . filemtime($ko_path . 'print.css') . '" />

<!--[if lte IE 6]>
<link rel="stylesheet" type="text/css" href="' . $ko_path . 'ie6.css?' . filemtime($ko_path . 'ie6.css') . '" />
<![endif]-->
<!--[if IE 7]>
<link rel="stylesheet" type="text/css" href="' . $ko_path . 'ie7.css?' . filemtime($ko_path . 'ie7.css') . '" />
<![endif]-->' . "\n";
    if (file_exists($ko_path . 'ko.css')) {
        $r .= '<link rel="stylesheet" type="text/css" href="' . $ko_path . 'ko.css?' . filemtime($ko_path . 'ko.css') . '" />' . "\n";
    }

    //Include CSS files from plugins
    foreach ($PLUGINS as $p) {
        $css_file = $ko_path . 'plugins/' . $p['name'] . '/' . $p['name'] . '.css';
        if (file_exists($css_file)) {
            $r .= '<link rel="stylesheet" type="text/css" href="' . $css_file . '?' . filemtime($css_file) . '" />' . "\n";
        }
    }

    return $r;
}//ko_include_css()


/**
 * Returns HTML code to include the given files
 * @param array $files Relative paths to the JS files to be included
 */
function ko_include_js($files, $module = '')
{
    global $ko_menu_akt;

    $r = '';

    if ($module !== false) {
        $module = $module ? $module : $ko_menu_akt;
        if ($module != '') {
            $r .= '<script type=\'text/javascript\'>var kOOL = {module:"' . $module . '", sid:"' . session_id() . '"};</script>' . "\n";
        }
    }

    foreach ($files as $file) {
        if (!$file) {
            continue;
        }
        $r .= '<script type=\'text/javascript\' src=\'' . $file . '?' . filemtime($file) . '\'></script>' . "\n";
    }

    //Add JS files from plugins
    $plugin_files = hook_include_js($module);
    if (is_array($plugin_files) && sizeof($plugin_files) > 0) {
        foreach ($plugin_files as $file) {
            if (!$file) {
                continue;
            }
            $r .= '<script type=\'text/javascript\' src=\'' . $file . '?' . filemtime($file) . '\'></script>' . "\n";
        }
    }

    return $r;
}//ko_include_js()


function ko_list_set_sorting($table, $sortCol)
{
    $rows = db_select_data($table, 'WHERE 1', 'id,sort,' . $sortCol, "ORDER BY $sortCol ASC");

    $cY = $cN = $max = 0;
    foreach ($rows as $row) {
        if ($row['sort'] > 0) {
            $cY++;
        } else {
            $cN++;
        }
        $max = max($max, $row['sort']);
    }

    if ($cY == 0) {
        $c = 1;
        foreach ($rows as $row) {
            db_update_data($table, "WHERE `id` = '" . $row['id'] . "'", array('sort' => $c++));
        }
    } else {
        $c = $max + 1;
        foreach ($rows as $row) {
            if ($row['sort'] > 0) {
                continue;
            }
            db_update_data($table, "WHERE `id` = '" . $row['id'] . "'", array('sort' => $c++));
        }
    }
}//ko_list_set_sorting()


/**
 * Liefert ein Array aller im Browser eingestellten Sprachen in der Reihenfolge der Priorit�ten
 */
function getBrowserLanguages()
{
    $languages = array();
    $strAcceptedLanguage = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($strAcceptedLanguage as $languageLine) {
        list ($languageCode, $quality) = explode(';', $languageLine);
        $arrAcceptedLanguages[$languageCode] = $quality ? substr($quality, 2) : 1;
    }

    // Now sort the accepted languages by their quality and create an array containing only the language codes in the correct order.
    if (is_array($arrAcceptedLanguages)) {
        arsort($arrAcceptedLanguages);
        $languageCodes = array_keys($arrAcceptedLanguages);
        if (is_array($languageCodes)) {
            reset($languageCodes);
            while (list ($languageCode, $quality) = each($languageCodes)) {
                $quality = substr($quality, 0, 5);
                $languages[$languageCode] = str_replace("-", "_", $quality);
            }
        }
    }
    return $languages;
}//getBrowserLanguages()


/**
 * Returns a localized string for the given key in the current language
 */
function getLL($string)
{
    global $LOCAL_LANG;

    if (!$string) {
        return '';
    }
    return $LOCAL_LANG[$_SESSION['lang']][$string];
}//getLL()


/**
 * Sends an email
 */
function ko_send_mail($from, $to, $subject, $body, $files = array(), $cc = array(), $bcc = array())
{
    try {
        $message = ko_prepare_mail($from, $to, $subject, $body, $files, $cc, $bcc);
    } catch (Exception $e) {
        if ($e->getCode() != 0) {
            koNotifier::Instance()->addTextError('Swiftmailer error ' . $e->getCode() . ': ' . $e->getMessage());
        } else {
            koNotifier::Instance()->addError($e->getCode(), '', array($e->getMessage()), 'swiftmailer');
        }
        return false;
    }
    return ko_process_mail($message);
} //ko_send_mail


function ko_send_html_mail($from, $to, $subject, $body, $files = array(), $cc = array(), $bcc = array())
{
    global $BASE_PATH;
    try {
        $message = ko_prepare_mail($from, $to, $subject, $body, $files, $cc, $bcc);
        $message->setContentType('text/html');
        require_once($BASE_PATH . 'inc/class.html2text.php');
        $html2text = new html2text($body);
        $plainText = $html2text->get_text();
        $message->addPart($plainText, 'text/plain');
    } catch (Exception $e) {
        if ($e->getCode() != 0) {
            koNotifier::Instance()->addTextError('Swiftmailer error ' . $e->getCode() . ': ' . $e->getMessage());
        } else {
            koNotifier::Instance()->addError($e->getCode(), '', array($e->getMessage()), 'swiftmailer');
        }
        return false;
    }
    return ko_process_mail($message);
} //ko_send_html_mail()


/**
 * Creates a SwiftMailerMessage object with the given data
 * This can either be used for further settings and sent with ko_process_mail()
 * Or it is called from ko_send_mail before this will call ko_process_mail() itself
 */
function ko_prepare_mail(
    $from = null,
    $to = null,
    $subject = null,
    $body = null,
    $files = array(),
    $cc = array(),
    $bcc = array()
)
{
    if (is_string($from)) {
        $from = array($from => $from);
    }

    if (is_string($to)) {
        $to = array($to => $to);
    }
    Swift_Preferences::getInstance()->setCharset('iso-8859-1');
    $message = Swift_Message::newInstance();
    $message->setBody($body)
        ->setSubject(utf8_decode($subject))
        ->setFrom($from)
        ->setTo($to)
        ->setCc($cc)
        ->setBcc($bcc);

    //Add Return-Path for error messages
    if (defined('EMAIL_SET_RETURN_PATH') && EMAIL_SET_RETURN_PATH == true) {
        $message->setReturnPath(ko_get_setting('info_email'));
    }
    $message->getHeaders()->addTextHeader('X-Mailer', getLL('kool'));

    foreach ($files as $filename => $displayName) {
        if (!file_exists($filename)) {
            continue;
        }
        $message->attach(
            Swift_Attachment::fromPath($filename)->setFilename($displayName)
        );
    }

    return $message;
} //ko_prepare_mail()


/**
 * Sets the transport method for SwiftMailer
 * Uses setting $MAIL_TRANSPORT from config/ko-config.php
 */
function ko_mail_transport()
{
    global $MAIL_TRANSPORT;

    switch (strtolower($MAIL_TRANSPORT['method'])) {
        case 'smtp':
            $transport = Swift_SmtpTransport::newInstance(
                $MAIL_TRANSPORT['host'] ? $MAIL_TRANSPORT['host'] : 'localhost',
                $MAIL_TRANSPORT['port'] ? $MAIL_TRANSPORT['port'] : '25',
                $MAIL_TRANSPORT['ssl'] ? 'ssl' : ($MAIL_TRANSPORT['tls'] ? 'tls' : '')
            );
            if ($MAIL_TRANSPORT['auth_user'] && $MAIL_TRANSPORT['auth_pass']) {
                $transport->setUsername($MAIL_TRANSPORT['auth_user']);
                $transport->setPassword($MAIL_TRANSPORT['auth_pass']);
            }
            break;

        case 'mail':
            $transport = Swift_MailTransport::newInstance();
            break;

        default:
            $transport = Swift_SendmailTransport::newInstance();
    }

    return $transport;
} //ko_mail_transport()


/**
 * Takes SwiftMessage and sends it using a SwiftTransport from ko_mail_transport()
 * @param Swift_Message $msg Message object created using ko_prepare_mail()
 */
function ko_process_mail(Swift_Message $msg)
{
    if (defined('ALLOW_SEND_EMAIL') && ALLOW_SEND_EMAIL === false) {
        return false;
    }

    if (defined('DEBUG_EMAIL') && DEBUG_EMAIL === true) {
        ko_echo_mail($msg);
        return true;
    }

    try {
        $transport = ko_mail_transport();
        // Create the Mailer using your created Transport
        $mailer = Swift_Mailer::newInstance($transport);
        $sent = $mailer->send($msg, $failures);
    } catch (Exception $e) {
        if ($e->getCode() != 0) {
            koNotifier::Instance()->addTextError('Swiftmailer error ' . $e->getCode() . ': ' . $e->getMessage());
        } else {
            koNotifier::Instance()->addError($e->getCode(), '', array($e->getMessage()), 'swiftmailer');
        }
        return false;
    }

    if (!$sent) {
        koNotifier::Instance()->addTextError('Swiftmailer error, could not send mail to the following addresses: ' . implode(',',
                $failures) . ';');
        return false;
    }

    return true;
} //ko_process_mail()


/**
 * Create debug output for email
 */
function ko_echo_mail(Swift_Message $message)
{
    global $BASE_PATH;

    print '<h2>Email sent</h2>';
    print '<b>' . ko_html(trim($message->getHeaders()->get('From'))) . '</b><br />';
    print '<b>' . ko_html(trim($message->getHeaders()->get('To'))) . '</b><br />';
    print '<b>' . ko_html(trim($message->getHeaders()->get('Cc'))) . '</b><br />';
    print '<b>' . ko_html(trim($message->getHeaders()->get('Bcc'))) . '</b><br />';
    print '<b>Subject: ' . ko_html($message->getSubject()) . '</b><br />';
    print '<b>Attachments: </b><ul>';

    foreach ($message->getChildren() as $child) {
        /** @var Swift_Attachment $child */
        if (!is_a($child, 'Swift_Attachment')) {
            continue;
        }
        $dirs = array(
            'download' . DIRECTORY_SEPARATOR . 'word',
            'download' . DIRECTORY_SEPARATOR . 'excel',
            'download' . DIRECTORY_SEPARATOR . 'pdf',
        );
        $link = null;
        foreach ($dirs as $dir) {
            $fullPath = $BASE_PATH . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $child->getFilename();
            if (file_exists($fullPath)) {
                $link = $dir . DIRECTORY_SEPARATOR . $child->getFilename();
            }
        }
        if ($link == null) {
            print '<li>' . $child->getFilename() . '</li>';
        } else {
            print '<li><a href="' . $link . '">' . $child->getFilename() . '</a></li>';
        }
    }

    print '</ul>';
    print '<hr>' . nl2br($message->getBody()) . '<hr>';
} //ko_echo_mail()


/**
 * DEPRECATED FUNCTION
 * Old function for sending email. Use ko_send_mail instead which is based on SwiftMailer.
 */
function ko_send_email($to, $subject, $message, $headers)
{
    if (defined('ALLOW_SEND_EMAIL') && ALLOW_SEND_EMAIL == false) {
        return false;
    }

    //Check for Reply-To header
    if (isset($headers["From"]) && !isset($headers["Reply-To"])) {
        $headers["Reply-To"] = $headers["From"];
    }

    $mail_headers = null;
    foreach ($headers as $k => $v) {
        $mail_headers[] = trim($k) . ": " . trim($v);
    }
    //Add Return-Path for error messages
    if (defined('EMAIL_SET_RETURN_PATH') && EMAIL_SET_RETURN_PATH == true) {
        $mail_headers[] = 'Return-Path: <' . ko_get_setting('info_email') . '>';
    }
    $mail_headers[] = 'X-Mailer: ' . getLL('kool');

    if (defined('DEBUG_EMAIL') && DEBUG_EMAIL) {
        print '<h2>Email sent</h2>';
        foreach ($mail_headers as $h) {
            print ko_html($h) . '<br />';
        }
        print '<b>To: ' . ko_html($to) . '</b><br />';
        print '<b>Subject: ' . ko_html($subject) . '</b><br />';
        print '<hr>' . nl2br($message) . '<hr>';
        return true;
    } else {
        $return_path = defined('EMAIL_SET_RETURN_PATH') && EMAIL_SET_RETURN_PATH == true ? '-f' . ko_get_setting('info_email') : '';
        $sent = mail($to, $subject, $message, implode("\n", $mail_headers), $return_path);
        return $sent;
    }
}//ko_send_email()


function ko_die($msg)
{
    print '<div style="border: 2px solid; padding: 10px; background: #3282be; color: white; font-weight: 900;">' . $msg . '</div>';
    exit;
}//ko_die()


function ko_round05($amount)
{
    $value = (floor(20 * $amount + 0.5) / 20);
    return $value;
}//ko_round05()


function ko_guess_date(&$v, $mode = "first")
{
    if (!$v) {
        return $v;
    }
    $r = "";

    $v = str_replace("-", ".", $v);
    $v = str_replace("/", ".", $v);
    $parts = explode(".", $v);
    if (sizeof($parts) == 3) {
        //TODO: first value could also be month (USA)!
        if ($parts[0] > 31) {  //assume sql date
            $r = intval($parts[0]) . "-" . intval($parts[1]) . "-" . intval($parts[2]);
        } else {  //assume date dd.mm.yyyy
            $r = intval($parts[2]) . "-" . intval($parts[1]) . "-" . intval($parts[0]);
        }
    } else {
        if (sizeof($parts) == 2) {
            $r = intval($parts[1]) . "-" . intval($parts[0]) . ($mode == "first" ? "-01" : "-31");
        } else {  //only one value --> year
            if ($v < 1900) {
                if ($v < 20) {
                    $v += 2000;
                } else {
                    $v += 1900;
                }
            }
            $r = intval($v) . ($mode == "first" ? "-01-01" : "-12-31");
        }
    }

    $v = strftime('%Y-%m-%d', strtotime($r));
}//ko_guess_date()


function ko_bar_chart($data, $legend, $mode = "", $total_width = 600)
{
    //find max value
    $max = 0;
    foreach ($data as $value) {
        $max = max($value, $max);
    }
    //find width of values
    $num_data = sizeof($data);
    $width1 = round($total_width / $num_data, 0);
    $width2 = round(0.75 * $total_width / $num_data, 0);
    //build table
    $c = '<table style="border:1px solid #aaa;" cellpadding="0" cellspacing="0"><tr height="100">';
    foreach ($data as $value) {
        if ($mode == "log") {
            $value = $value == 1 ? 1.5 : $value;
            $height = floor(log($value) / log($max) * 100);
            $value = $value == 1.5 ? 1 : $value;
        } else {
            $height = $max == 0 ? 0 : floor($value / $max * 100);
        }
        $c .= '<td align="center" valign="bottom" style="height:100px; width:' . $width1 . 'px;">';
        $c .= '<div style="text-align:center; color: white; width:' . $width2 . 'px; height:' . $height . 'px; background-color:#9abdea;">' . $value . '</div>';
        $c .= '</td>';
    }
    $c .= '</tr><tr>';
    foreach ($legend as $value) {
        $c .= '<td align="center">' . $value . '</td>';
    }
    $c .= '</tr></table><br />';

    return $c;
}//ko_bar_chart()


function ko_truncate($s, $l, $l2 = 0, $add = "..")
{
    if (strlen($s) <= $l) {
        return $s;
    } else {
        return substr($s, 0, $l - $l2) . $add . ($l2 > 0 ? substr($s, -$l2) : "");
    }
}//ko_truncate()


/**
 * Read from a URL and return the content as string.
 * It first with file_get_contents if this is allowed, fallback method is with cURL
 *
 * @param $url string The URL to fetch
 * @param $to int Timeout in seconds
 */
function ko_fetch_url($url, $to = 3)
{
    //Only use file_get_contents on the url if allow_url_fopen is set
    if (ini_get('allow_url_fopen')) {
        //TODO: Timeout does not seem to work...
        //ini_set('default_socket_timeout', $to);
        //$cxt = stream_context_create(array('http' => array('header'=>'Connection: close', 'timeout' => $to)));
        //return file_get_contents($url, FALSE, $ctx);
        return @file_get_contents($url);
    } else {
        //Otherwise use cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, '');  //Don't allow gzip or other compressions
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  //Follow 301 redirects
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $to);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}//ko_fetch_url()


function ko_redirect_after_login()
{
    global $MODULES, $BASE_URL, $ko_menu_akt;

    if (ko_get_userpref($_SESSION['ses_userid'], 'default_module') && !in_array($ko_menu_akt,
            array('consensus', 'updater'))
    ) {
        $m = ko_get_userpref($_SESSION['ses_userid'], 'default_module');
        if (in_array($m, $MODULES) && ko_module_installed($m)) {
            $action = ko_get_userpref($_SESSION['ses_userid'], 'default_view_' . $m);
            if (!$action) {
                $action = ko_get_setting('default_view_' . $m);
            }

            $loc = $BASE_URL . $m . "/index.php?action=$action";
            header('Location: ' . $loc);
            exit;
        }
    }
}//ko_redirect_after_login()


/**
 * Checks for constant FORCE_SSL and redirects to SSL enabled $BASE_URL
 */
function ko_check_ssl()
{
    global $BASE_URL;
    if (FORCE_SSL === true && (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off')) {
        //Only redirect if https URL is set in BASE_URL
        if (strtolower(substr($BASE_URL, 0, 5)) == 'https') {
            header('Location: ' . $BASE_URL, true, 301);
            exit;
        }
    }
}//ko_check_ssl()


function ko_check_login()
{
    global $ko_path, $LANGS;

    $do_guest = true;
    $reinit = false;

    //Login through sso (only from TYPO3 so far)
    if (ALLOW_SSO && KOOL_ENCRYPTION_KEY && $_GET["sso"] && $_GET["sig"]) {
        $ssoError = false;
        //Decrypt SSO data
        require($ko_path . "inc/class.mcrypt.php");
        $crypt = new mcrypt("aes");
        $crypt->setKey(KOOL_ENCRYPTION_KEY);
        list($kool_user, $timestamp, $ssoID, $user) = explode("@@@", $crypt->decrypt(base64_decode($_GET["sig"])));
        $kool_user = trim(format_userinput($kool_user, "js"));
        $timestamp = trim($timestamp);
        $ssoID = trim($ssoID);
        $user = trim($user);
        if (!$kool_user || (int)$timestamp < (int)time() || strlen($ssoID) != 32) {
            $ssoError = true;
        }
        //Check for unique ssoID
        $usedID = db_get_count("ko_log", "id", "AND `type` = 'singlesignon' AND `comment` REGEXP '$ssoID$'");
        if ($usedID > 0) {
            $ssoError = true;
        }

        //Check for valid user and log in
        $row = db_select_data("ko_admin", "WHERE login = '$kool_user'", "*", "", "", true);
        //Don't allow ko_guest or root
        if (!$ssoError && $row["id"] && !$row["disabled"] && $kool_user != "ko_guest" && $kool_user != "root") {
            $_SESSION["ses_username"] = $kool_user;
            $_SESSION["ses_userid"] = $row["id"];
            ko_log('singlesignon', $user . ' from ' . format_userinput($_GET['sso'], 'alphanum') . ': ' . $ssoID);
            ko_log("login", $_SESSION["ses_username"] . " from " . ko_get_user_ip() . " via SSO");

            //Last-Login speichern
            $_SESSION["last_login"] = ko_get_last_login($_SESSION["ses_userid"]);
            db_update_data("ko_admin", "WHERE `id` = '" . $_SESSION["ses_userid"] . "'",
                array("last_login" => date("Y-m-d H:i:s")));

            //select language from userpref, if set
            //Use language from userprefs
            $user_lang = ko_get_userpref($_SESSION["ses_userid"], "lang");
            if ($user_lang != "" && in_array($user_lang, $LANGS)) {
                $_SESSION["lang"] = $user_lang;
                include($ko_path . "inc/lang.inc");
            }

            //Reread user settings
            ko_init();
            //Clear all access data read so far. Will be reread next time if not set
            unset($access);

            $do_guest = false;
            $reinit = true;
        }//if(valid_login)
    }//if(sso)


    //Logout
    if ($_GET['action'] == "logout" && ($_SESSION["ses_username"] != "" && $_SESSION["ses_username"] != "ko_guest")) {
        ko_log("logout", $_SESSION["ses_userid"] . ": " . $_SESSION["ses_username"]);

        //Delete old session
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'],
                $params['httponly']);
        }
        session_destroy();

        include("inc/session.inc");
        $_SESSION = array();
        $_SESSION['ses_userid'] = ko_get_guest_id();
        $_SESSION['ses_username'] = 'ko_guest';

        $reinit = true;
    }

    //Login
    if ($_POST['Login'] && (!$_SESSION['ses_username'] || $_SESSION['ses_username'] == 'ko_guest')) {
        $login = db_select_data('ko_admin',
            "WHERE MD5(`login`) = '" . md5($_POST['username']) . "' AND `password` = '" . md5($_POST['password']) . "'",
            '*', '', '', true);
        if ($login['id'] > 0 && $login['login'] == $_POST['username']) {  //Valid login
            //Create new session id after login (to prevent session fixation)
            session_regenerate_id(true);
            //Empty session data so settings from ko_guest will not be used for logged in user
            $_SESSION = array();

            $_SESSION['ses_username'] = $login['login'];
            $_SESSION['ses_userid'] = $login['id'];
            ko_log('login', $_SESSION['ses_username'] . ' from ' . ko_get_user_ip());

            //Read and reset last login
            $_SESSION['last_login'] = ko_get_last_login($_SESSION['ses_userid']);
            db_update_data('ko_admin', "WHERE `id` = '" . $_SESSION['ses_userid'] . "'",
                array('last_login' => date('Y-m-d H:i:s')));

            $do_guest = false;
            $reinit = true;

            hook_action_handler_inline('login_success');
        } else {  //Wrong login
            ko_log('loginfailed',
                "Username: '" . format_userinput($_POST['username'], 'text') . "' from " . ko_get_user_ip());
            koNotifier::Instance()->addError(1);
            return false;
        }
    }//if(POST[login])


    if ($reinit) {
        unset($GLOBALS['kOOL']);
        ko_init();

        //Select language from userpref, if set
        $user_lang = ko_get_userpref($_SESSION['ses_userid'], 'lang');
        if (!$user_lang) {
            $user_lang = $LANGS[0];
        }
        if ($user_lang != '' && in_array($user_lang, $LANGS)) {
            $_SESSION['lang'] = $user_lang;
            include($ko_path . 'inc/lang.inc');
        }

        //Redirect to default page (if set)
        ko_redirect_after_login();
    }

}//ko_check_login()


/**
 * Stopwatch
 */
function sw($do = "", $tag = "")
{
    global $sw, $sws;

    list($usec, $sec) = explode(" ", microtime());
    $time = ((float)$usec + (float)$sec);

    switch ($do) {
        case "init":
            $sw = $time;
            break;

        case "tag":
            $sws[] = array("tag" => $tag, "value" => ($time - $sw));
            break;

        case "print":
            print "\n<!--\n";
            foreach ($sws as $s) {
                print ($s["tag"] ? '"' . $s["tag"] . '"' : "") . ': ' . $s["value"] . "\n";
            }
            print "\n-->\n";
            break;

        case "printout":
            print '<br /><hr width="100%" /><b>Time:</b><br />';
            foreach ($sws as $s) {
                print ($s["tag"] ? '"' . $s["tag"] . '"' : "") . ': ' . $s["value"] . "<br />";
            }
            break;

        default:
            return $time;
            break;
    }//switch(do)
}


/**
 * Debug-Print
 */
function print_d($array)
{
    print '<pre>';
    print_r($array);
    print '</pre>';
}//print_d()


/**
 * Testing function used for automated testing
 *
 * @param $fcn string Name of the function where this is called from (Usually __FUNCTION__)
 * @param $args array Array of arguments given to original function ($fcn). Usually func_get_args()
 * @param &$return mixed Return value of test function (kotest_TESTCASE_FCN()).
 * @returns boolean TRUE if test function has been found, FALSE otherwise.
 */
function ko_test($fcn, $args, &$return)
{
    global $TESTCASE;

    if (KOOLTEST === true && $TESTCASE != '' && function_exists('kotest_' . $TESTCASE . '_' . $fcn)) {
        $return = call_user_func_array('kotest_' . $TESTCASE . '_' . $fcn, $args);
        return true;
    }
    return false;
}//ko_test()


function ko_update_ko_config($mode, $data)
{
    global $ko_path;

    $start = $ignore = $found = false;
    //Open config file
    $config_file = $ko_path . "config/ko-config.php";
    $fp = @fopen($config_file, "r");
    if ($fp) {
        //Go through all the lines
        while (!feof($fp)) {
            $line = fgets($fp);
            switch ($mode) {
                case "plugins":
                    if (!$start && substr(trim($line), 0, 8) == '$PLUGINS') {
                        $found = true;
                        $start = true;
                        $ignore = true;
                    } else {
                        if ($start == true && trim($line) == ');') {
                            $start = false;
                            $ignore = false;
                            $line = $data;
                        }
                    }
                    break;  //plugins

                case "db":
                    if (!$start && substr(trim($line), 0, 11) == '$mysql_user') {
                        $found = true;
                        $start = true;
                        $ignore = true;
                    } else {
                        if ($start == true && substr(trim($line), 0, 9) == '$mysql_db') {
                            $start = false;
                            $ignore = false;
                            $line = $data;
                        }
                    }
                    break;  //db

                case "html_title":
                    $found = true;
                    if (substr(trim($line), 0, 11) == '$HTML_TITLE') {
                        $line = $data;
                    }
                    break;

                case "base_url":
                    $found = true;
                    if (substr(trim($line), 0, 9) == '$BASE_URL') {
                        $line = $data;
                    }
                    break;

                case "base_path":
                    $found = true;
                    if (substr(trim($line), 0, 10) == '$BASE_PATH') {
                        $line = $data;
                    }
                    break;

                case "modules":
                    $found = true;
                    if (substr(trim($line), 0, 8) == '$MODULES') {
                        $line = $data;
                    }
                    break;

                case "web_langs":
                    $found = true;
                    if (substr(trim($line), 0, 10) == '$WEB_LANGS') {
                        $line = $data;
                    }
                    break;

                case "get_lang_from_browser":
                    $found = true;
                    if (substr(trim($line), 0, 22) == '$GET_LANG_FROM_BROWSER') {
                        $line = $data;
                    }
                    break;

                case "sms":
                    $found = true;
                    if (substr(trim($line), 0, 14) == '$SMS_PARAMETER') {
                        $line = $data;
                    }
                    break;  //sms

                case "mail_transport":
                    $found = true;
                    if (substr(trim($line), 0, 15) == '$MAIL_TRANSPORT') {
                        $line = $data;
                    }
                    break;  //sms

                case "warranty":
                    if (!$start && substr(trim($line), 0, 23) == "@define('WARRANTY_GIVER") {
                        $found = true;
                        $start = true;
                        $ignore = true;
                    } else {
                        if ($start == true && substr(trim($line), 0, 21) == "@define('WARRANTY_URL") {
                            $start = false;
                            $ignore = false;
                            $line = $data;
                        }
                    }
                    break;  //warranty

                case "webfolders":
                    $found = true;
                    if (substr(trim($line), 0, 19) == '@define("WEBFOLDERS') {
                        $line = $data;
                    }
                    break;
            }//switch(mode)

            //Check whether the data could be updated before the last line
            if (trim($line) == '?>') {
                if (!$found) {  //else insert the data right before the end
                    $new_config .= "\n" . $data;
                }
            }

            //Build new config-file
            if (!$ignore) {
                $new_config .= $line;
            }

        }//while(!feof(fp))
        fclose($fp);
    } else {
        return false;
    }

    //Write new config
    $fp = @fopen($config_file, "w");
    fputs($fp, $new_config);
    fclose($fp);
    return true;
}//ko_update_ko-config()


function ko_fontsize_to_mm($fontSize)
{
    return $fontSize / 2.8457;
}

function ko_mm_to_fontsize($mm)
{
    return $mm * 2.8457;
}

function ko_explode_trim_implode($s, $separator = ',')
{
    $result = explode($separator, $s);
    foreach ($result as $k => $r) {
        $result[$k] = trim($r);
    }
    return implode($separator, $result);
}

function utf8_decode_array(&$value, $key)
{
    $value = utf8_decode($value);
}

function urldecode_array(&$value, $key)
{
    $value = urldecode($value);
}

/**
 * returns $date2 - $date1
 *
 * @param $format 'd' -> days,
 * @param $date1
 * @param $date2
 */
function ko_get_time_diff($format, $date1, $date2)
{
    $date1 = date_create($date1);
    $date2 = date_create($date2);
    $diff = date_diff($date1, $date2);
    switch ($format) {
        case 'd':
            $result = $diff->format('%R%a');
            break;
    }
    return $result;
}


function kota_reorder_groups($group)
{
    $group[0]['titel'] = getLL('kota_fieldgroup_general');
    foreach ($group as $groupKey => $thisGroup) {
        foreach ($thisGroup['row'] as $rowKey => $row) {
            foreach ($row['inputs'] as $inputKey => $input) {
                $input['half'] = 0;
                if (isset($input['group'])) {
                    $newKey = getLL($input['group']);
                    if (isset($input['PLUS'])) {
                        $input['half'] = 1;
                        $row['inputs'][1]['half'] = 2;
                    }
                    $group[$newKey]['row'][] = [
                        'inputs' => [
                            0 => $input,
                            1 => ($input['PLUS'] ? $row['inputs'][1] : null),
                        ]
                    ];
                    if (!isset($group[$newKey]['titel'])) $group[$newKey]['titel'] = $newKey;
                    unset($group[$groupKey]['row'][$rowKey]['inputs'][$inputKey]);
                    if (isset($input['PLUS'])) {
                        unset($group[$groupKey]['row'][$rowKey]['inputs'][1]);
                    }
                }
            }
            if (count($row['inputs']) == 0) unset($group[$groupKey]['row'][$rowKey]);
        }
    }
    // sort alphabetically
    $zGroup = $group[0];
    unset ($group[0]);
    ksort($group);
    return array_merge([0 => $zGroup], $group);
}