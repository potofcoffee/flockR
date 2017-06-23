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


use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\CoreModule;
use Peregrinus\Flockr\Core\Utility\ChurchYearUtility;
use Peregrinus\Flockr\Core\Utility\UriUtility;

class AjaxViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

    use \TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

    protected $escapeOutput = false;
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
        if (!isset($arguments['id'])) {
            $arguments['id'] = 'ajax_'.md5($arguments['route'].microtime());
        }
        if (!UriUtility::isAbsolute($arguments['route'])) {
            $arguments['route'] = FLOCKR_baseUrl.$arguments['route'];
        }
        $view = App::getInstance()->createView(CoreModule::getInstance(), 'ViewHelpers', 'ajax');
        $view->assign ('id', $arguments['id']);
        $view->assign ('route', $arguments['route']);
        $view->assign ('arguments', json_encode($arguments['arguments']));
        $view->assign ('onLoad', $arguments['onLoad']);
        return $view->render('ajax');
    }


    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('id', 'string', 'ID', false);
        $this->registerArgument('route', 'string', 'Route to retrieve ajax info', true);
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('onLoad', 'string', 'What to do onLoad', false);
    }

}