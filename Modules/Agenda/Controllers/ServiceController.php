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


namespace Peregrinus\Flockr\Agenda\Controllers;


use Peregrinus\Flockr\Core\AbstractController;

class ServiceController extends AbstractController {

	function listAction() {
        ko_get_events($events, 'AND (startdatum >= \''.date('Y-m-d').'\') AND (eventgruppen_id=1)');

        $teams = db_select_data('ko_rota_teams', 'WHERE 1', '*');

        foreach ($events as $key=>$event) {
            $events[$key] = $event = ko_rota_get_events('', $event['id']);
            $events[$key]['rota'] = [];
            foreach ($event['teams'] as $teamKey => $team) {
                if (isset($event['schedule'][$team])) {
                    $events[$key]['rota'][$team] = ko_rota_schedulled_text($event['schedule'][$team]);
                }
            }
        }

        $this->view->assign('events', $events);
        $this->view->assign('teams', $teams);
    }
	function newAction() {}
	function createAction() {}
	function editAction() {}
	function updateAction() {}
	function deleteAction() {}

	function planAction() {
		$this->app->getLayout()->addStyleSheet('Modules/Agenda/Resources/Public/Styles/Plan.css');
		$this->app->getLayout()->addJavaScript('footer', 'Modules/Agenda/Resources/Public/Scripts/lib/jquery-sortable-min.js');
		$this->app->getLayout()->addJavaScript('footer', 'Modules/Agenda/Resources/Public/Scripts/Plan.js');
	}

}