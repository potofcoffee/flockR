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


namespace Peregrinus\Flockr\Rota\Exports;


use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Services\AccessService;
use Peregrinus\Flockr\Core\Services\SettingsService;
use Peregrinus\Flockr\Legacy\Services\LoginService;
use Peregrinus\Flockr\Rota\RotaModule;
use Peregrinus\Flockr\Rota\Utility\TimeSpanUtility;

class PdfLandscapeExport
{

    /**
     * Export rota to a landscape pdf
     * @param $timeStart Start time
     * @param $timeSpan Timespan
     * @param array $events Events
     * @param array $teams Teams
     * @param array $eventGroups Event groups
     * @return string Filename of the exported pdf
     */
    public function export($timeStart, $timeSpan, array $events, array $teams, array $eventGroups) : string {
        $pdfRenderer = new \Peregrinus\Flockr\Core\Renderer\PDFRenderer('PdfLandscape', ['format' => 'A4-L']);
        $filename = 'download/pdf/'.getLL('rota_filename').strftime('%d%m%Y_%H%M%S', time()).'.pdf';

        $accessService = AccessService::getInstance();

        $commentFields = explode(',', SettingsService::getInstance()->getUserPreference('rota_eventfields'));
        foreach($commentFields as $key => $value) {
            if(!$value) {
                unset($commentFields[$key]);
            }
        }

        $columnCtr = 0;
        $rows = [
            'header' => [0 => ''],
            'comments' => [],
            'data' => [],
        ];
        foreach ($events as $event) {
            $columnCtr++;

            // header
            $rows['header'][$columnCtr] = $event['startdatum'].' '.$event['startzeit'];

            // comments
            $commentCtr = 0;
            foreach ($commentFields as  $commentField) {
                $commentCtr++;
                $rows['comments'][$commentCtr][0] = getLL('kota_ko_event_'.$commentField);
                $rows['comments'][$commentCtr][$columnCtr] = $event[$commentField];
            }

            // teams
            $teamCtr = 0;
            foreach ($teams as $team) {
                if ($accessService->hasAccess('rota', $team['id'], 2)) {
                    $teamCtr++;
                    if (isset($event['schedule'][$team['id']])) {
                        $rows['data'][$teamCtr][0] = utf8_decode($team['name']);
                        $rows['data'][$teamCtr][$columnCtr] = $event['schedule'][$team['id']];
                    }
                }
            }

        }

//        Debugger::dumpAndDie($rows['data']);

        $pdfRenderer->assign('rows', $rows);
        $pdfRenderer->assign('creator', LoginService::getInstance()->getUser());
        $pdfRenderer->assign('title', TimeSpanUtility::formatAsString($timeStart, $timeSpan));
        $pdfRenderer->assign('colWidth', 100/($columnCtr+1).'%');

        $pdfRenderer->render(FLOCKR_basePath.$filename, '', RotaModule::getInstance(), 'Export');
        return $filename;
    }
}