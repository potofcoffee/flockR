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


namespace Peregrinus\Flockr\Core\Services;


use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\ConfigurationManager;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Domain\User;

class PermissionsService
{

    protected $permissionsArray = [];

    public function __construct($modules) {
        foreach ($modules as $module) {
            $moduleConfiguration = ConfigurationManager::getInstance()->getConfigurationSet('Security', 'Modules/'.$module->moduleInfo['module'].'/Configuration');
            if (isset($moduleConfiguration['access'])) {
                $this->permissionsArray = array_merge_recursive($moduleConfiguration['access'], $this->permissionsArray);
            }
        }
    }


    /**
     * Check if a user has a specific permission
     *
     * TODO: implement PermissionService->hasPermission()
     * This is only a @stub so far, which will grant every permission to user christoph.fischer
     *
     * @param string $permission Permission
     * @param \Peregrinus\Flockr\Core\Domain\User|null $user User (leave blank for current user
     */
    public function hasPermission($permission, $user = null) {

        if (!$user) {
            $user = \Peregrinus\Flockr\Core\App::getInstance()->getSecurityContext()->getUser();
        }
        if (is_null($user)) return false; // guest user
        return ($user->getLogin() == 'christoph.fischer');
    }

    /**
     * Get the permission string for a module/controller/action set
     * @param string|\Peregrinus\Flockr\Core\AbstractModule $module Module
     * @param string $controller Controller
     * @param string $action Action
     * @return string Permission string
     */
    public function getPermissionString($module, $controller, $action) {
        if (is_object($module)) $module = substr($module->moduleInfo['ns'], 0, -1);
        return $module.':'.$controller.'/'.$action;
    }

    /**
     * Get all permissions for a user
     * @param User|null $user User (leave blank for currently logged-in user)
     * @return int Permission level
     */
    public function getAllPermissions(User $user = null) {
        if (!$user) {
            $user = \Peregrinus\Flockr\Core\App::getInstance()->getSecurityContext()->getUser();
        }
        $permissionsRepository = App::getInstance()->getEntityManager()->getRepository('Peregrinus\Flockr\Core\Domain\UserPermission');
        return $permissionsRepository->findByUser($user->getId());
    }


    /**
     * Get a specific permission record for a user
     * @param string $module Module
     * @param string $object Object (leave blank for *)
     * @param User|null $user User (leave blank for currently logged-in user)
     */
    public function getPermissionRecord ($module, $object = '*', User $user = null) {
        if (!$user) {
            $user = \Peregrinus\Flockr\Core\App::getInstance()->getSecurityContext()->getUser();
        }
        $permissionsRepository = App::getInstance()->getEntityManager()->getRepository('Peregrinus\Flockr\Core\Domain\UserPermission');
        $result = App::getInstance()->getEntityManager()->createQuery(
            'SELECT p FROM Peregrinus\Flockr\Core\Domain\UserPermission p WHERE (p.user = '.$user->getId().') AND (p.module = \''.$module.'\') AND (p.object = \''.$object.'\')'
        )->getResult();
        if (isset($result[0])) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Get a specific permission for a user
     * @param string $module Module
     * @param string $object Object (leave blank for *)
     * @param User|null $user User (leave blank for currently logged-in user)
     */
    public function getPermission ($module, $object = '*', User $user = null) {
        $result = $this->getPermissionRecord($module, $object, $user);
        if ($result) {
            return $result->getLevel();
        } else {
            return '';
        }
    }


    /**
     * Get the access expectation for a module/controller/action route
     * @param string $module Module name
     * @param string $controller Controller name
     * @param string $action Action
     * @return array Expectation
     */
    public function getExpectedAccessLevel($module, $controller, $action) {
        if (isset($this->permissionsArray[$module][$controller][$action])) {
            $access = $this->permissionsArray[$module][$controller][$action];
            if (!isset($access['module'])) $access['module'] = $module;
            if (!isset($access['object'])) $access['object'] = '*';
            return $access;
        } else {
            return [];
        }
    }


}