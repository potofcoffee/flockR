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


namespace Peregrinus\Flockr\Core\Settings;


use Peregrinus\Flockr\Core\Services\SettingsService;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class Setting
{
    const SCOPE_GLOBAL = 1;
    const SCOPE_USER = 2;

    protected $id = '';
    protected $label = '';
    protected $type = 'text';
    protected $scope = self::SCOPE_GLOBAL;
    protected $value = '';

    public function __construct($id, $label, $scope)
    {
        $this->setLabel($label);
        $this->setScope($scope);
        $this->setId($id);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
        if ($id) {
            if ($this->getScope() == self::SCOPE_GLOBAL) {
                $this->setValue(SettingsService::getInstance()->getGlobalSetting($id));
            } elseif ($this->getScope() == self::SCOPE_USER) {
                $this->setValue(SettingsService::getInstance()->getUserPreference($id));
            }
        }
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param int $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        // TODO: Filter Value!
        $this->value = $value;
    }


    /**
     * Save the setting's value to the database
     * @param int|null $userId user_id or null to use current user
     */
    public function persist($userId = null) {
        if ($this->getScope() == self::SCOPE_GLOBAL) {
            SettingsService::getInstance()->setGlobalSetting($this->getId(), $this->getValue());
        } elseif ($this->getScope() == self::SCOPE_USER) {
            SettingsService::getInstance()->setUserPreference($this->getId(), $this->getValue(), $userId);
        }
    }

}