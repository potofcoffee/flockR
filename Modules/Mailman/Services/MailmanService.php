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


namespace Peregrinus\Flockr\Mailman\Services;


use Peregrinus\Flockr\Core\Debugger;

require_once 'Services/Mailman.php';

class MailmanService
{
    protected static $instance = null;
    protected $pearService = null;

    /**
     * Get MailmanService instance
     * @return MailmanService instance
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->pearService = new \Services_Mailman('http://lists.vmfds.de/mailman/admin', '', 'fdsvmec');
    }

    public function getListMembers($list)
    {
        $this->pearService->setList($list);
        $raw = $this->pearService->members();
        return $raw[0];
    }

    public function unsubscribe ($list, $member) {
        $this->pearService->setList($list);
        try {
            $this->pearService->unsubscribe($member);
        } catch (\Services_Mailman_Exception $e) {
            //Debugger::dump($e, 'EXCEPTION');
        }
    }

    public function subscribe ($list, $member) {
        $this->pearService->setList($list);
        try {
            $this->pearService->subscribe($member);
        } catch (\Services_Mailman_Exception $e) {
            //Debugger::dump($e, 'EXCEPTION');
        }
    }

}