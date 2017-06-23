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

//Available languages (overwrite only with $WEB_LANGS in config/ko-config.php, or through the installation)
$LIB_LANGS = array('en', 'de', 'nl', 'fr');
//Regions available for each language (first one being the default)
$LIB_LANGS2 = array(
    'en' => array('UK', 'US'),
    'de' => array('CH', 'DE'),
    'nl' => array('NL'),
    'fr' => array('CH'),
);
require_once(KOOL_inc.'bootstrap/i18n.php');
if (isset($_DATETIME[$_SESSION['lang'] . '_' . $_SESSION['lang2']])) {
    $DATETIME = $_DATETIME[$_SESSION['lang'] . '_' . $_SESSION['lang2']];
} else {
    $DATETIME = $_DATETIME[$_SESSION['lang']];
}
