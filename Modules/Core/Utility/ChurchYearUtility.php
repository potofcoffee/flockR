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


namespace Peregrinus\Flockr\Core\Utility;


class ChurchYearUtility
{
    protected $fixedDates = [
        '01-01' => 'Neujahr',
        '01-06' => 'Epiphanias',
        '05-01' => 'Tag der Arbeit',
        '10-03' => 'Tag der deutschen Einheit',
        '10-31' => 'Reformationstag',
        '11-01' => 'Allerheiligen',
    ];

    protected $calendar = [];

    public function easterDate($year) {
        return date('Y-m-d', easter_date($year));
    }

    protected function buildAdventSeason($year) {
        $this->calendar[$year.'-12-24'] = 'Heiligabend';
        $this->calendar[$year.'-12-25'] = '1. Weihnachtstag';
        $this->calendar[$year.'-12-26'] = '2. Weihnachtstag';
        $this->calendar[$year.'-12-31'] = 'Silvester';

        $christmasDate = strtotime($year.'-12-25 0:00:00');
        $cmWeekday = date('w', $christmasDate);
        $cmWeekday = $cmWeekday ? $cmWeekday : 7;
        $adventSunday = $this->diffDate($year.'-12-25', -$cmWeekday);
        $this->calendar[$adventSunday] = '4. Advent';
        for ($i=3; $i>0; $i--) {
            $adventSunday = $this->diffDate($adventSunday, -7);
            $this->calendar[$adventSunday] = $i.'. Advent';
        }
        foreach (['Ewigkeitssonntag', 'Volkstrauertag', 'Drittletzter Sonntag des Kirchenjahres'] as $sundayTitle) {
            $adventSunday = $this->diffDate($adventSunday, -7);
            $this->calendar[$adventSunday] = $sundayTitle;
        }

        $secondSunday = $this->diffDate($year.'-12-25', 14);
        if (strtotime($secondSunday)<strtotime($year.'-01-06')) {
            $this->calendar[$secondSunday] = '2. Sonntag nach dem Christfest';
        }
    }

    protected function buildFixedDates($year) {
        foreach ($this->fixedDates as $date => $day) {
            $this->calendar[$year.'-'.$date] = $day;
        }
    }

    protected function buildEasterSeason($year) {
        $easter = date('Y-m-d', easter_date($year));
        $this->calendar[$this->diffDate($easter, -56)] = 'Septuagesimae';
        $this->calendar[$this->diffDate($easter, -49)] = 'Sexagesimae';
        $this->calendar[$this->diffDate($easter, -42)] = 'Estomihi';
        //$this->calendar[$this->diffDate($easter, -39)] = 'Aschermittwoch';
        $this->calendar[$this->diffDate($easter, -35)] = 'Invokavit';
        $this->calendar[$this->diffDate($easter, -28)] = 'Okuli';
        $this->calendar[$this->diffDate($easter, -21)] = 'Laetare';
        $this->calendar[$this->diffDate($easter, -14)] = 'Judika';
        $this->calendar[$this->diffDate($easter, -7)] = 'Palmsonntag';
        $this->calendar[$this->diffDate($easter, -3)] = 'Gründonnerstag';
        $this->calendar[$this->diffDate($easter, -2)] = 'Karfreitag';
        $this->calendar[$this->diffDate($easter, -1)] = 'Karsamstag';
        $this->calendar[$easter] = 'Ostersonntag';
        $this->calendar[$this->diffDate($easter, 1)] = 'Ostermontag';
        $this->calendar[$this->diffDate($easter, 7)] = 'Quasimodogeniti';
        $this->calendar[$this->diffDate($easter, 14)] = 'Miserikordia';
        $this->calendar[$this->diffDate($easter, 21)] = 'Jubilate';
        $this->calendar[$this->diffDate($easter, 28)] = 'Kantate';
        $this->calendar[$this->diffDate($easter, 35)] = 'Rogate';
        $this->calendar[$this->diffDate($easter, 39)] = 'Christi Himmelfahrt';
        $this->calendar[$this->diffDate($easter, 42)] = 'Exaudi';
        $this->calendar[$this->diffDate($easter, 49)] = 'Pfingstsonntag';
        $this->calendar[$this->diffDate($easter, 50)] = 'Pfingstmontag';
        $trinity = $this->diffDate($easter, 56);
        $this->calendar[$trinity] = 'Trinitatis';
        for ($i=1; $i<=24; $i++) {
            $trinitySunday = $this->diffDate($trinity, ($i*7));
            if (isset($this->calendar[$trinitySunday])) {
                continue;
            }
            $this->calendar[$trinitySunday] = $i.'. Sonntag nach Trinitatis';
        }
    }

    protected function buildEpiphanySeason($year) {
        $epiphany = $year.'-01-06';
        $weekDay = strftime('%w', strtotime($epiphany));
        if ($weekDay) {
            $sunday = $this->diffDate($epiphany, 7-$weekDay);
        } else {
            $sunday= $this->diffDate($epiphany, 7);
        }
        $this->calendar[$sunday] = '1. Sonntag nach Epiphanias';
        for ($i=1; $i<5; $i++) {
            $sunday = $this->diffDate($sunday, 7);
            if (isset($this->calendar[$sunday])) {
                $sunday = $this->diffDate($sunday, -7);
                continue;
            }
            $this->calendar[$sunday] = ($i+1).'. Sonntag nach Epiphanias';
        }
        $this->calendar[$sunday] = 'Letzter Sonntag nach Epiphanias';
    }

    protected function diffDate($date, $diff) {
        return date('Y-m-d', strtotime($date.' 0:00:00 '.($diff >0 ? '+' : '').$diff.' days'));
    }



    protected function buildCalendar($year) {
        $this->buildFixedDates($year);
        $this->buildAdventSeason($year-1);
        $this->buildAdventSeason($year);
        $this->buildEasterSeason($year);
        $this->buildEpiphanySeason($year);
        ksort($this->calendar);
    }

    public function getDayDescription($date) {
        if (is_numeric($date)) $date = date('Y-m-d', $date);
        if (!count($this->calendar)) $this->buildCalendar(substr($date, 0, 4));
        if (isset($this->calendar[$date])) {
            return $this->calendar[$date];
        }
    }
}