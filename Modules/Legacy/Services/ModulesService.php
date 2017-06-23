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


class ModulesService
{
    /**
     * Check if a module is installed an enabled for a specific user
     * @param string $moduleName Module name
     * @param \Peregrinus\Flockr\Core\Domain\User|null $user User (leave blank for currently logged-in user)
     * @return bool Install status
     */
    public function isInstalled($moduleName, \Peregrinus\Flockr\Core\Domain\User $user = null) {
        // TODO: SecurityContext->hasAllAccess() ?
        if (defined('ALL_ACCESS')) {
            return true;
        }

        if (!$user) {
            $user = \Peregrinus\Flockr\Core\App::getInstance()->getSecurityContext()->getUser();
        }

        $userModules = $this->getUserModules($user);
        return (in_array($moduleName, $userModules));
    }

    /**
     * Get all enabled modules for a specific user
     * @param \Peregrinus\Flockr\Core\Domain\User|null $user User (leave blank for currently logged-in user)
     * @return array List of module names
     */
    public function getUserModules(\Peregrinus\Flockr\Core\Domain\User $user = null) {
        if (!$user) {
            $user = \Peregrinus\Flockr\Core\App::getInstance()->getSecurityContext()->getUser();
        }

        $configService = \Peregrinus\Flockr\Legacy\Services\ConfigurationService::getInstance();

        // TODO: SecurityContext->hasAllAccess() ?
        if (defined("ALL_ACCESS")) {
            return $config['MODULES'];
        }

        // TODO: Get user modules
        $userModules = $user->getModules();
        $userGroups = $user->getUserGroups();
        foreach ($userGroups as $group) {
            $userModules = array_merge($group->getModules(), $userModules);
        }
        return array_unique($userModules);
    }
}