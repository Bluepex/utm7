<?php
require_once('guiconfig.inc');
require_once('/usr/local/pkg/netify-fwa/netify-fwa.inc');

if (array_key_exists('update_rule', $_POST)) {

    $result = true;

    $parts = explode('-', $_POST['update_rule']);

    $rule = array('type' => 'block');

    switch ($parts[2]) {
    case 'application':
        $rule['application'] = intval($parts[3]);
        break;
    case 'protocol':
        $rule['protocol'] = intval($parts[3]);
        break;
    case 'application_category':
        $rule['application_category'] = intval($parts[3]);
        break;
    case 'protocol_category':
        $rule['protocol_category'] = intval($parts[3]);
        break;
    default:
        $result = false;
    }

    netify_fwa_update_rule($rule);

    $response = json_encode(
        array('result' => $result, 'table' => $parts[2])
    );
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($response));

    echo $response;
    exit;
}
else if (array_key_exists('rules', $_GET)) {
    $rules = array();
    $conf = netify_fwa_load_conf();

    function action_button($id, $type, $entry) {

        $action = ($entry['type'] == 'block') ? 'unblock' : 'block';

        $btn_id = "btn-${action}-${type}-${id}";
        $btn_text = ($action == 'block') ?
            gettext('Block') : gettext('Unblock');
        $btn_class = ($action == 'block') ?
            'btn-danger' : 'btn-success';
        $btn_tooltip = ($action == 'block') ?
            gettext('Block') . " ${entry[label]}.":
            gettext('Unblock') . " ${entry[label]}.";

        return sprintf('<button id="%s" class="btn %s" style="width: 5em;" ' .
            'type="button" title="%s">%s</button>',
            $btn_id, $btn_class, $btn_tooltip, $btn_text
        );
    }

    if ($_GET['rules'] == 'application') {
        foreach ($conf['applications'] as $id => $entry) {
            $rule = array(
                netify_fwa_load_icon($entry['icon']), $entry['label'],
                    action_button($id, 'application', $entry)
            );

            $rules[] = $rule;
        }
    }
    else if ($_GET['rules'] == 'application_category') {
        foreach ($conf['application_category'] as $id => $entry) {
            $action = array($id, $entry['type']);
            $rule = array(
                '', $entry['label'],
                    action_button($id, 'application_category', $entry)
            );

            $rules[] = $rule;
        }
    }

    $response = json_encode(array('data' => $rules), true);
    //header('Content-Type: application/json');
    //header('Content-Length: ' . strlen($response));

    echo $response;
    exit;
}

$pglinks = array("", "./ssl_inspect/ssl_inspect.php", "@self");
$pgtitle = array(gettext("Services"), gettext("SSL Inspect"), gettext("Applications"));

include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Real Time"), false, "./ssl_inspect.php");
$tab_array[] = array(gettext("Registers"), false, "./ssl_inspect_registers.php");
$tab_array[] = array(gettext("Tables Custom Rules"), false, "./tables_custom.php");
$tab_array[] = array(gettext("Status"), false, "./netify-fwa_status.php");
$tab_array[] = array(gettext("Applications"), true, "./netify-fwa_apps.php");
$tab_array[] = array(gettext("Protocols"), false, "./netify-fwa_protos.php");
$tab_array[] = array(gettext("Whitelist"), false, "./netify-fwa_whitelist.php");


display_top_tabs($tab_array, true);

?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title"><?=gettext("Applications")?></h2>
    </div>
    <div class="panel-body">
        <div class="content table-responsive">
            <table id="apptable" class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th style="width: 1%;"></th>
                        <th><?=gettext("Application")?></th>
                        <th style="width: 1%;"><?=gettext("Action")?></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title"><?=gettext("Application Categories")?></h2>
    </div>
    <div class="panel-body">
        <div class="content table-responsive">
            <table id="appcattable" class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th style="width: 1%;"></th>
                        <th><?=gettext("Category")?></th>
                        <th style="width: 1%;"><?=gettext("Action")?></th>
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
    var appTable = undefined;
    var appcatTable = undefined;

    function init() {
        $(document).ready(function() {
            //console.log('DOM ready.');
            //$('[id^=btn-]').click(function() {
            $(document).on('click', '[id^=btn-]', '', function() {
                //console.log('Block/unblock clicked: ' + event.target.id);
                updateRule(event.target.id);
                return false;
            });
        });
    }

    $(document).ready(function() {
        appTable = $('#apptable').DataTable({
            'columnDefs': [{
                'targets': 0,
                'orderable': false,
                'render': function(data, type, row, meta) {
                    if (data.length > 0)
                        return '<img src="' + data + '" style="width: 1.5em;">';
                    else
                        return '&nbsp;'
                }
            }, {
                'targets': 2,
                'orderable': false
            }],
            'order': [[ 1, 'asc' ]],
            'pageLength': 10,
            'ajax': "<?=$_SERVER['SCRIPT_NAME'];?>?rules=application",
            'oLanguage': {
                'sEmptyTable': "<?=gettext('No applications defined.')?>"
            }
        });

        appcatTable = $('#appcattable').DataTable({
            'columnDefs': [{
                'targets': 0,
                'orderable': false,
                'render': function(data, type, row, meta) {
                    if (data.length > 0)
                        return '<img src="' + data + '" style="width: 1.5em;">';
                    else
                        return '&nbsp;'
                }
            }, {
                'targets': 2,
                'orderable': false
            }],
            'order': [[ 1, 'asc' ]],
            'pageLength': 10,
            'ajax': "<?=$_SERVER['SCRIPT_NAME'];?>?rules=application_category",
            'oLanguage': {
                'sEmptyTable': "<?=gettext('No application categories defined.')?>"
            }
        });
    });

    function updateRule(param) {

        $.ajax(
            "<?=$_SERVER['SCRIPT_NAME'];?>",
            {
                type: 'post',
                data: {
                    update_rule: param
                },
                success: refreshTable
            }
        );
    }

    function refreshTable(data) {
        //console.log(data);

        if (data['result'] && data['table'] == 'application')
            appTable.ajax.reload(null, false);
        else if (data['result'] && data['table'] == 'application_category')
            appcatTable.ajax.reload(null, false);
    }

    setTimeout(init, 500);
//]]>
</script>