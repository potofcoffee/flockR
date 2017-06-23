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


namespace Peregrinus\Flockr\Mailman;


use Peregrinus\Flockr\Core\AbstractModule;
use Peregrinus\Flockr\Core\ConfigurationManager;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Logger;
use Peregrinus\Flockr\Core\Scheduler;
use Peregrinus\Flockr\Core\Services\HookService;
use Peregrinus\Flockr\Mailman\Services\MailmanService;

class MailmanModule extends AbstractModule
{

    public function init() {
        // register this module with the scheduler
        HookService::getInstance()->addAction('init_scheduler', [$this, 'registerScheduledJobs']);
    }

    /**
     * Register jobs with the scheduler
     * It is called upon the @see 'init_scheduler' action
     * @return void
     */
    public function registerScheduledJobs() {
        // schedule the 'update_mailman' hook to be called hourly
        $scheduler = Scheduler::getInstance();
        if (!$scheduler->nextScheduled('update_mailman')) {
            $scheduler->scheduleEvent(time(), 'fiveminutes', 'update_mailman');
        }

        /**
         * Update mailman settings
         */
        HookService::getInstance()->addAction('update_mailman', [$this, 'updateMailman']);
    }

    protected function getGroupMembers($groupId) {
        $fullgid = \ko_groups_decode($groupId, 'full_gid');
        $people = db_select_data('ko_leute', "WHERE `groups` REGEXP '$fullgid' AND `deleted` = '0' AND `hidden` = '0' ", '*', 'ORDER BY nachname, vorname ASC');
        $members = [];
        foreach ($people as $person) {
            $email = '';
            \ko_get_leute_email($person, $email);
            if (is_array($email)) {
                foreach ($email as $address) $members[] = $address;
            } else {
                $members[] = $email;
            }
        }
        return array_unique($members);
    }


    /**
     * Update mailman settings
     */
    public function updateMailman() {
        Logger::getLogger()->addDebug('updateMailman running');

        $lists = ConfigurationManager::getInstance()->getConfigurationSet('MailingLists', $this->moduleInfo['relativePath'].'Configuration');
        foreach ($lists['lists'] as $key => $value) {
            if (strlen($value) <6) $lists['lists'][$key] = str_pad($value, 6, '0', STR_PAD_LEFT);
        }

        $mailmanService = new MailmanService();

        foreach ($lists['lists'] as $mailingList => $group) {
            $listMembers = MailmanService::getInstance()->getListMembers($mailingList);

            $groupMembers = $this->getGroupMembers($group);

            // find members to subscribe
            $subscribe = [];
            foreach ($groupMembers as $address) {
                if (!in_array($address, $listMembers)) {
                    $mailmanService->subscribe($mailingList, $address);
                    Logger::getLogger()->addDebug('subscribed '.$address.' to mailing list '.$mailingList);
                    $subscribe[] = $address;
                }
            }

            // find members to unsubscribe
            $unsubscribe = [];
            foreach ($listMembers as $address) {
                if (!in_array($address, $groupMembers)) {
                    $mailmanService->unsubscribe($mailingList, $address);
                    Logger::getLogger()->addDebug('unsubscribed '.$address.' from mailing list '.$mailingList);
                    $unsubscribe[] = $address;
                }
            }

        }
    }

}