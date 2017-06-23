<?php
require_once('vendor/autoload.php');

function vmfds_events_cat_submission()
{
    // extract form data from POST
    $data = $_POST["koi"]['ko_event_categories'];
    kota_process_data('ko_event_categories', $data, "post");
    return $data;
}

function my_action_handler_vmfds_events_cat_list()
{
    $_SESSION['show'] = 'vmfds_events_cat_list';
}

function my_action_handler_vmfds_events_cat_add()
{
    $_SESSION['show'] = 'vmfds_events_cat_add';
}

function my_action_handler_vmfds_events_cat_edit()
{
    $_SESSION['show'] = 'vmfds_events_cat_edit';
}

function my_action_handler_vmfds_events_cat_del()
{
    global $info;

    db_delete_data('ko_event_categories', 'WHERE (id=' . $_POST['id'] . ')');
    $info = 'vmfds_events_cat_deleted';
    $_SESSION['show'] = 'vmfds_events_cat_list';
}

function my_action_handler_vmfds_events_cat_submit_a()
{
    global $info;

    $cat = vmfds_events_cat_submission();
    db_insert_data('ko_event_categories', $cat);

    // show list:
    $info = 'vmfds_events_cat_added';
    $_SESSION['show'] = 'vmfds_events_cat_list';
}

function my_action_handler_vmfds_events_cat_submit_e()
{
    global $info;

    //Check for edit rights
    //if($access['daten']['MAX'] < 3) return;

    list($table, $columns, $id, $hash) = explode("@", $_POST["id"]);
    if (false === ($id = format_userinput($id, "uint", true))) {
        return;
    }

    $cat = vmfds_events_cat_submission();
    db_update_data('ko_event_categories', 'WHERE (id=' . $id . ')', $cat);

    // show list:
    $info = 'vmfds_events_cat_edited';
    $_SESSION['show'] = 'vmfds_events_cat_list';
}

function vmfds_events_category_form($id)
{
    global $smarty, $KOTA;
    global $access;

    if ($access['daten']['MAX'] < 3) {
        return;
    }

    if (!$id) {
        // new publisher
        $mode = 'neu';
        $id = 0;
    } else {
        // editing, so: preload data
        $mode = 'edit';
    }


    $form_data['title'] = $mode == 'neu' ? getLL('my_vmfds_events_add_category')
        : getLL('my_vmfds_events_edit_category');
    $form_data['submit_value'] = getLL('save');
    $form_data['action'] = ($mode == 'neu' ? 'vmfds_events_cat_submit_a' : 'vmfds_events_cat_submit_e');
    $form_data['cancel'] = 'vmfds_events_cat_list';

    ko_multiedit_formular('ko_event_categories', '', $id, '', $form_data);
}

function my_show_case_vmfds_events_cat_add()
{
    vmfds_events_category_form();
}

function my_show_case_vmfds_events_cat_edit()
{
    vmfds_events_category_form($_POST['id']);
}

function my_show_case_vmfds_events_cat_list()
{
    global $ko_path;
    global $access;

    if ($access['daten']['MAX'] < 3) {
        return;
    }

    $list = new kOOL_listview();

    // no filter!
    $z_where = '';

    // set limits and order
    $z_limit = 'LIMIT ' . ($_SESSION['show_start'] - 1) . ', ' . $_SESSION['show_limit'];
    $rows = db_get_count('ko_event_categories', 'id', $z_where);
    $order = ($_SESSION['sort_eventscats']) ? ' ORDER BY ' . $_SESSION['sort_eventscats'] . ' ' . $_SESSION['sort_eventscats_order']
        : '';

    // get data
    //var_dump($z_where, $order, $z_limit);
    $data = db_select_data('ko_event_categories', 'WHERE 1=1 ' . $z_where, '*',
        $order, $z_limit);

    $list->init('daten', 'ko_event_categories', array("chk", "edit", "delete"),
        $_SESSION["show_start"], $_SESSION["show_limit"]);
    $list->setTitle(getLL('my_event_publisher_list_title'));
    $list->setAccessRights(array('edit' => 3, 'delete' => 3), $access['daten']);
    $list->setActions(array(
            "edit" => array("action" => "vmfds_events_cat_edit"),
            "delete" => array("action" => "vmfds_events_cat_del", "confirm" => true)
        )
    );
    $list->setStats($rows);
    $list->setSort(true, "setsorteventscats", $_SESSION["sort_eventscats"],
        $_SESSION["sort_eventscats_order"]);

    if ($output) {
        $list->render($data);
    } else {
        print $list->render($data);
    }
}


/**
 * Geocode an address using Google's API
 * @param $address
 * @param string $apiKey
 * @return array|bool
 */
function vmfds_events_geocode($address, $apiKey = '')
{
    $url = 'https://maps.google.com/maps/api/geocode/json?address='. urlencode(utf8_encode($address)) . ($apiKey ? '&key=' . urlencode($apiKey) : '');
    $resp = json_decode(file_get_contents($url), true);

    // response status will be 'OK', if able to geocode given address
    if ($resp['status'] == 'OK') {

        // get the important data
        $lat = $resp['results'][0]['geometry']['location']['lat'];
        $lon = $resp['results'][0]['geometry']['location']['lng'];
        $formatted_address = $resp['results'][0]['formatted_address'];

        // verify if data is complete
        if ($lat && $lon && $formatted_address) {
            return [
                'lat' => $lat,
                'lon' => $lon,
                'formatted_address' => $formatted_address,
                'query_url' => $url,
                'response' => $resp,
            ];

        } else {
            return false;
        }

    } else {
        return false;
    }
}


function kota_events_post_location(&$value, &$data, $log, $orig_data)
{
    $value = format_userinput($value, 'text');
    if (!$value) $value = GEOCODE_DEFAULT_ADDRESS;
    if ($value != $orig_data[$data['col']][$data['id']]) {
        $address = str_replace("\n", ', ', str_replace("\r\n", "\n", $value));

        if ($geo = vmfds_events_geocode($address, GOOGLE_API_KEY)) {
            $data['dataset']['my_vmfds_events_nav_address_lat'][$data['id']] = $geo['lat'];
            $data['dataset']['my_vmfds_events_nav_address_lon'][$data['id']] = $geo['lon'];
        }
    }
    if ($data['dataset']['my_vmfds_events_location'][$data['id']] == '')
        $data['dataset']['my_vmfds_events_location'][$data['id']] = GEOCODE_DEFAULT_LOCATION;
}