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
 *
 * This file contains code adapted from WordPress <http://www.wordpress.org>.
 */


namespace Peregrinus\Flockr\Core;


use Peregrinus\Flockr\Core\Services\HookService;
use Peregrinus\Flockr\Core\Services\SettingsService;

class Scheduler
{

    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;

    protected static $instance = null;
    protected $hookService = null;

    /**
     * Scheduler constructor.
     */
    public function __construct()
    {
        $this->hookService = HookService::getInstance();
    }

    /**
     * Get the scheduler instance
     * @return Scheduler Scheduler instance
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        $this->hookService->doAction('init_scheduler');
    }

    /**
     * Schedules an event to run only once.
     *
     * Schedules an event which will execute once at a time which you specify.
     * The action will fire off when the flockR scheduler is called.
     *
     * Note that scheduling an event to occur within 10 minutes of an existing event
     * with the same action hook will be ignored unless you pass unique `$args` values
     * for each scheduled event.
     *
     * @link https://codex.wordpress.org/Function_Reference/wp_schedule_single_event
     *
     * @param int $timestamp Unix timestamp (UTC) for when to run the event.
     * @param string $hook Action hook to execute when event is run.
     * @param array $args Optional. Arguments to pass to the hook's callback public function.
     * @return false|void False if the event does not get scheduled.
     */
    public function scheduleSingleEvent($timestamp, $hook, $args = array())
    {
        // Make sure timestamp is a positive integer
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            return false;
        }

        // Don't schedule a duplicate if there's already an identical event due within 10 minutes of it
        $next = $this->nextScheduled($hook, $args);
        if ($next && abs($next - $timestamp) <= 10 * self::MINUTE) {
            return false;
        }

        $crons = $this->getJobArray();
        $event = (object)array('hook' => $hook, 'timestamp' => $timestamp, 'schedule' => false, 'args' => $args);
        /**
         * Filters a single event before it is scheduled.
         *
         * @param stdClass $event {
         *     An object containing an event's data.
         *
         * @type string $hook Action hook to execute when event is run.
         * @type int $timestamp Unix timestamp (UTC) for when to run the event.
         * @type string|false $schedule How often the event should recur. See `wp_get_schedules()`.
         * @type array $args Arguments to pass to the hook's callback public function.
         * }
         */
        $event = $this->hookService->applyFilters('schedule_event', $event);

        // This event may have been cleared by a hook
        if (!$event)
            return false;

        $key = md5(serialize($event->args));

        $crons[$event->timestamp][$event->hook][$key] = array('schedule' => $event->schedule, 'args' => $event->args);
        uksort($crons, "strnatcasecmp");
        $this->setJobArray($crons);
    }

    /**
     * Retrieve the next timestamp for an event.
     *
     * @param string $hook Action hook to execute when event is run.
     * @param array $args Optional. Arguments to pass to the hook's callback public function.
     * @return false|int The Unix timestamp of the next time the scheduled event will occur.
     */
    public function nextScheduled($hook, $args = array())
    {
        $jobs = $this->getJobArray();
        $key = md5(serialize($args));
        if (empty($jobs))
            return false;
        foreach ($jobs as $timestamp => $job) {
            if (isset($job[$hook][$key]))
                return $timestamp;
        }
        return false;
    }

    /**
     * Retrieve cron info array option.
     * @access private
     *
     * @return false|array CRON info array.
     */
    protected function getJobArray()
    {
        $jobs = SettingsService::getInstance()->getGlobalSetting('scheduler_jobs', []);
        return $jobs;
    }

    /**
     * Updates the CRON option with the new CRON array.
     * @access private
     *
     * @param array $jobs Cron info array from _get_cron_array().
     */
    protected function setJobArray($jobs)
    {
        SettingsService::getInstance()->setGlobalSetting('scheduler_jobs', $jobs);
    }

    /**
     * Reschedule a recurring event.
     *
     * @param int $timestamp Unix timestamp (UTC) for when to run the event.
     * @param string $recurrence How often the event should recur.
     * @param string $hook Action hook to execute when event is run.
     * @param array $args Optional. Arguments to pass to the hook's callback public function.
     * @return false|void False if the event does not get rescheduled.
     */
    public function rescheduleEvent($timestamp, $recurrence, $hook, $args = array())
    {
        // Make sure timestamp is a positive integer
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            return false;
        }

        $crons = $this->getJobArray();
        $schedules = $this->getSchedules();
        $key = md5(serialize($args));
        $interval = 0;

        // First we try to get it from the schedule
        if (isset($schedules[$recurrence])) {
            $interval = $schedules[$recurrence]['interval'];
        }
        // Now we try to get it from the saved interval in case the schedule disappears
        if (0 == $interval) {
            $interval = $crons[$timestamp][$hook][$key]['interval'];
        }
        // Now we assume something is wrong and fail to schedule
        if (0 == $interval) {
            return false;
        }

        $now = time();

        if ($timestamp >= $now) {
            $timestamp = $now + $interval;
        } else {
            $timestamp = $now + ($interval - (($now - $timestamp) % $interval));
        }

        $this->scheduleEvent($timestamp, $recurrence, $hook, $args);
    }

    /**
     * Retrieve supported event recurrence schedules.
     *
     * The default supported recurrences are 'hourly', 'twicedaily', and 'daily'. A plugin may
     * add more by hooking into the {@see 'cron_schedules'} filter. The filter accepts an array
     * of arrays. The outer array has a key that is the name of the schedule or for
     * example 'weekly'. The value is an array with two keys, one is 'interval' and
     * the other is 'display'.
     *
     * The 'interval' is a number in seconds of when the cron job should run. So for
     * 'hourly', the time is 3600 or 60*60. For weekly, the value would be
     * 60*60*24*7 or 604800. The value of 'interval' would then be 604800.
     *
     * The 'display' is the description. For the 'weekly' key, the 'display' would
     * be `__( 'Once Weekly' )`.
     *
     * For your plugin, you will be passed an array. you can easily add your
     * schedule by doing the following.
     *
     *     // Filter parameter variable name is 'array'.
     *     $array['weekly'] = array(
     *         'interval' => 604800,
     *           'display'  => __( 'Once Weekly' )
     *     );
     *
     *
     * @return array
     */
    public function getSchedules()
    {
        $schedules = array(
            'oneminute' => array('interval' => self::MINUTE, 'display' => 'Every minute'),
            'fiveminutes' => array('interval' => 5 * self::MINUTE, 'display' => 'Every five minutes'),
            'hourly' => array('interval' => self::HOUR, 'display' => 'Once Hourly'),
            'twicedaily' => array('interval' => 12 * self::HOUR, 'display' => 'Twice Daily'),
            'daily' => array('interval' => self::DAY, 'display' => 'Once Daily'),
        );
        /**
         * Filters the non-default cron schedules.
         *
         * @param array $new_schedules An array of non-default cron schedules. Default empty.
         */
        return array_merge($this->hookService->applyFilters('cron_schedules', array()), $schedules);
    }

    /**
     * Schedule a recurring event.
     *
     * Schedules a hook which will be executed on a specific interval, specified by you.
     * The action will fire off when the flockR scheduler is called, if the time is passed.
     *
     * Valid values for the recurrence are hourly, daily, and twicedaily. These can
     * be extended using the {@see 'cron_schedules'} filter in getSchedules().
     *
     * Use nextScheduled() to prevent duplicates
     *
     * @since 2.1.0
     *
     * @param int $timestamp Unix timestamp (UTC) for when to run the event.
     * @param string $recurrence How often the event should recur.
     * @param string $hook Action hook to execute when event is run.
     * @param array $args Optional. Arguments to pass to the hook's callback public function.
     * @return false|void False if the event does not get scheduled.
     */
    public function scheduleEvent($timestamp, $recurrence, $hook, $args = array())
    {
        // Make sure timestamp is a positive integer
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            return false;
        }

        $jobs = $this->getJobArray();
        $schedules = $this->getSchedules();

        if (!isset($schedules[$recurrence]))
            return false;

        $event = (object)array('hook' => $hook, 'timestamp' => $timestamp, 'schedule' => $recurrence, 'args' => $args, 'interval' => $schedules[$recurrence]['interval']);
        /** This filter is documented in wp-includes/cron.php */
        $event = $this->hookService->applyFilters('schedule_event', $event);

        // This event may have been cleared by a hook
        if (!$event)
            return false;

        $key = md5(serialize($event->args));

        $jobs[$event->timestamp][$event->hook][$key] = array('schedule' => $event->schedule, 'args' => $event->args, 'interval' => $event->interval);
        uksort($jobs, "strnatcasecmp");
        $this->setJobArray($jobs);
    }

    /**
     * Unschedule all events attached to the specified hook.
     *
     *
     * @param string $hook Action hook, the execution of which will be unscheduled.
     * @param array $args Optional. Arguments that were to be passed to the hook's callback public function.
     */
    public function clearScheduledHook($hook, $args = array())
    {

        // This logic duplicates nextScheduled()
        $crons = $this->getJobArray();
        if (empty($crons))
            return;

        $key = md5(serialize($args));
        foreach ($crons as $timestamp => $cron) {
            if (isset($cron[$hook][$key])) {
                $this->unscheduleEvent($timestamp, $hook, $args);
            }
        }
    }

    /**
     * Unschedule a previously scheduled event.
     *
     * The $timestamp and $hook parameters are required so that the event can be
     * identified.
     *
     * @since 2.1.0
     *
     * @param int $timestamp Unix timestamp (UTC) for when to run the event.
     * @param string $hook Action hook, the execution of which will be unscheduled.
     * @param array $args Arguments to pass to the hook's callback public function.
     * Although not passed to a callback public function, these arguments are used
     * to uniquely identify the scheduled event, so they should be the same
     * as those used when originally scheduling the event.
     * @return false|void False if the event does not get unscheduled.
     */
    public function unscheduleEvent($timestamp, $hook, $args = array())
    {
        // Make sure timestamp is a positive integer
        if (!is_numeric($timestamp) || $timestamp <= 0) {
            return false;
        }

        $crons = $this->getJobArray();
        $key = md5(serialize($args));
        unset($crons[$timestamp][$hook][$key]);
        if (empty($crons[$timestamp][$hook]))
            unset($crons[$timestamp][$hook]);
        if (empty($crons[$timestamp]))
            unset($crons[$timestamp]);
        $this->setJobArray($crons);
    }

//
// Private public functions
//

    /**
     * Run scheduled callbacks
     */
    public function run()
    {
        if (false === $jobs = $this->getJobArray())
            return;

        $gmt_time = microtime(true);
        $keys = array_keys($jobs);
        if (isset($keys[0]) && $keys[0] > $gmt_time)
            return;

        $schedules = $this->getSchedules();
        foreach ($jobs as $timestamp => $job) {
            if ($timestamp > $gmt_time) break;
            foreach ((array)$job as $hook => $subJobs) {
                foreach ($subJobs as $jobKey => $subJob) {
                    $this->hookService->doAction($hook, $subJob['args']);
                    $this->unscheduleEvent(time(), $hook, $subJob['args']);
                    if (false !== $subJob['schedule']) {
                        $this->rescheduleEvent(time(), $subJob['schedule'], $hook, $subJob['args']);
                    }
                }
            }
        }
        $jobs = $this->getJobArray();
    }

    /**
     * Retrieve the recurrence schedule for an event.
     *
     * @see getSchedules() for available schedules.
     *
     * @param string $hook Action hook to identify the event.
     * @param array $args Optional. Arguments passed to the event's callback public function.
     * @return string|false False, if no schedule. Schedule name on success.
     */
    public function getSchedule($hook, $args = array())
    {
        $crons = $this->getJobArray();
        $key = md5(serialize($args));
        if (empty($crons))
            return false;
        foreach ($crons as $timestamp => $cron) {
            if (isset($cron[$hook][$key]))
                return $cron[$hook][$key]['schedule'];
        }
        return false;
    }

    public function dumpJobs()
    {
        Debugger::dumpAndDie($this->getSchedules());
    }


}