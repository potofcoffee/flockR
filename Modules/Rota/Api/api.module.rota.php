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
 * R O T A                                                                                                              *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * @param _teams An array of team IDs that should be returned. If empty the teams currently set in the SESSION will be used
 * @param event_id An ID of a single event to be returned (may also be an array of event ids
 */
function ko_rota_get_events($_teams = '', $event_id = '', $include_weekteams = false)
{
    global $access, $DATETIME, $ko_menu_akt;

    $e = array();

    //Get all rota teams
    if (is_array($_teams)) {
        $teams = $_teams;
    } else {
        if ($ko_menu_akt == 'rota') {
            $teams = $_SESSION['rota_teams'];
        } else {
            $teams = array_keys(db_select_data('ko_rota_teams'));
        }
    }
    foreach ($teams as $k => $v) {
        if (!$v) {
            unset($teams[$k]);
        }
    }
    if (sizeof($teams) == '0') {
        return array();
    }
    if ($_SESSION['sort_rota_teams']) {
        $order = 'ORDER BY ' . $_SESSION['sort_rota_teams'] . ' ' . $_SESSION['sort_rota_teams_order'];
    } else {
        $order = 'ORDER BY ' . (ko_get_setting('rota_manual_ordering') ? 'sort' : 'name') . ' ASC';
    }
    $_rota_teams = db_select_data('ko_rota_teams', "WHERE `id` IN (" . implode(',', $teams) . ")", '*', $order);

    //Only show those of type event
    $rota_teams = array();
    if ($include_weekteams) {
        $rota_teams = $_rota_teams;
    } else {
        foreach ($_rota_teams as $t) {
            if ($t['rotatype'] == 'event') {
                $rota_teams[$t['id']] = $t;
            }
        }
    }

    //Check for access level 1 for all these teams (access check for level 2 must be done in other functions, if need be)
    if (!isset($access['rota'])) {
        ko_get_access('rota');
    }
    if ($access['rota']['ALL'] < 1) {
        foreach ($rota_teams as $ti => $t) {
            if ($access['rota'][$ti] < 1) {
                unset($rota_teams[$ti]);
            }
        }
    }


    //Multiple event ids given as array
    if (is_array($event_id)) {
        $where = " WHERE e.id IN ('" . implode("','", $event_id) . "') ";
    } //Only get one single event (e.g. for AJAX)
    else {
        if ($event_id > 0) {
            $where = " WHERE e.id = '$event_id' ";
        } //Or get all events from a given set of event groups
        else {
            $egs = $_SESSION['rota_egs'];

            if (sizeof($egs) == 0 || sizeof($rota_teams) == 0) {
                return array();
            }


            //Build SQL to only get events from selected event groups
            $where = 'WHERE e.rota = 1 ';
            $where .= ' AND e.eventgruppen_id IN (' . implode(',', $egs) . ') ';

            // check, if the login has the 'force_global_filter' flag set to 1
            $forceGlobalTimeFilter = ko_get_force_global_time_filter('daten', $_SESSION['ses_userid']);

            //Apply global event filters if needed
            if (!is_array($access['daten'])) {
                ko_get_access('daten');
            }
            if ($forceGlobalTimeFilter || $access['daten']['MAX'] < 2) {
                $perm_filter_start = ko_get_setting('daten_perm_filter_start');
                $perm_filter_ende = ko_get_setting('daten_perm_filter_ende');
                if ($perm_filter_start || $perm_filter_ende) {
                    if ($perm_filter_start != '') {
                        $where .= " AND enddatum >= '" . $perm_filter_start . "' ";
                    }
                    if ($perm_filter_ende != '') {
                        $where .= " AND startdatum <= '" . $perm_filter_ende . "' ";
                    }
                }
            }

            list($start, $stop) = rota_timespan_startstop($_SESSION['rota_timestart'], $_SESSION['rota_timespan']);
            $where .= " AND ( (e.startdatum >= '$start' AND e.startdatum < '$stop') OR (e.enddatum >= '$start' AND e.enddatum < '$stop') ) ";
        }
    }

    //Add date filter so only events in the future show (according to userpref)
    if (ko_get_userpref($_SESSION['ses_userid'], 'rota_date_future') == 1) {
        $where .= " AND (e.enddatum >= '" . date('Y-m-d') . "') ";
    }


    $query = "SELECT e.*,tg.name AS eventgruppen_name, tg.farbe AS eventgruppen_farbe FROM `ko_event` AS e LEFT JOIN ko_eventgruppen AS tg ON e.eventgruppen_id = tg.id " . $where . " ORDER BY startdatum ASC, startzeit ASC";
    $result = mysql_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        //Set individual event color
        ko_set_event_color($row);

        $all_teams = ko_rota_get_teams_for_eg($row['eventgruppen_id']);

        //Add IDs of all teams assigned to this event
        $teams = array();
        foreach ($rota_teams as $t) {
            if (in_array($row['eventgruppen_id'], explode(',', $t['eg_id']))) {
                $teams[] = $t['id'];
            }
        }
        if (sizeof($teams) == 0) {
            continue;
        }
        $row['teams'] = $teams;

        //Assign all schedulling information for this event
        $schedulling = db_select_data('ko_rota_schedulling', "WHERE `event_id` = '" . $row['id'] . "'", '*', '', '',
            false, true);
        $schedule = array();
        foreach ($schedulling as $s) {
            if (in_array($s['team_id'], array_keys($all_teams))) {
                $schedule[$s['team_id']] = $s['schedule'];
            }
        }
        $row['schedule'] = $schedule;
        $row['rotastatus'] = $schedulling[0]['status'] ? $schedulling[0]['status'] : 1;  //Status of this week (1 for open, 2 for closed)

        //Get status of schedulling for this event (done/total)
        $done = 0;
        foreach ($all_teams as $t => $v) {
            if (isset($schedule[$t]) && $schedule[$t] != '') {
                $done++;
            }
        }
        $row['_stats'] = array('total' => sizeof($all_teams), 'done' => $done);

        //Add nicely formated date and time
        $row['_time'] = $row['startzeit'] == '00:00:00' && $row['endzeit'] == '00:00:00' ? getLL('time_all_day') : substr($row['startzeit'],
            0, -3);
        $row['_date'] = strftime($DATETIME['DdmY'], strtotime($row['startdatum']));
        if ($row['enddatum'] != $row['startdatum'] && $row['enddatum'] != '0000-00-00') {
            $row['_date'] .= ' - ' . strftime($DATETIME['DdmY'], strtotime($row['enddatum']));
        }

        $e[] = $row;
    }

    //Only return one if event_id was given
    if (!is_array($event_id) && $event_id > 0) {
        $e = array_shift($e);
    }

    return $e;
}//ko_rota_get_events()


/**
 * Get all rota teams working in the given event group
 *
 * @param eg ID of a single event group
 */
function ko_rota_get_teams_for_eg($eg)
{
    global $access;

    if (isset($GLOBALS['kOOL']['rota_teams_for_eg'][$eg])) {
        return $GLOBALS['kOOL']['rota_teams_for_eg'][$eg];
    }

    if ($_SESSION['sort_rota_teams']) {
        $order = 'ORDER BY ' . $_SESSION['sort_rota_teams'] . ' ' . $_SESSION['sort_rota_teams_order'];
    } else {
        $order = 'ORDER BY ' . (ko_get_setting('rota_manual_ordering') ? 'sort' : 'name') . ' ASC';
    }
    $teams = db_select_data('ko_rota_teams', "WHERE `eg_id` REGEXP '(^|,)$eg(,|$)' AND `rotatype` = 'event'", '*',
        $order);

    //Check for access
    if ($access['rota']['ALL'] < 1) {
        foreach ($teams as $ti => $t) {
            if ($access['rota'][$ti] < 1) {
                unset($teams[$ti]);
            }
        }
    }

    $GLOBALS['kOOL']['rota_teams_for_eg'][$eg] = $teams;

    return $teams;
}//ko_rota_get_teams_for_eg()


/**
 * Get all rota teams that are schedulled weekly (Dienstwochen)
 */
function ko_rota_get_teams_week()
{
    global $access;

    if (isset($GLOBALS['kOOL']['rota_teams_week'])) {
        return $GLOBALS['kOOL']['rota_teams_week'];
    }

    if ($_SESSION['sort_rota_teams']) {
        $order = 'ORDER BY ' . $_SESSION['sort_rota_teams'] . ' ' . $_SESSION['sort_rota_teams_order'];
    } else {
        $order = 'ORDER BY ' . (ko_get_setting('rota_manual_ordering') ? 'sort' : 'name') . ' ASC';
    }
    $teams = db_select_data('ko_rota_teams', "WHERE `rotatype` = 'week'", '*', $order);
    //Check for access
    if ($access['rota']['ALL'] < 1) {
        foreach ($teams as $ti => $t) {
            if ($access['rota'][$ti] < 1) {
                unset($teams[$ti]);
            }
        }
    }

    $GLOBALS['kOOL']['rota_teams_week'] = $teams;

    return $teams;
}//ko_rota_get_teams_week()


function ko_rota_get_weeks($rota_teams, $week_id = '')
{
    global $access;

    if (sizeof($_SESSION['rota_teams']) == 0) {
        return array();
    }

    if ($_SESSION['sort_rota_teams']) {
        $order = 'ORDER BY ' . $_SESSION['sort_rota_teams'] . ' ' . $_SESSION['sort_rota_teams_order'];
    } else {
        $order = 'ORDER BY ' . (ko_get_setting('rota_manual_ordering') ? 'sort' : 'name') . ' ASC';
    }
    if (!is_array($rota_teams)) {
        $rota_teams = db_select_data('ko_rota_teams', "WHERE `id` IN (" . implode(',', $_SESSION['rota_teams']) . ")",
            '*', $order);
    }

    if ($week_id) {
        list($start, $stop) = ko_rota_week_get_startstop($week_id);
    } else {
        list($start, $stop) = rota_timespan_startstop($_SESSION['rota_timestart'], $_SESSION['rota_timespan']);
        $start = strtotime(date_find_last_monday($start));
        $stop = strtotime($stop);
    }


    //Get all weekly teams and check for access
    $teams = array();
    foreach ($rota_teams as $t) {
        if ($t['rotatype'] == 'week' && ($access['rota']['ALL'] > 0 || $access['rota'][$t['id']] > 0)) {
            $teams[] = $t['id'];
        }
    }

    $weeks = array();
    $ts = $start;
    while ($ts < $stop) {
        $weeks[date('Y-W', $ts)] = array(
            'id' => date('Y-W', $ts),
            'num' => date('W', $ts),
            'year' => date('Y', $ts),
            //Correct displayed date by rota_weekstart
            '_date' => ko_rota_timespan_title(date('Y-m-d', ($ts + (ko_get_setting('rota_weekstart') * 3600 * 24))),
                '1w'),
            'teams' => $teams
        );

        //Get all schedulling information
        $schedulling = db_select_data('ko_rota_schedulling', "WHERE `event_id` = '" . date('Y-W', $ts) . "'", '*', '',
            '', false, true);
        $schedule = array();
        foreach ($schedulling as $s) {
            $schedule[$s['team_id']] = $s['schedule'];
        }
        $weeks[date('Y-W', $ts)]['schedule'] = $schedule;
        $weeks[date('Y-W',
            $ts)]['rotastatus'] = $schedulling[0]['status'] ? $schedulling[0]['status'] : 1;  //Status of this week (1 for open, 2 for closed)

        //Get status of schedulling for this event (done/total)
        $done = 0;
        $all_teams = ko_rota_get_teams_week();
        foreach ($all_teams as $t => $v) {
            if (isset($schedule[$t]) && $schedule[$t] != '') {
                $done++;
            }
        }
        $weeks[date('Y-W', $ts)]['_stats'] = array('total' => sizeof($all_teams), 'done' => $done);


        $ts += 3600 * 24 * 7;
    }

    //Only return one if week_id was given
    if ($week_id > 0) {
        $weeks = array_shift($weeks);
    }

    return $weeks;
}//ko_rota_get_weeks()


/**
 * Get start and stop date for a given start date and timespan
 */
function rota_timespan_startstop($timestart, $timespan)
{
    //Add time frame from setting / param
    switch ($timespan) {
        case '1d':
            $start = $timestart;
            $stop = add2date($timestart, 'day', 1, true);
            break;

        case '1w':
            $start = date_find_last_monday($timestart);
            $stop = add2date($start, 'week', 1, true);
            break;
        case '2w':
            $start = date_find_last_monday($timestart);
            $stop = add2date($start, 'week', 2, true);
            break;

        case '1m':
            $start = substr($timestart, 0, -2) . '01';
            $stop = add2date($start, 'month', 1, true);
            break;
        case '2m':
            $start = substr($timestart, 0, -2) . '01';
            $stop = add2date($start, 'month', 2, true);
            break;
        case '3m':
            $start = substr($timestart, 0, -2) . '01';
            $stop = add2date($start, 'month', 3, true);
            break;
        case '6m':
            $start = substr($timestart, 0, -2) . '01';
            $stop = add2date($start, 'month', 6, true);
            break;
        case '12m':
            $start = substr($timestart, 0, -2) . '01';
            $stop = add2date($start, 'month', 12, true);
            break;
    }

    return array($start, $stop);
}//rota_timespan_startstop()


/**
 * @param event If it's an array, then it must be one event retrieved by ko_rota_get_events(). It may also be an ID of an event
 * @param mode May be event or week. If event holds an id, the mode tells whether this id is of an event or of a week (YYYY-MM)
 * @param _teams An array of teams used for ko_rota_get_events(). These teams can be schedulled.
 */
function ko_rota_get_schedulling_code($event, $mode = 'event', $_teams = '')
{
    global $access;

    if (!is_array($event)) {
        if ($mode == 'event') {
            $event = ko_rota_get_events($_teams, $event);
        } else {
            if ($mode == 'week') {
                $event = ko_rota_get_weeks('', $event);
            }
        }
    }

    if ($_SESSION['sort_rota_teams']) {
        $order = 'ORDER BY ' . $_SESSION['sort_rota_teams'] . ' ' . $_SESSION['sort_rota_teams_order'];
    } else {
        $order = 'ORDER BY ' . (ko_get_setting('rota_manual_ordering') ? 'sort' : 'name') . ' ASC';
    }
    $all_teams = db_select_data('ko_rota_teams', 'WHERE 1=1', '*', $order);

    //Get all people scheduled in this event for double checks
    if ($mode == 'event') {
        $temp = ko_rota_get_recipients_by_event_by_teams($event['id']);
        foreach ($temp as $tid => $t) {
            $currently_scheduled[$tid] = array_keys($t);
        }
    } else {
        if ($mode == 'week') {
            //Get all events of this week
            list($start, $stop) = ko_rota_week_get_startstop($event['id']);
            $start = date('Y-m-d', $start + (ko_get_setting('rota_weekstart') * 3600 * 24));
            $stop = date('Y-m-d', $stop + (ko_get_setting('rota_weekstart') * 3600 * 24));

            //Only check events where the given week-teams are active $event['teams']
            $egs = array();
            foreach ($event['teams'] as $tid) {
                $egs = array_merge($egs, explode(',', $all_teams[$tid]['eg_id']));
            }
            $egs = array_unique($egs);
            foreach ($egs as $k => $v) {
                if (!$v) {
                    unset($egs[$k]);
                }
            }

            $where = "WHERE `rota` = '1' AND (`startdatum` <= '$stop' AND `enddatum` >= '$start') ";
            if (sizeof($egs) > 0) {
                $where .= " AND `eventgruppen_id` IN (" . implode(',', $egs) . ") ";
            } else {
                $where .= " AND 1=2 ";
            }

            $events = db_select_data('ko_event', $where);
            foreach ($events as $e) {
                $temp = ko_rota_get_recipients_by_event_by_teams($e['id']);
                foreach ($temp as $tid => $t) {
                    $currently_scheduled[$tid] = array_merge((array)$currently_scheduled[$tid], array_keys($t));
                }
            }
        }
    }

    // needed to determine whether participation will be displayed
    $role = ko_get_setting('rota_teamrole');
    $helperRoleString = (trim($role) == '' ? '' : ':r' . $role);

    $c = '<div class="rota-schedule">';
    foreach ($event['teams'] as $tid) {
        if ($access['rota']['ALL'] < 1 && $access['rota'][$tid] < 1) {
            continue;
        }

        $c .= '<div class="row">';
        $c .= '<div class="col-md-2">' . $all_teams[$tid]['name'] . '</div>';

        if ($event['rotastatus'] == 1 && ($access['rota']['ALL'] > 2 || $access['rota'][$tid] > 2)) {  //open and enough access

            $consensusAllowed = $all_teams[$tid]['allow_consensus'] == 1;

            //Prepare select with groups and people to choose from
            $members = ko_rota_get_team_members($all_teams[$tid],
                ko_get_userpref($_SESSION['ses_userid'], 'rota_schedule_subgroup_members'));
            $o = '<option value=""></option>';
            $groupsFromConsensus = array();
            if (sizeof($members['groups']) > 0) {
                foreach ($members['groups'] as $group) {
                    //Check for double
                    $double = $title = $warntext = '';
                    $group_members = db_select_data('ko_leute', "WHERE `groups` REGEXP 'g" . $group['id'] . "'");
                    foreach ($group_members as $person) {
                        foreach ($all_teams as $_tid => $_team) {
                            if ($_tid == $tid) {
                                continue;
                            }
                            if (in_array($person['id'], $currently_scheduled[$_tid])) {
                                $double = ' (!)';
                                $title = 'title="' . sprintf(getLL('rota_schedule_warning_double_group'),
                                        ($person['vorname'] . ' ' . $person['nachname']), $_team['name']) . '"';
                                $warntext = trim(sprintf(getLL('rota_schedule_warning_double_group'),
                                    ($person['vorname'] . ' ' . $person['nachname']), $_team['name']));
                            }
                        }
                    }
                    $o .= '<option value="g' . $group['id'] . '" ' . $title . '>[' . $group['name'] . ']' . $double . '</option>';
                    if ($consensusAllowed) {
                        $groupAnswers = ko_consensus_get_answers('group', $event['id'], $tid, $group['id']);
                        $groupsFromConsensus[$group['id']] = array(
                            'id' => $group['id'],
                            'name' => '[' . $group['name'] . ']',
                            'double' => $double,
                            'warntext' => $warntext,
                            'answer' => $groupAnswers
                        );
                    }
                }
            }
            $personsFromConsensus = array();
            if (sizeof($members['people']) > 0) {
                foreach ($members['people'] as $person) {
                    $double = $title = $warntext = '';
                    foreach ($all_teams as $_tid => $_team) {
                        if ($_tid == $tid) {
                            continue;
                        }
                        if (in_array($person['id'], $currently_scheduled[$_tid])) {
                            $double = ' (!)';
                            $title = 'title="' . sprintf(getLL('rota_schedule_warning_double'), $_team['name']) . '"';
                            $warntext = trim(sprintf(getLL('rota_schedule_warning_double'), $_team['name']));
                        }
                    }
                    $name = $person['vorname'] . ' ' . $person['nachname'];
                    $o .= '<option value="' . $person['id'] . '" ' . $title . '>' . $name . $double . '</option>';
                    if ($consensusAllowed) {
                        $answer = ko_consensus_get_answers('person', $event['id'], $tid, $person['id']);
                        $personsFromConsensus[$person['id']] = array(
                            'id' => $person['id'],
                            'name' => $name,
                            'double' => $double,
                            'warntext' => $warntext,
                            'answer' => $answer
                        );
                    }
                }
            }

            //Schedulled values
            $sel_o = array();
            $schedulled = ko_rota_schedulled_text($event['schedule'][$tid], 'full');
            $size = 0;
            foreach ($schedulled as $k => $v) {
                if (!$k) {
                    continue;
                }
                $sel_o[] = '<div class="rota-entry" id="rota_entry_' . $event['id'] . '_' . $tid . '_' . $k . '">' . $v . '</div>';
                //unset($personsFromConsensus[$k]);
                $size++;
            }
            $size = max(2, $size);

            if ($consensusAllowed) {
                // Color table for consensus
                $bgColor = array(0 => 'no_answer', 1 => 'no', 2 => 'maybe', 3 => 'yes');
                //Consensus Values of groups
                $consensus_o_g = array();
                $groupToolTipHtml = '<table><tr><td>' . getLL('yes') . '</td><td>%s</td></tr><tr><td>(' . getLL('yes') . ')</td><td>%s</td></tr><tr><td>' . getLL('no') . '</td><td>%s</td></tr></table><p>%s</p>';
                foreach ($groupsFromConsensus as $k => $v) {
                    if ($k === '') {
                        continue;
                    }
                    $toolTipCode = "onmouseover=\"tooltip.show('" . sprintf($groupToolTipHtml, $v['answer'][3],
                            $v['answer'][2], $v['answer'][1], $v['warntext']) . "');\" onmouseout=\"tooltip.hide();\"";
                    $consensus_o_g[] = '<div class="rota-consensus-entry rota-consensus-entry-group" id="rota_consensus_entry_' . $event['id'] . '_' . $tid . '_g' . $v['id'] . '" style="background-image:url(\'/rota/inc/consensus_chart?x=' . implode('x',
                            $v['answer']) . '\');" ' . $toolTipCode . '>' . $v['name'] . $v['double'] . '</div>';
                }
                //Consensus Values of persons
                $consensus_o_p = array();
                $personToolTipHtml = '<p>' . getLL('rota_consensus_tooltip_header_person') . '</p><table><tr><td></td><td>' . getLL('time_month') . '</td><td>' . getLL('time_quarter') . '</td><td>' . getLL('time_year') . '</td></tr><tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr><tr><td>' . getLL('rota_consensus_all_teams') . '</td><td>%s</td><td>%s</td><td>%s</td></tr></table><p>%s</p>';
                foreach ($personsFromConsensus as $k => $v) {
                    if ($k === '') {
                        continue;
                    }
                    $participation = ko_rota_get_participation($v['id'], $tid);
                    $toolTipCode = "onmouseover=\"tooltip.show('" . sprintf($personToolTipHtml,
                            $all_teams[$tid]['name'], $participation[$tid]['month'], $participation[$tid]['quarter'],
                            $participation[$tid]['year'], $participation['all']['month'],
                            $participation['all']['quarter'], $participation['all']['year'],
                            $v['warntext']) . "');\" onmouseout=\"tooltip.hide();\"";
                    $consensus_o_p[] = '<div class="btn btn-default rota-consensus-entry ' . $bgColor[$v['answer']] . '" id="rota_consensus_entry_' . $event['id'] . '_' . $tid . '_' . $v['id'] . '" ' . $toolTipCode . '>' . $v['name'] . $v['double'] . '</div>';
                }
            }

            $c .= '<div class="rota-entries"><div class="col-md-3"><div class="form-group">';
            $c .= '<select class="rota-select form-control" id="' . $event['id'] . '_' . $tid . '" size="0">' . $o . '</select>';
            $c .= '<input class="rota-text form-control" type="text" id="rota_text_' . $event['id'] . '_' . $tid . '" />'
                . '</div></div><div class="col-md-2">';

            // determine the number of elements which should be stacked in each cell (height in [elements])
            $elemPerCell = max(2, ceil(sizeof($sel_o) / 3));

            $counter = 0;
            foreach ($sel_o as $entry) {
                $c .= $entry;
                $counter++;
            }
            $c .= '</div>';

            $c .= '</div>';

            if ($consensusAllowed) {
                // determine the number of elements which should be stacked in each cell (height in [elements])
                $elemPerCell = max(2, ceil((sizeof($consensus_o_g) + sizeof($consensus_o_p)) / 4));

                $c .= '<div class="col-md-5"><div class="rota-consensus-enries"><div class="row">';
                // Entries from Consensus groups
                $counter = 0;
                foreach ($consensus_o_g as $entry) {
                    if ($counter == 0) {
                        $c .= '<div class="col-md-1">';
                    }
                    $c .= $entry;
                    $counter++;
                    if ($counter == $elemPerCell) {
                        $counter = 0;
                        $c .= '</div>';
                    }
                }
                if ($counter > 0 && $counter < $elemPerCell) {
                    $c .= '</div>';
                }
                // Entries from Consensus persons
                $counter = 0;
                foreach ($consensus_o_p as $entry) {
                    if ($counter == 0) {
                        $c .= '<td valign="top">';
                    }
                    $c .= $entry;
                    $counter++;
                    if ($counter == $elemPerCell) {
                        $counter = 0;
                        $c .= '</td>';
                    }
                }
                if ($counter > 0 && $counter < $elemPerCell) {
                    $c .= '</div>';
                }
                $c .= '</div></div>';

                $c .= '</div>';
            }
        } else {  // 2 = closed
            $c .= '<td style="width:80%;">' . implode(ko_get_userpref($_SESSION['ses_userid'], 'rota_delimiter'),
                    ko_rota_schedulled_text($event['schedule'][$tid], 'full')) . '</td>';
        }

        $c .= '</div>';
    }
    $c .= '</div>';

    return $c;
}//ko_rota_get_schedulling_code()


/**
 * Get the text to be displayed for a certain scheduling: Name of persons, Name of groups or free text
 * @param schedule string Comma separated list as found in db table ko_rota_schedulling
 * @return array Array of all entires which can be imploded for text rendering
 */
function ko_rota_schedulled_text($schedule, $forceFormat = '')
{
    $r = array();

    foreach (explode(',', $schedule) as $s) {
        if (!$s) {
            continue;
        }

        if (is_numeric($s)) {  //Person id
            $format = ko_get_userpref($_SESSION['ses_userid'], 'rota_pdf_names');
            if ($forceFormat) {
                $format = $forceFormat;
            }

            ko_get_person_by_id($s, $p);
            switch ($format) {
                case 1:
                    $r[$s] = $p['vorname'] . ' ' . substr($p['nachname'], 0, 1) . '.';
                    break;
                case 2:
                    $r[$s] = $p['vorname'] . ' ' . substr($p['nachname'], 0, 2) . '.';
                    break;
                case 3:
                    $r[$s] = substr($p['vorname'], 0, 1) . '. ' . $p['nachname'];
                    break;
                case 4:
                    $r[$s] = $p['vorname'] . ' ' . $p['nachname'];
                    break;
                case 5:
                    $r[$s] = $p['vorname'];
                    break;
                default:
                    $r[$s] = $p['vorname'] . ' ' . $p['nachname'];
            }
        } else {
            if (preg_match('/^g[0-9]{6}$/', $s)) {  //Group id
                $id = str_replace('g', '', $s);
                $group = db_select_data('ko_groups', "WHERE `id` = '$id'", '*', '', '', true);
                $r[$s] = getLL('rota_prefix_group') . $group['name'];
            } else {  //Text
                $r[$s] = $s;
            }
        }
    }

    return $r;
}//ko_rota_schedulled_text()


/**
 * gets all helpers of a certain team at a certain event. Helpers are persons, no groups are returned
 *
 * @param $eventId
 * @param $teamId
 * @param $keepGroup : Set to true to have the group's name returned instead of the people
 * @return array
 */
function ko_rota_get_helpers_by_event_team($eventId, $teamId, $keepGroup = false)
{
    $schedule = db_select_data('ko_rota_schedulling',
        "where `team_id` = '" . $teamId . "' and `event_id` = '" . $eventId . "'", '*', '', '', true, true);
    if ($schedule == null) {
        return array();
    }
    $role = ko_get_setting('rota_teamrole');
    $roleString = (trim($role) == '' ? '' : ':r' . $role);
    $helpers = array();
    foreach (explode(',', $schedule['schedule']) as $helper) {
        if (trim($helper) == '') {
            continue;
        }
        if (is_numeric($helper)) { // person id
            ko_get_person_by_id($helper, $person);
            if ($person == null) {
                continue;
            }
            $helpers[] = $person;
        } else {
            if (preg_match('/g[0-9]{6}/', $helper)) {
                if ($keepGroup) {
                    $group = db_select_data('ko_groups', "WHERE `id` = '" . substr($helper, 1) . "'", '*', '', '',
                        true);
                    $helpers[] = $group['name'];
                } else {
                    $pattern = $helper . '(:g[0-9]{6})*' . $roleString;
                    $res = db_select_data('ko_leute', "where `groups` regexp '" . $pattern . "'");
                    foreach ($res as $helper) {
                        $helpers[] = $helper;
                    }
                }
            }
        }
    }
    return $helpers;
} // ko_rota_get_helpers_by_event_team()


/**
 * Get all people scheduled in a given event. Also find group's members if a whole group is scheduled
 * @param event_ids array/int An array of event ids of a single event ID
 * @param team_ids array An array of teams to include. Empty to include all teams
 * @param access_level int Access level necessary to include this team
 */
function ko_rota_get_recipients_by_event($event_ids, $team_ids = '', $access_level = 2)
{
    global $access;

    if (!is_array($event_ids)) {
        $event_ids = array($event_ids);
    }
    if (sizeof($event_ids) == 0) {
        return array();
    }

    $z_where = '';
    if (is_array($team_ids) || $team_ids != '') {
        if (!is_array($team_ids)) {
            $team_ids = array($team_ids);
        }
        $z_where .= ' AND `team_id` IN (' . implode(',', $team_ids) . ') ';
    }

    //Add restriction according to access level
    if ($access['rota']['ALL'] < $access_level) {
        $a_teams = array();
        $all_teams = db_select_data('ko_rota_teams');
        foreach ($all_teams as $tid => $team) {
            if ($access['rota'][$tid] >= $access_level) {
                $a_teams[] = $tid;
            }
        }
        if (sizeof($a_teams) > 0) {
            $z_where .= ' AND `team_id` IN (' . implode(',', $a_teams) . ') ';
        } else {
            $z_where .= ' AND 1=2 ';
        }
    }

    //Add weeks
    $events = db_select_data('ko_event', "WHERE `id` IN (" . implode(',', $event_ids) . ')');
    foreach ($events as $event) {
        $event_ids[] = date('Y-W', (strtotime($event['startdatum']) - (ko_get_setting('rota_weekstart') * 3600 * 24)));
    }

    $people = array();
    $schedulling = db_select_data('ko_rota_schedulling',
        "WHERE `event_id` IN ('" . implode("','", $event_ids) . "')" . $z_where, '*', '', '', false, true);
    foreach ($schedulling as $schedule) {
        foreach (explode(',', $schedule['schedule']) as $s) {
            $s = trim($s);
            if (is_numeric($s)) {  //Person id
                ko_get_person_by_id($s, $p);
                $people[$p['id']] = $p;
            } else {
                if (preg_match('/^g[0-9]{6}$/', $s)) {  //Group id
                    $rows = db_select_data('ko_leute',
                        "WHERE `groups` REGEXP '$s' AND `deleted` = '0' AND `hidden` = '0'");
                    foreach ($rows as $row) {
                        $people[$row['id']] = $row;
                    }
                } else {  //Text
                    //Don't include in recipients list
                }
            }
        }
    }

    return $people;
}//ko_rota_get_recipients_by_event()


function ko_rota_get_recipients_by_event_by_teams($event_ids, $team_ids = '', $access_level = 2)
{
    global $access;

    if (!is_array($event_ids)) {
        if ($event_ids == '') {
            return array();
        }
        $event_ids = array($event_ids);
    }
    if (sizeof($event_ids) == 0) {
        return array();
    }

    $z_where = '';
    if (is_array($team_ids) || $team_ids != '') {
        if (!is_array($team_ids)) {
            $team_ids = array($team_ids);
        }
        $z_where .= ' AND `team_id` IN (' . implode(',', $team_ids) . ') ';
    }

    //Add restriction according to access level
    if ($access['rota']['ALL'] < $access_level) {
        $a_teams = array();
        $all_teams = db_select_data('ko_rota_teams');
        foreach ($all_teams as $tid => $team) {
            if ($access['rota'][$tid] >= $access_level) {
                $a_teams[] = $tid;
            }
        }
        if (sizeof($a_teams) > 0) {
            $z_where .= ' AND `team_id` IN (' . implode(',', $a_teams) . ') ';
        } else {
            $z_where .= ' AND 1=2 ';
        }
    }

    //Add weeks
    $events = db_select_data('ko_event', "WHERE `id` IN (" . implode(',', $event_ids) . ')');
    foreach ($events as $event) {
        $event_ids[] = date('Y-W', (strtotime($event['startdatum']) - (ko_get_setting('rota_weekstart') * 3600 * 24)));
    }

    $people = array();
    $schedulling = db_select_data('ko_rota_schedulling',
        "WHERE `event_id` IN ('" . implode("','", $event_ids) . "')" . $z_where, '*', '', '', false, true);
    foreach ($schedulling as $schedule) {
        foreach (explode(',', $schedule['schedule']) as $s) {
            $s = trim($s);
            if (is_numeric($s)) {  //Person id
                ko_get_person_by_id($s, $p);
                $people[$schedule['team_id']][$p['id']] = $p;
            } else {
                if (preg_match('/^g[0-9]{6}$/', $s)) {  //Group id
                    $rows = db_select_data('ko_leute',
                        "WHERE `groups` REGEXP '$s' AND `deleted` = '0' AND `hidden` = '0'");
                    foreach ($rows as $row) {
                        $people[$schedule['team_id']][$row['id']] = $row;
                    }
                } else {  //Text
                    //Don't include in recipients list
                }
            }
        }
    }

    return $people;
}//ko_rota_get_recipients_by_event_by_teams()


/**
 * Returns team members/leaders for a rota team
 *
 * @param int /array $team teamID or team Array to get members for
 * @param boolean $resolve_groups Set to true to get group members as single people, otherwise just get whole groups
 * @param int $roleid Give a role ID to only get members/leaders according to this roleID (e.g. 0000XY)
 * @return Array with two keys: groups and people
 */
function ko_rota_get_team_members($team, $resolve_groups = false, $roleid = '')
{
    //Return from cache
    $tid = is_array($team) ? $team['id'] : $team;
    if (!$resolve_groups && isset($GLOBALS['kOOL']['rota_team_members'][$tid])) {
        return $GLOBALS['kOOL']['rota_team_members'][$tid];
    }


    if (!is_array($team)) {
        $team = db_select_data('ko_rota_teams', "WHERE `id` = '$team'", '*', '', '', true);
    }
    if (!$team['group_id']) {
        return array('people' => array(), 'groups' => array());
    }

    $r = array();

    //First get all subgroups of the given groups
    $not_leaves = db_select_distinct('ko_groups', 'pid');
    $gids = explode(',', $team['group_id']);
    foreach ($gids as $k => $v) {
        $gids[$k] = format_userinput($v, 'uint');
    }
    ko_get_groups($top, 'AND `id` IN (' . implode(',', $gids) . ')', '', 'ORDER BY name ASC');

    $level = 0;
    $g = array();
    foreach ($top as $t) {
        rec_groups($t, $g, '', $not_leaves, false);
    }//foreach(top)

    $r['groups'] = $g;


    //Then get all people assigned to the selected groups/roles
    if (ko_get_setting('rota_showroles') == 1) {  //Group select already shows roles so don't add the general role here
        $role = '';
    } else {  //Only groups get selected so add general role if set
        $teamrole = ko_get_setting('rota_teamrole');
        $role = $teamrole ? ':r' . $teamrole : '';
    }
    //'all' makes sure we return all team members (leaders and members)
    if ($roleid == 'all') {
        $role = '';
    } //roleid given as argument overwrites settings
    else {
        if ($roleid != '') {
            $role = ':r' . $roleid;
        }
    }

    //Add sql for each given group/role
    foreach (explode(',', $team['group_id']) as $gid) {
        if ($role && $rolepos = strpos($gid, ':r')) {  //Remove role in group_id if set
            $gid = substr($gid, 0, $rolepos);
        }
        $where .= " `groups` REGEXP '(^|,|:)" . $gid . $role . "($|,|:r)' OR ";
    }


    //Add members from groups above
    if ($resolve_groups) {
        foreach ($r['groups'] as $group) {
            $where .= " `groups` REGEXP 'g" . $group['id'] . ($role != '' ? '(g0-9:)*' . $role : '') . "' OR ";
        }
    }


    $where = substr($where, 0, -3);


    //Sorting
    $orderby = ko_get_userpref($_SESSION['ses_userid'], 'rota_orderby');
    if (!$orderby) {
        $orderby = 'vorname';
    }
    if ($orderby == 'nachname') {
        $orderby = 'nachname,vorname';
    } else {
        if ($orderby == 'vorname') {
            $orderby = 'vorname,nachname';
        }
    }

    $rows = db_select_data('ko_leute', "WHERE ($where) AND `deleted` = '0'", '*', 'ORDER BY ' . $orderby . ' ASC');
    $p = array();
    foreach ($rows as $row) {
        $p[$row['id']] = $row;
    }

    $r['people'] = $p;

    //Store in cache
    if (!$resolve_groups) {
        $GLOBALS['kOOL']['rota_team_members'][$team['id']] = $r;
    }

    return $r;
}//ko_rota_get_team_members()


/**
 * kept for backwards compatibility (needed to display old changes in person's history)
 */
function ko_dienstliste($dienste)
{
    if (!$dienste) {
        return false;
    }

    $r = '';
    $dienste_a = explode(',', $dienste);
    $all_teams = db_select_data('ko_rota_teams');
    foreach ($dienste_a as $d) {
        $ad = $all_teams[$d];
        if ($ad[$d]['name']) {
            $r .= $ad[$d]['name'] . ', ';
        }
    }
    $r = substr($r, 0, -2);

    return $r;
}//ko_dienstliste()


/**
 * calculates array_intersect(array1, array2) in time O(n+m).
 * ARRAYS MUST BE SORTED! KEYS MUST BE ASCENDING FROM, 0,1,2,3,...,n
 *
 * @param array $sortedArray1
 * @param array $sortedArray2
 * @return array sorted
 */
function ko_fast_array_intersect(array $sortedArray1, array $sortedArray2)
{
    $result = array();
    $done = false;
    $i = 0;
    $j = 0;
    $si = sizeof($sortedArray1);
    $sj = sizeof($sortedArray2);
    $xi = null;
    $xj = null;
    while (!$done) {
        $xi = $sortedArray1[$i];
        $xj = $sortedArray2[$j];
        if ($xi == $xj) {
            $result[] = $xj;
            $i++;
            $j++;
        } else {
            if ($xi > $xj) {
                $j++;
            } else {
                $i++;
            }
        }
        if ($i >= $si || $j >= $sj) {
            $done = true;
        }
    }
    return $result;
}

/**
 * calculates array_unique of a sorted array
 *
 * @param array $sortedArray1
 * @return array
 */
function ko_fast_array_unique(array $sortedArray1)
{
    $lastElem = null;
    $result = array();
    foreach ($sortedArray1 as $entry) {
        if ($entry == $lastElem) {
            continue;
        }
        $result[] = $entry;
        $lastElem = $entry;
    }
    return $result;
}


/**
 * returns the status of an event
 *
 * @param $teamId
 * @param $eventId
 * @return int 1 = open, 2 = closed
 */
function ko_rota_get_status($teamId, $eventId)
{
    $event = db_select_data("ko_rota_schedulling", "where `team_id` = " . $teamId . " and `event_id` = " . $eventId,
        'status', '', '', true, true);
    $eventStatus = $event['status'];
    $eventStatus = $eventStatus == null ? 1 : $eventStatus;
    return $eventStatus;
} // ko_rota_get_status()


/**
 * @param $sorting
 * @param $zWhere
 */
function ko_rota_get_all_teams($orderBy = 'userdef', $zWhere = '')
{
    $zWhere = 'where 1=1 ' . $zWhere;
    if ($orderBy == 'userdef') {
        $orderBy = 'order by `sort` asc';
    }
    $teams = db_select_data('ko_rota_teams', $zWhere, '*', $orderBy);
    return $teams;
}


/**
 * returns all events where $id was scheduled for during the supplied time frame
 *
 * @param $id the id of the person or group
 * @param $start the minimal ending time of the event, Y-m-d H:i:s
 * @param $stop the maximal starting time of the event
 * @param string $mode either 'person' or later 'group' // TODO : implement group functionality
 */
function ko_rota_get_scheduled_events($id, $start, $stop, $mode = 'person')
{
    global $BASE_PATH;

    if (array_key_exists('ko_scheduled_events', $GLOBALS['kOOL'])) {
        if (array_key_exists($id . $start . $stop . $mode, $GLOBALS['kOOL']['ko_scheduled_events'])) {
            return $GLOBALS['kOOL']['ko_scheduled_events'][$id . $start . $stop . $mode];
        }
    } else {
        $GLOBALS['kOOL']['ko_scheduled_events'] = array();
    }

    $role = ko_get_setting('rota_teamrole');
    $roleString = (trim($role) == '' ? '' : ':r' . $role);

    // get all non-leaf groups associated with a team
    if (array_key_exists('ko_non_leaf_team_groups', $GLOBALS['kOOL'])) {
        $nonLeafTeamGroups = $GLOBALS['kOOL']['ko_non_leaf_team_groups'];
    } else {
        $teams = db_select_data('ko_rota_teams', 'where 1=1', 'group_id');
        $nonLeafTeamGroups = array();
        $teamGroups = array();
        foreach ($teams as $team) {
            foreach (explode(',', $team['group_id']) as $teamGroup) {
                $teamGroup = trim($teamGroup);
                if ($teamGroup == '') {
                    continue;
                }
                if (preg_match('/^g[0-9]{6}$/', $teamGroup)) {
                    $teamGroups[] = substr($teamGroup, 1);
                } else {
                    if (preg_match('/^g[0-9]{6}:r[0-9]{6}$/', $teamGroup)) {
                        $teamGroups[] = substr($teamGroup, 1, 6);
                    }
                }
            }
        }


        if (sizeof($teamGroups) != 0) {
            $res = db_query("select distinct `id` from `ko_groups` g1 where `id` in ('" . implode("','",
                    $teamGroups) . "') and not exists (select `id` from `ko_groups` g2 where g2.`pid` = g1.`id`) order by g1.`id` asc;");
            foreach ($res as $nonLeafTeamGroup) {
                $nonLeafTeamGroups[] = (int)$nonLeafTeamGroup["id"];
            }
        }
        $GLOBALS['kOOL']['ko_non_leaf_team_groups'] = $nonLeafTeamGroups;
    }

    if (substr($id, 0, 1) != 'g') {
        ko_get_person_by_id($id, $person);
        if (!$person) {
            return;
        }
        $groupsString = $person['groups'];
        if (trim($groupsString) == '') {
            return;
        }

        $unprocGroups = array();
        foreach (explode(',', $groupsString) as $group) {
            if (trim($group) == '') {
                continue;
            }
            if ($roleString != '') { // only consider group memberships with 'helper' role
                if (substr($group, -8, 8) != $roleString) {
                    continue;
                }
                $group = substr($group, 0, strlen($group) - 8);
            } else {
                if (substr($group, -7,
                        1) == 'r'
                ) { // remove role so we won't search for it in the `schedule` column of ko_rota_schedulling
                    $group = substr($group, 0, strlen($group) - 8);
                }
            }
            $explodedGroups = explode(':', $group);
            foreach ($explodedGroups as $singleGroup) {
                if (trim($singleGroup) == '') {
                    continue;
                }
                $unprocGroups[] = (int)substr($singleGroup, 1);
            }
        }
        sort($unprocGroups);

        // get the intersection of all groups of the person and all non-leaf groups associated with a team
        $helperGroups = ko_fast_array_unique(ko_fast_array_intersect($nonLeafTeamGroups, $unprocGroups));

        $regexp = '(((,|^)' . $id . '(,|$))' . (sizeof($helperGroups) == 0 ? ')' : '|' . implode('|',
                    $helperGroups) . ')');
        $zWhere = " and `ko_rota_schedulling`.`schedule` regexp '" . $regexp . "'";


        $timeFilterEvents1 = " AND TIMESTAMPDIFF(SECOND,CONCAT(CONCAT(`ko_event`.`startdatum`, ' '), `ko_event`.`startzeit`),'" . $stop . "') >= 0";
        $timeFilterEvents2 = " AND TIMESTAMPDIFF(SECOND,CONCAT(CONCAT(`ko_event`.`enddatum`, ' '), `ko_event`.`endzeit`),'" . $start . "') <= 0";
        $timeFilterEvents = $timeFilterEvents1 . $timeFilterEvents2;

        $zWhere .= $timeFilterEvents;

        $events = array();
        $res = db_query("select `ko_event`.`id`,`ko_event`.`startdatum`,`ko_event`.`enddatum`,`ko_event`.`startzeit`,`ko_event`.`endzeit`,`ko_rota_schedulling`.`team_id` from `ko_rota_schedulling`, `ko_event` where `ko_rota_schedulling`.`event_id` = `ko_event`.`id` " . $zWhere);
        foreach ($res as $k => $event) {
            if (array_key_exists($events, $event['id'])) {
                $events[$event['id']]['in_teams'][] = $event['team_id'];
            } else {
                $events[$event['id']] = $event;
                $events[$event['id']]['in_teams'] = array($event['team_id']);
            }
        }

        // add weekly events
        $startUnix = strtotime($start);
        $startWeekDay = date('w', $startUnix);
        $stopUnix = strtotime($stop);
        $stopWeekDay = date('w', $stopUnix);
        // correct year depending on in which year the thursday of the current week lies
        $startDBForm = date('Y-W', $startUnix + (4 - $startWeekDay) * 3600 * 24);
        $stopDBForm = date('Y-W', $stopUnix + (4 - $stopWeekDay) * 3600 * 24);

        $zWhere = " and `schedule` regexp '" . $regexp . "'";
        $zWhere .= " and `event_id` regexp '[0-9]{4}-[0-9]{2}' and `event_id` >= '" . $startDBForm . "' and `event_id` <= '" . $stopDBForm . "'";

        $weeklyEvents = array();
        $res = db_query("select * from `ko_rota_schedulling` where 1=1 " . $zWhere);
        foreach ($res as $k => $weeklyEvent) {
            if (array_key_exists($weeklyEvents, $weeklyEvent['event_id'])) {
                $weeklyEvents[$weeklyEvent['event_id']]['in_teams'][] = $weeklyEvent['team_id'];
            } else {
                $weeklyEvents[$weeklyEvent['event_id']] = $weeklyEvent;
                $weeklyEvents[$weeklyEvent['event_id']]['in_teams'] = array($weeklyEvent['team_id']);
            }
        }

        foreach ($weeklyEvents as $k => $weeklyEvent) {
            list($year, $week) = explode('-', $k);
            $eventStart = strtotime("{$year}-W{$week}-1");
            $eventStop = strtotime("{$year}-W{$week}-7");


            if ($eventStop >= strtotime($start) && $eventStart <= strtotime($stop)) {
                $events[$k] = array(
                    'id' => $k,
                    'startdatum' => date('Y-m-d', $eventStart),
                    'startzeit' => date('H:i:s', $eventStart),
                    'enddatum' => date('Y-m-d', $eventStop),
                    'endzeit' => date('H:i:s', $eventStop),
                    'in_teams' => $weeklyEvent['in_teams']
                );
            }
        }

    } else {
        // TODO : group functionality
        $events = array();
    }

    // cache result
    $GLOBALS['kOOL']['ko_scheduled_events'][$id . $start . $stop . $mode] = $events;

    return $events;
} // ko_rota_get_scheduled_events()


function ko_rota_get_participation($id, $teamId)
{

    $result = array();
    $result[$teamId] = array();
    $result['all'] = array();
    $result['all']['month'] = 0;
    $result['all']['quarter'] = 0;
    $result['all']['year'] = 0;
    $result[$teamId]['month'] = 0;
    $result[$teamId]['quarter'] = 0;
    $result[$teamId]['year'] = 0;

    $events = ko_rota_get_scheduled_events($id, date('Y-m-d H:i:s', strtotime('-1 year')), date('Y-m-d H:i:s'),
        strtotime('+1 day'));

    foreach ($events as $event) {
        $endTime = strtotime($event['enddatum'] . ' ' . $event['endzeit']);
        $now = time();
        $inArray = in_array($teamId, $event['in_teams']);

        $result['all']['year'] += 1;
        if ($inArray) {
            $result[$teamId]['year'] += 1;
        }
        if ($now - $endTime <= 3600 * 24 * 90) {
            $result['all']['quarter'] += 1;
            if ($inArray) {
                $result[$teamId]['quarter'] += 1;
            }
        }
        if ($now - $endTime <= 3600 * 24 * 30) {
            $result['all']['month'] += 1;
            if ($inArray) {
                $result[$teamId]['month'] += 1;
            }
        }


    }
    return $result;
} // ko_rota_get_participation()


