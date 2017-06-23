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


namespace Peregrinus\Flockr\Legacy;


use Peregrinus\Flockr\Core\AbstractModule;
use Peregrinus\Flockr\Core\Debugger;

class AbstractLegacyModule extends AbstractModule
{

    /**
     * Get the menu for this module
     * @return array Menu
     */
    public static function getMenu()
    {
        return [];
    }

    /**
     * Get the key for this module
     * @return string Key
     */
    protected function getKey()
    {
        return str_replace('Module', '', (new \ReflectionClass(get_called_class()))->getShortName());
    }

    /**
     * Get a single menu item for this module (or any other)
     * @param string $action Action to be called
     * @param string $moduleKey Optional module key (default: current module)
     * @return array
     */
    protected function getMenuItem($action, $moduleKey = '')
    {
        if ($moduleKey == '') $moduleKey = strtolower($this->getKey());
        return [
            'name' => ko_menuitem($moduleKey, $action),
            'link' => FLOCKR_baseUrl.$moduleKey.'/index.php?action='.$action,
        ];
    }

    /**
     * Called on app initialization
     * @param App $app App
     */
    public function onInitializeApp(\Peregrinus\Flockr\Core\App $app)
    {


        $configService = \Peregrinus\Flockr\Legacy\Services\ConfigurationService::getInstance();
        $config = $configService->getConfig();

        // add legacy translations to translation store
        foreach (glob(FLOCKR_basePath . 'locallang/*.php') as $languageFile) {
            require_once($languageFile);
        }
        $app->getTranslationService()->addToLanguageStore($LL);
    }


}