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

class AddressSearchController extends AbstractController
{

    public function indexAction() {
        $content = '';
        $fast_filter = ko_get_fast_filter();
        if (count($fast_filter) > 1) {
            foreach($fast_filter as $id) {
                ko_get_filter_by_id($id, $ff);
                if ($ff['name']) $content .= $ff['name'].':<br />';;
                $ff_code = str_replace('var1', ('fastfilter'.$id), $ff['code1']);
                $ff_code = str_replace('submit_filter', 'set_fastfilter', $ff_code);
                $content .= $ff_code.'<br />';
            }
            $content .= '<button type="submit" name="submit_fm_fastfilter"  class="btn btn-primary"><span class="fa fa-search"></span></button>';

        } else {
            $id=$fast_filter[0];
            ko_get_filter_by_id($id, $ff);
            $ff_code = str_replace('var1', ('fastfilter'.$id), $ff['code1']);
            $ff_code = str_replace('submit_filter', 'set_fastfilter', $ff_code);
            $content .= '<div class="input-group">'.$ff_code.'<span class="input-group-btn">

                    <button type="submit" name="submit_fm_fastfilter"  class="btn btn-primary"><span class="fa fa-search"></span></button>

                </span></div>';

        }
        $this->view->assign('content', $content);
    }

}