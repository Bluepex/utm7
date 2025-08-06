<?php
/* ====================================================================
* Copyright (C) BluePex Security Solutions - All rights reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
* Rewritten by Guilherme R. Brechot <guilherme.brechot@bluepex.com>, 2023
*
* ====================================================================
*
*/

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("system.inc");
require_once("bp_webservice.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("bp_auditing.inc");

define("IF_EX_WAN", "{$g['etc_path']}/if_ex_wan.conf");

if ($_POST['save']) {
	$exceptions_in_file = file_exists(IF_EX_WAN) ? array_unique(array_filter(explode(",", file_get_contents(IF_EX_WAN)))) : [];
	$exceptions_post = [];

	foreach (array_unique(array_filter(explode(",", $_POST['exceptions']))) as $exceptions_now) {
		$exceptions_now = str_replace("_",".",$exceptions_now);
		$exceptions_post[] = $exceptions_now;
	}

	$exceptions = [];
	$change_interfaces = array_diff($exceptions_in_file, $exceptions_post);

	foreach ($change_interfaces as $exceptions_now) {
		$exceptions[] = $exceptions_now;
	}

	$change_interfaces = array_diff($exceptions_post, $exceptions_in_file);

	foreach ($change_interfaces as $exceptions_now) {
		$exceptions[] = $exceptions_now;
	}
	
	//Get changes in state post to operation 
	$out_interfaces = isset($config['installedpackages']['suricata']['rule']) ? $config['installedpackages']['suricata']['rule'] : [];

	foreach ($out_interfaces as $key => $suricata_interface) {
		$if = get_real_interface($interface['interface']);

		if (!in_array($if, $exceptions)) {
			continue;
		}

		$uuid = $suricata_interface['uuid'];
		suricata_stop($suricata_interface, $if);

		if (isset($suricata_interface["mixed_mode"]) &&
		    $suricata_interface["mixed_mode"] == "on") {
			mwexec("kill -9 `/bin/ps ax |".
			    " /usr/bin/grep suricata_{$uuid}_{$if} |" .
			    " /usr/bin/grep 'suricata2.yaml' |" .
			    " /usr/bin/grep -v grep |".
			    " /usr/bin/awk -F\" \" '{print $1}'`");
		}

		if (isset($config['installedpackages']['suricata']['rule'][$key]['mixed_mode'])) {
			unset($config['installedpackages']['suricata']['rule'][$key]['mixed_mode']);
		}

		write_config("Bluepex: Active Protection: Remove mixed mode {$if}!!!");
	}

	$remove_exceptions = array_diff($exceptions_in_file, $exceptions_post);
	$add_exceptions = array_diff($exceptions_post, $exceptions_in_file);

	if (!empty($remove_exceptions)) {
		bp_write_report_db("report_0008_acp_fapp_gtw_fapp_to_acp", implode(",", $remove_exceptions));
	}

	if (!empty($add_exceptions)) {
		bp_write_report_db("report_0008_acp_fapp_gtw_acp_to_fapp", implode(",", $add_exceptions));
	}

	file_put_contents(IF_EX_WAN, $_POST['exceptions']);
	file_put_contents("{$g['etc_path']}/tw_type", $_POST['tw_type']);
	file_put_contents("{$g['etc_path']}/tw_includes", $_POST['tw_includes']);

	$model = intval(substr(trim(file_get_contents("{$g['etc_path']}/model")),-4));
	$extra_path = ($model >= 4000) ? "tunning" : "original";
	copy("/usr/local/www/active_protection/yaml/{$extra_path}/suricata_yaml_template.inc", "/usr/local/pkg/suricata/suricata_yaml_template.inc");
	copy("/usr/local/www/active_protection/yaml/{$extra_path}/suricata_yaml_template_acp.inc", "/usr/local/pkg/suricata/suricata_yaml_template_acp.inc");
}

if (!file_exists(IF_EX_WAN)) {
	file_put_contents(IF_EX_WAN);
}
$all_gtw = getInterfacesInGatewaysWithNoExceptions();
$all_interfaces = get_configured_interface_with_descr(true);
$exp = file_get_contents(IF_EX_WAN);
$already_in_exception = str_replace(".","_",$exp);
$already_in_exception = array_unique(array_filter(explode(',', $already_in_exception)));

function list_interfaces() {
	global $all_gtw, $all_interfaces, $already_in_exception;

	$return_buttons = [];

	foreach($all_interfaces as $key_real => $descr) {
		$real_interface = get_real_interface($key_real);
		$return_buttons["{$real_interface}"] = "{$descr} ({$real_interface})";
	}

	return $return_buttons;
}

function generate_js_clicks() {
	global $all_gtw, $all_interfaces, $already_in_exception;

	$interfaces_no_exception = list_interfaces();

	foreach($interfaces_no_exception as $key_real => $descr) {
		$key_real = str_replace(".","_",$key_real);
		echo "\$(\"#interface_{$key_real}\").click(function(event) { event.preventDefault() });\n";
	}
}

function generate_buttons() {
	global $all_gtw, $all_interfaces, $already_in_exception;

	$interfaces_no_exception = list_interfaces();

	foreach($interfaces_no_exception as $key_real => $descr) {
		$key_real = str_replace(".","_",$key_real);
		if (in_array($key_real, $already_in_exception)) {
			$return_buttons .= "<button class='btn btn-success scan_select_interface' type='click' style='margin: 5px; border-radius:5px; text-transform: uppercase;' id='interface_{$key_real}' onclick=\"disabled_select_value_operation_scan('{$key_real}')\">{$descr}</button>";
		} else {
			$return_buttons .= "<button class='btn btn-primary scan_select_interface' type='click' style='margin: 5px; border-radius:5px; text-transform: uppercase;' id='interface_{$key_real}' onclick=\"select_value_operation_scan('{$key_real}')\">{$descr}</button>";
		}
	}

	return $return_buttons;
}

$pgtitle = array(gettext("Active Protection"), gettext("Exceptions"));
$pglinks = array("./active_protection/ap_services.php", "@self");
include("head.inc");

if ($_POST['save']) {
	print_info_box(gettext("Successfully saved exceptions"), 'success');
}

?>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div>
			<p><?=gettext("Exceptions will no longer be part of the Active Protection application")?></p>
			<p><?=gettext("Options for inserting interfaces are listed in 'presentation mode', where:")?></p>
			<ul>
				<li><?=gettext("Simple mode -> Blocks manual input and only enables input by selecting buttons;")?></li>
				<li><?=gettext("Advanced mode -> Enable manual insertion of interfaces;")?></li>
			</ul>
		</div>
	</div>
</div>

<?php
$tw_type = array(
	'lite' => 'lite',
	'full' => 'full',
	'acp_av' => 'acp_av'
);

$tw_includes = array(
	'with traffic' => 'with traffic',
	'only' => 'only'
);

$tw_type_value = file_get_contents("{$g['etc_path']}/tw_type");
$tw_includes_value = file_get_contents("{$g['etc_path']}/tw_includes");

$form = new Form;
$form->setMultipartEncoding();
$section = new Form_Section(gettext('Gateways Exceptions Config'));

$section->addInput(new Form_Input(
	'exceptions',
	gettext('*Exceptions'),
	'text',
	$exp,
	['placeholder' => gettext('Exceptions'), 'disabled' => true, 'onkeypress' => 'change_manual_exception()', 'onkeyup' => 'change_manual_exception()', 'onkeydown' => 'change_manual_exception()']
))->setHelp(gettext('Interface Gateways Exceptions List. Ex: em0,em1'));

$section->addInput(new Form_Select(
	'mode_select_exceptions',
	gettext('Presentation Mode'),
	'',
	array('simple' => 'Simples', 'advanced' => 'Advanced')
))->setHelp(gettext('Select how to populate interfaces in exception'));

$section->addInput(new Form_StaticText(
	gettext('Interfaces'),
	generate_buttons()
))->setHelp(gettext("Select the interfaces to exception in Active Protection"));

$section->addInput(new Form_Select(
	'tw_includes',
	'*Includes Traffic',
	$tw_includes_value,
	$tw_includes
))->setHelp(gettext('Includes Traffic'));

$model = intval(substr(trim(file_get_contents("{$g['etc_path']}/model")),-4));
$testing_mode = file_get_contents("{$g['etc_path']}/mode");

if ($model >= 4000 ||
    $testing_mode == "test") {

	$section->addInput(new Form_Select(
		'tw_type',
		'*Type Book',
		$tw_type_value,
		$tw_type
	))->setHelp(gettext('Type Base'));

}

$form->add($section);

print $form;
	
include("foot.inc");
?>

<script>
let array_mark_interface = [];
<?php 
if (!empty($already_in_exception)) {
	foreach($already_in_exception as $line_now) {
?>
	array_mark_interface.push('<?=str_replace("_",".",$line_now)?>');
<?php 
	}
}
?>

$("#mode_select_exceptions").change(function() {
	if ($("#mode_select_exceptions").val() == "simple") {
		$("#exceptions").attr("disabled", true);
	} else {
		$("#exceptions").removeAttr("disabled");
	}
});

$("#save").click(function() {
	$("#exceptions").removeAttr("disabled");
});

function change_manual_exception() {
	array_mark_interface = [];
	values_change = [];

	for(var counter=0; counter <= parseInt($("[class='btn btn-success scan_select_interface']").length)-1; counter++) {
		values_change.push(($($("[class='btn btn-success scan_select_interface']")[counter]).get(0).id).split('interface_')[1]);
	}

	for(var counter=0; counter <= parseInt(values_change.length)-1; counter++) {
		$("#interface_"+values_change[counter]).removeAttr('onclick').attr("onclick", "select_value_operation_scan('" + values_change[counter] + "')");
		$("#interface_"+values_change[counter]).removeAttr('class').attr("class", "btn btn-primary scan_select_interface");
	}

	values_exception = $("#exceptions").val().split(',');

	for(var counter=0; counter <= parseInt(values_exception.length)-1; counter++) {
		var interface = values_exception[counter];
		if (parseInt($("#interface_" + interface).length) >= 1) {
			$("#interface_" + interface).removeAttr('class').attr("class", "btn btn-success scan_select_interface");
			$("#interface_" + interface).removeAttr('onclick').attr("onclick", "disabled_select_value_operation_scan('" + interface + "')");
			if (!array_mark_interface.includes(interface)) {
				array_mark_interface.push(interface);
			}
		} 
	}
}

function disabled_select_value_operation_scan(interface) {
	$("#interface_" + interface).removeAttr('class').attr("class", "btn btn-primary scan_select_interface");
	$("#interface_" + interface).removeAttr('onclick').attr("onclick", "select_value_operation_scan('" + interface + "')");
	var cleanarray_mark_interface = [];
	interface = interface.replace("_",".");

	if (array_mark_interface.includes(interface)) {
		for(var counter=0;counter <= parseInt(array_mark_interface.length)-1;counter++) {
			if (array_mark_interface[counter] != interface) {
				cleanarray_mark_interface.push(array_mark_interface[counter]);
			}
		}
	}

	array_mark_interface = cleanarray_mark_interface;
	$("#exceptions").val(array_mark_interface.join(','));
}

function select_value_operation_scan(interface) {
	$("#interface_" + interface).removeAttr('class').attr("class", "btn btn-success scan_select_interface");
	$("#interface_" + interface).removeAttr('onclick').attr("onclick", "disabled_select_value_operation_scan('" + interface + "')");

	if (!array_mark_interface.includes(interface)) {
		interface = interface.replace("_",".");
		array_mark_interface.push(interface);
	}

	$("#exceptions").val(array_mark_interface.join(','));
}


<?=generate_js_clicks()?>

</script>
