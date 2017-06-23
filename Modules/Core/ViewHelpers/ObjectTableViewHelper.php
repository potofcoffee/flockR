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

class ObjectTableViewHelper extends AbstractViewHelper
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

        $templateVariableContainer = $renderingContext->getVariableProvider();

        $exclude = $arguments['excludeProperties'] ? explode(',', $arguments['excludeProperties']) : [];
        $expand = $arguments['expandProperties'] ? explode(',', $arguments['expandProperties']) : [];

        $o = '<table class="' . $arguments['class'] . '"><thead><tr>';
        $objects = $arguments['content'];
        if (is_array($objects)) {
            $object = $objects[0];
        } else {
            $object = $objects;
        }

        $properties = static::getProperties($object, $exclude, $expand);
        foreach ($properties as $property) {
            list ($class, $field) = explode(':', $property);
            $domainConfig = \Peregrinus\Flockr\Core\App::getInstance()->getDomainManager()->getDomainConfigForClass($class);
            $title = $domainConfig['properties'][$field]['title'] ? $domainConfig['properties'][$field]['title'] : $property;
            $o .= '<th>' . $title . '</th>';
        }
        if ($arguments['buttons']) $o .= '<th></th>';
        $o .= '</tr></thead><tbody>';

        foreach ($objects as $object) {
            $o .= '<tr>';
            foreach ($properties as $property) {
                $o .= '<td>' . static::getPropertyValue($object, $property, $expand) . '</td>';
            }
            if ($arguments['buttons']) {
                $templateVariableContainer->add($arguments['itemVarForButtons'], $object);
                $o .= '<td>'.$renderChildrenClosure().'</td>';
                $templateVariableContainer->remove($arguments['itemVarForButtons']);
            }
            $o .= '</tr>';
        }

        $o .= '</tbody></table>';


        return $o;
    }

    public static function getProperties($object, $exclude, $expand)
    {
        $class = get_class($object);
        $reflection = new \ReflectionObject($object);
        $properties = [];
        $objectProperties = $reflection->getProperties();
        foreach ($objectProperties as $property) {
            $propertyName = $property->getName();
            if (!in_array($propertyName, $exclude)) {
                $getter = 'get' . ucfirst($propertyName);
                if (method_exists($object, $getter)) {
                    if ((is_array($content = $object->$getter())) && (in_array($propertyName, $expand))) {
                        foreach ($content as $key => $value) {
                            $properties[] = $class . ':' . $propertyName . '.' . $key;
                        }
                    } else {
                        $properties[] = $class . ':' . $propertyName;
                    }
                }
            }
        }
        return $properties;
    }

    public static function getPropertyValue($object, $property, $expand)
    {
        if (strpos($property, ':')) {
            list($class, $property) = explode(':', $property);
        }
        $getter = 'get' . ucfirst($property);
        if (strpos($property, '.') !== false) {
            list($realProperty, $key) = explode('.', $property);
            $fullValue = static::getPropertyValue($object, $realProperty, $expand);
            return isset($fullValue[$key]) ? $fullValue[$key] : '';
        } else {
            if (method_exists($object, $getter)) {
                $data = $object->$getter();
                if ((is_array($data)) && (!in_array($property, $expand))) {
                    $data = join(',', $data);
                }
                return $data;
            } else {
                return 'Invald: ' . $class . '->' . $getter . '()';
            }
        }
    }

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('content', 'mixed', 'Content', false, false);
        $this->registerArgument('buttons', 'bool', 'Show buttons on each row', false, false);
        $this->registerArgument('itemVarForButtons', 'string', 'Item variable for buttons template', false, false);
        $this->registerArgument('class', 'string', 'CSS class(es)', false, false);
        $this->registerArgument('excludeProperties', 'string', 'Comma-separated list of properties to exclude', false,
            false);
        $this->registerArgument('expandProperties', 'string', 'Comma-separated list of properties to expand', false,
            false);
    }
}