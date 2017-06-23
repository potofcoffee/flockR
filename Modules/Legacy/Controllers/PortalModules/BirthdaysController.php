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
use Peregrinus\Flockr\Legacy\Services\LoginService;

class BirthdaysController extends AbstractController
{

    public function indexAction() {
        global $smarty, $ko_path, $access;

        $uid = LoginService::getInstance()->getUserId();

        if(!ko_module_installed('leute')) return FALSE;

        //Check for access to birthday column
        $columns = ko_get_leute_admin_spalten($uid);
        if(is_array($columns['view']) && !in_array('geburtsdatum', $columns['view'])) return FALSE;

        $all_rights = ko_get_access_all('leute_admin', $uid);
        if($all_rights > 0) {  //No access restrictions if all rights 1 or more
            $z_where = " AND `deleted` = '0' AND `hidden` = '0' ";
        } else {  //Else apply admin filter for the query
            apply_leute_filter('', $z_where, TRUE, $i);
        }

        //Get dealine settings for birthdays
        $deadline_plus = ko_get_userpref($uid, 'geburtstagsliste_deadline_plus');
        $deadline_minus = ko_get_userpref($uid, 'geburtstagsliste_deadline_minus');
        if(!$deadline_plus) $deadline_plus = 21;
        if(!$deadline_minus) $deadline_minus = 7;

        $where = '';
        $dates = array();
        $today = date('Y-m-d');
        for($inc = -1*$deadline_minus; $inc <= $deadline_plus; $inc++) {
            $d = add2date($today, 'day', $inc, TRUE);
            $dates[substr($d, 5)] = $inc;
            list($month, $day) = explode('-', substr($d, 5));
            $where .= " OR (MONTH(`geburtsdatum`) = '$month' AND DAY(`geburtsdatum`) = '$day') ";
        }
        $where = " AND (".substr($where, 3).") ".ko_get_birthday_filter();

        $es = db_select_data('ko_leute', 'WHERE 1=1 '.$where.$z_where, '*');

        $sort = array();
        foreach($es as $pid => $p) {
            $sort[$pid] = $dates[substr($p['geburtsdatum'], 5)];
        }
        asort($sort);

        $data = array();
        $row = 0;
        foreach($sort as $pid => $deadline) {
            $p = $es[$pid];

            $p['deadline'] = $deadline;
            $p['alter'] = (int)substr(add2date(date('Y-m-d'), 'day', $deadline, TRUE), 0, 4) - (int)substr($p['geburtsdatum'], 0, 4);

            $data[$row] = $p;
            $data[$row]['geburtsdatum'] = sql2datum($p['geburtsdatum']);

            //Overlib-Text mit ko_html2 f�r FM
            $data[$row]['_tooltip']  = '&lt;b&gt;'.ko_html2($p['vorname']).' '.ko_html2($p['nachname']).'&lt;/b&gt; ';
            $data[$row]['_tooltip'] .= '('.$p['alter'].')&lt;br /&gt;'.sql2datum($p['geburtsdatum']);

            //Link
            $data[$row]['_link'] = 'leute/index.php?action=set_idfilter&amp;id='.$p['id'];

            $row++;
        }//foreach(es)

        $this->view->assign('people', $data);

        /*
        $smarty->assign('people', $data);
        $smarty->assign('tpl_fm_id', 'fm_birthdays');
        $smarty->assign('tpl_fm_title', getLL('fm_birthdays_title'));
        $smarty->assign('label_years', getLL('fm_birthdays_label_years'));
        $smarty->assign('tpl_fm_pos', $pos);
        $smarty->assign('ttpos', $pos == 'r' ? 'l' : 'r');
        $smarty->display('ko_fm_geburtstage.tpl');
*/
    }

}