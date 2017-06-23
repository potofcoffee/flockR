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


namespace Peregrinus\Flockr\Core\PortalModules;


use Peregrinus\Flockr\Core\AbstractClass;
use Peregrinus\Flockr\Core\AbstractController;
use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Legacy\Services\LoginService;
use Peregrinus\Flockr\Core\Exception\StopActionException;

abstract class AbstractPortalModule extends AbstractClass
{

    protected $module;
    /**
     * @var AbstractController $controller
     */
    protected $controller;
    protected $view;
    protected $exclusiveActions = false;

    /**
     * AbstractPortalModule constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $controllerClass = FLOCKR_NS.$this->moduleInfo['module'].'\\Controllers\\PortalModules\\'.str_replace('PortalModule', 'Controller', $this->moduleInfo['class']);
        $moduleClass = FLOCKR_NS.$this->moduleInfo['module'].'\\'.$this->moduleInfo['module'].'Module';
        $this->module = new $moduleClass();
        $this->controller = new $controllerClass($this->module);
    }

    /**
     * Check if a specific user has access rights to this PortalModule
     * @param int|null $userId User id, defaults to current user
     * @return bool True if user has access rights
     */
    public function availableForUser($userId = null) {
        return true;
    }


    /**
     * @return string Rendered PortalModule
     */
    public function render($action = 'index', $data=null) {
        $this->view = App::getInstance()->createView($this->module, $this->controller, $action, 'PortalModules/');
        $this->controller->setView($this->view);
        try {
            $output = $this->controller->dispatch($action, $data);
        }
        catch (StopActionException $e) {
            $action = $this->controller->getRedirect();
            $output = $this->render($action, $this->controller->getData());
        }

        if (!is_null($output) && (!($output === false))) {
            echo $output;
            exit;
        }

        $this->view->getRenderingContext()->setControllerName($this->controller->getName());

        $this->view->assign('FLOCKR_baseUrl', FLOCKR_baseUrl);

        $this->view->assign('module', $this->controller->moduleInfo['module']);
        $this->view->assign('controller', $this->controller->getName());
        $this->view->assign('action', $action);

        if ($this->controller->showView) {
            //$this->view->sendContentTypeHeader();
            return $this->controller->renderView($action, false);
        }
        

    }

    /**
     * Get the modules configuration key
     * @return string Key
     */
    public function getKey() {
        return lcfirst(str_replace('PortalModule', '', $this->moduleInfo['class']));
    }

    /**
     * @return bool True if this module has exclusive actions
     */
    public function hasExclusiveActions() {
        return $this->exclusiveActions;
    }

}