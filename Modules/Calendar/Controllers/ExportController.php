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


namespace Peregrinus\Flockr\Calendar\Controllers;


use Peregrinus\Flockr\Core\AbstractController;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Renderer\PDFRenderer;
use Peregrinus\Flockr\Core\Router;
use Peregrinus\Flockr\Core\Utility\ChurchYearUtility;

class ExportController extends AbstractController
{

    public function yearAction()
    {
        if (!$this->request->hasArgument('startMonth')) $this->forward('index');
        if (!$this->request->hasArgument('holidayGroup')) $this->forward('index');
        if (!$this->request->hasArgument('showGroups')) $this->forward('index');

        list ($startYear, $startMonth) = explode('-', $this->request->getArgument('startMonth'));
        $holidayGroup = $this->request->getArgument('holidayGroup');
        $showGroups = array_keys($this->request->getArgument('showGroups'));

        $startDate = mktime(0,0,0, $startMonth, 1, $startYear);
        $endDate = strtotime('+12 months -1 day', $startDate);


        // 12 months
        $currentMonthStart = $startDate;
        $calendar = [];
        for ($i=0; $i<12; $i++) {
            $churchYearUtility = new ChurchYearUtility();
            $currentMonth = strftime('%m', $currentMonthStart);
            $month = [
                'start' => $currentMonthStart,
                'end' => strtotime('next month -1 day', $currentMonthStart),
            ];
            $month['numberOfDays'] = strftime('%d', $month['end']);
            $month['days'] = [];
            for ($day=$currentMonthStart; $day <= $month['end']+86399; $day+=86400) {
                $events = [];
                \ko_get_events_by_date(strftime('%d', $day),  strftime('%m', $day), strftime('%Y', $day), $events);
                $isSchoolHoliday = false;
                foreach ($events as $key => $event) {
                    if ($event['eventgruppen_id'] == $holidayGroup) {
                        $isSchoolHoliday = true;
                        unset($events[$key]);
                    }
                    if (!in_array($event['eventgruppen_id'], $showGroups)) {
                        unset($events[$key]);
                    }
                    if ($event['startzeit'] == '00:00:00') $events[$key]['startzeit'] = '';
                }
                $dayRecord = [
                    'date' => $day,
                    'events' => $events,
                    'isSchoolHoliday' => $isSchoolHoliday,
                    'isSunday' => (strftime('%u', $day) == 7),
                    'churchYear' => $churchYearUtility->getDayDescription($day),
                ];
                $month['days'][trim(strftime('%e', $day))] = $dayRecord;
            }
            ksort ($month['days']);
            $calendar[] = $month;
            $currentMonthStart = strtotime('next month', $currentMonthStart);
        }
        $pdfRenderer = new PDFRenderer('Year', [
            'format' => 'A4-L',
            'margin_top' => 7,
            'margin_bottom' => 5
        ]);
        $pdfRenderer->assign('calendar', $calendar);
        $pdfRenderer->assign('startDate',$startDate);
        $pdfRenderer->assign('endDate', $endDate);

        $fileName = strftime('%Y%m', $startDate).'-'.strftime('%Y%m', $endDate).' Jahreskalender.pdf';

        $pdfRenderer->render(FLOCKR_basePath.'download/pdf/'.$fileName, '', $this->module, 'Export', 'year');
        Router::getInstance()->redirectToUrl(FLOCKR_baseUrl.'download/pdf/'.$fileName);

        $this->dontShowView();
        return;
/*
        $this->view->assign('calendar', $calendar);
        $this->view->assign('startDate', $startDate);
        $this->view->assign('endDate', $endDate);
*/
    }

    public function indexAction()
    {
        $currentMonth = mktime(0, 0, 0, 1, 1, date('Y'));
        $months = [];
        for ($i = 1; $i <= 24; $i++) {
            $months[] = [
                'key' => strftime('%Y-%m', $currentMonth),
                'value' => strftime('%B %Y', $currentMonth)
            ];
            $currentMonth = strtotime('next month', $currentMonth);
        }

        $eventGroups = [];
        \ko_get_eventgruppen($eventGroups);
        $this->view->assign('eventGroups', $eventGroups);
        $this->view->assign('months', $months);
        $this->view->assign('currentMonth', strftime('%Y-%m'));
    }

}