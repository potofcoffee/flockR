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
 *
 * HookService
 * Based on PHP Hooks Class
 * * The PHP Hooks Class is a fork of the WordPress filters hook system rolled in to a class to be ported
 * into any php based system
 * * This class is heavily based on the WordPress plugin API and most (if not all) of the code comes from there.
 *
 * @version 0.1.3
 * @copyright 2012 - 2014
 * @author Ohad Raz (email: admin@bainternet.info)
 * @link http://en.bainternet.info
 *
 */

namespace Peregrinus\Flockr\Core\Services;


use Peregrinus\Flockr\Core\AbstractClass;

class HookService extends AbstractClass
{

    protected static $instance = null;

    /**
     * $filters holds list of hooks
     * @access public
     * @since 0.1
     * @var array
     */
    var $filters = array();
    /**
     * $merged_filters
     * @var array
     */
    var $mergedFilters = array();
    /**
     * $actions
     * @var array
     */
    var $actions = array();
    /**
     * $currentFilter  holds the name of the current filter
     * @access public
     * @since 0.1
     * @var array
     */
    var $currentFilter = array();


    /**
     * @return HookService
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * __construct class constructor
     * @access public
     * @since 0.1
     */
    public function __construct($args = null)
    {
        $this->filters = array();
        $this->mergedFilters = array();
        $this->actions = array();
        $this->currentFilter = array();
    }
    /**
     * FILTERS
     */
    /**
     * addFilter Hooks a function or method to a specific filter action.
     * @access public
     * @since 0.1
     * @param string $tag The name of the filter to hook the $function_to_add to.
     * @param callback $function_to_add The name of the function to be called when the filter is applied.
     * @param int $priority optional. Used to specify the order in which the functions associated with a particular action are executed (default: 10). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
     * @param int $accepted_args optional. The number of arguments the function accept (default 1).
     * @return boolean true
     */
    public function addFilter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        $idx =  $this->filterBuildUniqueId($tag, $function_to_add, $priority);
        $this->filters[$tag][$priority][$idx] = array('function' => $function_to_add, 'accepted_args' => $accepted_args);
        unset( $this->mergedFilters[ $tag ] );
        return true;
    }
    /**
     * removeFilter Removes a function from a specified filter hook.
     * @access public
     * @since 0.1
     * @param string $tag The filter hook to which the function to be removed is hooked.
     * @param callback $function_to_remove The name of the function which should be removed.
     * @param int $priority optional. The priority of the function (default: 10).
     * @param int $accepted_args optional. The number of arguments the function accepts (default: 1).
     * @return boolean Whether the function existed before it was removed.
     */
    public function removeFilter( $tag, $function_to_remove, $priority = 10 ) {
        $function_to_remove = $this->filterBuildUniqueId($tag, $function_to_remove, $priority);
        $r = isset($this->filters[$tag][$priority][$function_to_remove]);
        if ( true === $r) {
            unset($this->filters[$tag][$priority][$function_to_remove]);
            if ( empty($this->filters[$tag][$priority]) )
                unset($this->filters[$tag][$priority]);
            unset($this->mergedFilters[$tag]);
        }
        return $r;
    }
    /**
     * removeAllFilters Remove all of the hooks from a filter.
     * @access public
     * @since 0.1
     * @param string $tag The filter to remove hooks from.
     * @param int $priority The priority number to remove.
     * @return bool True when finished.
     */
    public function removeAllFilters($tag, $priority = false) {
        if( isset($this->filters[$tag]) ) {
            if( false !== $priority && isset($this->filters[$tag][$priority]) )
                unset($this->filters[$tag][$priority]);
            else
                unset($this->filters[$tag]);
        }
        if( isset($this->mergedFilters[$tag]) )
            unset($this->mergedFilters[$tag]);
        return true;
    }
    /**
     * hasFilter  Check if any filter has been registered for a hook.
     * @access public
     * @since 0.1
     * @param string $tag The name of the filter hook.
     * @param callback $function_to_check optional.
     * @return mixed If $function_to_check is omitted, returns boolean for whether the hook has anything registered.
     *   When checking a specific function, the priority of that hook is returned, or false if the function is not attached.
     *   When using the $function_to_check argument, this function may return a non-boolean value that evaluates to false
     *   (e.g.) 0, so use the === operator for testing the return value.
     */
    public function hasFilter($tag, $function_to_check = false) {
        $has = !empty($this->filters[$tag]);
        if ( false === $function_to_check || false == $has )
            return $has;
        if ( !$idx = $this->filterBuildUniqueId($tag, $function_to_check, false) )
            return false;
        foreach ( (array) array_keys($this->filters[$tag]) as $priority ) {
            if ( isset($this->filters[$tag][$priority][$idx]) )
                return $priority;
        }
        return false;
    }
    /**
     * applyFilters Call the functions added to a filter hook.
     * @access public
     * @since 0.1
     * @param string $tag The name of the filter hook.
     * @param mixed $value The value on which the filters hooked to <tt>$tag</tt> are applied on.
     * @param mixed $var,... Additional variables passed to the functions hooked to <tt>$tag</tt>.
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function applyFilters($tag, $value) {
        $args = array();
        // Do 'all' actions first
        if ( isset($this->filters['all']) ) {
            $this->currentFilter[] = $tag;
            $args = func_get_args();
            $this->_callAllHooks($args);
        }
        if ( !isset($this->filters[$tag]) ) {
            if ( isset($this->filters['all']) )
                array_pop($this->currentFilter);
            return $value;
        }
        if ( !isset($this->filters['all']) )
            $this->currentFilter[] = $tag;
        // Sort
        if ( !isset( $this->mergedFilters[ $tag ] ) ) {
            ksort($this->filters[$tag]);
            $this->mergedFilters[ $tag ] = true;
        }
        reset( $this->filters[ $tag ] );
        if ( empty($args) )
            $args = func_get_args();
        do {
            foreach( (array) current($this->filters[$tag]) as $the_ )
                if ( !is_null($the_['function']) ){
                    $args[1] = $value;
                    $value = call_user_func_array($the_['function'], array_slice($args, 1, (int) $the_['accepted_args']));
                }
        } while ( next($this->filters[$tag]) !== false );
        array_pop( $this->currentFilter );
        return $value;
    }
    /**
     * apply_filters_ref_array Execute functions hooked on a specific filter hook, specifying arguments in an array.
     * @access public
     * @since 0.1
     * @param string $tag The name of the filter hook.
     * @param array $args The arguments supplied to the functions hooked to <tt>$tag</tt>
     * @return mixed The filtered value after all hooked functions are applied to it.
     */
    public function applyFiltersRefArray($tag, $args) {
        // Do 'all' actions first
        if ( isset($this->filters['all']) ) {
            $this->currentFilter[] = $tag;
            $all_args = func_get_args();
            $this->_call_all_hook($all_args);
        }
        if ( !isset($this->filters[$tag]) ) {
            if ( isset($this->filters['all']) )
                array_pop($this->currentFilter);
            return $args[0];
        }
        if ( !isset($this->filters['all']) )
            $this->currentFilter[] = $tag;
        // Sort
        if ( !isset( $this->mergedFilters[ $tag ] ) ) {
            ksort($this->filters[$tag]);
            $this->mergedFilters[ $tag ] = true;
        }
        reset( $this->filters[ $tag ] );
        do {
            foreach( (array) current($this->filters[$tag]) as $the_ )
                if ( !is_null($the_['function']) )
                    $args[0] = call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));
        } while ( next($this->filters[$tag]) !== false );
        array_pop( $this->currentFilter );
        return $args[0];
    }
    /**
     * ACTIONS
     */
    /**
     * addAction Hooks a function on to a specific action.
     * @access public
     * @since 0.1
     * @param string $tag The name of the action to which the $function_to_add is hooked.
     * @param callback $function_to_add The name of the function you wish to be called.
     * @param int $priority optional. Used to specify the order in which the functions associated with a particular action are executed (default: 10). Lower numbers correspond with earlier execution, and functions with the same priority are executed in the order in which they were added to the action.
     * @param int $accepted_args optional. The number of arguments the function accept (default 1).
     */
    public function addAction($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        return $this->addFilter($tag, $function_to_add, $priority, $accepted_args);
    }
    /**
     * hasAction Check if any action has been registered for a hook.
     * @access public
     * @since 0.1
     * @param string $tag The name of the action hook.
     * @param callback $function_to_check optional.
     * @return mixed If $function_to_check is omitted, returns boolean for whether the hook has anything registered.
     *   When checking a specific function, the priority of that hook is returned, or false if the function is not attached.
     *   When using the $function_to_check argument, this function may return a non-boolean value that evaluates to false
     *   (e.g.) 0, so use the === operator for testing the return value.
     */
    public function hasAction($tag, $function_to_check = false) {
        return $this->hasFilter($tag, $function_to_check);
    }
    /**
     * removeAction Removes a function from a specified action hook.
     * @access public
     * @since 0.1
     * @param string $tag The action hook to which the function to be removed is hooked.
     * @param callback $function_to_remove The name of the function which should be removed.
     * @param int $priority optional The priority of the function (default: 10).
     * @return boolean Whether the function is removed.
     */
    public function removeAction( $tag, $function_to_remove, $priority = 10 ) {
        return $this->removeFilter( $tag, $function_to_remove, $priority );
    }
    /**
     * removeAllActions Remove all of the hooks from an action.
     * @access public
     * @since 0.1
     * @param string $tag The action to remove hooks from.
     * @param int $priority The priority number to remove them from.
     * @return bool True when finished.
     */
    public function removeAllActions($tag, $priority = false) {
        return $this->removeAllFilters($tag, $priority);
    }
    /**
     * doAction Execute functions hooked on a specific action hook.
     * @access public
     * @since 0.1
     * @param string $tag The name of the action to be executed.
     * @param mixed $arg,... Optional additional arguments which are passed on to the functions hooked to the action.
     * @return null Will return null if $tag does not exist in $filter array
     */
    public function doAction($tag, $arg = '') {
        if ( ! isset($this->actions) )
            $this->actions = array();
        if ( ! isset($this->actions[$tag]) )
            $this->actions[$tag] = 1;
        else
            ++$this->actions[$tag];
        // Do 'all' actions first
        if ( isset($this->filters['all']) ) {
            $this->currentFilter[] = $tag;
            $all_args = func_get_args();
            $this->_call_all_hook($all_args);
        }
        if ( !isset($this->filters[$tag]) ) {
            if ( isset($this->filters['all']) )
                array_pop($this->currentFilter);
            return;
        }
        if ( !isset($this->filters['all']) )
            $this->currentFilter[] = $tag;
        $args = array();
        if ( is_array($arg) && 1 == count($arg) && isset($arg[0]) && is_object($arg[0]) ) // array(&$this)
            $args[] =& $arg[0];
        else
            $args[] = $arg;
        for ( $a = 2; $a < func_num_args(); $a++ )
            $args[] = func_get_arg($a);
        // Sort
        if ( !isset( $this->mergedFilters[ $tag ] ) ) {
            ksort($this->filters[$tag]);
            $this->mergedFilters[ $tag ] = true;
        }
        reset( $this->filters[ $tag ] );
        do {
            foreach ( (array) current($this->filters[$tag]) as $the_ )
                if ( !is_null($the_['function']) )
                    call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));
        } while ( next($this->filters[$tag]) !== false );
        array_pop($this->currentFilter);
    }
    /**
     * doActionRefArray Execute functions hooked on a specific action hook, specifying arguments in an array.
     * @access public
     * @since 0.1
     * @param string $tag The name of the action to be executed.
     * @param array $args The arguments supplied to the functions hooked to <tt>$tag</tt>
     * @return null Will return null if $tag does not exist in $filter array
     */
    public function doActionRefArray($tag, $args) {

        if ( ! isset($this->actions) )
            $this->actions = array();
        if ( ! isset($this->actions[$tag]) )
            $this->actions[$tag] = 1;
        else
            ++$this->actions[$tag];
        // Do 'all' actions first
        if ( isset($this->filters['all']) ) {
            $this->currentFilter[] = $tag;
            $all_args = func_get_args();
            $this->_call_all_hook($all_args);
        }
        if ( !isset($this->filters[$tag]) ) {
            if ( isset($this->filters['all']) )
                array_pop($this->currentFilter);
            return;
        }
        if ( !isset($this->filters['all']) )
            $this->currentFilter[] = $tag;
        // Sort
        if ( !isset( $merged_filters[ $tag ] ) ) {
            ksort($this->filters[$tag]);
            $merged_filters[ $tag ] = true;
        }
        reset( $this->filters[ $tag ] );
        do {
            foreach( (array) current($this->filters[$tag]) as $the_ )
                if ( !is_null($the_['function']) )
                    call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));
        } while ( next($this->filters[$tag]) !== false );
        array_pop($this->currentFilter);
    }
    /**
     * didAction Retrieve the number of times an action is fired.
     * @access public
     * @since 0.1
     * @param string $tag The name of the action hook.
     * @return int The number of times action hook <tt>$tag</tt> is fired
     */
    public function didAction($tag) {
        if ( ! isset( $this->actions ) || ! isset( $this->actions[$tag] ) )
            return 0;
        return $this->actions[$tag];
    }
    /**
     * HELPERS
     */
    /**
     * currentFilter Retrieve the name of the current filter or action.
     * @access public
     * @since 0.1
     * @return string Hook name of the current filter or action.
     */
    public function currentFilter() {
        return end( $this->currentFilter );
    }
    /**
     * Retrieve the name of the current action.
     *
     * @since 0.1.2
     *
     * @uses currentFilter()
     *
     * @return string Hook name of the current action.
     */
    function currentAction() {
        return $this->currentFilter();
    }

    /**
     * Retrieve the name of a filter currently being processed.
     *
     * The function currentFilter() only returns the most recent filter or action
     * being executed. didAction() returns true once the action is initially
     * processed. This function allows detection for any filter currently being
     * executed (despite not being the most recent filter to fire, in the case of
     * hooks called from hook callbacks) to be verified.
     *
     * @since 0.1.2
     *
     * @see currentFilter()
     * @see didAction()
     * @global array $wp_currentFilter Current filter.
     *
     * @param null|string $filter Optional. Filter to check. Defaults to null, which
     *                            checks if any filter is currently being run.
     * @return bool Whether the filter is currently in the stack
     */
    function doingFilter( $filter = null ) {
        if ( null === $filter ) {
            return ! empty( $this->currentFilter );
        }
        return in_array( $filter, $this->currentFilter );
    }

    /**
     * Retrieve the name of an action currently being processed.
     *
     * @since 0.1.2
     *
     * @uses doingFilter()
     *
     * @param string|null $action Optional. Action to check. Defaults to null, which checks
     *                            if any action is currently being run.
     * @return bool Whether the action is currently in the stack.
     */
    function doingAction( $action = null ) {
        return $this->doingFilter( $action );
    }

    /**
     * filterBuildUniqueId Build Unique ID for storage and retrieval.
     * @param string $tag Used in counting how many hooks were applied
     * @param callback $function Used for creating unique id
     * @param int|bool $priority Used in counting how many hooks were applied. If === false and $function is an object reference, we return the unique id only if it already has one, false otherwise.
     * @return string|bool Unique ID for usage as array key or false if $priority === false and $function is an object reference, and it does not already have a unique id.
     */
    private function filterBuildUniqueId($tag, $function, $priority) {
        static $filter_id_count = 0;
        if ( is_string($function) )
            return $function;
        if ( is_object($function) ) {
            // Closures are currently implemented as objects
            $function = array( $function, '' );
        } else {
            $function = (array) $function;
        }
        if (is_object($function[0]) ) {
            // Object Class Calling
            if ( function_exists('spl_object_hash') ) {
                return spl_object_hash($function[0]) . $function[1];
            } else {
                $obj_idx = get_class($function[0]).$function[1];
                if ( !isset($function[0]->filter_id) ) {
                    if ( false === $priority )
                        return false;
                    $obj_idx .= isset($this->filters[$tag][$priority]) ? count((array)$this->filters[$tag][$priority]) : $filter_id_count;
                    $function[0]->filter_id = $filter_id_count;
                    ++$filter_id_count;
                } else {
                    $obj_idx .= $function[0]->filter_id;
                }
                return $obj_idx;
            }
        } else if ( is_string($function[0]) ) {
            // Static Calling
            return $function[0].$function[1];
        }
    }
    /**
     * callAllHooks
     * @access public
     * @since 0.1
     * @param  (array) $args [description]
     */
    public function callAllHooks($args) {
        reset( $this->filters['all'] );
        do {
            foreach( (array) current($this->filters['all']) as $the_ )
                if ( !is_null($the_['function']) )
                    call_user_func_array($the_['function'], $args);
        } while ( next($this->filters['all']) !== false );
    }

}