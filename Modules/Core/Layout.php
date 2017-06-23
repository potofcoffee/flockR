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


use Peregrinus\Flockr\Core\Menu\MenuBuilder;

class Layout {
	protected $data = [];
    protected $app = null;

	public function __construct( $app ) {
		$this->app = $app;
	}

	public function set( $key, $value ) {
		$this->data[ $key ] = $value;
	}

	public function addByIndex( $key, $idx, $value ) {
		$this->data[ $key ][ $idx ] = $value;
	}

	public function load( $a ) {
		$this->data = array_merge_recursive( $a, $this->data );
	}

	public function addStyleSheet( $stylesheet, $external = false ) {
		$this->add( 'css', [ 'href' => $stylesheet, 'external' => ( $external ? 1 : 0 ) ] );
	}

	public function add( $key, $value ) {
		$this->data[ $key ][] = $value;
	}

	public function addJavaScript( $area, $src, $external = false ) {
		$rec['href'] = $src;
		if ( $external ) {
			$ref['external'] = 1;
		}
		$this->data['js'][ $area ][] = $rec;
	}

	public function addFlashMessage( FlashMessage $message ) {
		$this->data['flashmessages'][] = $message;
	}

	public function getViewData() {
		$menuBuilder = MenuBuilder::getInstance();

		return [
			'route'    => [
				'module'     => $this->app->activeModule,
				'controller' => $this->app->activeController,
				'action'     => $this->app->activeAction,
			],
			'page'     => $this->data,
//			'menu'     => $menuBuilder->getMenu(),
//			'security' => $this->app->getSecurityContext()->getSecurityData(),
		];
	}

}