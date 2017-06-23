<?php
return [
    '_access' => [
        'module' => 'daten',
        'chk_col' => 'eventgruppen_id',
        'level' => 3,
        'condition' => "return 'import_id' == '';",  //Imported events may not be edited
    ],
    "_multititle" => [
        'title' => '',
        "eventgruppen_id" => "ko_get_eventgruppen_name('@VALUE@')",
        "startdatum" => "sql2datum('@VALUE@')",
        "startzeit" => "sql_zeit('@VALUE@')",
    ],
    '_inlineform' => [
        'redraw' => [
            'sort' => 'sort_events',
            'fcn' => 'ko_list_events(\'all\', FALSE);'
        ],
        'module' => 'daten',
    ],
    "_listview" => [
        10 => ["name" => "startdatum", "sort" => "startdatum", "multiedit" => "startdatum,enddatum"],
        20 => ["name" => "eventgruppen_id", "sort" => "eventgruppen_id"],
        25 => ['name' => 'title', 'sort' => 'title', 'filter' => true],
        30 => ["name" => "kommentar", "sort" => "kommentar", 'filter' => true],
        //35 for kommentar2 if not ko_guest
        40 => ["name" => "startzeit", "sort" => "startzeit", "multiedit" => "startzeit,endzeit", 'filter' => true],
        50 => ["name" => "room", "sort" => "room", 'filter' => true],
        //60 is reserved for rota (set further down) only if rota module is installed
        //70 is reserved for reservations (set further down) only if res module is installed
        80 => ["name" => "registrations", "sort" => "registrations", "filter" => true],
    ],
    '_listview_default' => ['startdatum', 'eventgruppen_id', 'title', 'startzeit', 'room', 'rota', 'reservationen'],
    "eventgruppen_id" => [
        "list" => 'db_get_column("ko_eventgruppen", @VALUE@, "name")',
        "post" => 'uint',
        "form" => array_merge([
            "type" => "dynselect",
            "js_func_add" => "event_cal_select_add",
            "params" => 'size="5"',
            'new_row' => true,
        ], []) //kota_get_form("ko_event", "eventgruppen_id")),
    ],  //eventgruppen_id
    'title' => [
        'list' => 'ko_html',
        'pre' => 'ko_html',
        'form' => [
            'type' => 'text',
            'params' => 'size="60" maxlength="255"',
        ],
    ],  //title
    "url" => [
        "pre" => "",
        "form" => [
            "type" => "text",
            "params" => 'size="60"',
        ],
    ],  //url
    "startdatum" => [
        "list" => 'FCN:kota_listview_date',
        "pre" => "sql2datum('@VALUE@')",
        "post" => "sql_datum('@VALUE@')",
        "form" => [
            "type" => "jsdate",
            'noinline' => true,
        ],
    ],  //startdatum
    "enddatum" => [
        "pre" => 'FCN:kota_pre_enddate',
        'post' => 'FCN:kota_post_enddate',
        "form" => [
            "type" => "jsdate",
            'noinline' => true,
        ],
    ],  //enddatum
    "startzeit" => [
        "list" => 'FCN:kota_listview_time',
        "pre" => "sql_zeit('@VALUE@')",
        "post" => "sql_zeit('@VALUE@')",
        "form" => [
            "type" => "text",
            "params" => 'size="11" maxlength="11"',
        ],
    ],  //startzeit
    "endzeit" => [
        "pre" => "sql_zeit('@VALUE@')",
        "post" => "sql_zeit('@VALUE@')",
        "form" => [
            "type" => "text",
            "params" => 'size="11" maxlength="11"',
        ],
    ],  //endzeit
    "room" => [
        "list" => "ko_html",
        "form" => [
            "type" => "textplus",
            "params" => 'size="0"',
            "params_PLUS" => 'size="50" maxlength="50"',
            'where' => "WHERE `import_id` = ''",
        ],
    ],  //room
    "kommentar" => [
        'list' => 'ko_html;FCN:kota_listview_rootid',
        "pre" => "ko_html",
        "form" => [
            "type" => "textarea",
            "params" => 'cols="50" rows="4"',
        ],
    ],  //kommentar
    "kommentar2" => [
        "list" => "ko_html",
        "pre" => "ko_html",
        "form" => [
            "type" => "textarea",
            "params" => 'cols="50" rows="4"',
        ],
    ],  //kommentar2
    "registrations" => [
        "list" => "FCN:kota_listview_ko_event_registrations",
    ],  //registrations
];
