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


namespace Peregrinus\Flockr\Songs\Services;

use Peregrinus\Flockr\Core\AbstractClass;

class CCLIService extends AbstractClass
{
    protected $config = [];
    protected $serviceConfig = [];
    protected $curl = null;
    protected $loggedIn = false;

    /**
     * CCLIService constructor.
     * Auto-configure service from /Modules/Songs/Configuration/CCLI.yaml
     */
    public function __construct($service)
    {
        $this->config = \Peregrinus\Flockr\Core\ConfigurationManager::getInstance()->getConfigurationSet('CCLI',
            'Modules/Songs/Configuration');
        $this->serviceConfig = $this->config['services'][$service];
        $this->initCurl();
    }

    /**
     * Initialize CURL for use with this object
     */
    protected function initCurl()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_NOBODY, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
//        curl_setopt($this->curl, CURLOPT_USERAGENT,
//            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36');
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($this->curl, CURLOPT_COOKIEJAR,
            'downloads/cookie.txt');  //could be empty, but cause problems on some hosts
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, 'downloads/cookie.txt');
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl, CURLOPT_AUTOREFERER, 1);
    }

    /**
     * Issues a POST request to an url
     *
     * @param string $url Url to post to
     * @param array $data Arguments for the POST request
     * @return string Answer
     */
    protected function post($url, $data = array())
    {
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data));
        return $this->curl_exec($url);
    }

    /**
     * Wrapper function for curl_exec()
     *
     * @param string $url Url to retrieve
     * @return string Curl answer
     */
    protected function curl_exec($url)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        $answer = curl_exec($this->curl);
        if (!(($answer === false) || (is_null($answer)))) {
            return $answer;
        }
    }

    /**
     * Get an URL via HTTP GET
     * @param string $url Url
     * @return string Url contents
     */
    protected function get($url)
    {
        return $this->curl_exec($url);
    }

    /**
     * Perform a CCLI login
     */
    protected function login() {
        $this->post($this->serviceConfig['loginUrl'], [
            'emailAddress' => $this->config['credentials']['user'],
            'password' => $this->config['credentials']['password'],
            'RememberMe' => 'false',
        ]);
        $this->loggedIn = true;
    }

    protected function getBaseUrl() {
        return $this->serviceConfig['baseUrl'];
    }
}