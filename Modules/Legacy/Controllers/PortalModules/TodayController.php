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
use Peregrinus\Flockr\Core\Debugger;

class TodayController extends AbstractController
{

    public function indexAction()
    {
        global $ko_path, $smarty, $access;


        //*** DATEN ***
        $events = [];
        ko_get_access('daten');
        if ($access['daten']['MAX'] > 0) {


            //Events this week
            $this->view->assign("title_event_week", getLL("fm_today_events_week"));
            $heute = date("d.m.Y");
            $events = array();

            for ($i = 0; $i <= 6; $i++) {
                $tag = add2date($heute, "tag", $i);
                unset($temp);
                ko_get_events_by_date($tag[0], $tag[1], $tag[2], $temp);
                if (sizeof($temp) > 0) $events = array_merge($events, $temp);
            }

            $done = array();
            foreach ($events as $idx => $event) {
                //Termine nicht doppelt anzeigen - w�rde bei mehrt�gigen passieren
                if (($access['daten']['ALL'] < 1 && $access['daten'][$event['eventgruppen_id']] < 1) || in_array($event["id"], $done)) {
                    unset($events[$idx]);
                    continue;
                }
                $done[] = $event["id"];

                if ($event["startdatum"] == $event["enddatum"]) $events[$idx]["enddatum"] = "";
                else $events[$idx]["enddatum"] = sql2datum($event["enddatum"]);
                $events[$idx]['startdatum'] = new \DateTime($events[$idx]['startdatum']);
                if ($events[$idx]['enddatum']) $events[$idx]['enddatum'] = new \DateTime($events[$idx]['enddatum']);

                $events[$idx]['allDay'] = ($event["startzeit"] == "00:00:00" && $event["endzeit"] == "00:00:00");
            }
        }
        $this->view->assign('events', $events);


        //*** RESERVATIONS ***
        //(Eigene oder bei Mod, die gemachten)
        ko_get_access('reservation');
        $reservations = [];
        if ($access['reservation']['MAX'] > 1 && $_SESSION["ses_userid"] != ko_get_guest_id()) {
            //Reservationen diese Woche
            $this->view->assign("title_res_week", getLL("fm_today_res_week"));
            $heute = date("d.m.Y");
            $res_woche_mod = array();

            for ($i = 0; $i <= 7; $i++) {
                $tag = add2date($heute, "tag", $i);
                unset($temp);
                ko_get_res_by_date($tag[0], $tag[1], $tag[2], $temp);
                if (sizeof($temp) > 0) $reservations = array_merge($reservations, $temp);
            }

            $done = array();
            ko_get_resitems($resitems);
            foreach ($reservations as $idx => $event) {
                $item = $resitems[$event["item_id"]];
                if (($access['reservation']['ALL'] < 1 && $access['reservation'][$event['item_id']] < 1)
                    || $event["user_id"] != $_SESSION["ses_userid"]  //Nur eigene anzeigen
                    || in_array($event["id"], $done)  //mehrt�gige Reservationen nicht mehrfach anzeigen
                ) {
                    unset($reservations[$idx]);
                    continue;
                }
                $done[] = $event["id"];

                $reservations[$idx]["item"] = ko_html($item["name"]);
                $reservations[$idx]["purpose"] = ko_html($event["zweck"]);
                $reservations[$idx]["name"] = ko_html($event["name"]);
                $reservations[$idx]["email"] = ko_html($event["email"]);
                $reservations[$idx]["telefon"] = ko_html($event["telefon"]);

                $tag = explode("-", $event["startdatum"]);
                $reservations[$idx]["wochentag"] = strftime("%A", mktime(1, 1, 1, $tag[1], $tag[2], $tag[0]));

                if ($event["startdatum"] == $event["enddatum"]) $reservations[$idx]["enddatum"] = "";
                else $reservations[$idx]["enddatum"] = sql2datum($event["enddatum"]);

                $reservations[$idx]['startdatum'] = new \DateTime($event['startdatum']);
                if ($event['enddatum']) $reservations[$idx]['enddatum'] = new \DateTime($event['enddatum']);

                $reservations[$idx]['allDay'] = ($event["startzeit"] == "00:00:00") && ($event["endzeit"] == "00:00:00");
            }//foreach(res_woche)
        }//if(sizeof(res))
        $this->view->assign('reservations', $reservations);
    }


}