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


namespace Peregrinus\Flockr\Legacy\ViewHelpers\Legacy;


use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Legacy\LegacyApp;

class MenuViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

    use \TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

    protected $escapeOutput = false;

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
    )
    {
        global $smarty, $ko_path, $MODULES;

        ob_start();

        //HOOK: Submenus einlesen
        $hooks = hook_include_sm();
        if (sizeof($hooks) > 0) {
            foreach ($hooks as $hook) {
                include($hook);
            }
        }


        include FLOCKR_basePath . 'menu.php';

        $menu = ob_get_contents();
        ob_end_clean();

        return $menu;
    }


    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('content', 'mixed', 'Content to be encoded');
    }

}