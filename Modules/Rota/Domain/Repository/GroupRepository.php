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
use Peregrinus\Flockr\Core\Debugger;

class GroupRepository extends AbstractRepository
{

    protected $table = 'ko_groups';

    public function findMultipleWithChildren($groupIds) {
        if (!is_array($groupIds)) $groupIds = explode(',', $groupIds);
        $groups = [];
        foreach ($groupIds as $group) {
            // cut off role
            $group = explode(':', $group)[0];
            if (substr($group, 0, 1) == 'g') $group = substr($group, 1);
            $groups = array_merge($this->findWithChildren($group), $groups);
        }
        return $groups;
    }

    public function findWithChildren($uid)
    {
        if (substr($uid, 0, 1) == 'g') $uid = substr($uid, 1);
        $groups = array_merge($this->findByUid($uid), $this->findChildrenRecursive($uid));
        return $groups;
    }

    public function findChildrenRecursive($uid)
    {
        if (substr($uid, 0, 1) == 'g') $uid = substr($uid, 1);
        $children = $this->findByPid($uid);
        foreach ($children as $child) {
            $children = array_merge($children, $this->findChildrenRecursive($child['id']));
        }
        return $children;
    }

    public function getFullGroupName($group, $glue = ' > ') {
        // if we only got an id, get the whole group record
        if (!is_array($group)) $group = $this->findOneByUid($group);
        if ($group) {
            $names = [];
            $parent = (string)$group['pid'];
            while ($parent != '') {
                $names[] = $group['name'];
                $group = $this->findOneByUid($parent);
                $parent = $group['pid'];

            }
            $names[] = $group['name'];
            return join($glue, array_reverse($names));
        } else {
            return '';
        }
    }
}