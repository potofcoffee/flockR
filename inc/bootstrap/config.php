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

use \Peregrinus\Flockr\Core\ConfigurationManager;

// LOAD CONFIGURATION

// Step 1: load defaults
foreach ([
             'DefaultConfig',
             'Notifier',
             'Ldap',
             'PDFLatex',
             'Modules',
         ] as $default) {
    require_once(KOOL_api . 'defaults/' . $default . '.php');
}

// Step 2: load local configuration (may override the above defaults)
//include(KOOL_configPath.'ko-config.php');

$configurationManager = ConfigurationManager::getInstance();
$legacyConstants = $configurationManager->loadAsConstants(
    $configurationManager->getConfigurationSet('Constants')
);
$configurationManager->loadIntoGlobalSpace(
    $configurationManager->getConfigurationSet('Setup')
);
$configurationManager->loadIntoGlobalSpace(
    $configurationManager->getConfigurationSet('FormLayout', 'Configuration/People/')
);

if ($defaultTimezone) date_default_timezone_set($defaultTimezone);
if ($locale) setlocale(LC_ALL, $locale);

//Leute-Formular Layout einlesen
//include(FLOCKR_basePath.'config/leute_formular.inc');

