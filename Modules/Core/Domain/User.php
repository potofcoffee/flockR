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


use Peregrinus\Flockr\Core\App;

class User
{

    protected $id;
    protected $login;
    protected $modules;
    protected $userPreferences;
    protected $userGroups;

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
        $this->userPreferences = new ArrayCollection();
    }

    /**
     * @return int Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string login
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @return array Modules for this user
     */
    public function getModules()
    {
        return explode(',', $this->modules);
    }

    /**
     * @param array $modules List of modules for this user
     */
    public function setModules($modules)
    {
        $this->modules = join(',', $modules);
    }




    /**
     * @return mixed
     */
    public function getUserPreferences()
    {
        return $this->userPreferences;
    }

    /**
     * @param mixed $userPreferences
     */
    public function setUserPreferences($userPreferences)
    {
        $this->userPreferences = $userPreferences;
    }

    /**
     * @return \Peregrinus\Flockr\Core\Domain\UserGroup List of user groups for this user
     */
    public function getUserGroups()
    {
        return App::getInstance()->getEntityManager()->createQuery(
            'SELECT g FROM Peregrinus\Flockr\Core\Domain\UserGroup g WHERE (g.id IN ('.$this->userGroups.'))'
        )->getResult();
    }

    /**
     * @param array $userGroups List of user groups for this user
     */
    public function setUserGroups($userGroups)
    {
        $ids = [];
        foreach ($userGroups as $group) {
            $ids[] = $group->getId();
        }
        $this->userGroups = join(',', $ids);
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleLeute()
    {
        return $this->accessLevelForModuleLeute;
    }

    /**
     * @param mixed $accessLevelForModuleLeute
     */
    public function setAccessLevelForModuleLeute($accessLevelForModuleLeute)
    {
        $this->accessLevelForModuleLeute = $accessLevelForModuleLeute;
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleDaten()
    {
        return $this->accessLevelForModuleDaten;
    }

    /**
     * @param mixed $accessLevelForModuleDaten
     */
    public function setAccessLevelForModuleDaten($accessLevelForModuleDaten)
    {
        $this->accessLevelForModuleDaten = $accessLevelForModuleDaten;
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleReservation()
    {
        return $this->accessLevelForModuleReservation;
    }

    /**
     * @param mixed $accessLevelForModuleReservation
     */
    public function setAccessLevelForModuleReservation($accessLevelForModuleReservation)
    {
        $this->accessLevelForModuleReservation = $accessLevelForModuleReservation;
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleRota()
    {
        return $this->accessLevelForModuleRota;
    }

    /**
     * @param mixed $accessLevelForModuleRota
     */
    public function setAccessLevelForModuleRota($accessLevelForModuleRota)
    {
        $this->accessLevelForModuleRota = $accessLevelForModuleRota;
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleFileshare()
    {
        return $this->accessLevelForModuleFileshare;
    }

    /**
     * @param mixed $accessLevelForModuleFileshare
     */
    public function setAccessLevelForModuleFileshare($accessLevelForModuleFileshare)
    {
        $this->accessLevelForModuleFileshare = $accessLevelForModuleFileshare;
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleKg()
    {
        return $this->accessLevelForModuleKg;
    }

    /**
     * @param mixed $accessLevelForModuleKg
     */
    public function setAccessLevelForModuleKg($accessLevelForModuleKg)
    {
        $this->accessLevelForModuleKg = $accessLevelForModuleKg;
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleTapes()
    {
        return $this->accessLevelForModuleTapes;
    }

    /**
     * @param mixed $accessLevelForModuleTapes
     */
    public function setAccessLevelForModuleTapes($accessLevelForModuleTapes)
    {
        $this->accessLevelForModuleTapes = $accessLevelForModuleTapes;
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleGroups()
    {
        return $this->accessLevelForModuleGroups;
    }

    /**
     * @param mixed $accessLevelForModuleGroups
     */
    public function setAccessLevelForModuleGroups($accessLevelForModuleGroups)
    {
        $this->accessLevelForModuleGroups = $accessLevelForModuleGroups;
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleDonations()
    {
        return $this->accessLevelForModuleDonations;
    }

    /**
     * @param mixed $accessLevelForModuleDonations
     */
    public function setAccessLevelForModuleDonations($accessLevelForModuleDonations)
    {
        $this->accessLevelForModuleDonations = $accessLevelForModuleDonations;
    }

    /**
     * @return mixed
     */
    public function getAccessLevelForModuleTracking()
    {
        return $this->accessLevelForModuleTracking;
    }

    /**
     * @param mixed $accessLevelForModuleTracking
     */
    public function setAccessLevelForModuleTracking($accessLevelForModuleTracking)
    {
        $this->accessLevelForModuleTracking = $accessLevelForModuleTracking;
    }

    /**
     * @return mixed
     */
    public function getPeopleFilter()
    {
        return $this->peopleFilter;
    }

    /**
     * @param mixed $peopleFilter
     */
    public function setPeopleFilter($peopleFilter)
    {
        $this->peopleFilter = $peopleFilter;
    }






}