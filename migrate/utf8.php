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


require_once ('ForceUTF8.php');
use \ForceUTF8\Encoding;

function dump($v)
{
    echo '<pre>' . print_r($v, 1) . '</pre>';
}

function encode($s) {
    return Encoding::fixUTF8($s);
}


$tables = [
    'ko_leute'
];

$textTypes = ['varchar', 'text', 'tinytext', 'bigtext', 'mediumtext'];

echo '<!DOCTYPE html><html lang="de"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/></head><body>';

$db = new PDO('mysql:host=localhost;dbname=usrdb_vmfredbb_kool', 'vmfredbb', 'fdsvmec');
$db->query('SET session.sql_mode = \'\'; SET NAMES=utf8;');

foreach ($tables as $table) {
    echo '<h1>' . $table . '</h1>';
    $colRes = $db->query('SHOW COLUMNS from ' . $table);

    $fields = ['id'];
    while ($row = $colRes->fetch(PDO::FETCH_ASSOC)) {
        $type = explode('(', $row['Type'])[0];
        if (in_array($type, $textTypes)) {
            $fields[] = $row['Field'];
        }
    }
    dump($fields);

    // get content
    $query = 'SELECT ' . join(',', $fields) . ' FROM `' . $table . '`;';

    echo '<table border="1" cellpadding="3" cellspacing="0"><tr>';
    foreach ($fields as $field) {
        echo '<th>' . $field . '</th>';
    }
    echo '</tr>';


    $updateFields = [];
    foreach ($fields as $field) {
        if ($field != 'id') $updateFields[].= '`'.$field.'` = :'.$field;
    }
    $updateQuery = 'UPDATE `'.$table.'` SET '.join(',', $updateFields).' WHERE `id`=:id;';
    dump($updateQuery);
    $updateStatement = $db->prepare($updateQuery);

    $statement = $db->query($query);
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($fields as $field) {
            $row[$field] = encode($row[$field]);
            echo '<td>' . $row[$field] . '</td>';
        }
        echo '</tr>';
        $updateStatement->execute($row);
    }
    echo '</table>';


    echo '<hr>';
}

echo '</body></html>';