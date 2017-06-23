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


class UserGroup
{

    protected $id;
    protected $title;
    protected $modules;

    protected $accessLevelForModuleLeute;
    protected $accessLevelForModuleDaten;
    protected $accessLevelForModuleReservation;
    protected $accessLevelForModuleRota;
    protected $accessLevelForModuleFileshare;
    protected $accessLevelForModuleKg;
    protected $accessLevelForModuleTapes;
    protected $accessLevelForModuleGroups;
    protected $accessLevelForModuleDonations;
    protected $accessLevelForModuleTracking;
    protected $peopleFilter;


    public function __construct() {
    }

    /**
     * @return int Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string userGroup title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title userGroup title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }




    /**
     * @return array Modules for this userGroup
     */
    public function getModules()
    {
        return explode(',', $this->modules);
    }

    /**
     * @param array $modules List of modules for this userGroup
     */
    public function setModules($modules)
    {
        $this->modules = join(',', $modules);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleLeute()
    {
        return explode(',', $this->accessLevelForModuleLeute);
    }

    /**
     * @param mixed $accessLevelForModuleLeute
     */
    public function setAccessLevelForModuleLeute($accessLevelForModuleLeute)
    {
        $this->accessLevelForModuleLeute = join (',', $accessLevelForModuleLeute);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleDaten()
    {
        return explode(',', $this->accessLevelForModuleDaten);
    }

    /**
     * @param mixed $accessLevelForModuleDaten
     */
    public function setAccessLevelForModuleDaten($accessLevelForModuleDaten)
    {
        $this->accessLevelForModuleDaten = join (',', $accessLevelForModuleDaten);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleReservation()
    {
        return explode(',', $this->accessLevelForModuleReservation);
    }

    /**
     * @param mixed $accessLevelForModuleReservation
     */
    public function setAccessLevelForModuleReservation($accessLevelForModuleReservation)
    {
        $this->accessLevelForModuleReservation = join (',', $accessLevelForModuleReservation);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleRota()
    {
        return explode(',', $this->accessLevelForModuleRota);
    }

    /**
     * @param mixed $accessLevelForModuleRota
     */
    public function setAccessLevelForModuleRota($accessLevelForModuleRota)
    {
        $this->accessLevelForModuleRota = join (',', $accessLevelForModuleRota);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleFileshare()
    {
        return explode(',', $this->accessLevelForModuleFileshare);
    }

    /**
     * @param mixed $accessLevelForModuleFileshare
     */
    public function setAccessLevelForModuleFileshare($accessLevelForModuleFileshare)
    {
        $this->accessLevelForModuleFileshare = join (',', $accessLevelForModuleFileshare);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleKg()
    {
        return explode(',', $this->accessLevelForModuleKg);
    }

    /**
     * @param mixed $accessLevelForModuleKg
     */
    public function setAccessLevelForModuleKg($accessLevelForModuleKg)
    {
        $this->accessLevelForModuleKg = join (',', $accessLevelForModuleKg);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleTapes()
    {
        return explode(',', $this->accessLevelForModuleTapes);
    }

    /**
     * @param mixed $accessLevelForModuleTapes
     */
    public function setAccessLevelForModuleTapes($accessLevelForModuleTapes)
    {
        $this->accessLevelForModuleTapes = join (',', $accessLevelForModuleTapes);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleGroups()
    {
        return explode(',', $this->accessLevelForModuleGroups);
    }

    /**
     * @param mixed $accessLevelForModuleGroups
     */
    public function setAccessLevelForModuleGroups($accessLevelForModuleGroups)
    {
        $this->accessLevelForModuleGroups = join (',', $accessLevelForModuleGroups);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleDonations()
    {
        return explode(',', $this->accessLevelForModuleDonations);
    }

    /**
     * @param mixed $accessLevelForModuleDonations
     */
    public function setAccessLevelForModuleDonations($accessLevelForModuleDonations)
    {
        $this->accessLevelForModuleDonations = join (',', $accessLevelForModuleDonations);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleTracking()
    {
        return explode(',', $this->accessLevelForModuleTracking);
    }

    /**
     * @param mixed $accessLevelForModuleTracking
     */
    public function setAccessLevelForModuleTracking($accessLevelForModuleTracking)
    {
        $this->accessLevelForModuleTracking = join (',', $accessLevelForModuleTracking);
    }

    /**
     * @return mixed
     */
    public function getPeopleFilter()
    {
        return unserialize($this->peopleFilter);
    }

    /**
     * @param mixed $peopleFilter
     */
    public function setPeopleFilter($peopleFilter)
    {
        $this->peopleFilter = serialize($peopleFilter);
    }




}