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

$cols = [
    'de' => [
        'id' => '',
        'famid' => 'Familie',
        'anrede' => 'Anrede',
        'firm' => 'Firma',
        'department' => 'Abteilung',
        'vorname' => 'Vorname',
        'nachname' => 'Nachname',
        'adresse' => 'Adresse privat',
        'adresse_zusatz' => 'Adresszusatz privat',
        'plz' => 'PLZ privat',
        'ort' => 'Ort privat',
        'land' => 'Land privat',
        'telp' => 'Telefon privat',
        'telg' => 'Telefon dienstlich',
        'natel' => 'Mobiltelefon privat',
        'fax' => 'Fax privat',
        'email' => 'E-Mail privat',
        'web' => 'Web privat',
        'adresse_g' => 'Adresse dienstlich',
        'adresse_zusatz_g' => 'Adresszusatz dienstlich',
        'plz_g' => 'PLZ dienstlich',
        'ort_g' => 'Ort dienstlich',
        'land_g' => 'Land dienstlich',
        'natel_g' => 'Mobiltelefon dienstlich',
        'email_g' => 'E-Mail dienstlich',
        'fax_g' => 'Fax dienstlich',
        'web_g' => 'Web dienstlich',
        'geburtsdatum' => 'Geburtsdatum',
        'zivilstand' => 'Familienstand',
        'geschlecht' => 'Geschlecht',
        'memo1' => 'Memo1',
        'memo2' => 'Memo2',
        'kinder' => 'Kinder',
        'smallgroups' => 'Kleingruppe',
        'lastchange' => 'LetzteAenderung',
        'famfunction' => 'FamFunktion',
        'groups' => 'Gruppen',
        'hidden' => 'Versteckt',
        'crdate' => '',
        'cruserid' => '',
        'facebook' => 'Facebook',
        'twitter' => 'Twitter',
        'myspace' => 'MySpace',
        'linkedin' => 'LinkedIn',
        'kwick' => 'Kwick',
        'typo3_feuser' => 'Zugehöriger Benutzer auf der Internetseite',
    ]
];

$s = serialize($cols);
echo $s;
echo '<pre>'.print_r(unserialize($s), 1);