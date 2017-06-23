<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2003-2015 Renzo Lauper (renzo@churchtool.org)
 *  All rights reserved
 *
 *  This script is part of the kOOL project. The kOOL project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  kOOL is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

$ko_path = "../";
$ko_menu_akt = "ical";

include($ko_path . "inc/ko.inc");
include($ko_path . "daten/inc/daten.inc");
ko_include_kota(array('ko_event'));

//Include plugins
$hooks = hook_include_main('daten');
if (sizeof($hooks) > 0) foreach ($hooks as $hook) include_once($hook);


$mapping = array(';' => '\;', ',' => '\,', "\n" => "\\n\n ", "\r" => '');
define('CRLF', chr(10));


$auth = FALSE;
if (isset($_GET['ko_guest']) || isset($_GET['guest'])) { //Stay with guest user
} else if (isset($_GET['user'])) { //User hash given in URL
    $userhash = $_GET['user'];
    if (strlen($userhash) != 32) exit;
    for ($i = 0; $i < 32; $i++) {
        if (!in_array(substr($userhash, $i, 1), array(1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 'a', 'b', 'c', 'd', 'e', 'f'))) exit;
    }
    if (!defined('KOOL_ENCRYPTION_KEY') || trim(KOOL_ENCRYPTION_KEY) == '') exit;

    ko_get_logins($logins);
    foreach ($logins as $login) {
        if (md5($login['id'] . $login['password'] . KOOL_ENCRYPTION_KEY) == $userhash) {
            $auth = TRUE;
            $_SESSION['ses_username'] = $login['login'];
            $_SESSION['ses_userid'] = $login['id'];
            ko_init();
        }
    }
    unset($logins);
} else {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header("WWW-Authenticate: Basic realm=\"kOOL\"");
        header("HTTP/1.0 401 Unauthorized");
    } else {
        $user = format_userinput($_SERVER["PHP_AUTH_USER"], "text", TRUE, 32);
        $pw = md5($_SERVER["PHP_AUTH_PW"]);
        $result = mysql_query("SELECT id,login FROM ko_admin WHERE `login` = '$user' AND `password` = '$pw'");
        if (mysql_num_rows($result) == 1) {
            $row = mysql_fetch_assoc($result);
            if ($row["login"] == $user) {
                $auth = TRUE;
                $_SESSION["ses_username"] = $row["login"];
                $_SESSION["ses_userid"] = $row["id"];
                ko_init();
            }
        }
    }
}
if (!$auth) {
    //header("HTTP/1.0 404 Not Found");
    //exit;
    $_SESSION["ses_username"] = "ko_guest";
    $_SESSION["ses_userid"] = ko_get_guest_id();
    ko_init();
}


if (!ko_module_installed("daten")) {
    header("HTTP/1.0 404 Not Found");
}

//Get access rights
ko_get_access('daten', $_SESSION['ses_userid'], TRUE);

//Set event groups to be shown set by GET or preset named ical
$use_itemset = FALSE;
if (isset($_GET['egs'])) {  //use event groups given in URL
    foreach (explode(',', $_GET['egs']) as $eg) {
        if (substr($eg, 0, 1) == 'p') {  //Preset
            $presetid = format_userinput(substr($eg, 1), 'uint');
            if ($presetid) {
                $userpref = db_select_data('ko_userprefs', "WHERE `id` = '$presetid'", '*', '', '', TRUE);
                if ($userpref['type'] == 'daten_itemset' && ($userpref['user_id'] == '-1' || $userpref['user_id'] == $_SESSION['ses_userid'])) {
                    foreach (explode(',', $userpref['value']) as $eventgroup_id) {
                        if (!$eventgroup_id) continue;
                        $use_itemset[] = $eventgroup_id;
                    }
                }
            }
        } else if (substr($eg, 0, 1) == 'c') {  //Calendar
            $calid = format_userinput(substr($eg, 1), 'uint');
            if ($calid) {
                $calendar_egs = db_select_data('ko_eventgruppen', "WHERE `calendar_id` = '$calid'", 'id');
                foreach ($calendar_egs as $eventgroup) {
                    if (!$eventgroup['id']) continue;
                    $use_itemset[] = $eventgroup['id'];
                }
            }
        } else {  //Single event group
            $eg = intval($eg);
            if ($eg > 0) $use_itemset[] = $eg;
        }
    }
} else {  //Get ical preset for the logged in user
    $itemsets = ko_get_userpref($_SESSION['ses_userid'], '', 'daten_itemset');
    foreach ($itemsets as $itemset) {
        if (strtolower($itemset['key']) == 'ical') {
            $use_itemset = explode(',', $itemset['value']);
        }
    }
}

//get all event groups and check access rights
ko_get_eventgruppen($eventgroups, '', "AND `type` != '1'");
$use_eg = array();
foreach ($eventgroups as $eg) {
    if ($use_itemset) {
        if (in_array($eg["id"], $use_itemset) && ($access['daten']['ALL'] > 0 || $access['daten'][$eg['id']] > 0)) $use_eg[] = $eg["id"];
    } else {
        if ($access['daten']['ALL'] > 0 || $access['daten'][$eg['id']] > 0) $use_eg[] = $eg["id"];
    }
}
//Get setting of how far back to export events
$ical_deadline = ko_get_userpref($_SESSION['ses_userid'], 'daten_ical_deadline');
if ($ical_deadline >= 0) $ical_deadline = 'today';

//Set KOTA filter from GET
unset($_SESSION['kota_filter']['ko_event']);
foreach ($_GET as $k => $v) {
    if (substr($k, 0, 5) != 'kota_') continue;
    $key = substr($k, 5);
    //Check for valid KOTA field
    $ok = FALSE;
    foreach ($KOTA['ko_event']['_listview'] as $klv) {
        if ($klv['name'] === $key && $klv['filter'] === TRUE) $ok = TRUE;
    }
    if (!$ok) continue;

    $_SESSION['kota_filter']['ko_event'][$key] = urldecode($v);
}

//apply filter
apply_daten_filter($z_where, $z_limit, $ical_deadline, 'immer', $use_eg);
//get events
ko_get_events($events, $z_where, '', 'ko_event', 'ORDER BY startdatum ASC, startzeit ASC');

//build ical file in a string
$ical = "BEGIN:VCALENDAR" . CRLF;
$ical .= "VERSION:2.0" . CRLF;
$ical .= "CALSCALE:GREGORIAN" . CRLF;
$ical .= "METHOD:PUBLISH" . CRLF;
$ical .= 'PRODID:-//' . str_replace('/', '', $HTML_TITLE) . "//www.churchtool.org//DE" . CRLF;
$kota_done = FALSE;
foreach ($events as $event) {
    //url
    if ($event['url']) $url = $event['url'];
    else if ($eventgroups[$event['eventgruppen_id']]['url']) $url = $eventgroups[$event['eventgruppen_id']]['url'];
    else $url = '';
    //build ics string
    $ical .= "BEGIN:VEVENT" . CRLF;
    $ical .= 'DTSTAMP:' . strftime('%Y%m%dT%H%M%S', time()) . CRLF;
    if ($event['cdate'] != '0000-00-00 00:00:00') $ical .= 'CREATED:' . strftime('%Y%m%dT%H%M%S', strtotime($event['cdate'])) . CRLF;
    if ($event['last_change'] != '0000-00-00 00:00:00') $ical .= 'LAST-MODIFIED:' . strftime('%Y%m%dT%H%M%S', strtotime($event['last_change'])) . CRLF;
    $base_url = $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $BASE_URL;
    $ical .= 'UID:e' . $event['id'] . '@' . $base_url . CRLF;
    if (intval(str_replace(':', '', $event['startzeit'])) >= 240000) $event['startzeit'] = '23:59:00';
    if (intval(str_replace(':', '', $event['endzeit'])) >= 240000) $event['endzeit'] = '23:59:00';
    if ($event['startzeit'] == '00:00:00' && $event['endzeit'] == '00:00:00') {  //daily event
        $ical .= 'DTSTART;VALUE=DATE:' . strftime('%Y%m%d', strtotime($event['startdatum'])) . CRLF;
        $ical .= 'DTEND;VALUE=DATE:' . strftime('%Y%m%d', strtotime(add2date($event['enddatum'], 'tag', 1, TRUE))) . CRLF;
    } else if ($event['startzeit'] != '00:00:00' && $event['endzeit'] == '00:00:00') {  //No end time given so set it to midnight
        $ical .= 'DTSTART:' . date_convert_timezone(($event['startdatum'] . ' ' . $event['startzeit']), 'UTC') . CRLF;
        $ical .= 'DTEND:' . date_convert_timezone(($event['enddatum'] . ' 23:59:00'), 'UTC') . CRLF;
    } else {
        $ical .= 'DTSTART:' . date_convert_timezone(($event['startdatum'] . ' ' . $event['startzeit']), 'UTC') . CRLF;
        $ical .= 'DTEND:' . date_convert_timezone(($event['enddatum'] . ' ' . $event['endzeit']), 'UTC') . CRLF;
    }
    //Summary: Event group's name and event's title if given
    $titles = ko_daten_get_event_title($event, $eventgroups[$event['eventgruppen_id']], ko_get_userpref($_SESSION['ses_userid'], 'daten_monthly_title'));
    $ical .= 'SUMMARY:' . strtr(trim($titles['text']), $mapping) . CRLF;

    //Description: Use kommentar2 (internal comments) and optionally other fields
    $description = '';
    if ($_SESSION['ses_username'] != 'ko_guest') $description .= trim($event['kommentar2']);

    //Add other event fields
    $_desc_fields = ko_get_userpref($_SESSION['ses_userid'], 'daten_ical_description_fields');
    if ($_desc_fields != '') {
        $desc_fields = explode(',', $_desc_fields);
        if (!$kota_done) {
            ko_include_kota(array('ko_event'));
            $kota_done = TRUE;
        }
        $event2 = $event;
        //Set keys so kota_process_data() can e.g. process rotateam columns
        foreach ($desc_fields as $dk) {
            if (!$dk) continue;
            if (!isset($event2[$dk])) $event2[$dk] = '';
        }
        kota_process_data('ko_event', $event2, 'list', $log, $event2['id']);
        foreach ($desc_fields as $dk) {
            if ($dk && $event2[$dk]) $description .= "\n" . getLL('kota_ko_event_' . $dk) . ': ' . strip_tags($event2[$dk]);
        }
    }
    $ical .= 'DESCRIPTION:' . strtr(trim($description), $mapping) . CRLF;


    $ical .= 'LOCATION:' . strtr(trim($event['room']), $mapping) . CRLF;
    if ($url) $ical .= 'URL:' . $url . CRLF;
    $ical .= "END:VEVENT" . CRLF;
}
$ical .= "END:VCALENDAR" . CRLF;
unset($events);
unset($eventgroups);


//Set charset to utf-8, but not for google calendar (there seem to be problems with utf-8 for google as of 2010-08)
$charset = 'utf-8';
$ical = $ical;


//Output
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
header('Content-Type: text/calendar; charset=' . $charset, TRUE);
header('Content-Disposition: attachment; filename="kOOL.ics"');
header("Content-Length: " . strlen($ical));
print $ical;


//Clear session
session_destroy();
unset($_SESSION);
unset($GLOBALS['kOOL']);
?>
