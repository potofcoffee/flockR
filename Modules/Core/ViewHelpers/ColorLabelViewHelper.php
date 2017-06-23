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


namespace Peregrinus\Flockr\Core\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class ColorLabelViewHelper extends AbstractViewHelper
{

    use CompileWithRenderStatic;

    /**
     * @var boolean
     */
    protected $escapeChildren = false;
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {

        $color = $arguments['color'] ? $arguments['color'] : $renderChildrenClosure();
        if(!$color || strlen($color) != 6) {
            $o = '';
        } else {
            $o = '<span class="flockr-listview-color label" style="font-family: monospace; font-size: 11px; background-color: #'.$color.'; color: '.self::getContrastColor($color).';">'.$color.'</span>';
        }

        return $o;
    }

    /**
     * Get a contrasting color
     * Based on YIQ color space, adapted from kOOL's ko.inc:ko_get_contrast_color()
     * @source http://24ways.org/2010/calculating-color-contrast/
     * @param string $color Color in hex notation
     * @param string $dark Dark color in hex notation
     * @param string $light Light color in hex notation
     * @return string Contrast color in hex notation
     */
    public static function getContrastColor($color, $dark = '#000000', $light = '#FFFFFF')
    {
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        return ($yiq >= 128) ? $dark : $light;
    }


    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('color', 'mixed', 'Color', false, false);
    }
}