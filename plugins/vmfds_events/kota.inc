<?php
if (!function_exists('array_insert_after')) {

    /**
     * Inserts any number of scalars or arrays at the point
     * in the haystack immediately after the search key ($needle) was found,
     * or at the end if the needle is not found or not supplied.
     * Modifies $haystack in place.
     *
     * @param
     *            array &$haystack the associative array to search. This will be modified by the function
     * @param string $needle
     *            the key to search for
     * @param mixed $stuff
     *            one or more arrays or scalars to be inserted into $haystack
     * @return int the index at which $needle was found
     */
    function array_insert_after(&$haystack, $needle = '', $stuff)
    {
        if (!is_array($haystack)) {
            return $haystack;
        }

        $new_array = array();
        for ($i = 2; $i < func_num_args(); ++$i) {
            $arg = func_get_arg($i);
            if (is_array($arg)) {
                $new_array = array_merge($new_array, $arg);
            } else {
                $new_array[] = $arg;
            }
        }

        $i = 0;
        foreach ($haystack as $key => $value) {
            ++$i;
            if ($key == $needle) {
                break;
            }
        }

        $haystack = array_merge(array_slice($haystack, 0, $i, true), $new_array,
            array_slice($haystack, $i, null, true));

        return $i;
    }
}

$KOTA['ko_event']['my_vmfds_events_categories'] = array(
    'form' => array(
        'type' => 'doubleselect',
        'params' => 'size="10"',
        'values' => array(),
        'descs' => array()
    )
);
$KOTA['ko_event']['my_vmfds_events_longdescription'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'kota_fieldgroup_description',
        'type' => 'richtexteditor',
        'params' => 'cols="60" rows="15"'
    )
);
$KOTA['ko_event']['my_vmfds_events_picture'] = array(
    'list' => 'FCN:kota_pic_tooltip',
    'form' => array(
        'type' => 'file',
        'noinline' => true,
        "params" => ''
    )
);
$KOTA['ko_event']['my_vmfds_events_registration_link'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'daten_group_subscription',
        'type' => 'text',
        'params' => 'size=60'
    )
);
$KOTA['ko_event']['my_vmfds_events_prices'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'daten_group_subscription',
        'type' => 'richtexteditor',
        'params' => 'cols="60" rows="5"'
    )
);
$KOTA['ko_event']['my_vmfds_events_tickets_link'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'daten_group_subscription',
        'type' => 'text',
        'params' => 'size=60'
    )
);
// reserve tickets
$KOTA['ko_event']['my_vmfds_events_has_reservations'] = array(
    'list' => 'FCN:kota_listview_boolx',
    'form' => array(
        'group' => 'daten_group_subscription',
        'type' => 'checkbox'
    )
);
$KOTA['ko_event']['my_vmfds_events_reservation_notes'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'daten_group_subscription',
        'type' => 'richtexteditor',
        'params' => 'cols="60" rows="5"'
    )
);
$KOTA['ko_event']['my_vmfds_events_max_reservations'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'daten_group_subscription',
        'type' => 'text',
        'params' => 'size=60'
    )
);
$KOTA['ko_event']['my_vmfds_events_is_soldout'] = array(
    'list' => 'FCN:kota_listview_boolx',
    'form' => array(
        'group' => 'daten_group_subscription',
        'type' => 'checkbox'
    )
);
$KOTA['ko_event']['my_vmfds_events_teaser_start'] = array(
    "list" => 'FCN:kota_listview_date',
    "pre" => "sql2datum('@VALUE@')",
    "post" => "sql_datum('@VALUE@')",
    "form" => array(
        'group' => 'kota_fieldgroup_ads',
        "type" => "jsdate",
        'noinline' => true,
        'new_row' => 1
    )
);
$KOTA['ko_event']['my_vmfds_events_fblink'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'kota_fieldgroup_ads',
        'type' => 'text',
        'params' => 'size=60',
        'new_row' => 1
    )
);
$KOTA['ko_event']['my_vmfds_events_gi_image'] = array(
    'list' => 'FCN:kota_pic_tooltip',
    'form' => array(
        'group' => 'kota_fieldgroup_ads',
        'type' => 'file',
        'noinline' => true,
        "params" => ''
    )
);
$KOTA['ko_event']['my_vmfds_events_announcement_image'] = array(
    'list' => 'FCN:kota_pic_tooltip',
    'form' => array(
        'group' => 'kota_fieldgroup_ads',
        'type' => 'file',
        'noinline' => true,
        "params" => '',
        'new_row' => 1
    )
);
// my_vmfds_events_announcement_start
$KOTA['ko_event']['my_vmfds_events_announcement_start'] = array(
    "list" => 'FCN:kota_listview_date',
    "pre" => "sql2datum('@VALUE@')",
    "post" => "sql_datum('@VALUE@')",
    "form" => array(
        'group' => 'kota_fieldgroup_ads',
        "type" => "jsdate",
        'noinline' => true
    )
);
$KOTA['ko_event']['my_vmfds_events_announcement_title'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'kota_fieldgroup_ads',
        'type' => 'text',
        'params' => 'size=60'
    )
);
$KOTA['ko_event']['my_vmfds_events_announcement_note'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'kota_fieldgroup_ads',
        'type' => 'text',
        'params' => 'size=60'
    )
);
$KOTA['ko_event']['my_vmfds_events_mitteilungsblatt_title'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'kota_fieldgroup_ads',
        'type' => 'text',
        'params' => 'size=60'
    )
);
$KOTA['ko_event']['my_vmfds_events_mitteilungsblatt_text'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'kota_fieldgroup_ads',
        'type' => 'textarea',
        'params' => 'cols=60,rows=5'
    )
);
$KOTA['ko_event']['my_vmfds_events_series_title'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'kota_fieldgroup_ads',
        'type' => 'text',
        'params' => 'size=60'
    )
);
$KOTA['ko_event']['my_vmfds_events_reference'] = array(
    'post' => 'format_userinput("@VALUE@", "text")',
    'form' => array(
        'group' => 'kota_fieldgroup_ads',
        'type' => 'text',
        'params' => 'size=60'
    )
);


// `my_vmfds_events_skip_listtitle` varchar(255) default NULL,

$KOTA['ko_eventgruppen']['my_vmfds_events_skip_listtitle'] = array(
    'list' => 'FCN:kota_listview_boolx',
    'form' => array(
        'type' => 'checkbox'
    )
);
$KOTA['ko_eventgruppen']['my_vmfds_events_picture'] = array(
    'list' => 'FCN:kota_pic_tooltip',
    'form' => array(
        'type' => 'file',
        'noinline' => true,
        "params" => ''
    )
);
$KOTA['ko_eventgruppen']['my_vmfds_events_announcement_group_image'] = array(
    'list' => 'FCN:kota_pic_tooltip',
    'form' => array(
        'type' => 'file',
        'noinline' => true,
        "params" => ''
    )
);

$KOTA['ko_event_categories'] = array(
    "_listview" => array(
        10 => array(
            "name" => "title",
            "sort" => "title",
            "multiedit" => "title"
        )
    ),
    '_listview_default' => array(
        'title'
    ),
    'title' => array(
        'post' => 'format_userinput("@VALUE@", "text")',
        'form' => array(
            'type' => 'text',
            'params' => 'size=60'
        )
    )
);

// address for google maps:
array_insert_after($KOTA['ko_event'], 'endzeit', array(
    'my_vmfds_events_location' => array(
        'post' => 'format_userinput("@VALUE@", "text")',
        'form' => array(
            'group' => 'kota_fieldgroup_location',
            'type' => 'text',
            'params' => 'size="60" placeholder="Leer lassen f&uuml;r: '.GEOCODE_DEFAULT_LOCATION.'"',
        )
    ),
    'my_vmfds_events_nav_address' => array(
        'post' => 'FCN:kota_events_post_location',
        'form' => array(
            'group' => 'kota_fieldgroup_location',
            'type' => 'text',
            'params' => 'size="60" placeholder="Leer lassen f&uuml;r: '.GEOCODE_DEFAULT_ADDRESS.'"',
        )
    ),
    'my_vmfds_events_nav_address_lat' => array(
    ),
    'my_vmfds_events_nav_address_lon' => array(
    ),
    'my_vmfds_events_itinerary' => array(
        'post' => 'format_userinput("@VALUE@", "text")',
        'form' => array(
            'group' => 'kota_fieldgroup_location',
            'type' => 'richtexteditor',
            'params' => 'cols="60" rows="15"'
        ),
    ),
));

$KOTA['ko_event']['room']['form']['group'] = 'kota_fieldgroup_location';

$KOTA['ko_event']['my_vmfds_events_categories'] = array(
    'form' => array(
        'group' => 'kota_fieldgroup_cat',
        'type' => 'doubleselect',
        'params' => 'size="10"',
        'values' => array(),
        'descs' => array()
    )
);

// get categories select:
$cats = db_select_data('ko_event_categories', '');
foreach ($cats as $cat) {
    $KOTA['ko_event']['my_vmfds_events_categories']['form']['values'][] = $cat['id'];
    $KOTA['ko_event']['my_vmfds_events_categories']['form']['descs'][] = $cat['title'];
}

if (!function_exists(kota_vmfds_events_file_selector)) {

    function kota_vmfds_events_file_selector($field)
    {
        $o = array();
        $o['type'] = 'html';
        $o['value'] = '<input type="file" name="' . $field . '_file_selector"  />';

        // debug w/ file:
        $o = array(
            'type' => 'file'
        );
        return $o;
    }
}



