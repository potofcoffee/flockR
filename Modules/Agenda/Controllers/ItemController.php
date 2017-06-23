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


namespace Peregrinus\Flockr\Agenda\Controllers;


use Peregrinus\Flockr\Core\AbstractController;

class ItemController extends AbstractController {

	function formAction() {
		$this->request->applyUriPattern(['type']);
		$itemType = $this->request->getArgument('type');

		$paths = $this->view->getTemplatePaths();
		$paths->setTemplatePathAndFileName($this->module->getBasePath().'Resources/Private/Templates/Item/'.ucfirst($itemType).'/Form.html');
	}

	function planAction() {
		$this->request->applyUriPattern(['type']);
		$itemType = $this->request->getArgument('type');
		$this->view->assign('item', $this->request->getArgument('item'));
		$paths = $this->view->getTemplatePaths();
		$paths->setTemplatePathAndFileName($this->module->getBasePath().'Resources/Private/Templates/Item/'.ucfirst($itemType).'/Plan.html');
	}

}