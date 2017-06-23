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


namespace Peregrinus\Flockr\Core\Controllers\PortalModules;


use Peregrinus\Flockr\Core\AbstractController;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class WelcomeController extends AbstractController
{

    protected $loginService = null;

    public function __construct($module)
    {
        parent::__construct($module);
        $this->loginService = LoginService::getInstance();
    }

    public function indexAction() {
        $this->view->assign('welcomeGuest', \ko_get_setting('guest_portal_note'));
        $this->view->assign('isGuest', $this->loginService->isGuest());
    }

}