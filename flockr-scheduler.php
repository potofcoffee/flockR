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
use \Peregrinus\Flockr\Core\Scheduler;

define('FLOCKR_preventRedirectToRoot', true);

$app = App::getInstance();
$app->setModuleName('scheduler');
include(FLOCKR_basePath.'inc/ko.inc');
$app->bootstrap();

$scheduler = Scheduler::getInstance();

// invoke the 'init_scheduler' action, which permits modules to register their jobs and schedules
$scheduler->init();

//\Peregrinus\Flockr\Core\Debugger::dumpAndDie($scheduler->nextScheduled('update_mailman'));

//$scheduler->dumpJobs();

// run the necessary jobs
$scheduler->run();
