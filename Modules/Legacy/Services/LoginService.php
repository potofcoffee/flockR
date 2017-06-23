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


use Peregrinus\Flockr\Core\DB;
use Peregrinus\Flockr\Core\Services\SessionService;

class LoginService
{

    protected static $instance = NULL;

    /**
     * Get instance
     * Implements singleton pattern
     * @return LoginService
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
    }

    /**
     * Get the id of the current user
     * @return int User id
     */
    public function getUserId() {
        return (int)SessionService::getInstance()->getArgument('ses_userid');
    }

    /**
     * Get the current user's data
     * @return mixed User data
     */
    public function getUser() {
        \ko_get_login($this->getUserId(), $user);
        return $user;
    }

    /**
     * Checks if a specific user is the guest user
     * @param null $userId User id, if null will default to current user
     * @return bool True if user is guest
     */
    public function isGuest($userId = null) {
        if (is_null($userId)) $userId = $this->getUserId();
        return ($userId == \ko_get_guest_id());
    }

    /**
     * Checks if a user is logged in
     * @return bool True if user is logged in
     */
    public function isLoggedIn() {
        return !$this->isGuest();
    }

    /**
     * Checks whether this should only see events specified by the global time filter
     *
     * @param string $moduleName Module name
     * @param int $userId User id, or null to use current user
     * @return boolean
     */
    public function hasGlobalTimeFilter($moduleName, $userId = null) {
        if (is_null($userId)) $userId = $this->getUserId();
        if (!$module) {
            return false;
        }

        $adminGroups = ko_get_admingroups($userId);
        $admin = DB::getInstance()->select('ko_admin', 'WHERE `id` = :id',
            $module . '_force_global', '', '', true, true, ['id' => $userId]);
        if ($admin[$module . '_force_global'] == 1) {
            return true;
        }
        foreach ($adminGroups as $ag) {
            if ($ag[$module . '_force_global'] == 1) {
                return true;
            }
        }
        return false;

    }

}