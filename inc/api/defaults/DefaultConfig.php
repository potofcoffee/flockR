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

//Reservation: Objekt-Bild
$RESITEM_IMAGE_WIDTH = 60;
//Itemlist-Stringl�ngen-Maximum
define("ITEMLIST_LENGTH_MAX", 35);
//Default-Werte f�r Logins
$DEFAULT_USERPREFS = array(
    array('key' => 'show_limit_daten', 'value' => '20', 'type' => ''),
    array('key' => 'cal_jahr_num', 'value' => '6', 'type' => ''),
    array('key' => 'show_limit_leute', 'value' => '20', 'type' => ''),
    array('key' => 'show_limit_kg', 'value' => '20', 'type' => ''),
    array('key' => 'show_limit_reservation', 'value' => '20', 'type' => ''),
    array('key' => 'show_limit_logins', 'value' => '20', 'type' => ''),
    array('key' => 'show_limit_fileshare', 'value' => '20', 'type' => ''),
    array('key' => 'show_limit_tapes', 'value' => '20', 'type' => ''),
    array('key' => 'show_limit_groups', 'value' => '20', 'type' => ''),
    array('key' => 'show_limit_donations', 'value' => '20', 'type' => ''),
    array('key' => 'show_limit_trackings', 'value' => '20', 'type' => ''),
    array('key' => 'tracking_date_limit', 'value' => '7', 'type' => ''),
    array('key' => 'default_view_daten', 'value' => 'show_cal_monat', 'type' => ''),
    array('key' => 'default_view_reservation', 'value' => 'show_cal_monat', 'type' => ''),
    array('key' => 'front_modules_left', 'value' => 'daten_cal,fastfilter,news', 'type' => ''),
    array('key' => 'front_modules_center', 'value' => 'today', 'type' => ''),
    array('key' => 'front_modules_right', 'value' => 'geburtstage', 'type' => ''),
    array('key' => 'do_mod_email_for_edit_res', 'value' => '0', 'type' => ''),
    array('key' => 'do_mod_email_for_edit_daten', 'value' => '0', 'type' => ''),
    array('key' => 'do_res_email', 'value' => '0', 'type' => ''),
    array('key' => 'modules_dropdown', 'value' => 'ja', 'type' => ''),
    array('key' => 'daten_monthly_title', 'value' => 'eventgruppen_id', 'type' => ''),
    array('key' => 'daten_title_length', 'value' => '30', 'type' => ''),
    array('key' => 'daten_pdf_show_time', 'value' => '2', 'type' => ''),
    array('key' => 'daten_pdf_week_start', 'value' => '1', 'type' => ''),
    array('key' => 'daten_pdf_week_length', 'value' => '7', 'type' => ''),
    array('key' => 'daten_mark_sunday', 'value' => '0', 'type' => ''),
    array('key' => 'daten_ical_deadline', 'value' => '0', 'type' => ''),
    array('key' => 'show_dateres_combined', 'value' => '0', 'type' => ''),
    array('key' => 'res_pdf_show_time', 'value' => '2', 'type' => ''),
    array('key' => 'res_pdf_show_comment', 'value' => '0', 'type' => ''),
    array('key' => 'res_pdf_week_start', 'value' => '1', 'type' => ''),
    array('key' => 'res_pdf_week_length', 'value' => '7', 'type' => ''),
    array('key' => 'res_mark_sunday', 'value' => '0', 'type' => ''),
    array('key' => 'res_monthly_title', 'value' => 'item_id', 'type' => ''),
    array('key' => 'res_title_length', 'value' => '30', 'type' => ''),
    array('key' => 'res_ical_deadline', 'value' => '0', 'type' => ''),
    array('key' => 'cal_woche_start', 'value' => '6', 'type' => ''),
    array('key' => 'cal_woche_end', 'value' => '22', 'type' => ''),
    array('key' => 'geburtstagsliste_deadline_plus', 'value' => '21', 'type' => ''),
    array('key' => 'geburtstagsliste_deadline_minus', 'value' => '5', 'type' => ''),
    array('key' => 'leute_force_family_firstname', 'value' => '', 'type' => ''),
    array('key' => 'leute_children_columns', 'value' => '_father,_mother,_natel', 'type' => ''),
    array('key' => 'show_passed_groups', 'value' => '1', 'type' => ''),
    array('key' => 'groups_filterlink_add_column', 'value' => '1', 'type' => ''),
    array('key' => 'rota_delimiter', 'value' => ', ', 'type' => ''),
    array('key' => 'rota_pdf_fontsize', 'value' => '11', 'type' => ''),
    array('key' => 'rota_eventfields', 'value' => 'kommentar,kommentar2', 'type' => ''),
    array('key' => 'rota_orderby', 'value' => 'vorname', 'type' => ''),
);

$LEUTE_WORD_ADDRESSBLOCK = array(
    array(array('field' => 'firm', 'ifEmpty' => 'anrede')),
    array(array('field' => 'vorname'), array('field' => 'nachname')),
    array(array('field' => 'adresse_zusatz')),
    array(array('field' => 'adresse')),
    array(array('field' => 'plz'), array('field' => 'ort')),
);

$LEUTE_ADRESSLISTE = array(
    "firm",
    "department",
    "anrede",
    "vorname",
    "nachname",
    "adresse",
    "plz",
    "ort",
    "land",
    "telp",
    "telg",
    "natel",
    "fax",
    "email",
    "web"
);
$LEUTE_ADRESSLISTE_LAYOUT = array(
    array("firm", "department"),
    array("anrede"),
    array("vorname", "nachname"),
    array("adresse"),
    array("plz", "ort"),
    array("land"),
    array("@P: ", "telp", "@G: ", "telg"),
    array("@Mobil: ", "natel", "@Fax: ", "fax"),
    array("email"),
    array("web")
);

//mapping table for LDAP-entries
$LDAP_ATTRIB = array(
    'firm' => 'o',
    'department' => 'ou',
    'vorname' => 'givenName',
    'nachname' => 'sn',
    'adresse' => array('street', 'postalAddress', 'mozillaHomeStreet', 'homePostalAddress'),
    'adresse_zusatz' => array('postalAddress', 'mozillaHomeStreet2'),
    'plz' => array('postalCode', 'mozillaHomePostalCode'),
    'ort' => array('l', 'mozillaHomeLocalityName'),
    'telp' => 'homePhone',
    'telg' => 'telephoneNumber',
    'natel' => 'mobile',
    'fax' => 'facsimileTelephoneNumber',
    'email' => 'mail',
    /*'email2' => 'mozillaSecondEmail',*/
    'land' => array('c', 'mozillaHomeCountryName'),
);

// LDAP schema to be used for address records
$LDAP_SCHEMA = array(
    0 => 'top',
    1 => 'person',
    2 => 'organizationalPerson',
    3 => 'inetOrgPerson',
    4 => 'mozillaAddressBookEntry',
);

//Zugeh�rigkeiten der Familien-Daten zu den Personendaten
//Diese Spalten kommen sowohl in ko_leute wie auch in ko_familie vor. ko_leute.* wird jeweils von ko_familie.* �berschrieben.
//Ausser beim Neu-Anlegen einer Person, dann ist es umgekehrt.
//Nachname ist standardm�ssig kein Fam-Feld, da es oft verschiedene Namen in Familien geben kann.
$COLS_LEUTE_UND_FAMILIE = array("adresse", "adresse_zusatz", "plz", "ort", "land", "telp");

//default columns from ko_leute to be hidden in the form
//can be overridden or extended in config/ko-config.php
$LEUTE_EXCLUDE = array("id", "famid", "lastchange", "deleted", "kinder", "crdate", "cruserid");

//Default fields containing an email address. Add additionals in config/ko-config.php
$LEUTE_EMAIL_FIELDS = array("email");

//Default fields containing a mobile number. Add additionals in config/ko-config.php.
$LEUTE_MOBILE_FIELDS = array('natel');

//Smallgroup roles
//L: Leader, M: Member. Add more in your ko-config.php and set names in LL (see kg_roles_*)
$SMALLGROUPS_ROLES = array('L', 'M');
$SMALLGROUPS_ROLES_FOR_NUM = array('M');

//Tracking modes
$TRACKING_MODES = array('simple', 'value', 'valueNonNum', 'type', 'typecheck');

//Fields from ko_reservation shown in form for events
//IMPORTANT: Add DB fields res_FIELD to ko_event_mod for new fields, so moderations can be stored
$EVENTS_SHOW_RES_FIELDS = array('startzeit', 'endzeit');

//Set date and time formats (see http://php.net/strftime for help)
//Can be overwritten in config/ko-config.php if needed
$_DATETIME['de'] = array(
    'dm' => '%d.%m',
    'dM' => '%e. %B',
    'db' => '%e. %b',
    'mY' => '%m %Y',
    'nY' => '%b %Y',
    'MY' => '%B %Y',
    'dmy' => '%d.%m.%y',
    'dmY' => '%d.%m.%Y',
    'dMY' => '%e. %B %Y',
    'dbY' => '%e. %b %Y',
    'DdM' => '%A, %e. %B',
    'ddmy' => '%a, %d.%m.%y',
    'DdmY' => '%A, %d.%m.%Y',
    'DdMY' => '%A, %e. %B %Y'
);
$_DATETIME['en'] = array(
    'dm' => '%d/%m',
    'dM' => '%e %B',
    'db' => '%e %b',
    'mY' => '%m/%Y',
    'nY' => '%b %Y',
    'MY' => '%B %Y',
    'dmy' => '%d/%m/%y',
    'dmY' => '%d/%m/%Y',
    'dMY' => '%e %B %Y',
    'dbY' => '%e %b %Y',
    'DdM' => '%A, %e %B',
    'ddmy' => '%a, %d/%m/%y',
    'DdmY' => '%A, %d/%m/%Y',
    'DdMY' => '%A, %e %B %Y'
);
$_DATETIME['en_US'] = array(
    'dm' => '%m/%d',
    'dM' => '%B %e',
    'db' => '%b %e',
    'mY' => '%m/%Y',
    'nY' => '%b %Y',
    'MY' => '%B %Y',
    'dmy' => '%m/%d/%y',
    'dmY' => '%m/%d/%Y',
    'dMY' => '%B %e %Y',
    'dbY' => '%b %e %Y',
    'DdM' => '%A, %e %B',
    'ddmy' => '%a, %m/%d/%y',
    'DdmY' => '%A, %m/%d/%Y',
    'DdMY' => '%A, %B %e %Y'
);
$_DATETIME['nl'] = array(
    'dm' => '%e %b',
    'dM' => '%e %B',
    'mY' => "%b '%y",
    'db' => '%e %b',
    'nY' => '%b %Y',
    'MY' => '%B %Y',
    'dmy' => '%d-%m-%y',
    'dmY' => '%d-%m-%Y',
    'dMY' => '%e %B %Y',
    'dbY' => '%e %b %Y',
    'DdM' => '%A %e %B',
    'ddmy' => "%a %e %b '%y",
    'DdmY' => '%A %e %b %Y',
    'DdMY' => '%A %e %B %Y'
);
$_DATETIME['fr'] = array(
    'dm' => '%d.%m',
    'dM' => '%e. %B',
    'db' => '%e. %b',
    'mY' => '%m %Y',
    'nY' => '%b %Y',
    'MY' => '%B %Y',
    'dmy' => '%d.%m.%y',
    'dmY' => '%d.%m.%Y',
    'dMY' => '%e. %B %Y',
    'dbY' => '%e. %b %Y',
    'DdM' => '%A, %e. %B',
    'ddmy' => '%a, %d.%m.%y',
    'DdmY' => '%A, %d.%m.%Y',
    'DdMY' => '%A, %e. %B %Y'
);


//If TRUE all access priviliges will be set to maximum (use with caution!)
//define("ALL_ACCESS", TRUE);

//If set to TRUE the PHP Quick Profiler (PQP) will be displayed for each page (not included in standard kOOL package)
//define('DEBUG', TRUE);

//Logo files
$FILE_LOGO_SMALL = 'images/kool-text.gif';
$FILE_LOGO_BIG = 'images/kool-text.gif';

//Individually set colors for events by event field (overwrite in config/ko-config.php)
// $EVENT_COLOR['field']: DB field from ko_event
// $EVENT_COLOR['map']:   Array to map above field values to hex colors (e.g. 'foo' => '00ff00', 'bar' => '0000ff')
$EVENT_COLOR = array();

//Set to TRUE (in ko-config.php) if you want to enable the versioning view in the fast filter of the people module
$ENABLE_VERSIONING_FASTFILTER = false;

//Properties for vCard export (used from people module, for QRCode and cardDAV)
$VCARD_PROPERTIES = array(
    'version' => '3.0',
    'phone' => array(
        'PREF;HOME;VOICE' => 'telp',
        'PREF;WORK;VOICE' => 'telg',
        'PREF;CELL;VOICE' => 'natel',
        'PREF;FAX' => 'fax',
    ),
    'address' => array(
        'HOME;POSTAL' => array('adresse', 'plz', 'ort', 'land'),
    ),
    'url' => array(
        'WORK' => 'web',
    ),
    'email' => array(
        'INTERNET' => 'email',
    ),
    'fields' => array(
// set an array element named _[sep] to define another separator
// set an array element named _[noenc] to false to switch off encoding

// organization
        'O' => array('_' => array('sep' => ' '), 0 => 'firm', 1 => 'department'),

// name
        'N' => array('nachname', 'vorname', null, null, null),
        'FN' => array('_' => array('sep' => ' '), 'vorname', 'nachname'),

// birthday
        'BDAY' => array('geburtsdatum'),

// phone:
        'TEL;HOME;VOICE' => array('telp'),
        'TEL;CELL;VOICE' => array('natel'),
        'TEL;WORK;VOICE' => array('telg'),
        'TEL;WORK;FAX' => array('fax'),

// address:
        'ADR;HOME;POSTAL' => array(null, null, 'adresse', 'ort', null, 'plz', 'land'),
// url
        'URL;WORK' => array('web'),

// email
        'EMAIL;INTERNET' => array('email'),

// modified:
        'REV' => array('lastchange|crdate'),
    ),
    'format' => array(
        'telp' => array('phone', null),
        'telg' => array('phone', null),
        'natel' => array('phone', null),
        'fax' => array('phone', null),
        'geburtsdatum' => array('date', null),
        'lastchange' => array('tzdate', 'UTC'),
        'crdate' => array('tzdate', 'UTC'),
    ),
    'encoding' => array(),
);


//Configuration for install/update.phpsh
$UPDATER_CONF = array(
    'updateTypes' => array('create', 'add', 'modify'),
    'excludeFields' => array('ko_leute.anrede'),
);


//Set some country codes
$COUNTRY_CODES = array(
    '41' => array('names' => array('ch', 'switzerland', 'schweiz')),
    '49' => array('names' => array('de', 'germany', 'deutschland')),
    '33' => array('names' => array('fr', 'france', 'frankreich')),
    '39' => array('names' => array('it', 'italy', 'italien', 'italia'), 'keep_zero' => true),
    '34' => array('names' => array('es', 'spain', 'spanien', 'espa�a')),
    '40' => array('names' => array('ro', 'romania', 'roumania', 'rum�nien')),
);

