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


namespace Peregrinus\Flockr\Rota\Domain\Repository;


use Peregrinus\Flockr\Core\AbstractRepository;

class ScheduleRepository extends AbstractRepository
{
    protected $table = 'ko_rota_schedulling';

    public function findOneByEventAndTeam($eventId, $teamId)
    {
        $schedules = parent::find('WHERE `event_id` = :event_id AND `team_id` = :team_id',
            ['event_id' => $eventId, 'team_id' => $teamId]);
        if (count($schedules)) {
            $schedule = $schedules[0];
            $peopleRepository = new PeopleRepository();
            $members = explode(',', $schedule['schedule']);
            $schedule['schedule'] = [];
            $schedule['schedule']['members'] = [];
            foreach ($members as $member) {
                if (is_numeric($member)) {
                    $schedule['schedule']['members'][] = [
                        'type' => 'person',
                        'person' => $peopleRepository->findOneByUid($member),
                    ];
                } else {
                    $schedule['schedule']['members'][] = [
                        'type' => 'text',
                        'text' => $member,
                    ];
                }
            }
            $schedule['schedule']['count'] = count($members);
            return $schedule;
        } else {
            return [];
        }
    }

    public function delete($record)
    {
        $this->db->delete(
            $this->table,
            'WHERE `event_id` = :event_id AND `team_id` = :team_id',
            ['event_id' => $record['event_id'], 'team_id' => $record['team_id']]
        );
    }

    public function update($record) {
        $this->db->update($this->table, "WHERE `event_id` = :event_id AND `team_id` = :team_id", $record);
    }
}