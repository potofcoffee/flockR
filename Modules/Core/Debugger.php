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


namespace Peregrinus\Flockr\Core;


class Debugger
{
    /**
     * Send debug output to a file in Temp/Debug/
     * @param $v Variable to be debugged
     * @param string $debugFile Debug file name
     * @return void
     */
    public static function toFile($v, $debugFile)
    {
        if (!is_dir(FLOCKR_basePath . 'Temp/Debug/')) {
            mkdir(FLOCKR_basePath . 'Temp/Debug/', 0777, true);
        }
        $fp = fopen(FLOCKR_basePath . 'Temp/Debug/' . $debugFile, 'w');
        fwrite($fp, print_r($v, 1));
        fclose($fp);
    }

    public static function dumpAndDie($v, $label='')
    {
        self::dump($v, $label, 2, true);
    }

    public static function dump($v, $label='', $traceSteps = 1, $dieAfter = false)
    {
        $backtrace = debug_backtrace();
        echo '<pre style="border: solid 1px red; padding: 5px; background-color: lightpink;">';
        if ($label) echo '<b>'.$label.'</b><br />';
        //print_r($backtrace);
        echo '<small><b>' . ($backtrace[$traceSteps]['class'] ? $backtrace[$traceSteps]['class'] . '::' : '') . $backtrace[$traceSteps]['function'] . '() ' . pathinfo($backtrace[$traceSteps - 1]['file'], PATHINFO_BASENAME) . ':' . $backtrace[$traceSteps - 1]['line'] . '</b></small><hr />';
        self::showContext($backtrace[$traceSteps-1]['file'], $backtrace[$traceSteps-1]['line']);
        echo '<div style="background-color: white">';
        if (!empty($v)) {
            print_r($v);
        } else {
            echo '<i>Empty value</i>';
        }
        echo '</div>';
        if ($dieAfter) echo '<div style="background-color: red;"><small>Aborting for debug.</small></div>';
        echo '</pre>';
        if ($dieAfter) die();
    }

    public static function showContext($file, $line, $context = 2)
    {
        $line--;
        $start = $line - 2;
        if ($start < 0) $start == 0;
        $end = $line + 2;
        $lines = explode("\n", file_get_contents($file));
        for ($i = $start; $i <= $end; $i++) {
            if (isset($lines[$i])) {
                $color = ($line == $i ? 'yellow' : 'lightgray');
                echo '<div style="background-color: ' . $color . '">'
                    .'<span style="color: darkgray">'.str_pad($i, 6, ' ', STR_PAD_LEFT).'</span> '
                    . $lines[$i]
                    . '</div>';
            }
        }
    }

    public static function flag($label= '', $dieAfter = true)
    {
        $backtrace = debug_backtrace();
        echo '<pre style="border: solid 1px blue; padding: 5px; background-color: lightblue;">';
        if ($label) echo '<b>'.$label.'</b><br />';
        echo '<small>' . ($backtrace[1]['class'] ? $backtrace[1]['class'] . '::' : '') . $backtrace[1]['function'] . '() ' . pathinfo($backtrace[0]['file'], PATHINFO_BASENAME) . ':' . $backtrace[0]['line'] . '</small></pre>';
        if ($dieAfter) die();
    }


}