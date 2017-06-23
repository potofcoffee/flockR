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


namespace Peregrinus\Flockr\Core;

use Peregrinus\Flockr\Core\Domain\User;
use Peregrinus\Flockr\Core\Domain\UserPermission;

/**
 * Class SecurityContext
 * @package Peregrinus\Flockr\Core
 */
class SecurityContext
{
    /**
     * User
     * @var \Peregrinus\Flockr\Core\Domain\User
     */
    protected $user = null;

    public function __construct()
    {
    }

    /**
     * Check if a user is logged in
     * @return bool Login status
     */
    public function isLoggedIn() {
        return !(!$_SESSION['ses_username'] || $_SESSION['ses_username'] == 'ko_guest');
    }


    /**
     * Get the currently logged in user
     * @return \Peregrinus\Flockr\Core\Domain\User User
     */
    public function getUser() {
        if (!$this->user) {
            $userName = $_SESSION['ses_username'] ? $_SESSION['ses_username'] : 'ko_guest';
            $userRepository = \Peregrinus\Flockr\Core\App::getInstance()->entityManager->getRepository('Peregrinus\Flockr\Core\Domain\User');
            $this->user = $userRepository->findOneByLogin($userName);
        }
        return $this->user;
    }

    /**
     * Get the relevant security data for the final view
     * @return array Security data
     */
    public function getSecurityData() {
        return [
            'isLoggedIn' => $this->isLoggedIn(),
            'user' => $this->getUser(),
        ];
    }


}