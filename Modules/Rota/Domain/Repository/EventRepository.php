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


namespace Peregrinus\Flockr\Rota\Domain\Repository;


use Peregrinus\Flockr\Core\AbstractRepository;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Services\AccessService;
use Peregrinus\Flockr\Core\Services\SettingsService;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class EventRepository extends AbstractRepository
{

    protected $table = 'ko_event';
    /**
     * @var TeamRepository
     */
    protected $teamRepository = null;

    /**
     * @var ScheduleRepository
     */
    protected $scheduleRepository = null;

    /**
     * @var PeopleRepository
     */
    protected $peopleRepository = null;

    public function __construct()
    {
        parent::__construct();
        $this->teamRepository = new TeamRepository();
        $this->scheduleRepository = new ScheduleRepository();
        $this->peopleRepository = new PeopleRepository();
    }

    public function setEventStatus($eventId, $status)
    {
        $schedules = $this->scheduleRepository->findByEventId($eventId);

        foreach ($schedules as $schedule) {
            $schedule['status'] = $status;
            $this->scheduleRepository->update($schedule);
        }
    }

    /**
     * Get all currently selected events
     * @param mixed $timeStart Start time
     * @param mixed $timeSpan Time span covered
     * @param array $teams Teams
     * @param array $eventGroups Event groups
     * @return array Events
     */
    public function getDisplayedEvents($timeStart, $timeSpan, array $teams, array $eventGroups) : array
    {
        $events = $this->findPlanableEvents(
            $teams,
            0,
            $eventGroups,
            $timeStart,
            $timeSpan
        );
        return $events;
    }

    /**
     * Find all planable events for specified teams and date range
     * @param array $teams Teams
     * @param array|int $eventId Select specific events
     * @param array $eventGroups Select specific eventGroups
     * @param mixed $timeStart Start time
     * @param mixed $timeSpan Time span
     * @return array Events
     */
    public function findPlanableEvents($teams, $eventId, $eventGroups, $timeStart, $timeSpan)
    {
        global $access, $DATETIME, $ko_menu_akt;

        $events = [];
        if (sizeof($teams) == 0) return [];

        //Multiple event ids given as array
        if (is_array($eventId)) {
            $where = " WHERE e.id IN ('" . implode("','", $eventId) . "') ";
        } //Only get one single event (e.g. for AJAX)
        else {
            if ($eventId > 0) {
                $where = " WHERE e.id = '$eventId' ";
            } //Or get all events from a given set of event groups
            else {

                if (sizeof($eventGroups) == 0 || sizeof($teams) == 0) {
                    Debugger::dumpAndDie($eventGroups);
                    return [];
                }

                //Build SQL to only get events from selected event groups
                $where = 'WHERE e.rota = 1 ';
                $where .= ' AND e.eventgruppen_id IN (' . implode(',', $eventGroups) . ') ';

                //Apply global event filters if needed
                if (LoginService::getInstance()->hasGlobalTimeFilter('daten')
                    || !AccessService::getInstance()->hasAccess('daten', 'MAX', 2)
                ) {
                    $permFilterStart = SettingsService::getInstance()->getGlobalSetting('daten_perm_filter_start');
                    $permFilterEnd = SettingsService::getInstance()->getGlobalSetting('daten_perm_filter_ende');
                    if ($permFilterStart || $permFilterEnd) {
                        if ($permFilterStart != '') {
                            $where .= " AND enddatum >= '" . $permFilterStart . "' ";
                        }
                        if ($permFilterEnd != '') {
                            $where .= " AND startdatum <= '" . $permFilterEnd . "' ";
                        }
                    }
                }

                list($start, $stop) = rota_timespan_startstop($timeStart, $timeSpan);
                $where .= " AND ( (e.startdatum >= '$start' AND e.startdatum < '$stop') OR (e.enddatum >= '$start' AND e.enddatum < '$stop') ) ";
            }
        }

        //Add date filter so only events in the future show (according to userpref)
        if (SettingsService::getInstance()->getUserPreference('rota_date_future')) {
            $where .= " AND (e.enddatum >= '" . date('Y-m-d') . "') ";
        }


        $query = "SELECT e.*,tg.name AS eventgruppen_name, tg.farbe AS eventgruppen_farbe FROM `ko_event` AS e LEFT JOIN ko_eventgruppen AS tg ON e.eventgruppen_id = tg.id " . $where . " ORDER BY startdatum ASC, startzeit ASC";
        $statement = $this->db->getStatement($query);
        if ($statement->execute()) {
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                //Set individual event color
                \ko_set_event_color($row);

                $teamsForEvent = $this->teamRepository->findByEventGroup($row['eventgruppen_id'], true, true, false);
                $teamsForEvent = $this->teamRepository->filterByWhitelist($teamsForEvent, $teams);
                $teamIds = $this->teamRepository->getIds($teamsForEvent);
                if (sizeof($teamsForEvent) == 0) {
                    continue;
                }
                $row['teams'] = $teamsForEvent;

                //Assign all schedulling information for this event
                //$schedulling = db_select_data('ko_rota_schedulling', "WHERE `event_id` = '" . $row['id'] . "'", '*', '', '', false, true);
                $schedule = [];
                foreach ($teamIds as $teamId) {
                    $thisSchedule = $this->scheduleRepository->findOneByEventAndTeam($row['id'], $teamId);
                    if (!isset($row['rotastatus'])) $row['rotastatus'] = $thisSchedule['status'];
                    if (isset($thisSchedule['schedule'])) {
                        $schedule[$teamId] = $thisSchedule['schedule'];
                    }
                }

                $row['schedule'] = $schedule;
                if (!isset($row['rotastatus'])) $row['rotastatus'] = 1;

                //Get status of schedulling for this event (done/total)
                $done = 0;
                foreach ($teamsForEvent as $t => $v) {
                    if (isset($schedule[$t]) && $schedule[$t] != '') {
                        $done++;
                    }
                }
                $row['_stats'] = array('total' => sizeof($teamsForEvent), 'done' => $done);

                //Add nicely formated date and time
                $row['_time'] = $row['startzeit'] == '00:00:00' && $row['endzeit'] == '00:00:00' ? getLL('time_all_day') : substr($row['startzeit'],
                    0, -3);
                $row['_date'] = strftime($DATETIME['DdmY'], strtotime($row['startdatum']));
                if ($row['enddatum'] != $row['startdatum'] && $row['enddatum'] != '0000-00-00') {
                    $row['_date'] .= ' - ' . strftime($DATETIME['DdmY'], strtotime($row['enddatum']));
                }

                $events[] = $row;
            }
        }
        //Only return one if event_id was given
        if (!is_array($eventId) && $eventId > 0) {
            $events = array_shift($events);
        }

        return $events;

    }

}