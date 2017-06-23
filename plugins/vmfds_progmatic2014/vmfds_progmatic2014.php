<?php
require_once(FLOCKR_basePath.'plugins/vmfds_progmatic2014/lib/progmatic/lib/hex2bin.php');
require_once(FLOCKR_basePath.'plugins/vmfds_progmatic2014/lib/progmatic/classes/Profile.php');

/**
 * Action handler for the vmfds_progmatic2014_home action
 */
function my_action_handler_vmfds_progmatic2014_home()
{
    $_SESSION['show'] = 'vmfds_progmatic2014_home';
}

/**
 * View function for vmfds_progmatic2014_home view
 */
function my_show_case_vmfds_progmatic2014_home()
{
    global $ko_path, $access, $BASE_URL, $BASE_PATH;
    echo '<h1>'.getLL('my_vmfds_progmatic2014_home_title').'</h1>';

    $config = yaml_parse_file($BASE_PATH.'plugins/vmfds_progmatic2014/config/progmatic2014.yaml');

    $profile = new \de\peregrinus\progmatic\profile();


    // determine time period (today -> today + 6 days);
    $start = strtotime(strftime('%Y-%m-%d').' 0:00:00');
    $end = strtotime('+6 days', $start);

    $reservations = db_select_data('ko_reservation',
        'where (enddatum>=\''.strftime('%Y-%m-%d', $start).'\') AND (startdatum<=\''.strftime('%Y-%m-%d',
            $end).'\') order by startdatum, startzeit', '*');

    $program = array();

    foreach ($reservations as $key => $res) {
        $resStart = strtotime($res['startdatum'].' '.$res['startzeit']);
        $resEnd = strtotime($res['enddatum'].' '.$res['endzeit']);
        $wkDay = strftime('%u', $resStart) - 1;
        if (isset($config['rooms'][$res['item_id']])) {
            if (($res['startzeit'] != '00:00:00') && ($res['startzeit'] != '00:00:00')) {
                // add preheat offset
                if (isset($config['rooms'][$res['item_id']]['preheat'])) {
                    $resStart = strtotime('-'.$config['rooms'][$res['item_id']]['preheat'].' minutes',
                        $resStart);
                }
                // add postheat offset
                if (isset($config['rooms'][$res['item_id']]['postheat'])) {
                    $resEnd = strtotime('+'.$config['rooms'][$res['item_id']]['postheat'].' minutes',
                        $resEnd);
                }
                $program[$res['item_id']][$wkDay][] = array(
                    'start' => array('hour' => strftime('%H', $resStart), 'minute' => strftime('%M',
                            $resStart)),
                    'end' => array('hour' => strftime('%H', $resEnd), 'minute' => strftime('%M',
                            $resEnd)),
                    'reason' => $res['zweck'],
                );
            }
        }
    }

    if (count($program)) {
        foreach ($config['rooms'] as $roomIdx => $room) {
            $profileIdx = $room['profile'] - 1;
            $profile->enableRoomProfile($profileIdx);
            $profile->getRoomProfile($profileIdx)->setTitle($room['name']);
            if ($room['high'])
                    $profile->getRoomProfile($profileIdx)->setHighTemperature($room['high']);
            if ($room['low'])
                    $profile->getRoomProfile($profileIdx)->setLowTemperature($room['low']);
            if ($room['offset'])
                    $profile->getRoomProfile($profileIdx)->setOffsetTemperature($room['offset']);
            $profile->getRoomProfile($profileIdx)->setLockState($room['lock']);

            foreach ($program[$roomIdx] as $dayIdx => $dayProgram) {
                $ctr = 0;
                foreach ($dayProgram as $progIdx => $prog) {
                    if ($ctr < 4) {
                        $profile->getRoomProfile($profileIdx)
                            ->getProgram($dayIdx)
                            ->getItem($progIdx)
                            ->setDataManually(
                                $prog['start']['minute'],
                                $prog['start']['hour'], $prog['end']['minute'],
                                $prog['end']['hour']
                        );
                    }
                }
            }
        }

        $profileFileName = strftime('download/progmatic2014-%Y%m%d-%H%M%S.dat');
        $profile->toFile($BASE_PATH.$profileFileName);
        $weekDays = array('', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So');

        echo '<h2>In den folgenden R&auml;umen m&uuml;ssen die Thermostate programmiert werden</h2><table border="1" cellpadding="2" cellspacing="0">';
        echo '<tr><th>Raum</th><th>Veranstaltungen</th><th>Profil</th></tr>';
        foreach ($program as $roomIdx => $day) {
            echo '<tr><td valign="top">'.$config['rooms'][$roomIdx]['name'].'</td><td>';
            foreach ($day as $dayIdx => $dayProgram) {
                foreach ($dayProgram as $itemIdx => $item) {
                    echo $weekDays[$dayIdx].', '.$item['start']['hour'].':'.$item['start']['minute'].' - '.$item['end']['hour'].':'.$item['end']['minute'].' Uhr, '.$item['reason'].'<br />';
                }
                if (count($dayProgram) > 4) {
                    echo '<span style="color: red; font-weight: bold;">PROGmatic erlaubt nur max. 4 Heizzeiten pro Tag. Bitte passe den Eintrag f&uuml;r diesen Tag in der PROGmatic2014-Software entsprechend an.</span><br />';
                }
            }
            echo '</td><td valign="top">P'.sprintf('%02d',
                $config['rooms'][$roomIdx]['profile']).'</td></tr>';
        }
        echo '</table>';

        echo '<h2>Vorgehensweise</h2><ol>';
        echo '<li><h3>Programmdatei herunterladen</h3>Die Programmdatei kann <a href="'.$BASE_URL.$profileFileName.'">hier heruntergeladen werden.</a></li>';
        echo '<li><h3>Programmdatei importieren</h3><ul>'
        .'<li>&Ouml;ffne die "Progmatic2014" software<br /><img src="'.$BASE_URL.'plugins/vmfds_progmatic2014/res/progmatic2014_1.jpg"/></li>'
        .'<li>Klicke im Men&uuml; auf Datei > &Ouml;ffnen... <br /><img src="'.$BASE_URL.'plugins/vmfds_progmatic2014/res/progmatic2014_2.jpg"/></li>'
        .'<li>Klicke auf "Computer" <br /><img src="'.$BASE_URL.'plugins/vmfds_progmatic2014/res/progmatic2014_3.jpg"/></li>'
        .'<li>W&auml;hle die gerade heruntergeladene Datei.</li>'
        .'</ul></li>';

        echo '<li><h3>Programm speichern</h3><ul>'
        .'<li>Stecke den Progmatic2014 USB-Stick ein.</li>'
        .'<li>Klicke im Men&uuml; auf Datei > Speichern ... <br /><img src="'.$BASE_URL.'plugins/vmfds_progmatic2014/res/progmatic2014_4.jpg"/></li>'
        .'<li>Klicke auf "PROGmatic" <br /><img src="'.$BASE_URL.'plugins/vmfds_progmatic2014/res/progmatic2014_5.jpg"/></li>'
        .'<li>Warte, bis die Best&auml;tigung erscheint... <br /><img src="'.$BASE_URL.'plugins/vmfds_progmatic2014/res/progmatic2014_6.jpg"/>. </li>'
        .'<li>Jetzt kannst du den USB-Stick abziehen.</li>'
        .'</ul></li>';

        echo '<li><h3>Thermostate programmieren</h3><ul>'
        .'<li>Stecke den Progmatic2014 USB-Stick in den Programmierport des jeweiligen Thermostats. In der Anzeige erscheint "P01".</li>'
        .'<li>W&auml;hle durch Drehen am Rad das passende Raumprofil aus.</li>'
        .'<li>Best&auml;tige mit der Taste OK.</li>'
        .'<li>Sobald wieder die normale Anzeige im Display erscheint, kann der USB-Stick abgezogen werden.</li>'
        .'</ul></li>';

        //progmatic2014_1.jpg

        echo '</ol>';

        echo '<script type="text/javascript">'
        .'window.setTimeout(function(){window.location.href = "'.$BASE_URL.$profileFileName.'";}, 1000);'
        .'</script>';
    }
}
