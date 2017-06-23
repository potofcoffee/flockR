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

/************************************************************************************************************************
 *                                                                                                                      *
 * D B - F U N K T I O N E N                                                                                            *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Get the enum values of a db column
 *
 * @param string Table where the enum column is defined
 * @param string Column to get the enum values from
 * @return array All the enum values as array
 */
function db_get_enums($table, $col)
{
    global $DEBUG_db;

    if (isset($GLOBALS["kOOL"]["db_enum"][$table][$col])) {
        return $GLOBALS["kOOL"]["db_enum"][$table][$col];
    }

    $query = "SHOW COLUMNS FROM $table LIKE '$col'";
    if (DEBUG_SELECT) {
        $time_start = microtime(true);
    }
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_get_enums): ' . mysql_errno() . ': ' . mysql_error() . ', QUERY: ' . $query,
            E_USER_ERROR);
    }
    if (DEBUG_SELECT) {
        $DEBUG_db->queryCount++;
        $DEBUG_db->queries[] = array('time' => (microtime(true) - $time_start) * 1000, 'sql' => $query);
    }
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_row($result);
        $options = explode("','", preg_replace("/(enum|set)\('(.+?)'\)/", "\\2", $row[1]));
    }

    $GLOBALS["kOOL"]["db_enum"][$table][$col] = $options;

    return $options;
}//db_get_enums()


/**
 * Get the corresponding ll values for enum values of a db column
 *
 * @param string Table where the enum column is defined
 * @param string Column to get the enum values from
 * @return array All the localised enum values as array
 */
function db_get_enums_ll($table, $col)
{
    $ll = array();

    $options = db_get_enums($table, $col);
    foreach ($options as $o) {
        $ll_value = getLL($table . "_" . $col . "_" . $o);
        if (!$ll_value) {
            $ll_value = getLL('kota_' . $table . '_' . $col . '_' . $o);
        }
        $ll[$o] = $ll_value ? $ll_value : $o;
    }
    return $ll;
}//db_get_enums_ll()


/**
 * Get columns of a db table
 *
 * @param string Name of database
 * @param string Name of table
 * @param string A search string to only show columns that match this value
 * @return array Columns
 */
function db_get_columns($table, $field = "")
{
    global $DEBUG_db;

    $r = array();

    //Get value from global cache array if already set
    if ($field != "" && isset($GLOBALS["kOOL"]["db_columns"][$table][$field])) {
        return $GLOBALS["kOOL"]["db_columns"][$table][$field];
    }

    if ($field != "") {
        $like = "LIKE '$field'";
    } else {
        $like = "";
    }

    $query = "SHOW COLUMNS FROM $table $like";
    if (DEBUG_SELECT) {
        $time_start = microtime(true);
    }
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_get_columns): ' . mysql_errno() . ': ' . mysql_error() . ', QUERY: ' . $query,
            E_USER_ERROR);
    }
    if (DEBUG_SELECT) {
        $DEBUG_db->queryCount++;
        $DEBUG_db->queries[] = array('time' => (microtime(true) - $time_start) * 1000, 'sql' => $query);
    }
    while ($row = mysql_fetch_assoc($result)) {
        $r[] = $row;
    }

    //Store value in global cache array
    if ($field) {
        $GLOBALS["kOOL"]["db_columns"][$table][$field] = $r;
    }

    return $r;
}//db_get_columns()


/**
 * Number of entries in a db table
 *
 * @param string Table
 * @param string Column to count the different values for
 * @param string WHERE statement to add
 * @return int Number of different entries
 */
function db_get_count($table, $field = "id", $z_where = "")
{
    global $DEBUG_db;

    if ($field == '') {
        $field = 'id';
    }
    $query = "SELECT COUNT(`$field`) as count FROM `$table` " . (($z_where) ? " WHERE 1=1 $z_where" : "");
    if (DEBUG_SELECT) {
        $time_start = microtime(true);
    }
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_get_count): ' . mysql_errno() . ': ' . mysql_error() . ', QUERY: ' . $query,
            E_USER_ERROR);
    }
    if (DEBUG_SELECT) {
        $DEBUG_db->queryCount++;
        $DEBUG_db->queries[] = array('time' => (microtime(true) - $time_start) * 1000, 'sql' => $query);
    }
    $row = mysql_fetch_assoc($result);
    return $row["count"];
}//db_get_count()


/**
 * Get the next auto_increment value for a table
 *
 * @param string Table
 * @return int Next auto_increment value
 */
function db_get_next_id($table)
{
    $query = "SHOW TABLE STATUS LIKE '$table'";
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_get_next_id): ' . mysql_errno() . ': ' . mysql_error() . ', QUERY: ' . $query,
            E_USER_ERROR);
    }
    $row = mysql_fetch_assoc($result);
    return $row["Auto_increment"];
}


/**
 * Inserts data into a database table
 *
 * This should be used instead of issuing INSERT queries directly
 *
 * @param string Table where the data should be inserted
 * @param array Data array with the keys beeing the name of the db columns
 * @return int id of the newly inserted row
 */
function db_insert_data($table, $data)
{
    global $DEBUG_db;

    $columnsTemp = db_get_columns($table);
    $columns = array();
    $unset = array();

    foreach ($columnsTemp as $column) {
        $columns[] = $column['Field'];
    }

    foreach ($data as $field => $value) {
        if (!in_array($field, $columns)) {
            $unset[$field] = $data[$field];
            unset($data[$field]);
        }
    }

    if (sizeof($unset) > 0) {
        $logMessage = "unknown column error: \nunknown columns: ";
        foreach ($unset as $key => $value) {
            $logMessage .= $key . ': `' . $value . '`, ';
        }
        $logMessage .= "\ntable:" . $table . "\ndata: " . print_r($data, true);
        ko_log('db_error_update', $logMessage);
    }

    $query = "INSERT INTO `$table` ";
    //Alle Daten setzen
    foreach ($data as $key => $value) {
        $query1 .= "`$key`, ";
        if ((string)$value == "NULL") {
            $query2 .= "NULL, ";
        } else {
            $query2 .= "'" . mysql_real_escape_string(stripslashes($value)) . "', ";
        }
    }
    $query .= "(" . substr($query1, 0, -2) . ") VALUES (" . substr($query2, 0, -2) . ")";

    if (DEBUG_INSERT) {
        $time_start = microtime(true);
    }
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_insert_data): ' . mysql_errno() . ': ' . mysql_error() . ', QUERY: ' . $query,
            E_USER_ERROR);
    }
    if (DEBUG_INSERT) {
        $DEBUG_db->queryCount++;
        $DEBUG_db->queries[] = array('time' => (microtime(true) - $time_start) * 1000, 'sql' => $query);
    }
    return Mysql_Insert_ID();
}//db_insert_data()


/**
 * Update data in the database
 *
 * This should be used instead of issuing UPDATE queries directly
 *
 * @param string Table where the data should be stored
 * @param string WHERE statement that defines the rows to be updated
 * @param array Data array with the keys beeing the name of the db columns
 */
function db_update_data($table, $where, $data)
{
    global $DEBUG_db;

    $columnsTemp = db_get_columns($table);
    $columns = array();
    $unset = array();

    foreach ($columnsTemp as $column) {
        $columns[] = $column['Field'];
    }

    foreach ($data as $field => $value) {
        if (!in_array($field, $columns)) {
            $unset[$field] = $data[$field];
            unset($data[$field]);
        }
    }

    if (sizeof($unset) > 0) {
        $logMessage = "unknown column error: \nunknown columns: ";
        foreach ($unset as $key => $value) {
            $logMessage .= $key . ': `' . $value . '`, ';
        }
        $logMessage .= "\ntable:" . $table . "\nwhere: " . $where . "\ndata: " . print_r($data, true);
        ko_log('db_error_update', $logMessage);
    }

    $found = false;
    $query = "UPDATE $table SET ";
    //Alle Daten setzen
    foreach ($data as $key => $value) {
        if (!$key) {
            continue;
        }
        $found = true;
        if ((string)$value == "NULL") {
            $query .= "`$key` = NULL, ";
        } else {
            $query .= "`$key` = '" . mysql_real_escape_string(stripslashes($value)) . "', ";
        }
    }
    if (!$found) {
        return false;
    }
    $query = substr($query, 0, -2);

    //WHERE-Bedingung
    $query .= " $where ";

    if (DEBUG_UPDATE) {
        $time_start = microtime(true);
    }
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_update_data): ' . mysql_errno() . ': ' . mysql_error() . ", QUERY: $query",
            E_USER_ERROR);
    }
    if (DEBUG_UPDATE) {
        $DEBUG_db->queryCount++;
        $DEBUG_db->queries[] = array('time' => (microtime(true) - $time_start) * 1000, 'sql' => $query);
    }
}//db_update_data()


/**
 * Delete data from the database
 *
 * This should be used instead of issuing DELETE queries directly
 *
 * @param string Table where the data should be deleted
 * @param string WHERE statement that defines the rows to be deleted
 */
function db_delete_data($table, $where)
{
    global $DEBUG_db;

    $query = "DELETE FROM $table $where";
    if (DEBUG_DELETE) {
        $time_start = microtime(true);
    }
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_delete_data): ' . mysql_errno() . ': ' . mysql_error() . ", QUERY: $query",
            E_USER_ERROR);
    }
    if (DEBUG_DELETE) {
        $DEBUG_db->queryCount++;
        $DEBUG_db->queries[] = array('time' => (microtime(true) - $time_start) * 1000, 'sql' => $query);
    }
}//db_delete_data()


/**
 * Get data from the database
 *
 * This should be used instead of issuing SELECT queries directly
 *
 * @param string Table where the data should be selected from
 * @param string WHERE statement that defines the rows to be selected
 * @param string Comma seperated value with the columns to be selected. * for all of them
 * @param string ORDER BY statement
 * @param string LIMIT statement
 * @param boolean Returns a single entry if set, otherwise an array of entries is returned with their ids as keys
 */
function db_select_data($table, $where, $columns = "*", $order = "", $limit = "", $single = false, $no_index = false)
{
    global $DEBUG_db;

    if (ko_test(__FUNCTION__, func_get_args(), $testreturn) === true) {
        return $testreturn;
    }

    //Spalten
    if (is_array($columns)) {
        foreach ($columns as $col_i => $col) {
            $columns[$col_i] = "`" . $col . "`";
        }
        $columns = implode(",", $columns);
    }

    $query = "SELECT $columns FROM $table $where $order $limit";
    if (DEBUG_SELECT) {
        $time_start = microtime(true);
    }
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_select_data): ' . mysql_errno() . ': ' . mysql_error() . ' QUERY: ' . $query,
            E_USER_ERROR);
    }
    if (DEBUG_SELECT) {
        $DEBUG_db->queryCount++;
        $DEBUG_db->queries[] = array('time' => (microtime(true) - $time_start) * 1000, 'sql' => $query);
    }
    if (mysql_num_rows($result) == 0) {
        return;
    } else {
        if ($single && mysql_num_rows($result) == 1) {
            $return = mysql_fetch_assoc($result);
            return $return;
        } else {
            if ($no_index) {
                $index = '';
            } elseif (substr($columns, 0, 1) == '*' || false !== strpos($columns, 'AS id') || in_array('id',
                    explode(',', $columns)) || in_array('*', explode(',', $columns))
            ) {
                $index = 'id';
            } else {
                $cols = explode(",", $columns);
                $index = trim(str_replace("`", "", $cols[0]));
            }
            $return = array();
            while ($row = mysql_fetch_assoc($result)) {
                if ($index) {
                    $return[$row[$index]] = $row;
                } else {
                    $return[] = $row;
                }
            }
            return $return;
        }
    }
}//db_select_data()


/**
 * @param $query : the whole sql query in one string
 * @param string $index : supply a field name that should be used to index the return array
 * @return array: the query result as an array, NULL if there are no matching entries
 */
function db_query($query, $index = '')
{
    // TODO: support testing
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_select_data): ' . mysql_errno() . ': ' . mysql_error() . ' QUERY: ' . $query,
            E_USER_ERROR);
    }
    if (mysql_num_rows($result) == 0) {
        return;
    } else {
        $return = array();
        while ($row = mysql_fetch_assoc($result)) {
            if ($index) {
                $return[$row[$index]] = $row;
            } else {
                $return[] = $row;
            }
        }
        return $return;
    }
}//db_query()


/**
 * Get the value of a single column
 *
 * @param string Table to select data from
 * @param string WHERE statement
 * @param string Name of column to get value for
 * @return mixed Value from database
 */
function db_get_column($table, $where, $column, $split = " ")
{
    if (is_int($where)) {
        $where = "WHERE `id` = '$where'";
    } else {
        if (substr($where, 0, 5) == "WHERE") {
            $where = $where;
        } else {
            return false;
        }
    }

    //Allow several columns
    if (strstr($column, ",")) {
        $new = array();
        foreach (explode(",", $column) as $col) {
            $new[] = "`" . $col . "`";
        }
        $column = implode(",", $new);
    } else {
        $column = "`" . $column . "`";
    }

    $row = db_select_data($table, $where, $column, '', '', true);
    return implode($split, $row);
}//db_get_column()


/**
 * Execute a distinct select
 *
 * @param string Table to get the data from
 * @param string Column to get the values from
 * @param string ORDER BY statement
 * @return array All the different values
 */
function db_select_distinct($table, $col, $order_ = "", $where = "", $case_sensitive = false)
{
    global $DEBUG_db;

    $r = array();

    $order = $order_ ? $order_ : "ORDER BY $col ASC";

    if ($case_sensitive) {
        $query = "SELECT DISTINCT BINARY $col AS $col FROM $table $where $order";
    } else {
        $query = "SELECT DISTINCT $col FROM $table $where $order";
    }
    if (DEBUG_SELECT) {
        $time_start = microtime(true);
    }
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_select_distinct): ' . mysql_errno() . ': ' . mysql_error() . ', QUERY: ' . $query,
            E_USER_ERROR);
    }
    if (DEBUG_SELECT) {
        $DEBUG_db->queryCount++;
        $DEBUG_db->queries[] = array('time' => (microtime(true) - $time_start) * 1000, 'sql' => $query);
    }
    while ($row = mysql_fetch_assoc($result)) {
        $r[] = $row[ltrim(rtrim($col, '`'), '`')];
    }
    return $r;
}//db_select_distinct()


/**
 * Execute an alter table statement
 *
 * @param string Table to alter
 * @param string new value
 */
function db_alter_table($table, $change)
{
    $query = "ALTER TABLE `$table` $change";
    $result = mysql_query($query);
    if ($result === false) {
        trigger_error('DB ERROR (db_alter_table): ' . mysql_errno() . ': ' . mysql_error() . ', QUERY: ' . $query,
            E_USER_ERROR);
    }
}//db_alter_table()


/**
 * Parses an SQL string and updates the kOOL-database accordingly
 *
 * Used in /install/index.php and the plugins
 *
 * @param string SQL statement of the entry to be
 */
function db_import_sql($tobe)
{
    $create_code = 'CREATE TABLE `%s` (%s) ENGINE=MyISAM';
    $alter_code = 'ALTER TABLE `%s` CHANGE `%s` %s';
    $add_code = 'ALTER TABLE `%s` ADD %s';

    //find tables in actual db
    $is_tables = null;
    $result = mysql_query("SHOW TABLES");
    while ($row = mysql_fetch_row($result)) {
        $is_tables[] = $row[0];
    }

    $table = "";
    foreach (explode("\n", $tobe) as $line) {

        $line = trim($line);


        //don't allow any destructive commands
        if (strstr(strtoupper($line), "DROP ") || strstr(strtoupper($line), "TRUNCATE ") || strstr(strtoupper($line),
                "DELETE ")
        ) {
            continue;
        }


        //INSERT Statement
        if (strtoupper(substr($line, 0, 11)) == "INSERT INTO") {
            $do_sql[] = $line;
            continue;
        }


        //UPDATE Statement
        if (strtoupper(substr($line, 0, 7)) == "UPDATE ") {
            $do_sql[] = $line;
            continue;
        }


        //ALTER Statement
        if (strtoupper(substr($line, 0, 6)) == "ALTER ") {
            $do_sql[] = $line;
            continue;
        }


        //start of a create table statement
        if (strtoupper(substr($line, 0, 12)) == "CREATE TABLE") {
            //find table-name to be edited
            $temp = explode(" ", $line);
            $table = str_replace("`", "", trim($temp[2]));

            //find table in current db
            if (in_array($table, $is_tables)) {
                //table already exists - get table create definition
                $result = mysql_query("SHOW CREATE TABLE $table");
                $row = mysql_fetch_row($result);
                $is = $row[1];
                $new_table = false;
            } else {
                //create table
                $is = array();
                $new_table = true;
                $new_table_sql = "";
            }
            continue;
        } //end of create table
        else {
            if (substr($line, 0, 1) == ")") {
                if ($new_table_sql != "") {
                    $do_sql[] = sprintf($create_code, $table, $new_table_sql);
                }
                $new_table_sql = "";
                $new_table = false;
                $table = "";
                continue;
            } else {
                if (strstr($line, "KEY")) {
                    if ($new_table) {
                        $new_table_sql .= $line;
                    }
                    continue;
                } //empty or comment line
                else {
                    if (substr($line, 0, 1) == "#" || substr($line, 0, 1) == "-" || $line == "") {
                        continue;
                    } //line inside of a create table statement
                    else {
                        if (!$table) {
                            continue;
                        }

                        //find field name
                        $temp = explode(" ", $line);
                        $field = $temp[0];

                        //check for this field in db
                        $found = false;
                        foreach (explode("\n", $is) as $is_line) {
                            $is_line = trim($is_line);
                            $temp = explode(" ", $is_line);
                            $is_field = $temp[0];
                            if ($is_field == $field) {
                                //field found
                                $found = true;
                                //change if not the same
                                if ($is_line != $line) {
                                    $do_sql[] = sprintf($alter_code, $table, str_replace("`", "", $field), $line);
                                }
                            }
                        }//foreach(is as is_line)

                        //add field if not found in existing table definition
                        if (!$found) {
                            if ($new_table) {
                                $new_table_sql .= $line;
                            } else {
                                $do_sql[] = sprintf($add_code, $table, $line);
                            }
                        }

                    }
                }
            }
        }//if..else(line == CREATE TABLE)
    }//foreach(tobe as line)

    //print_d($do_sql);
    //return;

    foreach ($do_sql as $query) {
        if ($query) {
            if (substr($query, -1) == ",") {
                $query = substr($query, 0, -1);
            }
            $result = mysql_query($query);
            if ($result === false) {
                trigger_error('DB ERROR (db_import_sql): ' . mysql_errno() . ': ' . mysql_error(), E_USER_ERROR);
            }
        }
    }
}//db_import_sql()


/**
 * Performs a fuzzy search in a db table
 * The search is performed by concatenating the db field values to a single string
 * and calculating the Levenshtein difference.
 * The best match is returned if it is in the given limit.
 *
 * @param array Data array with column names as indizes
 * @param string DB table
 * @param int Maximum allowed errors per db column
 * @param boolean Set to false to ignore the case
 * @param int Levenshtein limit which must be reached to treat the best find as a valuable find
 *
 * return array IDs of db entries with the best levenshtein difference
 */
function ko_fuzzy_search($data, $table, $error = 1, $case = false, $lev_limit = "")
{
    //Get all DB columns
    foreach ($data as $col => $value) {
        $cols[] = $col;
    }
    $num_cols = sizeof($cols);

    //Concatenate data to search for
    $orig = implode("", $data);
    $orig_length = strlen($orig);
    //Calculate limit for string length
    $limit = $num_cols * $error + 1;

    //Calculate lev limit
    if (!$lev_limit) {
        $lev_limit = $limit;
        $lev_limit = $num_cols * $error - 1;
    }

    //Only get db entries matching the total string length (+/- limit) of the original data
    $query = "SELECT id, CONCAT(`" . implode("`, `", $cols) . "`) as teststring FROM `$table` ";
    $query .= "WHERE (CHAR_LENGTH(`" . implode("`)+CHAR_LENGTH(`", $cols) . "`)) > " . ($orig_length - $limit) . " ";
    $query .= "AND (CHAR_LENGTH(`" . implode("`)+CHAR_LENGTH(`", $cols) . "`)) < " . ($orig_length + $limit) . " ";
    $query .= "AND deleted = '0'";

    //Find the best matching db entry
    $result = mysql_query($query);
    $best = 100;
    while ($row = mysql_fetch_assoc($result)) {
        if ($case) {
            $lev = levenshtein($orig, $row["teststring"]);
        } else {
            $lev = levenshtein(strtolower($orig), strtolower($row["teststring"]));
        }
        if ($lev <= $best) {
            $found[$lev][] = $row["id"];
            $best = $lev;
        }
    }

    //Return ID if levenshtein difference is smaller than limit
    if ($best <= $lev_limit) {
        return $found[$best];
    } else {
        return false;
    }
}//ko_fuzzy_search()

