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


use Peregrinus\Flockr\Core\AbstractController;
use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Legacy\LegacyLayout;
use Peregrinus\Flockr\Legacy\LegacyApp;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class AbstractLegacyController extends AbstractController
{
    /** @var  LegacyLayout $layout */
    protected $layout;
    protected $app;

    protected function initializeController()
    {

        parent::initializeController();
        $this->app = App::getInstance();
        //$this->layout = new LegacyLayout();
        $this->layout = $this->app->getLayout();

        $this->layout->set('title', $GLOBALS['HTML_TITLE']);
        $this->layout->set('shortcutIcon', FLOCKR_baseUrl.'images/kOOL_logo.ico');
        $this->layout->set('lang', $_SESSION["lang"]);
        $this->layout->set('hardcodedCSS', ko_include_css());
        if (!LoginService::getInstance()->isGuest()) {
            $this->layout->addJavaScript('header', 'core/js/sessionTimeout', false);
        }

        $this->layout->set('onloadCode', $GLOBALS['onload_code']);
    }

    public function renderView($action, $show = true)
    {
        $this->view->assign('layout', $this->layout->getViewData());
        return parent::renderView($action, $show);
    }

}