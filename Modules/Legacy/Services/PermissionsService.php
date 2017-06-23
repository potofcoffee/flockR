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


namespace Peregrinus\Flockr\Legacy\Services;


class PermissionsService
{

    /**
     * Get all access rights for a specific area
     * @param string $area Area or module
     * @param \Peregrinus\Flockr\Core\Domain\User $user User (leave blank for currently logged-in user)
     * @param bool $getMaximum Get maximum possible area rights instead
     * @return int|mixed Access level
     */
    public function getAllAccessRights($area, \Peregrinus\Flockr\Core\Domain\User $user = null, $getMaximum = false)
    {
        $maxAccessLevel = 0;
        $accessLevel = 0;
        if (defined('ALL_ACCESS')) {
            $user = \Peregrinus\Flockr\Core\App::getInstance()
                ->getEntityManager()
                ->getRepository('Peregrinus\Flockr\Core\Domain\User')
                ->findByLogin('root');
        }
        if (is_null($user)) {
            $user = \Peregrinus\Flockr\Core\App::getInstance()
                ->getSecurityContext()
                ->getUser();

        }

        //Fake access rights for tools module for root user
        if ($area == 'tools' && $user->getId() == 'root') {
            return 4;
        }

        //Accept module name instead of col name as well
        $accessCheckMethod = 'getAccessLevelForModule' . ucfirst(str_replace('_admin', '', $area));

        // TODO: continue here
        // Check settings for login
        $rights = explode(',', $user->$accessCheckMethod());
        foreach ($rights as $right) {
            if (false === strpos($right, "@")) {
                $accessLevel = $right;
            }
            $maxAccessLevel = max($maxAccessLevel, substr($right, 0, 1));
        }

        $userGroups = $user->getUserGroups();
        foreach ($userGroups as $group) {
            $groupRights = $group->$accessCheckMethod();
            foreach ($groupRights as $right) {
                if (false === strpos($right, "@")) {
                    $accessLevel = $right;
                }
                $maxAccessLevel = max($maxAccessLevel, substr($right, 0, 1));
            }
            if ($area == 'leute_admin') {
                $peopleFilter = $group->getPeopleFilter();
                if ($maxAccessLevel < 3 && $peopleFilter[3]) {
                    $maxAccessLevel = 3;
                } else {
                    if ($maxAccessLevel < 2 && $peopleFilter[2]) {
                        $maxAccessLevel = 2;
                    } else {
                        if ($maxAccessLevel < 1 && $peopleFilter[1]) {
                            $maxAccessLevel = 1;
                        }
                    }
                }

            }
        }

        //Check for settings for admingroups
        if ($rights["admingroups"]) {
            $admingroups = db_select_data("ko_admingroups",
                "WHERE `id` IN ('" . implode("','", explode(",", $rights["admingroups"])) . "')");
            foreach ($admingroups as $ag) {
                foreach (explode(",", $ag[$area]) as $r) {
                    if (false === strpos($r, "@")) {
                        $value = max($value, $r);
                    }
                    $maxAccessLevel = max($maxAccessLevel, substr($r, 0, 1));
                }
                //Raise max rights for people module if a admin_filter is set for the given access level
                if ($area == 'leute_admin') {
                    $peopleFilter = unserialize($ag['leute_admin_filter']);
                    $maxAccessLevel = $this->raiseMaxAccessLevelByPeopleFilter($peopleFilter, $maxAccessLevel);
                }
            }
        }

        //Raise max rights for people module if a admin_filter is set for the given access level
        if ($area == 'leute_admin') {
            $peopleFilter = $user->getPeopleFilter();
            $maxAccessLevel = $this->raiseMaxAccessLevelByPeopleFilter($peopleFilter, $maxAccessLevel);
        }

        if ($area == 'groups_admin') {
            $entityManager = \Peregrinus\Flockr\Core\App::getInstance()
                ->getEntityManager();

            if ($maxAccessLevel < 4) {
                if ((count($entityManager->createQuery('SELECT g FROM Peregrinus\Flockr\Core\Domain\Group g WHERE ' . $user->getId() . 'IN deleteRights')
                        ->getResult())) > 0
                ) {
                    $maxAccessLevel = 4;
                }
            }
            if ($maxAccessLevel < 3) {
                if ((count($entityManager->createQuery('SELECT g FROM Peregrinus\Flockr\Core\Domain\Group g WHERE ' . $user->getId() . 'IN editRights')
                        ->getResult())) > 0
                ) {
                    $maxAccessLevel = 3;
                }
            }
            if ($maxAccessLevel < 2) {
                if ((count($entityManager->createQuery('SELECT g FROM Peregrinus\Flockr\Core\Domain\Group g WHERE ' . $user->getId() . 'IN createRights')
                        ->getResult())) > 0
                ) {
                    $maxAccessLevel = 2;
                }
            }
            if ($maxAccessLevel < 1) {
                if ((count($entityManager->createQuery('SELECT g FROM Peregrinus\Flockr\Core\Domain\Group g WHERE ' . $user->getId() . 'IN viewRights')
                        ->getResult())) > 0
                ) {
                    $maxAccessLevel = 1;
                }
            }
        }

        return ($getMaximum ? $maxAccessLevel : $accessLevel);

    }

    protected function raiseMaxAccessLevelByPeopleFilter($peopleFilter, $maxAccessLevel)
    {
        if ($maxAccessLevel < 3 && $peopleFilter[3]) {
            $maxAccessLevel = 3;
        } else {
            if ($maxAccessLevel < 2 && $peopleFilter[2]) {
                $maxAccessLevel = 2;
            } else {
                if ($maxAccessLevel < 1 && $peopleFilter[1]) {
                    $maxAccessLevel = 1;
                }
            }
        }
        return $maxAccessLevel;
    }


}