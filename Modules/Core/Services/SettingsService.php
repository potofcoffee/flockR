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


use Peregrinus\Flockr\Core\DB;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class SettingsService
{
    protected static $instance = null;
    protected $cache = [];

    /**
     * Get an instance of SettingsService
     * @return SettingsService Instance
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /*
     * Get a setting from the database
     *
     * @param string $key Key to get setting for
     * @param boolean $force Set to true to force rereading setting from db
     * @param mixed $defaultValue Optional default value (default: false)
     * @return mixed Value for the specified key
     */
    public function getGlobalSetting($key, $force = false, $defaultValue = false)
    {
        if (!$force && isset($this->cache['settings'][$key])) {
            // get from cache
            $result = $this->cache['settings'][$key];
        } else {
            // get from DB
            $statement = DB::getInstance()->getStatement('SELECT `value` FROM `ko_settings` WHERE `key` = :key LIMIT 1');
            if ($statement->execute(['key' => $key])) {
                $result = $statement->fetch(\PDO::FETCH_ASSOC)['value'];
            } else {
                // fall back to default value
                $result = $defaultValue;
            }
        }
        // process throug hook
        $result = HookService::getInstance()->applyFilters('get_setting', $result, $key);

        // save to cache
        $this->cache['settings'][$key] = $result;
        return $result;

    }


    /*
     * Stores a setting in the database
     *
     * @param string Key of the setting to be stored
     * @param mixed Value to be stored
     * @return boolean True on succes, false on failure
     */
    function setGlobalSetting($key, $value)
    {
        // process value through hook
        $value = HookService::getInstance()->applyFilters('set_setting', $value, $key);
        $db = DB::getInstance();
        // save to db
        if ($db->count('ko_settings', 'key', 'AND `key`= :key', ['key' => $key])) {
            $db->insert('ko_settings', ['key' => $key, 'value' => format_userinput($value, 'text')]);
        } else {
            $db->update('ko_settings', 'WHERE `key` = :key', ['key' => $key, 'value' => format_userinput($value, 'text')]);
        }
        // update cache
        $this->cache['settings'][$key] = $value;
        return true;
    }//ko_set_setting()

    /**
     * Get a user preference as stored in ko_userprefs
     *
     * @param string Key of user preference
     * @param int user id or null, if current user
     * @param string Type of user preference to get
     * @param string ORDER BY statement to pass to the db
     * @param boolean Set to true to have the userpref read from DB instead of from cache
     * @return mixed Value of user preference
     */
    public function getUserPreference($key = "", $userId = null, $type = "", $order = "", $force = false)
    {
        if (is_null($userId)) $userId = LoginService::getInstance()->getUserId();
        if ($type != "") {
            if ($key != "") {
                //Look up userpref in cache
                if (!$force && $userId == LoginService::getInstance()->getUserId() && isset($this->cache['preferences']["TYPE@" . $type][$key])) {
                    return array($this->cache['preferences']["TYPE@" . $type][$key]);
                }
                //Get it from DB if not set
                $statement = DB::getInstance()->getStatement('SELECT * FROM `ko_userprefs` WHERE `user_id` = :user_id AND `key` = :key AND `type` = :type ' . $order);
                $statement->execute(['user_id' => $userId, 'key' => $key, 'type' => $type]);
            } else {
                //Look up userpref in cache
                if (!$force && $userId == LoginService::getInstance()->getUserId() && is_array($this->cache['preferences']["TYPE@" . $type])) {
                    return $this->cache['preferences']["TYPE@" . $type];
                }
                //Get it from DB if not set
                $statement = DB::getInstance()->getStatement('SELECT * FROM `ko_userprefs` WHERE `user_id` = :user_id AND `type` = :type ' . $order);
                $statement->execute(['user_id' => $userId, 'type' => $type]);
            }
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            //Look up userpref in cache
            if (!$force && ($userId == LoginService::getInstance()->getUserId()) && (isset($this->cache['preferences'][$key]))) {
                return $this->cache['preferences'][$key];
            }
            //Get it from DB if not set
            $statement = DB::getInstance()->getStatement('SELECT * FROM `ko_userprefs` WHERE `user_id` = :user_id AND `key` = :key ' . $order);
            $statement->execute(['user_id' => $userId, 'key' => $key]);
            if (defined('DEBUG_THIS_STATEMENT')) {
                Debugger::dumpAndDie([$statement, ['user_id' => $userId, 'key' => $key], $statement->fetchAll(\PDO::FETCH_ASSOC)]);
            }
            $row = $statement->fetch(\PDO::FETCH_ASSOC);
            return $row['value'];
        }
    }

    /**
     * Store a user preference in ko_userprefs
     *
     * @param string $key Key of user preference
     * @param mixed $value Value to be stored
     * @param int $userId user id or null to use current user
     * @param string $type Type of user preference to store
     */
    function setUserPreference($key, $value, $userId = null, $type = "")
    {
        if (is_null($userId)) $userId = LoginService::getInstance()->getUserId();
        $userId = format_userinput($userId, "int");
        $key = format_userinput($key, "text");
        $type = format_userinput($type, "alphanum+");

        $db = DB::getInstance();
        if ($db->count('ko_userprefs', 'key', 'AND `user_id`= :user_id AND `key` = :key AND `type` = :type',
            ['user_id' => $userId, 'key' => $key, 'type' => $type])) {
            $db->update('ko_userprefs', 'WHERE `user_id`= :user_id AND `key` = :key AND `type` = :type',
                ['user_id' => $userId, 'key' => $key, 'type' => $type, 'value' => $value]);
        } else {
            $db->insert('ko_userprefs', ['user_id' => $userId, 'key' => $key, 'type' => $type, 'value' => $value]);
        }
        //Save in GLOBALS as well (but only for logged in user)
        if ($userId == $_SESSION["ses_userid"]) {
            if ($type != "") {
                $this->cache['preferences']["TYPE@" . $type][$key] = array(
                    "type" => $type,
                    "key" => $key,
                    "value" => $value
                );
            } else {
                $this->cache['preferences'][$key] = $value;
            }
        }
    }//ko_save_userpref()


}