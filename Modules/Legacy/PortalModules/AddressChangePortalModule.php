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


namespace Peregrinus\Flockr\Legacy\PortalModules;


use Peregrinus\Flockr\Core\PortalModules\AbstractPortalModule;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class AddressChangePortalModule extends AbstractPortalModule
{

    /**
     * @var bool $exclusiveActions This module has exclusive actions
     */
    protected $exclusiveActions = true;

    /**
     * Check if this is the guest user
     * This PortalModule is only available to guests
     * @param int|null $userId User id, defaults to current user
     * @return bool True if user has access rights
     */
    public function availableForUser($userId = null)
    {
        global $access;
        $rights_all = ko_get_access_all('leute_admin', LoginService::getInstance()->getUserId());
        return ($rights_all < 2);
    }

}