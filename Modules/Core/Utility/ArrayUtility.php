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


class ArrayUtility
{

    /**
     * Build a reduced version of a complex array
     * @param array $array Complex array
     * @param string $keyField Use this field as key (leave empty to use existing key)
     * @param string $valueField Use this field's value as value (leave empty to use whole element as value)
     * @return array Reduced array
     */
    public static function extract(array $array, string $keyField = '', string $valueField = ''): array
    {
        $result = [];
        foreach ($array as $key => $element) {
            $newKey = $keyField != '' ? $element[$keyField] : $key;
            $newValue = $valueField != '' ? $element[$valueField] : $element;
            $result[$newKey] = $newValue;
        }
        return $result;
    }

    /**
     * Create a new array where each key matches the corresponding value
     * @param array $array Subject
     * @return array New array
     */
    public static function copyValuesToKeys(array $array): array
    {
        $result = [];
        foreach ($array as $element) {
            $result[$element] = $element;
        }
        return $result;
    }

}