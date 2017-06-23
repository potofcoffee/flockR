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


use Peregrinus\Flockr\Core\ConfigurationManager;
use Peregrinus\Flockr\Core\Debugger;

class ConfigurationService
{

    protected static $instance = null;

    protected $config = [];

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new ConfigurationService();
        }
        return self::$instance;
    }

    public function getConfig()
    {
        // Check cache
        if (!count($this->config)) {
            //require_once (FLOCKR_basePath.'config/ko-config.php');
            //$this->config = get_defined_vars();
            $this->config = array_merge(
                ConfigurationManager::getInstance()->getConfigurationSet('Setup'),
                ConfigurationManager::getInstance()->getConfigurationSet('Constants'),
                ConfigurationManager::getInstance()->getConfigurationSet('FormLayout', 'Configuration/People/')
            );
            ConfigurationManager::getInstance()->loadAsConstants(
                ConfigurationManager::getInstance()->getConfigurationSet('Constants')
            );
        }
        return $this->config;
    }
}