<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2015 Renzo Lauper (renzo@churchtool.org)
*  All rights reserved
*
*  This script is part of the kOOL project. The kOOL project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  kOOL is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


//Send headers to ensure latin1 charset
header('Content-Type: text/html; charset=ISO-8859-1');

$ko_path = './';
include($ko_path.'inc/ko.inc');

function human_filesize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

switch($_GET["action"]) {
	case "file":
		//Nur Dateien aus dem Download-Verzeichnis des Webverzeichnises erlauben
		$full_path = realpath($_GET["file"]);
		//Find empty file
		if($full_path == '') ko_die('No file found');
		//Replace \ with / for windows systems otherwise the check below will always trigger an error
		if(DIRECTORY_SEPARATOR == '\\') $full_path = str_replace('\\', '/', $full_path);
		if(substr($full_path, 0, strlen($BASE_PATH."download")) != ($BASE_PATH."download")) {
			trigger_error('Not allowed download file: '.$_GET['file'], E_USER_ERROR);
			exit;
		}
		if(!file_exists($_GET["file"])) {
			exit;
		}
		if(substr($_GET["file"], 0, 1) == "/") {
			exit;
		}

		$fileName = basename($_GET["file"]);
		$fileFullPath = $BASE_URL.$_GET["file"];
		$fileSize = filesize($_GET['file']);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileMimeType = finfo_file($finfo, $GET['file']);
        finfo_close($finfo);

	break;  //case "file"


	case "passthrough":
		ko_returnfile($_GET["file"]);
		exit;
	break;


	default:
		exit;
}//switch(action)
?><div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4><span class="fa fa-download"></span> Datei herunterladen</h4>
</div>
<div class="modal-body">
    <p>Die gew&uuml;nschte Datei steht hier f&uuml;r dich bereit:</p>
    <a class="btn btn-primary" href="<?php echo $fileFullPath; ?>" target="_blank"><span class="fa fa-download"></span> Herunterladen</a>
    <a class="btn btn-default" href="?action=show_filesend&file=<?php echo $_GET['file']; ?>&filetype=<?php echo $_GET['filetype']; ?>"><span class="fa fa-envelope"></span> Per E-Mail versenden</a>
</div>
<div class="modal-footer">
    <small>Datei: <?php echo $fileName.' &middot; '.($fileMimeType ? $fileMimeType.' &middot; ' : '').human_filesize($fileSize); ?></small>
</div>