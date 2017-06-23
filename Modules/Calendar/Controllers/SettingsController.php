<?php

namespace Peregrinus\Flockr\Calendar\Controllers;

class SettingsController extends \Peregrinus\Flockr\Core\AbstractController {

    protected function initializeController() {
        ko_include_kota(array('ko_event', 'ko_eventgruppen', 'ko_reservation', 'ko_pdf_layout', 'ko_reminder'));
        $hooks = hook_include_main("daten");
        if(sizeof($hooks) > 0) foreach($hooks as $hook) include_once($hook);
    }

    public function dialogAction() {
        if($access['daten']['MAX'] < 1) continue;
        $_SESSION['show_back'] = $_SESSION['show'];
        $_SESSION['show'] = 'daten_settings';

    }

    public function submitAction() {
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