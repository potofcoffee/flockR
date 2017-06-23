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


namespace Peregrinus\Flockr\Rota\Settings;


use Peregrinus\Flockr\Core\Settings\DoubleSelectSetting;
use Peregrinus\Flockr\Core\Services\SettingsService;

class EventFieldsSetting extends DoubleSelectSetting
{

    public function __construct($id, $label, $scope)
    {
        global $KOTA;
        \ko_include_kota('ko_event');
        // Prepare consensus field settings
        $options = $chosen = [];
        $exclude = array('eventgruppen_id', 'startdatum', 'enddatum', 'startzeit', 'endzeit', 'room', 'rota', 'reservationen');
        foreach ($KOTA['ko_event'] as $field => $data) {
            if (substr($field, 0, 1) == '_' || in_array($field, $exclude)) continue;
            if (substr($field, 0, 9) == 'rotateam_') continue;
            $options[$field] = getLL('kota_ko_event_' . $field) ? getLL('kota_ko_event_' . $field) : $field;
        }

        if ($scope == self::SCOPE_GLOBAL) {
            $chosenFields = explode(',', SettingsService::getInstance()->getGlobalSetting($id));
        } elseif ($scope == self::SCOPE_USER) {
            $chosenFields = explode(',', SettingsService::getInstance()->getUserPreference($id));
        }

        foreach ($chosenFields as $field) {
            $chosen[$field] = getLL('kota_ko_event_' . $field);
        }

        parent::__construct($id, $label, $scope, $options, $chosen);
    }
}