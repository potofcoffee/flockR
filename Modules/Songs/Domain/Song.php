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

use Peregrinus\Flockr\Core\Helpers\StringHelper;


class Song
{
    const EOL = "\r\n"; // Windows EOL

    protected $meta = [];
    protected $file = '';
    protected $units = [];
    protected $preview;

    /**
     * Song constructor.
     * @param string $songFile Path to song file
     */
    public function __construct($songFile = '')
    {
        if ($songFile) {
            $this->file = $songFile;
            $this->loadFromFile($this->file);
        }
    }

    /**
     * Create a song object from a .sng file
     * @param string $songFile Path to file
     */
    public function loadFromFile($songFile) {
        $lines = explode("\n", str_replace("\r\n", "\n", file_get_contents($songFile)));
        $unit = [];
        $ctr = 0;
        foreach ($lines as $line) {

            if (substr($line, 0, 1) == '#') {
                $tmp = explode('=', $line);
                $this->meta[strtolower(substr($tmp[0], 1))] = $tmp[1];
                if (($x = strpos($line, 'CCLI-Liednummer ')) !== false) {
                    $this->meta['ccli'] = substr($line, $x + 16);
                }
            } else {
                if (trim($line)=='---') {
                    if (count($unit)) {
                        $this->units[$ctr] = join(" \n", $unit);
                        $ctr++;
                        $unit = [];
                    }
                } else {
                    $unit[] = $line;
                }
            }
        }
        if (!isset($this->meta['title'])) {
            $this->meta['title'] = pathinfo($songFile, PATHINFO_FILENAME);
        }

        $tmp = explode(' - ', $this->meta['title']);
        if (count($tmp) > 1) {
            if (is_numeric(substr($tmp[0], 0, 1))) {
                unset ($tmp[0]);
                $tmp = array_values($tmp);
            }
            $this->meta['title'] = $tmp[0];
        }

        $this->meta['title'] = str_replace(['Lobpreis\\', '.RR', '"'], [], $this->meta['title']);
        $this->meta['title'] = StringHelper::windowsDecode(ucfirst($this->meta['title']));

        if ($this->meta['CCLI']) {
            echo $this->meta['CCLI'];
        }
    }

    public function saveToSongFile($songFile = '') {
        if (!$songFile) $songFile = $this->file;
        if ($songFile) {
            $this->file = $songFile;
            if (file_exists($songFile)) {
                rename($songFile, $songFile.'.bak.'.time());
            }

            $fp = fopen($songFile, 'w');

            foreach ($this->meta as $key => $val) {
                fwrite ($fp, '#'.ucfirst($key).'='.$val.self::EOL);
            }
            foreach ($this->units as $unit) {
                fwrite ($fp, '---'.self::EOL);
                fwrite ($fp, str_replace('<br>', self::EOL, $unit).self::EOL);
            }

            fclose($fp);

        }
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return pathinfo($this->file, PATHINFO_BASENAME);
    }

    /**
     * @return array
     */
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * @param array $meta
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @param array $units
     */
    public function setUnits($units)
    {
        $this->units = $units;
    }

    /**
     * @return mixed
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * @param mixed $preview
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;
    }




}