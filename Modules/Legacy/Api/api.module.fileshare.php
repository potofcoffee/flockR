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
 * MODUL-FUNKTIONEN   F I L E S H A R E                                                                                 *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Liefert die vorhandenen Shares
 * Erlaubt die Angaben von zus�tzlichen Filtern und Limiten
 */
function ko_get_shares($filter, $orderBy = "", $limit = "")
{
    if (!$filter) {
        return false;
    }

    $r = db_select_data('ko_fileshare', 'WHERE 1=1 ' . $filter, '*',
        'ORDER BY ' . ($orderBy ? $orderBy : 'filename ASC'), $limit);
}//ko_get_shares()


/**
 * Speichert mit den gemachten Angaben ein neues Share
 */
function ko_fileshare_save_share($id, $user_id, $filename, $type, $parent, $size)
{
    if (!$id) {
        return false;
    }

    db_insert_data('ko_fileshare', array(
        'id' => $id,
        'user_id' => $user_id,
        'filename' => format_userinput($filename, 'text'),
        'type' => $type,
        'c_date' => date('Y-m-d H:i:s'),
        'filesize' => $size,
        'parent' => $parent
    ));
}//ko_fileshare_save_share()


/**
 * Macht Sent-Eintrag nach dem Versenden eines Mails
 */
function ko_fileshare_send_file($fileid, $rec, $recid)
{
    if (!$fileid || !$recid) {
        return false;
    }

    db_insert_data('ko_fileshare_sent', array('file_id' => $fileid, 'recipient' => $rec, 'recipient_id' => $recid));
}//ko_fileshare_send_file()


/**
 * Liefert alle Ordner f�r einen Benutzer
 */
function ko_fileshare_get_folders($userid, $mode = "view")
{
    global $access;

    $r = array();
    if (!in_array($mode, array("view", "new", "edit", "mod"))) {
        return false;
    }
    if (!$userid || !ko_module_installed("fileshare", $userid)) {
        return false;
    }
    $levels = array('view' => 1, 'new' => 2, 'edit' => 3, 'mod' => 4);
    $level = $levels[$mode];

    if ($access['fileshare']['MAX'] >= $level) {
        //Top-Folders
        $top = db_select_data('ko_fileshare_folders', "WHERE `user` = '$userid' AND `parent` = '0' AND `flag` != 'S'",
            '*', 'ORDER BY parent ASC, name ASC');

        //Build Array in alphabetic tree-order
        foreach ($top as $t) {
            $r[$t["id"]] = $t;
            rec_folders($t, $userid, $r);
        }
    }

    //Add Freigabe-Ordner
    $shareroot = ko_fileshare_get_shareroot($userid);
    $r[$shareroot["id"]] = $shareroot;
    //Add Shared Folders
    $shareroot = ko_fileshare_get_shareroot($_SESSION["ses_userid"]);
    $rows = db_select_data('ko_fileshare_folders',
        "WHERE `user` != '$userid' AND `share_users` REGEXP '@$userid@' AND `share_rights` >= '$level'", '*',
        'ORDER BY name ASC');
    foreach ($rows as $row) {
        $row['parent'] = $shareroot['id'];
        $r[$row['id']] = $row;
    }

    return $r;
}//ko_fileshare_get_folders()

function rec_folders(&$t, $userid, &$r)
{
    //Children
    $children = db_select_data('ko_fileshare_folders',
        "WHERE `user` = '$userid' AND `parent` = '" . $t['id'] . "' AND `flag` != 'S'", '*', 'ORDER BY name ASC');

    foreach ($children as $c) {
        $r[$c["id"]] = $c;
        rec_folders($c, $userid, $r);
        unset($children[$c["id"]]);
    }
}//rec_folders()


function ko_fileshare_get_folder(&$folder, $id)
{
    $folder = db_select_data('ko_fileshare_folders', "WHERE `id` = '$id'", '*', '', '', true);
}//ko_fileshare_get_folder()


/**
 * Stellt eine Gr�ssenangabe sch�n in B, KB, MB oder GB dar
 */
function ko_nice_size($size)
{
    if ($size > (1024 * 1024 * 1024)) {
        $size = round($size / (1024 * 1024 * 1024), 2) . "GB";
    } else {
        if ($size > (1024 * 1024)) {
            $size = round($size / (1024 * 1024), 2) . "MB";
        } else {
            if ($size > 1024) {
                $size = round($size / 1024) . "KB";
            } else {
                if ($size > 0) {
                    $size = $size . "B";
                }
            }
        }
    }
    return $size;
}//ko_nice_size()


/**
 * Liefert Rootline eines Folders
 */
function ko_fileshare_get_rootline($id, $userid)
{
    $rootline = array();

    ko_fileshare_get_folder($af, $id);
    if ($af["user"] != $userid) {  //Bei Shared-Folders S-Folder als Parent angeben
        $parent_ = ko_fileshare_get_shareroot($userid);
        $parent = $parent_["id"];
    } else {  //Sonst richtigen Parent w�hlen
        $parent = $af["parent"];
    }
    $rootline[$id] = $af["name"];
    while ($parent != 0) {
        ko_fileshare_get_folder($af, $parent);
        $parent = $af["parent"];
        $rootline[$af["id"]] = $af["name"];
    }//while(parent != 0)

    return $rootline;
}//ko_fileshare_get_rootline()


/**
 * Liefert ID des Eingang-Folders f�r einen Benutzer
 */
function ko_fileshare_get_inbox($userid)
{
    return db_select_data('ko_fileshare_folders', "WHERE `user` = '$userid' AND `flag` = 'I'", '*', '', '', true);
}//ko_fileshare_get_inbox()


/**
 * Liefert ID des Shared-Rootfolders f�r einen Benutzer
 */
function ko_fileshare_get_shareroot($userid)
{
    return db_select_data('ko_fileshare_folders', "WHERE `user` = '$userid' AND `flag` = 'S'", '*', '', '', true);
}//ko_fileshare_get_shareroot()


/**
 * Check for Inbox- and Shared-Folders, and create them if not present
 */
function ko_fileshare_check_inbox_shareroot($id)
{
    if (!ko_module_installed('fileshare', $id)) {
        return;
    }

    $inbox = ko_fileshare_get_inbox($id);
    if (!$inbox["id"]) {
        db_insert_data('ko_fileshare_folders', array(
            'user' => $id,
            'name' => getLL('fileshare_inbox'),
            'comment' => getll('fileshare_inbox_comment'),
            'c_date' => date('Y-m-d H:i:s'),
            'flag' => 'I'
        ));
    }
    $shares = ko_fileshare_get_shareroot($id);
    if (!$shares["id"]) {
        db_insert_data('ko_fileshare_folders', array(
            'user' => $id,
            'name' => getLL('fileshare_share'),
            'comment' => getll('fileshare_share_comment'),
            'c_date' => date('Y-m-d H:i:s'),
            'flag' => 'S'
        ));
    }
}//ko_fileshare_check_inbox_shareroot()


/**
 * Liefert Select-Box f�r Pfad-Auswahl
 */
function ko_fileshare_get_folder_select($userid, $mode = "view", &$values, &$descs, $shareroot = true)
{
    $code = "";
    $values = $descs = array();

    $folders = ko_fileshare_get_folders($userid, $mode);
    foreach ($folders as $f) {
        if (!$shareroot && $f["flag"] == "S") {
            continue;
        }
        $sel = ($f["id"] == $_SESSION["folderid"]) ? 'selected="selected"' : '';
        $code .= '<option value="' . $f["id"] . '" ' . $sel . '>';
        $depth = sizeof(ko_fileshare_get_rootline($f["id"], $userid)) - 1;
        for ($i = 0; $i < $depth; $i++) {
            $code .= "&nbsp;&nbsp;";
        }
        $code .= $f["name"];
        $code .= '</option>';

        $values[] = $f["id"];
        $desc = "";
        for ($i = 0; $i < $depth; $i++) {
            $desc .= "&nbsp;&nbsp;";
        }
        $descs[] = $desc . $f["name"];
    }
    return $code;
}//ko_fileshare_get_folder_select()


/**
 * �berpr�ft einen Ordner auf gewisse Rechte f�r einen Benutzer
 */
function ko_fileshare_check_permission($userid, $folderid, $action)
{
    global $access;

    if ($userid <= 0) {
        return false;
    }

    ko_fileshare_get_folder($folder, $folderid);
    if ($folderid > 0 && $folder["user"] <= 0) {
        return false;
    }

    if ($folder["user"] == $userid || $folderid == 0) {  //Owned Folder
        $own = true;
    } else {  //Shared Folder
        $own = false;
        if ($folder["share_rights"] >= 1) {
            $share_view = true;
        } else {
            $share_view = false;
        }
        if ($folder["share_rights"] >= 2) {
            $share_new = true;
        } else {
            $share_new = false;
        }
        if ($folder["share_rights"] >= 3) {
            $share_del = true;
        } else {
            $share_del = false;
        }
    }

    switch ($action) {
        case "view_file":
            if ($own && $access['fileshare']['MAX'] > 0) {
                return true;
            } else {
                if (!$own && $share_view) {
                    return true;
                } else {
                    return false;
                }
            }
            break;

        case "new_file":
            if ($own && $access['fileshare']['MAX'] > 1) {
                return true;
            } else {
                if (!$own && $share_new) {
                    return true;
                } else {
                    return false;
                }
            }
            break;

        case "del_file":
            if ($own && $access['fileshare']['MAX'] > 2) {
                return true;
            } else {
                if (!$own && $share_del) {
                    return true;
                } else {
                    return false;
                }
            }
            break;

        case "new_folder":
        case "del_folder":
        case "edit_folder":
            if ($own && $access['fileshare']['MAX'] > 3) {
                return true;
            } else {
                return false;
            }
            break;

    }//switch(action)

}//ko_fileshare_check_permission()


/**
 * Speichert eine Datei als Share-Datei f�r den definierten User in seiner Inbox
 */
function ko_fileshare_save_file_as_share($uid, $dateiname)
{
    global $FILESHARE_FOLDER;

    if (!ENABLE_FILESHARE || !ko_module_installed("fileshare", $uid)) {
        return false;
    }

    $save_filename = basename($dateiname);
    $save_id = md5($save_filename . microtime());
    $save_type = exec("file -ib " . escapeshellcmd($dateiname));
    $inbox = ko_fileshare_get_inbox($uid);
    clearstatcache();
    $file_size = filesize($dateiname);
    copy($dateiname, $FILESHARE_FOLDER . $save_id);
    chmod($FILESHARE_FOLDER . $save_id, 0644);
    ko_fileshare_save_share($save_id, $uid, $save_filename, $save_type, $inbox["id"], $file_size);
}//ko_fileshare_save_file_as_share()
