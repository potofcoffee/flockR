<?php

namespace Peregrinus\Flockr\Calendar\Controllers;

class EventController extends \Peregrinus\Flockr\Core\AbstractController {

    protected function initializeController() {
        ko_include_kota(array('ko_event', 'ko_eventgruppen', 'ko_reservation', 'ko_pdf_layout', 'ko_reminder'));
    }

    public function newAction() {
        //Get new date and time from GET param dayDate
        if(isset($_GET['dayDate'])) $start_stamp = $end_stamp = strtotime($_GET['dayDate']);
        if(isset($_GET['endDate'])) $end_stamp = strtotime($_GET['endDate']);
        if(!$start_stamp) $start_stamp = $end_stamp = time();

        $new_date_start = strftime('%Y-%m-%d', $start_stamp);
        $new_date_end   = strftime('%Y-%m-%d', $end_stamp);
        $new_time_start = strftime("%H:%M", $start_stamp);
        if($new_time_start == '00:00') {  //All day
            $new_time_end = '';
        } else {  //time given
            $new_time_end = $end_stamp != $start_stamp ? strftime('%H:%M', $end_stamp) : strftime('%H:00', (int)$end_stamp+3600);
        }

        kota_assign_values("ko_event", array("startdatum" => $new_date_start));
        if($new_date_start != $new_date_end) kota_assign_values("ko_event", array("enddatum" => $new_date_end));

        $new_event_data = array("start_time" => $new_time_start, "end_time" => $new_time_end);

        if($_SESSION['show'] != 'neuer_termin') $_SESSION['show_back'] = $_SESSION['show'];
        $_SESSION["show"]= "neuer_termin";
        $onload_code = "form_set_first_input();".$onload_code;

        $dataStructure = $this->module->getRepositoryConfig();
        $this->view->assign('dataStructure', $dataStructure);


    }

    public function editAction() {

    }

    public function listAction()
    {
        //if($access['daten']['MAX'] < 1) continue;

    }

    public function setFilterStartAction() {
        $_SESSION['filter_start'] = 'today';
        $_SESSION['filter_ende'] = 'immer';
    }

    public function submitFilterAction() {
        if(FALSE === ($_SESSION["filter_start"] = format_userinput($_POST["sel_filter_start"], "alphanum+", TRUE, 5))) {
            trigger_error("Not allowed filterstart: ".$_POST["sel_filter_start"], E_USER_ERROR);
        }
        if(FALSE === ($_SESSION["filter_ende"] = format_userinput($_POST["sel_filter_ende"], "alphanum+", TRUE, 5))) {
            trigger_error("Not allowed filterend: ".$_POST["sel_filter_ende"], E_USER_ERROR);
        }
    }

    public function unsetPermFilterAction() {
        if($access['daten']['MAX'] > 3) {
            ko_set_setting("daten_perm_filter_start", "");
            ko_set_setting("daten_perm_filter_ende", "");
        }
    }

    public function setPermFilterAction() {
        if($access['daten']['MAX'] > 3) {
            get_heute($tag, $monat, $jahr);
            if($_SESSION['filter_start'] != 'immer') {
                if($_SESSION['filter_start'] == 'today') {
                    $pfs = strftime('%Y-%m-%d', time());
                } else {
                    addmonth($monat, $jahr, $_SESSION['filter_start']);
                    $pfs = strftime('%Y-%m-%d', mktime(1,1,1, $monat, 1, $jahr));
                }
            } else $pfs = '';

            get_heute($tag, $monat, $jahr);
            if($_SESSION['filter_ende'] != 'immer') {
                if($_SESSION['filter_ende'] == 'today') {
                    $pfe = strftime('%Y-%m-%d', time());
                } else {
                    addmonth($monat, $jahr, ($_SESSION['filter_ende']+1));
                    $pfe = strftime('%Y-%m-%d', mktime(1,1,1, $monat, 0, $jahr));  //0 gleich letzter Tag des Vormonates
                }
            } else $pfe = '';

            ko_set_setting('daten_perm_filter_start', $pfs);
            ko_set_setting('daten_perm_filter_ende', $pfe);
        }
    }

    public function deleteAction() {
        if(FALSE === ($del_id = format_userinput($_POST['id'], 'uint', TRUE))) {
            trigger_error('Not allowed del_id: '.$_POST['id'], E_USER_ERROR);
        }
        $event = db_select_data('ko_event', "WHERE `id` = '$del_id'", '*', '', '', TRUE);
        if($access['daten'][$event['eventgruppen_id']] < 2) continue;

        $mode = do_del_termin($del_id);
        if($mode == 'del') $notifier->addInfo(3, $do_action);
        else $notifier->addInfo(10, $do_action);
    }

    public function deleteSelectedAction() {
        foreach($_POST["chk"] as $c_i => $c) {
            if($c) {
                if(FALSE === ($del_id = format_userinput($c_i, "uint", TRUE))) {
                    trigger_error("Not allowed del_id (multiple): ".$c_i, E_USER_ERROR);
                }
                $event = db_select_data('ko_event', "WHERE `id` = '$del_id'", '*', '', '', TRUE);
                if($access['daten'][$event['eventgruppen_id']] > 1) do_del_termin($del_id);
            }
        }
        $notifier->addInfo(7, $do_action);

    }


    public function deleteGroupAction() {
        if(FALSE === ($del_id = format_userinput($_POST["id"], "uint", TRUE))) {
            trigger_error("Not allowed del_id: ".$_POST["id"], E_USER_ERROR);
        }

        //Check for ALL rights to be able to delete
        if($access['daten']['ALL'] < 3) continue;

        //Log-Meldung erstellen
        ko_get_eventgruppe_by_id($del_id, $del_eventgruppe);
        $log_message  = $del_eventgruppe["name"].": ".substr($del_eventgruppe["startzeit"],0,-3)."-".substr($del_eventgruppe["endzeit"],0,-3);
        $log_message .= " in ".$del_eventgruppe["room"].' "'.$del_eventgruppe["beschreibung"].'", '.$del_eventgruppe["farbe"];

        //Gruppe l�schen
        db_delete_data("ko_eventgruppen", "WHERE `id` = '$del_id'");
        ko_log("delete_termingruppe", $log_message);

        //Alle Termine dieser Termingruppe l�schen (inkl. zugeh�riger Reservationen)
        $rows = db_select_data("ko_event", "WHERE `eventgruppen_id` = '$del_id'");
        foreach($rows as $row) {
            do_del_termin(format_userinput($row["id"], "uint"));
        }

        //Check for empty calendars
        ko_delete_empty_calendars();

        //Rota
        if(in_array('rota', $MODULES)) {
            //Delete reference to this event group for weekly teams
            db_update_data('ko_rota_teams', "WHERE `export_eg` = '$del_id'", array('export_eg' => '0'));

            //Delete event group from rota teams
            $teams = db_select_data('ko_rota_teams', "WHERE `eg_id` REGEXP '(^|,)$del_id(,|$)'", '*', 'ORDER BY name ASC');
            if(sizeof($teams) > 0) {
                foreach($teams as $tid => $team) {
                    $new_eg_id = explode(',', $team['eg_id']);
                    foreach($new_eg_id as $k => $v) {
                        if($v == $del_id) unset($new_eg_id[$k]);
                    }
                    db_update_data('ko_rota_teams', "WHERE `id` = '$tid'", array('eg_id' => implode(',', $new_eg_id)));
                }
            }
        }

        $notifier->addInfo(4, $do_action);
    }

    public function settingsAction() {
        if($access['daten']['MAX'] < 1) continue;
        $_SESSION['show_back'] = $_SESSION['show'];
        $_SESSION['show'] = 'daten_settings';

    }

    public function submitSettingsAction() {
        if($access['daten']['MAX'] < 1) continue;

        ko_save_userpref($_SESSION['ses_userid'], 'default_view_daten', format_userinput($_POST['sel_daten'], 'js'));
        ko_save_userpref($_SESSION['ses_userid'], 'show_limit_daten', format_userinput($_POST['txt_limit_daten'], 'uint'));
        ko_save_userpref($_SESSION['ses_userid'], 'cal_jahr_num', format_userinput($_POST['sel_cal_jahr_num'], 'uint'));
        ko_save_userpref($_SESSION['ses_userid'], 'cal_woche_start', format_userinput($_POST['txt_cal_woche_start'], 'uint'));
        ko_save_userpref($_SESSION['ses_userid'], 'cal_woche_end', format_userinput($_POST['txt_cal_woche_end'], 'uint'));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_monthly_title', format_userinput($_POST['sel_monthly_title'], 'js', FALSE));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_title_length', format_userinput($_POST['txt_title_length'], 'uint', FALSE));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_pdf_show_time', format_userinput($_POST['sel_pdf_show_time'], 'uint', FALSE, 1));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_pdf_use_shortname', format_userinput($_POST['sel_pdf_use_shortname'], 'uint', FALSE, 1));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_export_show_legend', format_userinput($_POST['sel_export_show_legend'], 'uint', FALSE, 1));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_pdf_week_start', format_userinput($_POST['sel_pdf_week_start'], 'uint', FALSE, 1));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_pdf_week_length', format_userinput($_POST['sel_pdf_week_length'], 'uint', FALSE, 2));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_mark_sunday', format_userinput($_POST['sel_mark_sunday'], 'uint', FALSE, 1));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_no_cals_in_itemlist', format_userinput($_POST['sel_no_cals_in_itemlist'], 'uint', FALSE, 1));
        ko_save_userpref($_SESSION['ses_userid'], 'show_birthdays', format_userinput($_POST['sel_show_birthdays'], 'uint', FALSE, 1));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_show_res_in_tooltip', format_userinput($_POST['sel_show_res_in_tooltip'], 'uint', FALSE, 1));
        ko_save_userpref($_SESSION['ses_userid'], 'daten_rooms_only_future', format_userinput($_POST['chk_daten_rooms_only_future'], 'uint', FALSE, 1));
        if($_SESSION['ses_userid'] != ko_get_guest_id()) {
            ko_save_userpref($_SESSION['ses_userid'], 'daten_ical_deadline', format_userinput($_POST['sel_ical_deadline'], 'int', FALSE));
            if($access['daten']['MAX'] > 3) {
                ko_save_userpref($_SESSION['ses_userid'], 'do_mod_email_for_edit_daten', format_userinput($_POST['sel_do_mod_email_for_edit_daten'], 'uint', FALSE, 1));
            }
        }
        ko_save_userpref($_SESSION['ses_userid'], 'daten_ical_description_fields', format_userinput($_POST['sel_ical_description_fields'], 'alphanumlist'));

        if($access['daten']['MAX'] > 3) {
            if(in_array('groups', $MODULES)) {
                ko_set_setting('daten_gs_pid', format_userinput($_POST['sel_gs_pid'], 'uint'));
                ko_set_setting('daten_gs_role', format_userinput($_POST['sel_gs_role'], 'uint'));
                ko_set_setting('daten_gs_available_roles', format_userinput($_POST['sel_gs_available_roles'], 'alphanumlist'));
            }
            ko_set_setting('daten_show_mod_to_all', format_userinput($_POST['sel_show_mod_to_all'], 'uint'));
            ko_set_setting('daten_mod_exclude_fields', format_userinput($_POST['sel_mod_exclude_fields'], 'alphanumlist'));
            ko_set_setting('daten_mandatory', format_userinput($_POST['sel_mandatory'], 'alphanumlist'));
            ko_set_setting('daten_access_calendar', format_userinput($_POST['sel_calendar_access'], 'uint'));
            if ($_SESSION['ses_userid'] == ko_get_root_id()) {
                ko_set_setting('activate_event_program', format_userinput($_POST['sel_activate_event_program'], 'uint'));
            }
        }


        $_SESSION['show'] = ($_SESSION['show_back'] && in_array($_SESSION['show_back'], array_keys($DISABLE_SM['daten']))) ? $_SESSION['show_back'] : 'daten_settings';

    }
}