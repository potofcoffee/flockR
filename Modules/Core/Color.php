<?php
/*
 * CUTTER
 * Versatile Image Cutter and Processor
 * http://github.com/VolksmissionFreudenstadt/cutter
 *
 * Copyright (c) 2015 Volksmission Freudenstadt, http://www.volksmission-freudenstadt.de
 * Author: Christoph Fischer, chris@toph.de
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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

namespace VMFDS\Cutter\Core;

class Color
{
    public $R = 255;
    public $G = 255;
    public $B = 255;

    /**
     * Create instance of color object from hex string
     * @param string $hex Hex string (e.g. ffffff, i.e. without leading #)
     */
    function __construct($hex)
    {
        $this->R = hexdec(substr($hex, 0, 2));
        $this->G = hexdec(substr($hex, 2, 2));
        $this->B = hexdec(substr($hex, 4, 2));
        \VMFDS\Cutter\Core\Logger::getLogger()->addDebug('Parsed color: '.print_r($this,
                1));
    }
}