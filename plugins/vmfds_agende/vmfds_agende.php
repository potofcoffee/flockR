<?php
//require_once $BASE_PATH.'plugins/vmfds_agende/lib/PHPWord/src/PhpWord/Autoloader.php';
//\PhpOffice\PhpWord\Autoloader::register();

/**
 * Action handler for the vmfds_agende_list action
 */
function my_action_handler_vmfds_agende_list()
{
    $_SESSION['show'] = 'vmfds_agende_list';
}

function my_action_handler_vmfds_agende_create()
{
    $_SESSION['show'] = 'vmfds_agende_create';
}

/**
 * View function for vmfds_agende_list view
 */
function my_show_case_vmfds_agende_list()
{
    global $ko_path, $access, $BASE_URL;
    echo '<h1>Gottesdienstablauf erstellen</h1></form>';
    echo '<table class="table"><thead><tr><th>Datum</th><th>Thema</th><th>Aktionen</th></tr></thead><tbody>';

    $events = ko_rota_get_events();
//print_r($events);
    foreach ($events as $event) {

        echo '<tr>';
        $timeCode = strtotime($event['startdatum'] . ' ' . $event['startzeit']);
        echo '<td>' . strftime('%A, %d.%m.%y, %H:%M Uhr', $timeCode) . '</td>';
        echo '<td><b>' . $event['title'] . '</b></td>';
        echo '<td><form action="index.php" method="post" enctype="multipart/form-data">'
            . '<input type="hidden" name="action" value="vmfds_agende_create" />'
            . '<input type="hidden" name="eid" value="' . $event['id'] . '" />'
            . '<button class="btn btn-default" type="submit"><span class="fa fa-download"></span> Ablauf herunterladen</button></form></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

/**
 * View function for vmfds_agende_create view
 */
function my_show_case_vmfds_agende_create()
{
    global $access, $ko_path, $DATETIME, $BASE_PATH, $BASE_URL;

    if ($access['rota']['MAX'] < 2) return;

    $config = $style = yaml_parse_file($BASE_PATH . 'plugins/vmfds_agende/config/config.yaml');

    $event = ko_rota_get_events('', $_REQUEST['eid'], TRUE);
    $w = date('Y-W',
        (strtotime($event['startdatum']) - (ko_get_setting('rota_weekstart') * 3600
                * 24)));
    $week = ko_rota_get_weeks('', $w);
    $order = 'ORDER BY ' . $_SESSION['sort_rota_teams'] . ' ' . $_SESSION['sort_rota_teams_order'];
    $all_teams = db_select_data('ko_rota_teams', 'WHERE 1', '*', $order);

    $formatting = array('formats' => array('bold' => array('bold' => 1), 'italic' => array(
        'italic' => 1)));
    $data = array();
    $row = 1;


//Add all teams and the schedulled data
    $log_teams = array();
    foreach ($event['teams'] as $key => $tid) {
        if ($access['rota']['ALL'] < 2 && $access['rota'][$tid] < 2) continue;

        if ($all_teams[$tid]['rotatype'] == 'event') {
            $schedulled = ko_rota_schedulled_text($event['schedule'][$tid]);
        } else if ($all_teams[$tid]['rotatype'] == 'week') {
            $schedulled = ko_rota_schedulled_text($week['schedule'][$tid]);
        }

        $team = array();
        foreach ($schedulled as $entry) {
            if (ko_get_userpref($_SESSION['ses_userid'], 'rota_markempty') == 1 && $entry
                == ''
            ) {
                $team[] = getLL('rota_empty');
            } else {
                $team[] = $entry;
            }
        }
        $tName = strtr(utf8_encode($all_teams[$tid]['name']),
            array(
                'Ä' => 'Ae',
                'Ö' => 'Oe',
                'Ü' => 'Ue',
                'ä' => 'ae',
                'ö' => 'oe',
                'ü' => 'ue',
                'ß' => 'ss',
                'é' => 'e',
            ));
        $event['plan'][$tName] = join(', ', $team);
    }

// get external data (sermon, announcements, ...) via json
    foreach ($config['external'] as $key => $extConfig)
        $event[$key] = my_vmfds_agende_extension_get($event['startdatum'],
            $extConfig);

    $worshipTeam = $event['plan']['Lobpreisleiter'] . ' und Team';
    $event['plan']['Leiter'] = $event['plan']['Leitung'] ? $event['plan']['Leitung']
        : 'Christoph Fischer';

    $timeCode = strtotime($event['startdatum'] . ' ' . $event['startzeit']);

// build the file
    $phpWord = new \PhpOffice\PhpWord\PhpWord();

    $phpWord->setDefaultFontName('Open Sans');
    $phpWord->setDefaultFontSize(9);

    $phpWord->addParagraphStyle('Programmpunkt',
        array(
            'align' => 'left',
            'spaceAfter' => 0,
//        'indent' => 200,
            'indent' => 6.3,
            'hanging' => 6.3,
            'tabs' => array(
                new \PhpOffice\PhpWord\Style\Tab('left', 2268),
                new \PhpOffice\PhpWord\Style\Tab('left', 4819),
                new \PhpOffice\PhpWord\Style\Tab('left', 6000),
            ),
        ));

    $phpWord->addFontStyle(
        'Überschrift1',
        array('name' => 'Open Sans Extrabold', 'size' => 16, 'bold' => true)
    );
    $phpWord->addFontStyle(
        'Überschrift2',
        array('name' => 'Open Sans Extrabold', 'size' => 13, 'bold' => true, 'smallCaps' => true)
    );
    $phpWord->addFontStyle(
        'Programmpunkt',
        array('name' => 'Open Sans', 'size' => 10, 'bold' => false)
    );
    $phpWord->addFontStyle(
        'klein', array('name' => 'Open Sans', 'size' => 7, 'bold' => false)
    );
    $phpWord->addFontStyle(
        'ProgrammpunktFett',
        array('name' => 'Open Sans', 'size' => 10, 'bold' => true)
    );

    $section = $phpWord->addSection(array(
        'marginTop' => 709,
        'marginRight' => 709,
        'marginBottom' => 709,
        'marginLeft' => 709,
    ));


    // header
    $section->addText($event['title'], 'Überschrift1', 'AbsÜberschrift1');
    $section->addText("Gottesdienst:\t" . strftime('%A, %d.%m.%Y, %H:%M Uhr',
            $timeCode), 'Programmpunkt', 'Programmpunkt');
    if ($event['plan']['Leitung'])
        $section->addText("Leitung:\t" . $event['plan']['Leitung'],
            'Programmpunkt', 'Programmpunkt');
    $section->addText("Lobpreis:\t" . $event['plan']['Lobpreisleiter'],
        'Programmpunkt', 'Programmpunkt');
    $section->addText("Predigt:\t" . $event['plan']['Predigt'], 'Programmpunkt',
        'Programmpunkt');
    $section->addText("Technik:\t" . $event['plan']['Technik'], 'Programmpunkt',
        'Programmpunkt');
    $section->addText("Beamer:\t" . $event['plan']['Beamerdienst'],
        'Programmpunkt', 'Programmpunkt');
    $section->addText("Welcome:\t" . $event['plan']['Begruessung'],
        'Programmpunkt', 'Programmpunkt');
    $section->addText("Lounge:\t" . $event['plan']['Gemeindecafe'],
        'Programmpunkt', 'Programmpunkt');
    $section->addTextBreak(1, 'Standard');


    // shorten certain names to first names
    foreach (array('Leiter', 'Leitung', 'Predigt', 'Lobpreisleiter') as $ministry) {
        $p = $event['plan'][$ministry];
        list($firstName, $lastName) = explode(' ', $p);
        $names[$p] = $firstName;
    }
    $nameCount = array_count_values($names);
    foreach (array('Leiter', 'Leitung', 'Predigt', 'Lobpreisleiter') as $ministry) {
        $p = $event['plan'][$ministry];
        if ($nameCount[$names[$p]] == 1) $event['plan'][$ministry] = $names[$p];
    }


    $tpl = yaml_parse_file($BASE_PATH . 'plugins/vmfds_agende/config/agende.yaml');

    foreach ($tpl['sections'] as $tplSection) {
        $textrun = $section->addTextRun('AbsÜberschrift2');
        $textrun->addLine(
            array(
                'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(18.5),
                'height' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(0),
                'positioning' => 'absolute',
            )
        );
        $textrun->addText($tplSection['title'], 'Überschrift2',
            'AbsÜberschrift2');
        foreach ($tplSection['items'] as $item) {
            // marker replacements
            foreach ($item as $key => $val) {
                $item[$key] = str_replace('\t', "\t", strip_tags($item[$key]));
                $item[$key] = my_vmfds_agende_marker_replace_multiple($item[$key],
                    $event['plan']);
                foreach ($config['external'] as $eKey => $extConfig) {
                    $item[$key] = my_vmfds_agende_marker_replace_multiple($item[$key],
                        $event[$eKey], $eKey);
                    $item[$key] = my_vmfds_agende_marker_replace($item[$key],
                        $eKey, join('\r', $event[$eKey]));
                }
            }

            $style = $item['smallprint'] ? 'klein' : 'Programmpunkt';

            $textrun = $section->addTextRun('Programmpunkt');
            $textrun->addText($item['title'], 'ProgrammpunktFett');
            $textrun->addText("\t" . $item['by'] . "\t", 'Programmpunkt');
            $lines = explode('\r', $item['description']);
            if ($item['firstLine']) {
                $textrun->addText($lines[0], 'Programmpunkt');
                if (count($lines) > 1) $textrun->addTextBreak();
                unset($lines[0]);
            }
            $ct = 0;
            foreach ($lines as $line) {
                $ct++;
                $textrun->addText($line, $style);
                if ($ct < count($lines)) $textrun->addTextBreak();
            }
        }
        $section->addTextBreak();
    }

    $fileName = strftime('%Y%m%d', $timeCode) . '_Ablauf.docx';
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($ko_path . 'download/' . $fileName);

    echo '<h1>' . $event['title'] . '</h1>';
    echo '<a href="' . $BASE_URL . 'download/' . $fileName . '">Download</a><hr />';
    echo '<a href="' . $BASE_URL . 'rota/index.php?action=vmfds_agende_list">Back</a><hr />';
    echo '<script type="text/javascript">window.location.href="' . $BASE_URL . 'download/' . $fileName . '";</script>';
}

/**
 * Replace a marker with its value in a string
 *
 * @param string $s String to search
 * @param string $marker Marker name
 * @param string $val Marker value
 * @param string $prepend String to prepend the marker name
 * @return string String with marker replaced
 */
function my_vmfds_agende_marker_replace($s, $marker, $val, $prepend = NULL)
{
    return str_replace('{' . ($prepend ? $prepend . '.' : '') . $marker . '}', $val, $s);
}

/**
 * Replace multiple markers with their values
 *
 * @param type $s String to search
 * @param type $markerArray Marker contents in marker name => marker value pairs
 * @param string $prepend String to prepend the marker names
 * @return type String with markers replaced
 */
function my_vmfds_agende_marker_replace_multiple($s, $markerArray,
                                                 $prepend = NULL)
{
    foreach ($markerArray as $marker => $val) {
        $s = my_vmfds_agende_marker_replace($s, $marker, $val, $prepend);
    }
    return $s;
}

/**
 * Get data from an external url
 *
 * This will retrieve external data pertaining to $date from an external url.
 * It expects a json object with the sermon data.
 *
 * The url to retrieve the external data is specified in config/config.yaml
 * in the external:{extension}:url key, {extension} being a name for the
 * data type pulled in. The marker {date} in the url is replaced by the
 * specified date, which is expected to be in YYYY-MM-DD format.
 *
 * @param string $date Date in format YYYY-MM-DD
 * @param array $config Config array from config/config.yaml
 * @return array Data from external source
 */
function my_vmfds_agende_extension_get($date, $myConfig)
{
    if ($myConfig['url']) {
        $url = my_vmfds_agende_marker_replace($myConfig['url'], 'date', $date);
        $data = json_decode(file_get_contents($url));
        if (($myConfig['firstOnly']) && (isset($data[0]))) $data = $data[0];
    } elseif ($myConfig['sql']) {
        $sql = my_vmfds_agende_marker_replace($myConfig['sql'], 'date', $date);
        $res = mysql_query($sql);
        $data = mysql_fetch_assoc($res);
    }
    return $data;
}


function my_action_handler_vmfds_agende_serviceplan()
{
    $_SESSION['show'] = 'vmfds_agende_serviceplan';
}

function my_show_case_vmfds_agende_serviceplan()
{
    //$events = db_select_data('ko_rota_teams', 'WHERE 1', '*', $order);
    ko_get_events($events, 'AND (startdatum >= \''.date('Y-m-d').'\') AND (eventgruppen_id=1)');
    /*
    $timeStart = $_SESSION['rota_timestart'];
    $timeSpan = $_SESSION['rota_timestart'];
    $_SESSION['rota_timestart'] = date('Y-m-d');
    $_SESSION['rota_timespan'] = '12m';
    $events = ko_rota_get_events('', '', true);
    $_SESSION['rota_timestart'] = $timeStart;
    $_SESSION['rota_timestart'] = $timeSpan;
    //\Peregrinus\Flockr\Core\Debugger::dumpAndDie($events);
    */
    $view = \Peregrinus\Flockr\Core\App::getInstance()->createView(
        new \Peregrinus\Flockr\Legacy\LegacyModule(),
        'Agende',
        'ServicePlan',
        'Plugins/'
        );

    $teams = db_select_data('ko_rota_teams', 'WHERE 1', '*', $order);


    foreach ($events as $key=>$event) {
        $events[$key] = $event = ko_rota_get_events('', $event['id']);
        $events[$key]['rota'] = [];
        foreach ($event['teams'] as $teamKey => $team) {
            if (isset($event['schedule'][$team])) {
                $events[$key]['rota'][$team] = ko_rota_schedulled_text($event['schedule'][$team]);
            }
        }
    }

    $view->assign('events', $events);
    $view->assign('teams', $teams);

    echo $view->render('ServicePlan');
}
