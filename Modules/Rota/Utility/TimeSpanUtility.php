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


namespace Peregrinus\Flockr\Rota\Utility;


class TimeSpanUtility
{


    /**
     * Create a nice date title with the given startdate and timespan
     * @param start date Start date of the timespan
     * @param $timespan string Timespan code (see switch statement for possible values)
     */
    public static function formatAsString($start, $timespan) {
        global $DATETIME;

        switch($timespan) {
            case '1d':
                $startTime = $endTime = strtotime($start);
                break;

            case '1w':
            case '2w':
                $inc = substr($timespan, 0, -1);
                $startTime = strtotime($start);
                $endTime = strtotime(add2date(add2date($start, 'week', $inc, TRUE), 'day', -1, TRUE));
                break;

            case '1m':
            case '2m':
            case '3m':
            case '6m':
            case '12m':
                $inc = substr($timespan, 0, -1);
                $startTime = strtotime($start);
                $endTime = strtotime(add2date(add2date($start, 'month', $inc, TRUE), 'day', -1, TRUE));
                break;
        }

        if($startTime == $endTime) {
            $result = strftime($DATETIME['DdMY'], $startTime);
        } else if(date('m', $startTime) == date('m', $endTime)) {
            $result = strftime('%d.', $startTime).' - '.strftime($DATETIME['dMY'], $endTime);
        } else if(date('Y', $startTime) == date('Y', $endTime)) {
            $result = strftime($DATETIME['dM'], $startTime).' - '.strftime($DATETIME['dMY'], $endTime);
        } else {
            $result = strftime($DATETIME['dMY'], $startTime).' - '.strftime($DATETIME['dMY'], $endTime);
        }

        return $result;
    }//ko_rota_timespan_title()


}