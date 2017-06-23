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
 * Import-FUNKTIONEN                                                                                                    *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Parses a vCard file (.vcf) and assigns the values to an array to be imported into ko_leute
 */
function ko_parse_vcf($content)
{
    $data = array();

    foreach ($content as $line) {
        //Check for encodings
        $quoted = strstr($line, ";ENCODING=QUOTED-PRINTABLE");
        $utf8 = strstr($line, ";CHARSET=UTF-8");

        $line = preg_replace("/;ENCODING=QUOTED-PRINTABLE/", "", $line);
        $line = preg_replace("/;CHARSET=ISO-\d{4}-\d{1,2}/", "", $line);
        $line = preg_replace("/;CHARSET=UTF-8/", "", $line);

        //Find prop and value
        $temp = explode(":", $line);
        $prop = strtoupper($temp[0]);
        unset($temp[0]);
        $value = trim(implode(":", $temp));
        if ($quoted) {
            $value = quoted_printable_decode($value);
        }
        if ($utf8) {
            $value = utf8_decode($value);
        }

        //Begin of a vCard
        if ($prop == "BEGIN" && $value == "VCARD") {
            $new_data = array();
        } //Name
        else {
            if ($prop == "N") {
                list($new_data["nachname"], $new_data["vorname"], $temp1, $new_data["anrede"], $temp2) = explode(";",
                    $value);
            } //address
            else {
                if (substr($prop, 0, 3) == "ADR") {
                    $values = explode(";", $value);
                    list($temp1, $new_data["adresse_zusatz"], $new_data["adresse"], $new_data["ort"], $temp2, $new_data["plz"], $new_data["land"]) = $values;
                } //Phone
                else {
                    if (substr($prop, 0, 3) == "TEL") {
                        if (strstr($prop, "HOME")) {
                            $new_data["telp"] = $value;
                        } else {
                            if (strstr($prop, "WORK")) {
                                $new_data["telg"] = $value;
                            } else {
                                if (strstr($prop, "CELL")) {
                                    $new_data["natel"] = $value;
                                } else {
                                    if (strstr($prop, "FAX")) {
                                        $new_data["fax"] = $value;
                                    }
                                }
                            }
                        }
                    } //email
                    else {
                        if (substr($prop, 0, 5) == "EMAIL") {
                            $new_data["email"] = $value;
                        } //Birthdate
                        else {
                            if (substr($prop, 0, 4) == "BDAY") {
                                $new_data["geburtsdatum"] = substr($value, 0, 10);
                            } //note
                            else {
                                if (substr($prop, 0, 4) == "NOTE") {
                                    $new_data["memo1"] = $value;
                                } //url
                                else {
                                    if (substr($prop, 0, 3) == "URL") {
                                        $new_data["web"] = $value;
                                    } //End of a vCard
                                    else {
                                        if ($prop == "END" && $value == "VCARD") {
                                            $data[] = $new_data;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    //prepare for mysql
    foreach ($data as $key => $value) {
        foreach ($value as $k => $v) {
            $return[$key][$k] = mysql_real_escape_string($v);
        }
    }
    return $return;
}//ko_parse_vcf()


/**
 * Runs some checks, before a csv import can be performed
 */
function ko_parse_csv($content, $options, $test = false)
{
    $separator = $options["separator"];
    $content_separator = $options["content_separator"];
    $first_line = $options["first_line"];
    $dbcols = $options["dbcols"];
    $num_cols = sizeof($dbcols);

    //find date-cols
    $date_cols = $enum_cols = array();
    $table_cols = db_get_columns("ko_leute");
    foreach ($table_cols as $col) {
        if ($col["Type"] == "date") {
            $date_cols[] = $col["Field"];
        }
        if (substr($col["Type"], 0, 4) == "enum") {
            $enum_cols[] = $col["Field"];
        }
    }


    $error = 0;
    $data = array();
    $first = true;
    foreach ($content as $line) {
        $line = trim($line);

        //Encoding
        if ($options['file_encoding'] == 'macintosh') {
            $line = iconv('macintosh', 'ISO-8859-1', $line);
        } else {
            if ($options['file_encoding'] == 'utf-8') {
                $line = utf8_decode($line);
            }
        }

        //ignore first line if set
        if ($first && $first_line) {
            $first = false;
        } else {
            $first = false;

            //get values from one line
            $parts = ko_get_csv_values($line, $separator, $content_separator);

            if ($test) {
                if (sizeof($parts) < $num_cols) {
                    $error = 1;
                }
                if (sizeof($parts) > $num_cols) {
                    $error = 2;
                }
            } else {
                $new_data = array();
                foreach ($dbcols as $col) {
                    $new_data[$col] = mysql_real_escape_string(array_shift($parts));
                    //create sql-date
                    if (in_array($col, $date_cols)) {
                        $new_data[$col] = sql_datum($new_data[$col]);
                    }
                    //Check for LL values in enum fields
                    if (in_array($col, $enum_cols)) {
                        $enums = db_get_enums("ko_leute", $col);
                        //If not in English then try to find it in the ll version
                        if (!in_array($new_data[$col], $enums)) {
                            $enums_ll = db_get_enums_ll("ko_leute", $col);
                            foreach ($enums_ll as $key => $value) {
                                if (strtolower($value) == strtolower($new_data[$col])) {
                                    $new_data[$col] = $key;
                                }
                            }
                        }//if(!in_array(enums))
                    }//if(enum_cols)
                }
                $data[] = $new_data;
            }//if..else(test)
        }//if..else(first)
    }//foreach(content as line)

    if ($test) {
        if ($error) {
            return false;
        } else {
            return true;
        }
    } else {
        return $data;
    }
}//ko_parse_csv()


/**
 * parses a csv line and returns the values as array
 * recognises values separated by sep and embraced between csep
 * from usercomments on php.net for function split()
 */
function ko_get_csv_values($string, $sep = ",", $csep = "")
{
    //no content separator, so just explode it
    if (!$csep) {
        $elements = explode($sep, $string);
    } else {
        $elements = explode($sep, $string);
        for ($i = 0; $i < count($elements); $i++) {
            $nquotes = substr_count($elements[$i], '"');
            if ($nquotes % 2 == 1) {
                for ($j = $i + 1; $j < count($elements); $j++) {
                    if (substr_count($elements[$j], $csep) > 0) {
                        // Put the quoted string's pieces back together again
                        array_splice($elements, $i, $j - $i + 1,
                            implode($sep, array_slice($elements, $i, $j - $i + 1)));
                        break;
                    }
                }
            }
            if ($nquotes > 0) {
                // Remove first and last quotes, then merge pairs of quotes
                $qstr =& $elements[$i];
                $qstr = substr_replace($qstr, '', strpos($qstr, $csep), 1);
                $qstr = substr_replace($qstr, '', strrpos($qstr, $csep), 1);
                $qstr = str_replace('""', '"', $qstr);
            }
        }
    }
    return $elements;
}//ko_get_csv_values()


/**
 * Return HTML img tag with the thumbnail for the given image
 * @param $img Name of image in folder my_images
 * @param $max_dim Size in pixels of bigger dimension to be used for thumbnail
 */
function ko_pic_get_thumbnail($img, $max_dim, $imgtag = true)
{
    global $BASE_PATH, $ko_path;

    //Check for valid image
    $img = basename($img);
    if (trim($img) == '') {
        return '';
    }
    if (!is_file($BASE_PATH . 'my_images/' . $img)) {
        return '';
    }

    clearstatcache();

    //Get modification time for the image
    $file = $BASE_PATH . 'my_images/' . $img;
    $ext = strtolower(substr($img, strrpos($img, '.')));
    $filemtime = filemtime($file);

    //Create filename for cache image (using filename and file's modification time)
    $cache_filename = md5($img . $filemtime) . '_' . $max_dim . '.png';
    $cache_file = $BASE_PATH . 'my_images/cache/' . $cache_filename;
    $cachemtime = filemtime($cache_file);

    //Create new thumbnail if none stored yet
    if (!$cachemtime || $filemtime > $cachemtime) {
        //Create new thumbnail
        $scaled = ko_pic_scale_image($file, $max_dim);
        if ($scaled === false) {
            return '';
        }
    }

    if ($imgtag) {
        $r = '<img src="' . $ko_path . 'my_images/cache/' . $cache_filename . '" />';
    } else {
        $r = $ko_path . 'my_images/cache/' . $cache_filename;
    }
    return $r;
}//ko_pic_get_preview()


/**
 * Return HTML img tag with tooltip effect showing a thumbnail of the given image
 * @param $thumb Size of thumbnail to be used. Set to 0 (default) to only display icon
 * @param $img Name of image in folder my_images
 * @param $dim Size in pixels of the tooltip (defaults to 200px)
 * @param $pv Vertical position for tooltip (t, m, b)
 * @param $ph Horizontal position for tooltip (l, c, r)
 * @param $link boolean Link to original image
 */
function ko_pic_get_tooltip($img, $thumb = 0, $dim = 200, $pv = 't', $ph = 'c', $link = false)
{
    global $ko_path;

    $ttimg = ko_pic_get_thumbnail($img, $dim);
    if ($ttimg == '') {
        return '';
    }

    if ($thumb > 0) {
        $thumbimg = ko_pic_get_thumbnail($img, $thumb, false);
    } else {
        $thumbimg = $ko_path . 'images/image.png';
    }

    $r = '<img src="' . $thumbimg . '" border="0" onmouseover="tooltip.show(\'' . ko_html($ttimg) . '\', \'\', \'' . $pv . '\', \'' . $ph . '\');" onmouseout="tooltip.hide();" />';

    if ($link) {
        $r = '<a href="' . $img . '" target="_blank">' . $r . '</a>';
    }

    return $r;
}//ko_pic_get_tooltip()


/**
 * Creates a scaled down image of the given file and stores it in my_images/cache
 * @param $file Absolute path to image file to be scaled
 * @param $max_dim Size in pixels for the scaled down image
 */
function ko_pic_scale_image($file, $max_dim)
{
    global $BASE_PATH;

    //detect type and process accordinally
    $size = getimagesize($file);
    switch ($size['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file);
            break;
        default:
            $image = false;
    }
    if ($image === false) {
        return false;
    }

    //Get name for cached file
    $cache_filename = md5(basename($file) . filemtime($file)) . '_' . $max_dim . '.png';
    $cache_file = $BASE_PATH . 'my_images/cache/' . $cache_filename;

    //Get current image size
    $w = imagesx($image);
    $h = imagesy($image);
    //Get new height
    if ($w > $h) {
        $thumb_w = $max_dim;
        $thumb_h = floor($thumb_w * ($h / $w));
    } else {
        $thumb_h = $max_dim;
        $thumb_w = floor($thumb_h * ($w / $h));
    }
    //Create thumb
    $thumb = ImageCreateTrueColor($thumb_w, $thumb_h);
    imagecopyResampled($thumb, $image, 0, 0, 0, 0, $thumb_w, $thumb_h, $w, $h);
    imagepng($thumb, $cache_file);
    //Clean up
    imagedestroy($image);
    imagedestroy($thumb);

    //Clean up image cache by deleting not used images
    ko_pic_cleanup_cache();

    return true;
}//ko_pic_scale_image()


/**
 * Remove unused images from my_images/cache
 */
function ko_pic_cleanup_cache()
{
    global $BASE_PATH;

    clearstatcache();

    //Get all images in my_images and calculate their md5 values for comparison
    $hashes = array();
    if ($dh = opendir($BASE_PATH . 'my_images/')) {
        while (($file = readdir($dh)) !== false) {
            if (!in_array(strtolower(substr($file, -4)), array('.gif', '.jpg', 'jpeg', '.png'))) {
                continue;
            }
            $hashes[] = md5($file . filemtime($BASE_PATH . 'my_images/' . $file));
        }
    }
    @closedir($dh);

    //Check all cache files for corresponding hash from above
    if ($dh = opendir($BASE_PATH . 'my_images/cache/')) {
        while (($file = readdir($dh)) !== false) {
            if (!in_array(strtolower(substr($file, -4)), array('.gif', '.jpg', 'jpeg', '.png'))) {
                continue;
            }
            $hash = substr($file, 0, strpos($file, '_'));
            if (!in_array($hash, $hashes)) {
                unlink($BASE_PATH . 'my_images/cache/' . $file);
            }
        }
    }
    @closedir($dh);
}//ko_pic_cleanup_cache()


/**
 * Plugin function to connect to a TYPO3 database
 * Connetion details for TYPO3 db are taken from settings which can be changed in the tools module
 */
function plugin_connect_TYPO3()
{
    global $mysql_server, $BASE_PATH;

    if (!ko_get_setting('typo3_db')) {
        return false;
    }

    //Get password and decrypt
    $pwd_enc = ko_get_setting('typo3_pwd');
    include_once($BASE_PATH . 'inc/class.mcrypt.php');
    $crypt = new mcrypt('aes');
    $crypt->setKey(KOOL_ENCRYPTION_KEY);
    $pwd = trim($crypt->decrypt($pwd_enc));

    if ($mysql_server != ko_get_setting('typo3_host')) {
        mysql_connect(ko_get_setting('typo3_host'), ko_get_setting('typo3_user'), $pwd);
    }

    if (!mysql_select_db(ko_get_setting('typo3_db'))) {
        ko_die('Could not establish connection to the TYPO3 database: ' . mysql_error());
    }
}//plugin_connect_TYPO3()


/**
 * Plugin function to connect to the current kOOL database again (called after plugin_connect_TYPO3())
 */
function plugin_connect_kOOL()
{
    global $mysql_db, $mysql_server, $mysql_user, $mysql_pass;

    if ($mysql_server != ko_get_setting('typo3_host') || $mysql_user != ko_get_setting('typo3_user')) {
        mysql_connect($mysql_server, $mysql_user, $mysql_pass);
    }

    mysql_select_db($mysql_db);
}//plugin_connect_kOOL()


function ko_get_ical_link($url, $text)
{
    global $ko_path;

    $r = '';

    $r .= '<a href="javascript:ko_image_popup(\'' . $ko_path . 'inc/qrcode.php?s=' . base64_encode($url) . '&h=' . md5(KOOL_ENCRYPTION_KEY . $url) . '&size=5\');"><img src="' . $ko_path . 'images/icon_qrcode.png" title="' . getLL('ical_qrcode') . '" /></a>';
    $r .= '&nbsp;&nbsp;';
    $r .= '<a href="' . $url . '" onclick="return false;">' . $text . '</a>';

    return $r;
}//ko_get_ical_link()


/**
 * Creates ICS string for reservations and returns the string
 *
 * @param $res array DB array from ko_reservation
 * @param $forceDetails boolean Set to true to always have details included normally only visible to logged in users
 * @return string ICS feed as string
 */
function ko_get_ics_for_res($res, $forceDetails = false)
{
    global $BASE_URL;

    $mapping = array(';' => '\;', ',' => '\,', "\n" => "\n ", "\r" => '');
    define('CRLF', chr(10));

    //build ical file in a string
    $ical = "BEGIN:VCALENDAR" . CRLF;
    $ical .= "VERSION:2.0" . CRLF;
    $ical .= "CALSCALE:GREGORIAN" . CRLF;
    $ical .= "METHOD:PUBLISH" . CRLF;
    $ical .= "PRODID:-//" . str_replace("/", "", $HTML_TITLE) . "//www.churchtool.org//DE" . CRLF;
    foreach ($res as $r) {
        //build ics string
        $ical .= "BEGIN:VEVENT" . CRLF;
        if ($r['cdate'] != '0000-00-00 00:00:00') {
            $ical .= "CREATED:" . strftime("%Y%m%dT%H%M%S", strtotime($r["cdate"])) . CRLF;
        }
        if ($r['last_change'] != '0000-00-00 00:00:00') {
            $ical .= "LAST-MODIFIED:" . strftime("%Y%m%dT%H%M%S", strtotime($r["last_change"])) . CRLF;
        }
        $ical .= "DTSTAMP:" . strftime("%Y%m%dT%H%M%S", time()) . CRLF;
        $base_url = $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $BASE_URL;
        $ical .= 'UID:r' . $r['id'] . '@' . $base_url . CRLF;
        if (intval(str_replace(':', '', $r['startzeit'])) >= 240000) {
            $r['startzeit'] = '23:59:00';
        }
        if (intval(str_replace(':', '', $r['endzeit'])) >= 240000) {
            $r['endzeit'] = '23:59:00';
        }
        if ($r["startzeit"] == "00:00:00" && $r["endzeit"] == "00:00:00") {  //daily event
            $ical .= "DTSTART;VALUE=DATE:" . strftime("%Y%m%d", strtotime($r["startdatum"])) . CRLF;
            $ical .= "DTEND;VALUE=DATE:" . strftime("%Y%m%d",
                    strtotime(add2date($r["enddatum"], "tag", 1, true))) . CRLF;
        } else {
            if ($r['startzeit'] != '00:00:00' && $r['endzeit'] == '00:00:00') {  //No end time given so set it to midnight
                $ical .= 'DTSTART:' . date_convert_timezone(($r['startdatum'] . ' ' . $r['startzeit']), 'UTC') . CRLF;
                $ical .= 'DTEND:' . date_convert_timezone(($r['enddatum'] . ' 23:59:00'), 'UTC') . CRLF;
            } else {
                $ical .= 'DTSTART:' . date_convert_timezone(($r['startdatum'] . ' ' . $r['startzeit']), 'UTC') . CRLF;
                $ical .= 'DTEND:' . date_convert_timezone(($r['enddatum'] . ' ' . $r['endzeit']), 'UTC') . CRLF;
            }
        }
        $ical .= 'SUMMARY:' . strtr(trim($r['item_name']), $mapping) . ($r['zweck'] ? (': ' . strtr(trim($r['zweck']),
                    $mapping)) : '') . CRLF;
        $desc = '';
        if ($_SESSION["ses_username"] != "ko_guest" || $forceDetails === true) {
            $desc .= $r["name"] . ($r["email"] ? ", " . $r["email"] : "") . ($r["telefon"] ? ", " . $r["telefon"] : "") . CRLF;
            $desc .= $r['comments'] . CRLF;
        }
        if ($desc) {
            $ical .= "DESCRIPTION:" . strtr(trim($desc), $mapping) . CRLF;
        }
        $ical .= "END:VEVENT" . CRLF;
    }
    $ical .= "END:VCALENDAR" . CRLF;

    return $ical;
}//ko_get_ics_for_res()


/**
 * Writes an ICS file with the given data and returns the filename
 *
 * @param $mode string Can be res or daten to either create ICS for reservations or events
 * @param $data array DB data for ko_reservation or ko_events
 * @param $forceDetails boolean Force the inclusion of details normally not visible to ko_guest
 * @return string Filename of ics file relative to BASE_PATH/download/
 */
function ko_get_ics_file($mode, $data, $forceDetails = false)
{
    global $BASE_PATH;

    switch ($mode) {
        case 'res':
            $ical = ko_get_ics_for_res($data, $forceDetails);
            break;

        case 'daten':
            //TODO
            break;
    }

    $filename = 'ical_' . date('Ymd_His') . '.ics';
    $fp = fopen($BASE_PATH . 'download/' . $filename, 'w');
    fputs($fp, $ical);
    fclose($fp);

    return $filename;
}//ko_get_ics_file()

