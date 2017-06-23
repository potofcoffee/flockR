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


class PreferencesService
{

    /**
     * Get the string value of a preference, either from user or from system store
     * @param string $key Preference key
     * @param \Peregrinus\Flockr\Core\Domain\User|null $user User (leave blank for currently logged-in user)
     * @return string Preference value
     */
    public function getPreferenceValue($key, \Peregrinus\Flockr\Core\Domain\User $user = null)
    {
        $pref = $this->getUserPreferenceValue($key, $user);
        if ($pref == '') {
            $pref = $this->getSystemPreferenceValue($key);
        }
        return $pref;
    }

    /**
     * Get the string value of a UserPreference
     * @param string $key Preference key
     * @param \Peregrinus\Flockr\Core\Domain\User|null $user User (leave blank for currently logged-in user)
     * @return mixed|string Preference value
     */
    public function getUserPreferenceValue($key, \Peregrinus\Flockr\Core\Domain\User $user = null)
    {
        $pref = $this->getUserPreference($key, $user);
        return (is_object($pref) ? $pref->getValue() : '');
    }

    /**
     * Get a UserPreference object
     * @param string $key Preference key
     * @param \Peregrinus\Flockr\Core\Domain\User|null $user User (leave blank for currently logged-in user)
     * @return \Peregrinus\Flockr\Core\Domain\UserPreference|null UserPreference object
     */
    public function getUserPreference($key, \Peregrinus\Flockr\Core\Domain\User $user = null)
    {
        if (!$user) {
            $user = \Peregrinus\Flockr\Core\App::getInstance()
                ->getSecurityContext()
                ->getUser();
        }
        $entityManager = \Peregrinus\Flockr\Core\App::getInstance()
            ->getEntityManager();
        $userPreferencesRepository = $entityManager->getRepository('Peregrinus\Flockr\Core\Domain\UserPreference');
        $dql = 'SELECT p FROM Peregrinus\Flockr\Core\Domain\UserPreference p WHERE (p.user=' . $user->getId() . ') AND (p.key=\'' . $key . '\')';
        $query = $entityManager->createQuery($dql);
        $prefs = $query->getResult();
        if (isset($prefs[0])) {
            return $pref[0];
        } else {
            return null;
        }
    }

    /**
     * Get the string value of a system-wide preference
     * @param string $key Preference key
     * @return string Preference value
     */
    public function getSystemPreferenceValue($key)
    {
        $pref = $this->getSystemPreference($key);
        return (is_object($pref) ? $pref->getValue() : '');
    }

    /**
     * Get a system-wide preference
     * @param string $key Preference key
     * @return \Peregrinus\Flockr\Core\Domain\Preference|null Preference value
     */
    public function getSystemPreference($key)
    {
        $preferencesRepository = \Peregrinus\Flockr\Core\App::getInstance()
            ->getEntityManager()
            ->getRepository('Peregrinus\Flockr\Core\Domain\Preference');
        return $preferencesRepository->findOneByKey($key);
    }

}