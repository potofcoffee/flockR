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


namespace Peregrinus\Flockr\Core\Services;


class SessionService
{

    protected static $instance = NULL;

    /**
     * Get instance
     * Implements singleton pattern
     * @return SessionService
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * SessionService constructor.
     * Start session if necessary
     */
    public function __construct()
    {
        if (!$this->isSessionStarted()) session_start();
    }

    /**
     * @return bool
     */
    protected function isSessionStarted()
    {
        if ( php_sapi_name() !== 'cli' ) {
            if ( version_compare(phpversion(), '5.4.0', '>=') ) {
                return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
            } else {
                return session_id() === '' ? FALSE : TRUE;
            }
        }
        return FALSE;
    }


    /**
     * Get the session id
     * @return string Session id
     */
    public function getId() {
        return session_id();
    }

    /**
     * Checks if a session argument exists
     * @param $key Argument key
     * @return bool True if argument exists
     */
    public function hasArgument($key) {
        return isset($_SESSION[$key]);
    }


    /**
     * Get an argument from the session
     * @param $key Argument key
     * @param null $defaultValue Optional default value
     * @return null|mixed Argument value or optional default
     */
    public function getArgument($key, $defaultValue = null) {
        return ($this->hasArgument($key) ? $_SESSION[$key] : $defaultValue);
    }

    /**
     * Set a session argument
     * @param $key Argument key
     * @param $value Argument value
     */
    public function setArgument($key, $value) {
        $_SESSION[$key] = $value;
    }

}