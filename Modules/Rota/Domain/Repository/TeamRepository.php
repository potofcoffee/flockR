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
use Peregrinus\Flockr\Core\Services\SessionService;
use Peregrinus\Flockr\Core\Services\SettingsService;

class TeamRepository extends AbstractRepository
{

    protected $table = 'ko_rota_teams';
    protected $order = 'name ASC';


    /**
     * Find all teams
     * @param bool $respectOrder respect order set in settings? (default: no, which behaves like normal findAll())
     * @param bool $checkAccess check access settings (default: no, which behaves like normal findAll())
     * @return array
     */
    public function findAll($respectOrder = false, $checkAccess = false)
    {
        if ($respectOrder) $this->setDefinedOrder();
        $teams = parent::findAll();
        if ($checkAccess) $teams = $this->checkAccess($teams);
        return $teams;
    }


    protected function setDefinedOrder() {
        if ($order = SessionService::getInstance()->getArgument('sort_rota_teams')) {
            $this->setOrder($order.' '.SessionService::getInstance()->getArgument('sort_rota_teams_order'));
        } else {
            if (SettingsService::getInstance()->getGlobalSetting('rota_manual_ordering')) {
                $this->setOrder('sort ASC');
            } else {
                $this->setOrder('name ASC');
            }
        }
    }

    protected function checkAccess($teams) {
        // check access to teams
        $accessService = AccessService::getInstance();
        if (!$accessService->hasAccess('rota', 'ALL', 1)) {
            foreach ($teams as $key => $team) {
                if (!$accessService->hasAccess('rota', $team['id'], 1)) {
                    unset($teams[$key]);
                }
            }
        }
        return $teams;
    }

    public function findByEventGroup($eventGroup, $respectOrder = false, $checkAccess = false, $returnOnlyIds = false) {
        if ($respectOrder) $this->setDefinedOrder();
        $teams = parent::find("WHERE `eg_id` REGEXP '(^|,)$eventGroup(,|$)' AND `rotatype` = 'event'");
        if ($checkAccess) $teams = $this->checkAccess($teams);
        if ($returnOnlyIds) {
            return $this->getIds($teams);
        } else {
            return $teams;
        }
    }


    /**
     * @param array $teams Teams
     * @param array $allowedTeams Whitelist
     * @return array Filtered teams
     */
    public function filterByWhitelist(array $teams, array $allowedTeams) : array {
        foreach ($teams as $key => $team) {
            if (!in_array($team, $allowedTeams)) unset($teams[$key]);
        }
        return $teams;
    }



    /**
     * Get teams according to session setting
     * @return array Teams
     */
    public function getFromSession() : array {
        $teams = SessionService::getInstance()->getArgument('rota_teams');

        if (sizeof($teams) == 0) {
            $teams = $this->findAll(true, true);
        } else {
            foreach ($teams as $key => $team) {
                $teams[$key] = $this->findOneByUid($team);
            }
        }
        return $teams;
    }


}