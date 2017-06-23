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


namespace Peregrinus\Flockr\People\Domain\Repository;


use Peregrinus\Flockr\Core\AbstractRepository;
use Peregrinus\Flockr\Core\ConfigurationManager;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Utility\StringUtility;
use Peregrinus\Flockr\Legacy\Services\LoginService;

class PeopleRepository extends AbstractRepository
{
    protected $table = 'ko_leute';

    /**
     * Find the person record corresponding to a user
     * @param int|null $userId User id, leave blank for currently logged-in user
     * @return array|bool User record or false if not found
     */
    public function findOneByUser($userId = null) : array {
        $user = LoginService::getInstance()->getUser($userId);
        if (isset($user['leute_id'])) {
            return $this->findOneByUid($user['leute_id']);
        } else {
            return false;
        }
    }

    /**
     * Get the email addresses for a person
     * @param array|int $person Person (or id)
     * @param bool $includeName Include name with address? (default: no)
     * @param bool $htmlSpecialChars Treat with htmlSpecialChars? (default: no)
     * @return array Email addresses
     */
    public function getEmailAddresses($person, bool $includeName = false, bool $htmlSpecialChars = false) : array {
        if (is_numeric($person)) $person = $this->findOneByUid($person);

        $emailFields = $this->db->select(
            'ko_leute_preferred_fields',
            "WHERE `type` = 'email' AND `lid` = :lid",
            '*', '', '', false, false,
            ['lid' => $person['id']]);

        $emailAddresses = [];

        // try fields from preferred setting
        foreach ($emailFields as $field) {
            if (StringUtility::isEmailAddress($address = $person[$field['field']])) {
                if ($includeName) $address = $person['vorname'].' '.$person['nachname'].' <'.$address.'>';
                if ($htmlSpecialChars) $address = htmlspecialchars($address);
                $emailAddresses[] = $address;
            }
        }

        // if no fields found, try the global setting
        if (sizeof($emailAddresses)==0) {
            $globalEmailFields = ConfigurationManager::getInstance()->getConfigurationSet('Setup')['LEUTE_EMAIL_FIELDS'];
            foreach ($globalEmailFields as $field) {
                if (StringUtility::isEmailAddress($address = $person[$field])) {
                    if ($includeName) $address = $person['vorname'].' '.$person['nachname'].' <'.$address.'> **';
                    if ($htmlSpecialChars) $address = htmlspecialchars($address);
                    $emailAddresses[] = $address;
                }
            }
        }

        return $emailAddresses;
    }

}