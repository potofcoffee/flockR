<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2003-2015 Renzo Lauper (renzo@churchtool.org)
 *  All rights reserved
 *
 *  This script is part of the kOOL project. The kOOL project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  kOOL is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use \Peregrinus\Flockr\Legacy\Services\BootstrapService;

define('VERSION', 'R45-flockr');

// include FlockR bootstrap
if (!defined('FLOCKR_version')) {
   require_once($ko_path . 'flockr-bootstrap.php');
}


if (!defined('API_LOADED')) {
    define ('API_LOADED', true);
    // call legacy api loader manually, since FlockR hasn't done that yet
    $apiTime = microtime(true);
    $api = \Peregrinus\Flockr\Core\Loaders\ApiLoader::loadAll();
}

$bootstrapService = new BootstrapService();
$bootstrapService->run();


// load each individual bootstrap step
foreach ([
             'config',
             'modules',
             'session',
             'error_handling',
             'baseurl',
             'hooks',
             'smarty',
             'languages',
             'db',
             'guest',
             'submenu',
             'submenu_actions',
             'frontmodules',
             'init',
             'jscalendar'
         ] as $bootstrap) {
    require_once(KOOL_inc . 'bootstrap/' . $bootstrap . '.php');
}

// load libraries
foreach ([
             'swiftmailer/swift_required.php',

         ] as $lib) {
    require_once(KOOL_inc . $lib);
}













