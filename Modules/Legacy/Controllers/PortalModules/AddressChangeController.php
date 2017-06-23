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


namespace Peregrinus\Flockr\Legacy\Controllers\PortalModules;


use Peregrinus\Flockr\Core\AbstractController;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Router;

class AddressChangeController extends AbstractController
{

    public function indexAction()
    {
    }

    public function searchAction($data)
    {
        if (!isset($data['lastname'])) $this->forward('index');
        if (!isset($data['firstname'])) $this->forward('index');
        $firstName = format_userinput($data['firstname'], 'alphanum');
        $lastName = format_userinput($data['lastname'], 'alphanum');


        if (isset($data['id'])) {
            $person = $this->getSinglePerson($data);
            $people = [$person];
        } else {
            $ids = ko_fuzzy_search(['vorname' => $firstName, 'nachname' => $lastName],
                'ko_leute', 1, false, 3);
            // more than 5 ids? This sounds fishy, let's not do this!
            if (count($ids) > 5) $this->forward('index');
            $people = [];
            foreach ($ids as $id) {
                ko_get_person_by_id($id, $person);
                $people[] = $person;
            }
        }
        if (count($people)==1) {
            $this->data['id'] = $people[0]['id'];
            $this->forward('edit');
        }
        $this->view->assign('people', $people);
    }

    public function editAction($data)
    {
        $person = $this->getSinglePerson($data);

        $cols = db_get_columns('ko_leute_mod');

        //Only fill in values if ALL rights for people module
        $do_fillout = ko_module_installed('leute') && ko_get_access_all('leute') > 0;

        $fields = array();
        $counter = 0;
        $col_namen = ko_get_leute_col_name();
        foreach ($cols as $column) {
            if (substr($column['Field'], 0, 1) != '_') {  //Alle Spalten, die mit '_' beginnen, ignorieren
                $fields[$counter]['name'] = $column['Field'];
                $fields[$counter]['desc'] = $col_namen[$column['Field']];
                //Vor- und Nachname immer ausgeben, denn diese d�rfen immer angezeigt werden, da diese ja vorher selber eingegeben wurden.
                if ($do_fillout || (!$do_fillout && ($column['Field'] == 'vorname' || $column['Field'] == 'nachname'))) {
                    $fields[$counter]['value'] = ($fm_aa_ids[0] == -1) ? ${'aa_use_' . $column['Field']} : $p[$column['Field']];
                } else {
                    $fields[$counter]['value'] = '';
                }

                if (substr($column['Type'], 0, 7) == 'varchar' || substr($column['Type'], 0, 4) == 'date') {
                    $fields[$counter]['type'] = 'text';
                }
                if (substr($column['Type'], 0, 4) == 'date') {
                    $fields[$counter]['value'] = ($do_fillout) ? sql2datum($fields[$counter]['value']) : '';
                }
                if (substr($column['Type'], 0, 4) == 'enum') {
                    $fields[$counter]['type'] = 'select';
                    $fields[$counter]['values'] = db_get_enums('ko_leute_mod', $column['Field']);
                    $fields[$counter]['descs'] = db_get_enums_ll('ko_leute_mod', $column['Field']);
                }
                $counter++;
            }
        }//foreach(cols as c)

        $this->view->assign('person', $person);
        $this->view->assign('fields', $fields);

    }

    public function saveAction($rawData) {
        if (!isset($data['lastname'])) $this->forward('index');
        if (!isset($data['firstname'])) $this->forward('index');
        $firstName = format_userinput($data['firstname'], 'alphanum');
        $lastName = format_userinput($data['lastname'], 'alphanum');

        $person = $this->getSinglePerson($rawData);
        $columns = db_get_columns('ko_leute_mod');
        foreach ($columns as $column) {
            if (substr($column['Field'], 0, 1) != '_') {
                if ($column['Type'] == 'date') {  //Datum-Eingaben wieder in SQL-Format konvertieren.
                    $data[$column['Field']] = sql_datum($rawData[$column['Field']]);
                } else {
                    $data[$column['Field']] = format_userinput($rawData[$column['Field']], 'text');
                }
            }
        }

        // save to DB
        $data['_leute_id'] = $aa_id;
        $data['_bemerkung'] = format_userinput($data['txt_bemerkung'], 'text');
        $data['_crdate'] = strftime('%Y-%m-%d %T', time());
        $data['_cruserid'] = $_SESSION['ses_userid'];
        db_insert_data('ko_leute_mod', $data);

        // log
        $data["vorname"] = $firstName;
        $data["nachname"] = $lastName;
        ko_log_diff('aa_antrag', $data, $person);
        koNotifier::Instance()->addTextInfo('Herzlichen Dank. Deine Änderungswünsche wurden gespeichert und werden von uns überprüft.');
        Router::getInstance()->redirectToUrl(FLOCKR_baseUrl);
        return false;
    }


    protected function getSinglePerson($data) {
        $id = format_userinput($data['id'], 'int');
        $firstName = format_userinput($data['firstname'], 'alphanum');
        $lastName = format_userinput($data['lastname'], 'alphanum');
        ko_get_person_by_id($id, $person);
        if (!$person) $this->forward('index');
        /*
         * When calling a person by ID, first and last name have to be supplied as well
         */
        if (($person['vorname'] != $firstName) || ($person['nachname'] != $lastName)) {
            $this->forward('index');
        }
        return $person;
    }
}