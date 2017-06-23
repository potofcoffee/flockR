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


namespace Peregrinus\Flockr\Rota;


use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Services\SessionService;
use Peregrinus\Flockr\Rota\Domain\Repository\EventRepository;
use Peregrinus\Flockr\Rota\Domain\Repository\TeamRepository;

class RecipientResolver
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

    public function __construct()
    {
        $this->sessionService = SessionService::getInstance();
        $this->eventRepository = new EventRepository();
        $this->teamRepository = new TeamRepository();
    }

    /**
     * Factory method: resolve recipients
     * @param string $recipientOptions Chosen option
     * @param array $teams Selected teams
     * @param array $individuals Selected individuals
     * @return array Recipients
     */
    public function resolve(string $recipientOptions, array $teams, array $individuals) : array {
        $resolveMethod = 'get'.ucfirst($recipientOptions).'Recipients';
        if (method_exists($this, $resolveMethod)) {
            return $this->$resolveMethod($teams, $individuals);
        } else return [];
    }


    /**
     * Get all scheduled team members
     * @param array $teams Selected teams
     * @param array $individuals Selected individuals
     * @return array Recipients
     */
    protected function getScheduledRecipients(array $teams, array $individuals) : array {
        $timeStart = $this->sessionService->getArgument('rota_timestart');
        $timeSpan = $this->sessionService->getArgument('rota_timespan');

        $teams = $this->teamRepository->getFromSession();
        $eventGroups = $this->sessionService->getArgument('rota_egs');

        $people = [];
        $events = $this->eventRepository->getDisplayedEvents($timeStart, $timeSpan, $teams, $eventGroups);
        foreach ($events as $event) {
            foreach ($event['schedule'] as $schedule) {
                foreach ($schedule['members'] as $member) {
                    if ($member['type'] == 'person') {
                        $people[$member['person']['id']] = $member['person'];
                    }
                }
            }
        }

        return $people;
    }

}
