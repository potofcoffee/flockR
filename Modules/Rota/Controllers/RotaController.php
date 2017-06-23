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


namespace Peregrinus\Flockr\Rota\Controllers;


use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Domain\Group;
use Peregrinus\Flockr\Core\Router;
use Peregrinus\Flockr\Core\Services\AccessService;
use Peregrinus\Flockr\Core\Services\SessionService;
use Peregrinus\Flockr\Core\Services\SettingsService;
use Peregrinus\Flockr\Core\Settings\DoubleSelectSetting;
use Peregrinus\Flockr\Core\Settings\RichTextSetting;
use Peregrinus\Flockr\Core\Settings\SelectSetting;
use Peregrinus\Flockr\Core\Settings\Setting;
use Peregrinus\Flockr\Core\Settings\TextAreaSetting;
use Peregrinus\Flockr\Core\Utility\ArrayUtility;
use Peregrinus\Flockr\Legacy\Controllers\AbstractLegacyController;
use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Legacy\Services\LoginService;
use Peregrinus\Flockr\Rota\Domain\Repository\EventGroupRepository;
use Peregrinus\Flockr\Rota\Domain\Repository\EventRepository;
use Peregrinus\Flockr\Rota\Domain\Repository\GroupRepository;
use Peregrinus\Flockr\Rota\Domain\Repository\PeopleRepository;
use Peregrinus\Flockr\Rota\Domain\Repository\TeamRepository;
use Peregrinus\Flockr\Core\Services\HookService;
use Peregrinus\Flockr\Rota\RecipientResolver;
use Peregrinus\Flockr\Rota\Utility\TimeSpanUtility;

class RotaController extends AbstractLegacyController
{

    /**
     * @var SessionService
     */
    protected $sessionService = null;

    /**
     * @var TeamRepository
     */
    protected $teamRepository = null;

    /**
     * @var EventRepository
     */
    protected $eventRepository = null;


    /**
     * @var EventGroupRepository
     */
    protected $eventGroupRepository = null;

    public function initializeController()
    {
        parent::initializeController();
        $this->teamRepository = new TeamRepository();
        $this->eventRepository = new EventRepository();
        $this->eventGroupRepository = new EventGroupRepository();
        $this->sessionService = SessionService::getInstance();
    }

    public function testAction()
    {

    }

    public function planAction()
    {
        App::getInstance()->getLayout()->addStyleSheet('Modules/Rota/Resources/Public/Styles/Schedule.css');
        App::getInstance()->getLayout()->addJavaScript('header', 'Modules/Rota/Resources/Public/Scripts/Schedule.js');

        // get teams
        $teams = $this->getTeams();

        // get event groups
        $eventGroups = $this->getEventGroups();

        $this->view->assign('teams', $teams);
        $this->view->assign('eventGroups', $eventGroups);

    }

    /**
     * Get array of teams to display
     * @return array Teams
     */
    protected function getTeams(): array
    {
        $teams = $this->teamRepository->findAll(false, true);
        $teamsFromSession = $this->sessionService->getArgument('rota_teams');
        if ((!is_array($teamsFromSession)) || (count($teamsFromSession == 0))) {
            $teamsFromSession = [];
            foreach ($teams as $team) {
                $teamsFromSession[] = $team['id'];
            }
            $this->sessionService->setArgument('rota_teams', $teamsFromSession);
        }
        foreach ($teams as $key => $team) {
            $teams[$key]['on'] = in_array($team['id'], $teamsFromSession) ? 1 : 0;
        }
        return $teams;
    }

    protected function getEventGroups(): array
    {
        $eventGroupIds = $this->sessionService->getArgument('rota_egs', []);
        if (sizeof($eventGroupIds) == 0) {
            $eventGroupIds = $this->eventGroupRepository->getIds($this->eventGroupRepository->findByRota(1));
            $this->sessionService->setArgument('rota_egs', $eventGroupIds);
        }
        $eventGroups = $this->eventGroupRepository->findByRota(1);
        foreach ($eventGroups as $key => $eventGroup) {
            $eventGroups[$key]['on'] = in_array($eventGroup['id'], $eventGroupIds) ? 1 : 0;
        }
        return $eventGroups;
    }

    public function servicePlanAction()
    {
        ko_get_events($events, 'AND (startdatum >= \'' . date('Y-m-d') . '\') AND (eventgruppen_id=1)');
        $teams = db_select_data('ko_rota_teams', 'WHERE 1', '*');

        foreach ($events as $key => $event) {
            $events[$key] = $event = ko_rota_get_events('', $event['id']);
            $events[$key]['rota'] = [];
            foreach ($event['teams'] as $teamKey => $team) {
                if (isset($event['schedule'][$team])) {
                    $events[$key]['rota'][$team] = ko_rota_schedulled_text($event['schedule'][$team]);
                }
            }
        }
        $this->layout->addJavaScript('header', 'Modules/Rota/Resources/Public/Scripts/ServicePlan.js');
        $this->view->assign('events', $events);
        $this->view->assign('teams', $teams);

    }

    public function batchUpdateServicesAction()
    {
        if (!$this->request->hasArgument('events'))
            Router::getInstance()->redirect('rota', 'rota', 'servicePlan');
        $events = $this->request->getArgument('events');
        foreach ($events as $eventId => $event) {
            \db_update_data('ko_event', 'WHERE id=' . $eventId, $event);
        }
        Router::getInstance()->redirect('rota', 'rota', 'servicePlan');
    }

    /**
     * Export rota
     * @action export
     */
    public function exportAction()
    {
        $accessService = AccessService::getInstance();
        $peopleRepository = new \Peregrinus\Flockr\People\Domain\Repository\PeopleRepository();

        $this->request->applyUriPattern(['export']);
        $this->forwardIfMissingArguments(['export'], 'plan');
        if (!$accessService->hasAccess('rota', 'MAX', 3)) $this->forward('plan');

        $exportId = $this->request->getArgument('export');
        $allExports = HookService::getInstance()->applyFilters('rota_exports', []);
        if (!isset($allExports[$exportId])) $this->forward('plan');

        $export = $allExports[$exportId];
        if (!is_callable($export['hook'])) $this->forward('plan');

        $timeStart = $this->getFromRequestOrSession('rota_timestart');
        $timeSpan = $this->getFromRequestOrSession('rota_timespan');

        $teams = $this->teamRepository->getFromSession();
        $eventGroups = $this->sessionService->getArgument('rota_egs');
        $events = $this->eventRepository->getDisplayedEvents($timeStart, $timeSpan, $teams, $eventGroups);

        // rota file
        $exportedFile = call_user_func_array($export['hook'], [$timeStart, $timeSpan, $events, $teams, $eventGroups]);

        // sender
        $userPerson = $peopleRepository->findOneByUser();
        $sender = new SelectSetting('sender', 'Absender', 0,
            ArrayUtility::copyValuesToKeys($peopleRepository->getEmailAddresses($userPerson, true, true))
        );

        // subject
        $subject = new Setting('subject', 'Betreff', 0);
        $subject->setValue('[flockR] Dienstplan für ' . TimeSpanUtility::formatAsString($timeStart, $timeSpan));

        // teams
        $teamSelect = new DoubleSelectSetting('teams', 'Dienste', 0, ArrayUtility::extract($teams, 'id', 'name'), []);

        $people = [];
        $showGroups = [];
        $groupRepository = new GroupRepository();
        foreach ($teams as $team) {
            // get all groups for this team
            $groups = $groupRepository->findMultipleWithChildren($team['group_id']);

            // get full group paths for list
            $groupIds = [];
            foreach ($groups as $group) {
                $groupIds[] = $group['id'];
            }
            $groupIds = array_unique($groupIds);
            foreach ($groupIds as $group) {
                $showGroups[$group] = $groupRepository->getFullGroupName($group);
            }

            // get the necessary role, if set
            if (SettingsService::getInstance()->getGlobalSetting('rota_showroles')) {
                $role = SettingsService::getInstance()->getGlobalSetting('rota_teamrole');
                $role = $role ? ':r' . $role : '';
            } else {
                $role = '';
            }

            // find all people in the respective groups
            $where = [];
            foreach ($groups as $group) {
                $where[] .= "`groups` REGEXP 'g" . $group['id'] . ($role != '' ? '(g0-9:)*' . $role : '') . "'";
            }
            $people = array_merge($people, $peopleRepository->find(count($where) ? 'WHERE ' . join(' OR ', $where) : ''));
        }

        //$people = array_unique($people);
        uasort($people, function ($a, $b) {
            return $a['nachname'] . $a['vorname'] <=> $b['nachname'] . $b['vorname'];
        });
        $peopleList = [];
        foreach ($people as $person) $peopleList[$person['id']] = $person['vorname'] . ' ' . $person['nachname'];
        $peopleSelect = new DoubleSelectSetting('individualRecipients', 'Einzelne Empfänger', 0, $peopleList, []);

        sort($showGroups);

        // text
        $text = new TextAreaSetting('text', 'Nachrichtentext', 0);

        // presets
        $presets = array_merge(
            (array)SettingsService::getInstance()->getUserPreference('', -1, 'rota_emailtext_presets', 'ORDER by `key` ASC'),
            (array)SettingsService::getInstance()->getUserPreference('', null, 'rota_emailtext_presets', 'ORDER by `key` ASC')
        );
        $templates = [];
        foreach ($presets as $preset) {
            if ($preset['value'] != '') {
                $templates[$preset['key']] = [
                    'key' => $preset['key'],
                    'label' => ($preset['user_id'] == -1) ? '<span class="fa fa-globe"></span> '.$preset['key'] : $preset['key'],
                    'labelText' => $preset['key'],
                    'value' => $preset['value'],
                    'userId' => $preset['user_id'],
                ];
            }
        }

        // placeholders
        $placeholderKeys = ['_SALUTATION', '_SALUTATION_FORMAL', 'FIRSTNAME', 'LASTNAME', 'TEAM_NAME', 'LEADER_TEAM_NAME', 'PERSONAL_SCHEDULE', 'TEAM_EVENTS', 'TEAM_EVENTS_SCHEDULE', 'LEADER_TEAM_EVENTS', 'LEADER_TEAM_EVENTS_SCHEDULE', 'ALL_EVENTS', 'ALL_EVENTS_SCHEDULE', 'CONSENSUS_LINK'];
        $placeholders = [];
        foreach ($placeholderKeys as $placeholderKey) {
            $placeholders[] = ['key' => $placeholderKey, 'label' => \getLL('rota_placeholder_' . $placeholderKey)];
        }


        // javascript
        App::getInstance()->getLayout()->addJavaScript('header', 'Modules/Rota/Resources/Public/Scripts/Export.js');

        // assign variables
        $this->view->assign('export', $export);
        $this->view->assign('exportedFile', $exportedFile);
        $this->view->assign('timeStart', $timeStart);
        $this->view->assign('timeSpan', $timeSpan);
        $this->view->assign('sender', $sender);
        $this->view->assign('subject', $subject);
        $this->view->assign('teams', $teamSelect);
        $this->view->assign('groups', $showGroups);
        $this->view->assign('people', $peopleSelect);
        $this->view->assign('text', $text);
        $this->view->assign('placeholders', json_encode($placeholders));
        $this->view->assign('templates', json_encode($templates));
    }


    /**
     * Send an export file by email
     * @action sendMail
     */
    public function sendMailAction() {
        $accessService = AccessService::getInstance();
        if (!$accessService->hasAccess('rota', 'MAX', 3)) $this->forward('plan');

        // check required arguments
        $this->forwardIfMissingArguments(['recipientOptions', 'sender', 'teams', 'individualRecipients', 'subject',
            'text', 'attachments'], 'plan');

        $recipientOptions = $this->request->getArgument('recipientOptions');
        $teams = $this->request->getArgument('teams');
        $individuals = $this->request->getArgument('individualRecipients');
        $resolver = new RecipientResolver();
        $resolver->resolve($recipientOptions, $teams, $individuals);

        Debugger::dumpAndDie($_REQUEST);
    }

}                                           