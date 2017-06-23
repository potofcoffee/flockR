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


use Peregrinus\Flockr\Core\Debugger;

class TranslationService
{

    protected $languageStore = [];
    protected $language = 'de';

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Add data to the language store
     * @param array $data Data
     */
    public function addToLanguageStore($data) {
        if (is_array($data)) $this->languageStore = array_merge_recursive($data, $this->languageStore);
    }

    /**
     * Get a localized string
     * @param string $key Key
     * @param string $language Language (leave blank for current system language)
     * @return string Translated string
     */
    public function translate($key, $language = '') {
        if (!$language) $language = $this->language;
        return $this->languageStore[$language][$key] ? $this->languageStore[$language][$key] : $key;
    }

}