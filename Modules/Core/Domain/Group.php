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


namespace Peregrinus\Flockr\Core\Domain;


class Group
{

    protected $id;
    protected $parent;
    protected $title;
    protected $viewRights;
    protected $editRights;
    protected $createRights;
    protected $deleteRights;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getViewRights()
    {
        return $this->viewRights;
    }

    /**
     * @param mixed $viewRights
     */
    public function setViewRights($viewRights)
    {
        $this->viewRights = $viewRights;
    }

    /**
     * @return mixed
     */
    public function getEditRights()
    {
        return $this->editRights;
    }

    /**
     * @param mixed $editRights
     */
    public function setEditRights($editRights)
    {
        $this->editRights = $editRights;
    }

    /**
     * @return mixed
     */
    public function getCreateRights()
    {
        return $this->createRights;
    }

    /**
     * @param mixed $createRights
     */
    public function setCreateRights($createRights)
    {
        $this->createRights = $createRights;
    }

    /**
     * @return mixed
     */
    public function getDeleteRights()
    {
        return $this->deleteRights;
    }

    /**
     * @param mixed $deleteRights
     */
    public function setDeleteRights($deleteRights)
    {
        $this->deleteRights = $deleteRights;
    }



}