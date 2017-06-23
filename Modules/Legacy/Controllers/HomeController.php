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


namespace Peregrinus\Flockr\Legacy\Controllers;


class HomeController extends AbstractLegacyController
{

    public function initializeController()
    {
        global $ko_path;
        parent::initializeController();

        // javascript
    }

    public function indexAction()
    {

        // PortalModules

        foreach (['sidebar', 'main'] as $position) {
            $output = '';
            $portalModules = \Peregrinus\Flockr\Core\Loaders\PortalModuleLoader::load(
                \Peregrinus\Flockr\Legacy\Services\LoginService::getInstance()->getUserId(),
                $position
            );
            foreach ($portalModules as $portalModule) {
                // check if we have an action set for this PortalModule?
                if (isset($_REQUEST['portalModule'][$portalModule->getKey()])) {
                    $data = $_REQUEST['portalModule'][$portalModule->getKey()];
                    $portalAction = isset($data['action']) ? format_userinput($data['action'], 'alphanum') : 'index';
                    $portalData = $data;
                } else {
                    $portalAction = 'index';
                    $portalData = null;
                }
                $output .= $portalModule->render($portalAction, $portalData);
            }
            $this->view->assign($position, $output);
        }


    }

    public function logoutAction() {
        $this->forward('index');
    }

}