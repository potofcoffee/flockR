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


namespace Peregrinus\Flockr\Core\ViewHelpers\Format;


class DateViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

    use \TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

    /**
     * Needed as child node's output can return a DateTime object which can't be escaped
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     * @throws Exception
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
    ) {
        $format = $arguments['format'];
        $base = $arguments['base'] === null ? time() : $arguments['base'];
        if (is_string($base)) {
            $base = trim($base);
        }
        if ($format === '') {
            $format = 'Y-m-d';
        }
        $date = $renderChildrenClosure();
        if ($date === null) {
            $date = 'now';
        }
        if (is_string($date)) {
            $date = trim($date);
        }
        if ($date === '') {
            $date = 'now';
        }
        if (!$date instanceof \DateTimeInterface) {
            try {
                $base = $base instanceof \DateTimeInterface ? $base->format('U') : strtotime((static::canBeInterpretedAsInteger($base) ? '@' : '') . $base);
                $dateTimestamp = strtotime((static::canBeInterpretedAsInteger($date) ? '@' : '') . $date, $base);
                $date = new \DateTime('@' . $dateTimestamp);
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            } catch (\Exception $exception) {
                throw new \Exception('"' . $date . '" could not be parsed by \DateTime constructor: ' . $exception->getMessage(),
                    1241722579  );
            }
        }
        if (strpos($format, '%') !== false) {
            return strftime($format, $date->format('U'));
        } else {
            return $date->format($format);
        }
    }


    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('date', 'mixed',
            'Either an object implementing DateTimeInterface or a string that is accepted by DateTime constructor');
        $this->registerArgument('format', 'string', 'Format String which is taken to format the Date/Time', false, '');
        $this->registerArgument('base', 'mixed',
            'A base time (an object implementing DateTimeInterface or a string) used if $date is a relative date specification. Defaults to current time.');
    }

    public static function canBeInterpretedAsInteger($var) {
        return (string)(int)$var === (string)$var;
    }

}