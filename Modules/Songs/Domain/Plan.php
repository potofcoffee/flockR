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


namespace Peregrinus\Flockr\Songs\Domain;

use \Peregrinus\Flockr\Core\Helpers\StringHelper;
use \Peregrinus\Flockr\Core\Helpers\FileSystemHelper;

class Plan
{
    protected $songs = [];
    protected $date = 0;
    protected $file = '';
    protected $key = '';

    function __construct($planFile, $songFolder)
    {
        $this->file = $planFile;
        $lines = explode("\n", str_replace("\r\n", "\n", StringHelper::windowsDecode(file_get_contents($planFile))));
        foreach ($lines as $line) {
            if (substr($line = trim($line), 0, 8) == 'FileName') {
                $file = substr(trim(substr($line, 10)), 1, -1);
                if (pathinfo($file, PATHINFO_EXTENSION) == 'sng') {
                    if (file_exists($songFolder.$file)) {
                        $this->songs[] = new \Peregrinus\Flockr\Songs\Domain\Song($songFolder.$file);;
                    }
                }
            }
        }
        $name = pathinfo($planFile, PATHINFO_FILENAME);
        if (strpos($name, '.') !== false) {
            $tmp = explode('.', $name);
            $this->key = mktime(0, 0, 0, $tmp[1], $tmp[0], StringHelper::getNumericChars($tmp[2]));
        } else {
            $this->key = mktime(0, 0, 0, (int)substr($name, 4, 2), (int)substr($name, 6, 2),
                (int)substr($name, 0, 4));
        }
        $this->date = new \DateTime();
        $this->date->setTimestamp((int)$this->key);
    }

    /**
     * Get a list of all songs in this plan
     * @return array Songs
     */
    function getSongs()
    {
        return $this->songs;
    }


    function countSongs($data)
    {
        foreach ($this->data as $songFile) {
            $song = new Song($songFile);
            $key = $song->data['title'];
            $key = str_replace(['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'], ['Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss'], $key);

            if (!isset($data[$key])) {
                $data[$key] = [
                    'count' => 0,
                    'dates' => [],
                    'title' => $song->data['title'],
                    'ccli' => $song->data['ccli'],
                    'file' => pathinfo($songFile, PATHINFO_BASENAME),
                ];
                $data[$key]['count']++;
                $data[$key]['dates'][] = strftime('%d.%m.%Y', $this->date);
            }
        }
        return $data;
    }

    /**
     * @return false|int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return false|int|string
     */
    public function getKey()
    {
        return $this->key;
    }




}