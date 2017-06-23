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

require_once('flockr-bootstrap.php');

use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Legacy\LegacyApp;


$app = App::getInstance();
if ($app->canHandleRequest()) {
    include(FLOCKR_basePath.'inc/ko.inc');
    $app->bootstrap();
    $app->run();
    exit;
}

// reset error reporting for legacy kOOL
//error_reporting(0);


/////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Legacy kOOL code starts here
$legacyApp = new LegacyApp('home', './');
include(FLOCKR_basePath.'inc/ko.inc');
$legacyApp->bootstrap();
if ($legacyApp->run()) exit(); // handled by LegacyApp, we're done!

// actually, we shouldn't be here:
trigger_error("invalid action: " . $do_action, E_USER_ERROR);
