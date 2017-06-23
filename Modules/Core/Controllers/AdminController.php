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


namespace Peregrinus\Flockr\Core\Controllers;


use Peregrinus\Flockr\Core\AbstractController;
use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Router;
use Peregrinus\Flockr\Core\Services\HookService;
use Peregrinus\Flockr\Core\Settings\Setting;
use Peregrinus\Flockr\Core\Settings\SettingsGroup;
use Peregrinus\Flockr\Core\Settings\SettingsPage;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class AdminController extends AbstractController
{

    public function settingsAction()
    {
        $this->view->assign('options', HookService::getInstance()->applyFilters('build_global_settings', []));
        App::getInstance()->getLayout()->addJavaScript('header', 'Modules/Core/Resources/Public/Scripts/SettingsPage.js');
    }

    /**
     * Get settings from request and save them
     * @param string $hook Hook used to build settings array
     * @param string $redirectToAction Redirect to action after saving
     */
    protected function saveSettingsFromRequest($hook, $redirectToAction) {
        $optionPages = HookService::getInstance()->applyFilters($hook, []);
        foreach ($optionPages as $optionPage) {
            /**
             * @var $optionPage SettingsPage
             */
            foreach ($optionPage->getSettings() as $optionGroups) {
                /**
                 * @var $optionGroups SettingsGroup
                 */
                foreach ($optionGroups->getSettings() as $option) {
                    /**
                     * @var $option Setting
                     */
                    if ($this->request->hasArgument($option->getId())) {
                        $option->setValue($this->request->getArgument($option->getId()));
                        $option->persist();
                    }
                }
            }
        }
        Router::getInstance()->redirect('core', 'admin', $redirectToAction);
    }

    public function setSettingsAction()
    {
        $this->saveSettingsFromRequest('build_global_settings', 'settings');
    }


    public function preferencesAction() {
        $this->view->assign('options', HookService::getInstance()->applyFilters('build_preferences', []));
        $this->view->assign('user', LoginService::getInstance()->getUser());
        App::getInstance()->getLayout()->addJavaScript('header', 'Modules/Core/Resources/Public/Scripts/SettingsPage.js');
    }

    public function setPreferencesAction() {
        $this->saveSettingsFromRequest('build_preferences', 'preferences');
    }

}