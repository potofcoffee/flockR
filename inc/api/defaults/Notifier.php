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



//Set default notification levels
require_once(KOOL_inc . 'class.koNotifier.php');
$NOTIFIER_LEVEL_DISPLAY = koNotifier::ERRS | koNotifier::INFO | koNotifier::WARNING;
$NOTIFIER_LEVEL_LOG_TO_DB = koNotifier::ALL ^ koNotifier::DEBUG ^ koNotifier::INFO;
$NOTIFIER_LEVEL_LOG_TO_FILE = koNotifier::DEBUG;
$NOTIFIER_LOG_FILE_NAME = 'log.txt';


//Set log types, that cause server to send an email to the warranty email
$EMAIL_LOG_TYPES = array('db_error_insert', 'db_error_update', 'mailing_smtp_error');

//Set default value for option LEUTE_NO_FAMILY, which disables FAMILY-related options
$LEUTE_NO_FAMILY = false;

//set notification levels
koNotifier::Instance()->setDisplayLevel($NOTIFIER_LEVEL_DISPLAY);
koNotifier::Instance()->setLogToDBLevel($NOTIFIER_LEVEL_LOG_TO_DB);
koNotifier::Instance()->setLogToFileLevel($NOTIFIER_LEVEL_LOG_TO_FILE);
koNotifier::Instance()->setLogFileName($NOTIFIER_LOG_FILE_NAME);

