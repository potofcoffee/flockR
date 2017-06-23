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


namespace Peregrinus\Flockr\Legacy\Services;


use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Legacy\LegacyModule;

class LayoutService
{
    public static function pageHeader($title, $shortcutIcon) {
        return self::renderSnippet('header', [
            'lang' => $_SESSION["lang"],
            'title' => $title,
            'shortcutIcon' => $shortcutIcon,
        ]);
    }

    /**
     * Render a snippet view
     * @param string $snippet Snippet name
     * @param array $data Data
     */
    public static function renderSnippet($snippet, $data) {
        /** @var \TYPO3Fluid\Fluid\View\TemplateView $view */
        $view = App::getInstance()->createView(new LegacyModule(), null, null);
        $view->getRenderingContext()->setControllerName('Snippet');
        foreach ($data as $key => $value) {
            $view->assign($key, $value);
        }
        return $view->render($snippet);
    }
}