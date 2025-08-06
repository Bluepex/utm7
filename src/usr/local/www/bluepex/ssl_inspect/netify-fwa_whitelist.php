<?php
require_once('guiconfig.inc');
require_once('/usr/local/pkg/netify-fwa/netify-fwa.inc');

if (array_key_exists('add_whitelist', $_POST)) {

    $result = netify_fwa_add_whitelist_entry(
        $_POST['add_whitelist']
    );

    $response = json_encode(
        array('result' => $result)
    );
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($response));

    echo $response;
    exit;
}
if (array_key_exists('delete_whitelist', $_POST)) {

    $result = true;

    $parts = explode('-', $_POST['delete_whitelist']);

    switch ($parts[2]) {
    case 'whitelist':
        $id = intval($parts[3]);
        break;
    default:
        $result = false;
    }

    if ($result)
        $result = netify_fwa_delete_whitelist_entry($id);

    $response = json_encode(
        array('result' => $result)
    );
    header('Content-Type: application/json');
    header('Content-Length: ' . strlen($response));

    echo $response;
    exit;
}
else if (array_key_exists('rules', $_GET)) {
    $rules = array();
    $whitelist = netify_fwa_load_whitelist();

    function action_button($id, $type) {

        $action = 'delete';

        $btn_id = "btn-${action}-${type}-${id}";
        $btn_text = gettext('Delete');
        $btn_class = 'btn-danger';
        $btn_tooltip = gettext('Delete host from whitelist.');

        return sprintf('<button id="%s" class="btn %s" style="width: 5em;" ' .
            'type="button" title="%s">%s</button>',
            $btn_id, $btn_class, $btn_tooltip, $btn_text
        );
//        return sprintf('<button id="%s" class="btn %s" ' .
//            'type="button" title="%s"><i class="fa fa-trash"></i></button>',
//            $btn_id, $btn_class, $btn_tooltip
//        );
    }

    foreach ($whitelist as $id => $entry) {
        $rule = array($entry['address'], action_button($id, 'whitelist'));
        $rules[] = $rule;
    }

    $response = json_encode(array('data' => $rules), true);
    //header('Content-Type: application/json');
    //header('Content-Length: ' . strlen($response));

    echo $response;
    exit;
}

$pglinks = array("", "./ssl_inspect/ssl_inspect.php", "@self");
$pgtitle = array(gettext("Services"), gettext("SSL Inspect"), gettext("Whitelist"));

include("head.inc");

$tab_array = array();
$tab_array[] = array(gettext("Real Time"), false, "./ssl_inspect.php");
$tab_array[] = array(gettext("Registers"), false, "./ssl_inspect_registers.php");
$tab_array[] = array(gettext("Tables Custom Rules"), false, "./tables_custom.php");
$tab_array[] = array(gettext("Status"), false, "./netify-fwa_status.php");
$tab_array[] = array(gettext("Applications"), false, "./netify-fwa_apps.php");
$tab_array[] = array(gettext("Protocols"), false, "./netify-fwa_protos.php");
$tab_array[] = array(gettext("Whitelist"), true, "./netify-fwa_whitelist.php");

display_top_tabs($tab_array, true);

?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title"><?=gettext("Whitelist")?></h2>
    </div>
    <div class="panel-body">
        <div class="content table-responsive">
            <table id="whitelisttable" class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th><?=gettext("Address")?></th>
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
        <h2 class="panel-title"><?=gettext("Add Whitelist Entry")?></h2>
    </div>
    <div class="panel-body">
        <div class="content table-responsive">
            <table id="addentrytable" class="table table-striped table-condensed">
                <thead>
                    <tr>
                        <th colspan="2"><?=gettext("Address")?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="whitelist-error" style="display: none;">
                        <td colspan="2" class="text-danger"><?=gettext("There was an error adding the address.");?></td>
                    </tr>
                    <tr id="whitelist-success" style="display: none;">
                        <td colspan="2" class="text-success"><?=gettext("Successfully added address to the whitelist.");?></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: bottom;"><input id="whitelist-address" type="text" class="form-control"></input></td>
                        <td style="width: 1%; padding-right: 0.5em;">
                            <button id="btn-whitelist-add" type="button" class="btn btn-success form-control">
                                <i class="fa fa-plus icon-embed-btn"></i><?=gettext("Add");?>
                            </button>
                        </td>
                    </tr>
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
    var whitelistTable = undefined;
    var whitelistAddress = undefined;

    function init() {
        $(document).ready(function() {
            //console.log('DOM ready.');

            $("#btn-whitelist-add").click(function() {
                //console.log('Add clicked: ' + whitelistAddress);

                addWhitelistEntry(whitelistAddress);
                return false;
            });

            $(document).on('click', '[id^=btn-delete-]', '', function() {
                //console.log('Delete clicked: ' + event.target.id);
                deleteWhitelistEntry(event.target.id);
                return false;
            });

            $("#whitelist-address").blur(function() {
                whitelistAddress = $("#whitelist-address").val();

                $("#whitelist-address").val("<?=gettext('Enter an IP v4/6 address; CIDR notation permitted.');?>");
                $("#whitelist-address").attr('readonly', true);
            });

            $("#whitelist-address").focus(function() {
                $("#whitelist-address").val('');
                $("#whitelist-address").attr('readonly', false);
            });

            $("#whitelist-address").blur();
        });
    }

    $(document).ready(function() {
        whitelistTable = $('#whitelisttable').DataTable({
            'dom': 'tipr',
            'columnDefs': [{
                'targets': 1,
                'orderable': false
            }],
            'pageLength': 10,
            'ajax': "<?=$_SERVER['SCRIPT_NAME'];?>?rules=whitelist",
            'oLanguage': {
                'sEmptyTable': "<?=gettext('No whitelist addresses defined.')?>"
            }
        });
    });

    function addWhitelistEntry(param) {

        $.ajax(
            "<?=$_SERVER['SCRIPT_NAME'];?>",
            {
                type: 'post',
                data: {
                    add_whitelist: param
                },
                success: addWhitelistEntryResult
            }
        );
    }

    function deleteWhitelistEntry(param) {

        $.ajax(
            "<?=$_SERVER['SCRIPT_NAME'];?>",
            {
                type: 'post',
                data: {
                    delete_whitelist: param
                },
                success: refreshTable
            }
        );
    }

    function addWhitelistEntryResult(data) {
        if (data['result']) {
            $('#whitelist-error').hide();
            $('#whitelist-success').show();

            refreshTable(data);
        }
        else {
            $('#whitelist-error').show();
            $('#whitelist-success').hide();
        }
    }

    function refreshTable(data) {
        if (data['result'])
            whitelistTable.ajax.reload(null, false);
    }

    setTimeout(init, 500);
//]]>
</script>
