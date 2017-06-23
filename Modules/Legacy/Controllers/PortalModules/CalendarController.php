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


namespace Peregrinus\Flockr\Legacy\Controllers\PortalModules;


use Peregrinus\Flockr\Core\AbstractController;

class CalendarController extends AbstractController
{

    public function indexAction() {
        global $ko_path, $smarty, $access;

        ko_get_access('daten');
        include($ko_path . "daten/inc/daten.inc");

        $egs = array();
        if($access['daten']['ALL'] > 0) {
            $z_where = '';
        } else {
            //Get all eventgroups, access check will be done in apply_daten_filter()
            ko_get_eventgruppen($egs, '', "AND `type` = '0'");
            apply_daten_filter($z_where, $z_limit, 'immer', 'immer', array_keys($egs));
        }

        $title_length = ko_get_userpref($_SESSION['ses_userid'], 'daten_title_length');
        $startstamp = mktime(1,1,1, date('m'), 1, date('Y'));
        $endstamp = mktime(1,1,1, (date('m') == 12 ? 1 : date('m')+1), 0, (date('m') == 12 ? date('Y')+1 : date('Y')));
        $z_where .= ' AND `enddatum` >= \''.strftime('%Y-%m-%d', $startstamp).'\' AND `startdatum` <= \''.strftime('%Y-%m-%d', $endstamp).'\'';
        ko_get_events($events, $z_where);

        $data = array();
        foreach($events as $event) {
            $content = array();
            $content['text'] = $event['eventgruppen_name'].($event['kommentar'] ? ': '.$event['kommentar'] : '');
            if(strlen($content['text']) > $title_length) $content['text'] = substr($content['text'], 0, $title_length).'..';

            if($event['startzeit'] == '00:00:00' && $event['endzeit'] == '00:00:00') {
                $content['zeit'] = getLL('time_all_day');
            } else {
                $content['zeit'] = substr($event['startzeit'], 0, -3).'-'.substr($event['endzeit'], 0, -3);
            }

            //Multiday events
            if($event['startdatum'] != $event['enddatum']) {
                $date = $event['startdatum'];
                while((int)str_replace('-', '', $date) <= (int)str_replace('-', '', $event['enddatum'])) {
                    if(substr($date, 5, 2) == date('m')) {
                        $data[(int)substr($date, -2)][] = $content;
                    }
                    $date = add2date($date, 'tag', 1, TRUE);
                }
            } else {
                $data[(int)substr($event['startdatum'], -2)][] = $content;
            }
        }//foreach(events)

        //Datums-Berechnungen
        //Start des Monats
        $startdate = date(date('Y')."-".date('m')."-01");
        $today = date("Y-m-d");
        $startofmonth = $date = $startdate;

        //Den letzten Tag dieses Monats finden
        $endofmonth = add2date($date, "monat", 1, TRUE);
        $endofmonth = add2date($endofmonth, "tag", -1, TRUE);
        //Ende der letzten Woche dieses Monats finden
        $enddate = date_find_next_sunday($endofmonth);
        //Start der ersten Woche dieses Monats finden
        $date = date_find_last_monday($date);

        //Table header
        $r  = '<table class="table fm-kalender" width="100%" cellspacing="0" border="1">';
        $r .= '<tr><td kalender_header>&nbsp;</td>';
        $tempdate = $date;
        for($i=0; $i<7; $i++) {
            $r .= '<td class="kalender_header">'.substr(strftime('%a', strtotime($tempdate)), 0, 1).'</td>';
            $tempdate = add2date($tempdate, 'tag', 1, TRUE);
        }
        $r .= '</tr>';

        $dayofweek = 0;
        $jsmap = array("\n" => ' ', "\r" => ' ', "'" => '', '"' => '');
        while((int)str_replace("-", "", $date) <= (int)str_replace("-", "", $enddate)) {
            if($dayofweek == 0) {
                $r .= '<tr>';
                //Add week number
                $r .= '<td class="kalender_weeks">'.strftime('%V', strtotime($date)).'</td>';
            }
            $class = $today == $date ? 'kalender_tag_aktiv' : 'kalender_tag';
            if(strftime('%m', strtotime($date)) == date('m')) {
                $tooltip = '';
                if(isset($data[substr($date, -2)])) {
                    foreach($data[substr($date, -2)] as $entry) {
                        $tooltip .= '<b>'.strtr($entry['text'], $jsmap).'</b><br />'.strtr($entry['zeit'], $jsmap).'<br />';
                    }
                    $ph = $pos == 'r' ? 'l' : 'r';
                    $r .= '<td class="'.$class.'" onmouseover="tooltip.show(\''.$tooltip.'\', \'\', \'b\', \''.$ph.'\');" onmouseout="tooltip.hide();">';
                    $r .= '<b>'.strftime('%d', strtotime($date)).'</b>';
                } else {
                    $r .= '<td class="'.$class.'">'.strftime('%d', strtotime($date));
                }
            } else {
                $r .= '<td class="'.$class.'">&nbsp';
            }
            $r .= '</td>';

            $date = add2date($date, "tag", 1, TRUE);
            $dayofweek++;
            if($dayofweek == 7) {
                $r .= '</tr>';
                $dayofweek = 0;
            }
        }
        $r .= '</table>';

        $this->view->assign('calendar', $r);
        $this->view->assign('time', new \DateTime());

        //$smarty->assign("tpl_cal_titel", getLL("fm_daten_title")." ".strftime($GLOBALS["DATETIME"]["mY"], time()));
        //$smarty->assign('table', $r);

        //$smarty->display('ko_fm_daten_cal.tpl');


    }

}