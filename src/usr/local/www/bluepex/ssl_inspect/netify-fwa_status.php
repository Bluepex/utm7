<?php
require_once('guiconfig.inc');
require_once('/usr/local/pkg/netify-fwa/netify-fwa.inc');

if ($_POST['status'] == 'update') {
    $status = array(
        'version' => NETIFY_FWA_VERSION,
        'running' => netify_fwa_is_running(),
        'status' => array()
    );

    if (file_exists(NETIFY_FWA_JSON_STATUS)) {
        $status['status'] = json_decode(
            file_get_contents(NETIFY_FWA_JSON_STATUS)
        );
    }
    else {
        $status['error'] = 'FWA status file not found.';
    }

    $response = json_encode($status);
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($response));

    echo $response;
    exit;
}
else if ($_GET['update'] == 'blocks') {
/*
  {
    "timestamp": 1574718115,
    "type": "block",
    "protocol": 0,
    "application": 119,
    "protocol_category": 0,
    "application_category": 0,
    "flows": 1
  }
*/
    $entries = array();

    if (file_exists(NETIFY_FWA_JSON_STATUS_MATCHES)) {

        $app_proto_data = json_decode(
            file_get_contents(NETIFY_FWA_JSON_APP_PROTO_DATA), true
        );

        $entries = array();
        $matches = json_decode(
            file_get_contents(NETIFY_FWA_JSON_STATUS_MATCHES), true
        );

        if ($matches !== false) {

            foreach ($matches as $match) {

                $icon = '';
                $ids = array();

                if ($match['protocol'] > 0)
                    $ids[] = $app_proto_data['protocols'][$match['protocol']]['label'];
                if ($match['application'] > 0) {
                    $ids[] = $app_proto_data['applications'][$match['application']]['label'];
                    $icon = netify_fwa_load_icon($app_proto_data['applications'][$match['application']]['icon']);
                }
                if ($match['protocol_category'] > 0)
                    $ids[] = $app_proto_data['protocol_category'][$match['protocol_category']]['label'];
                if ($match['application_category'] > 0)
                    $ids[] = $app_proto_data['application_category'][$match['application_category']]['label'];

                $entry = array(
                    $icon,
                    implode('/', $ids),
                    ucwords($match['type']),
                    number_format($match['flows']),
                    strftime('%x %X', $match['timestamp'])
                );

                $entries[] = $entry;
            }
        }
    }

    $data = array('data' => $entries);

    $response = json_encode($data);
    //header('Content-Type: application/json');
    //header('Content-Length: ' . strlen($response));

    echo $response;
    exit;
}

$pglinks = array("", "./ssl_inspect/ssl_inspect.php", "@self");
$pgtitle = array(gettext("Services"), gettext("SSL Inspect"), gettext("Status"));

include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Real Time"), false, "./ssl_inspect.php");
$tab_array[] = array(gettext("Registers"), false, "./ssl_inspect_registers.php");
$tab_array[] = array(gettext("Tables Custom Rules"), false, "./tables_custom.php");
$tab_array[] = array(gettext("Status"), true, "./netify-fwa_status.php");
$tab_array[] = array(gettext("Applications"), false, "./netify-fwa_apps.php");
$tab_array[] = array(gettext("Protocols"), false, "./netify-fwa_protos.php");
$tab_array[] = array(gettext("Whitelist"), false, "./netify-fwa_whitelist.php");

display_top_tabs($tab_array, true);

?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title"><?=gettext("Netify Firewall Agent")?></h2>
    </div>
    <div class="panel-body">
        <div class="content table-responsive">
            <table id="maintable" class="table table-hover table-condensed">
                <tr style="border-bottom: 1px solid black">
                    <!--<th><?=gettext("Version")?></th>
                    <td id="fwa_version"></td>-->
                    <th><?=gettext("Status")?></th>
                    <th style="border-left:1px solid black;"><?=gettext("Active Flows")?></th>
                    <td id="flows_active"></td>
                    <th style="border-left:1px solid black;"><?=gettext("Recently Blocked")?></th>
                    <td id="flows_blocked"></td>
                </tr>
                <tr>
                    <td id="fwa_status">Unknown</td>
                    <th style="border-left:1px solid black;"><?=gettext("Uptime")?></th>
                    <td id="fwa_uptime">Unknown</td>
                    <th style="border-left:1px solid black;"><?=gettext("Total Blocked")?></th>
                    <td id="flows_blocked_total"></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title"><?=gettext("Recent Activity")?></h2>
    </div>
    <div class="panel-body">
        <div class="content table-responsive">
            <table id="activitytable" class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th style="width: 1%;"></th>
                        <th><?=gettext("Application/Protocol")?></th>
                        <th><?=gettext("Action")?></th>
                        <th><?=gettext("Flows")?></th>
                        <th><?=gettext("Last Event")?></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include("foot.inc");
?>
<link rel="stylesheet" type="text/css" href="./css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="./css/dataTables.fontAwesome.css">
<link rel="stylesheet" type="text/css" href="./css/netify-fwa.css">
<script type="text/javascript" charset="utf8" src="./js/jquery.dataTables.min.js"></script>

<script type="text/javascript">
//<![CDATA[

    var activityTable = undefined;

    $(document).ready(function() {
        activityTable = $('#activitytable').DataTable({
            'dom': 'tipr',
            'columnDefs': [{
                'targets': 0,
                'orderable': false,
                'render': function(data, type, row, meta) {
                    if (data.length > 0)
                        return '<img src="' + data + '" style="width: 1.5em;">';
                    else
                        return '&nbsp;'
                }
            }],
            'order': [[ 4, 'desc' ], [ 1, 'asc' ]],
            'pageLength': 25,
            'ajax': "<?=$_SERVER['SCRIPT_NAME'];?>?update=blocks",
            'oLanguage': {
                'sEmptyTable': "<?=gettext('No recent activty.')?>"
            }
        });

        setInterval(function() {
            activityTable.ajax.reload(null, false);
        }, 7250);
    });

    function statusRequest() {

        $.ajax(
            "<?=$_SERVER['SCRIPT_NAME'];?>",
            {
                type: 'post',
                data: {
                    status: 'update'
                },
                success: statusUpdate,
                complete: function() {
                    setTimeout(statusRequest, 2000);
                }
            }
        );
    }

    function uptime(seconds) {
        var days = 0, hours = 0, minutes = 0;

        if (seconds >= 86400) {
            days = Math.floor(seconds / 86400);
            seconds -= days * 86400;
        }

        if (seconds >= 3600) {
            hours = Math.floor(seconds / 3600);
            seconds -= hours * 3600;
        }

        if (seconds >= 60) {
            minutes = Math.floor(seconds / 60);
            seconds -= minutes * 60;
        }

        return days + 'd ' +
            hours.toString().padStart(2, '0') + ':' +
            minutes.toString().padStart(2, '0') + ':' +
            seconds.toString().padStart(2, '0');
    }

    function statusUpdate(responseData) {
        /*
        {
          "uptime": 12841,
          "flows": 47,
          "blocked": 0,
          "prioritized": 0,
          "blocked_total": 54,
          "prioritized_total": 0
        }
        */
        /*console.log('statusUpdate:');

        for(var key in responseData.status) {
            console.log(
                'key: ' + key +
                ', value: ' + responseData.status[key]
            );
        }
        */

        //$('#fwa_version').html('v' + responseData.version);
        $('#fwa_status').html(responseData.running ? 'Running' : 'Stopped');
        $('#fwa_status').addClass(
            responseData.running ? 'text-success' : 'text-danger'
        );
        $('#fwa_status').removeClass(
            responseData.running ? 'text-danger' : 'text-success'
        );
        $('#flows_active').html(responseData.status['flows'].toLocaleString());
        $('#fwa_uptime').html(uptime(responseData.status['uptime']));
        $('#flows_blocked').html(responseData.status['blocked'].toLocaleString());
        $('#flows_blocked_total').html(
            responseData.status['blocked_total'].toLocaleString()
        );
    }

    setTimeout(statusRequest, 1000);
//]]>
</script>