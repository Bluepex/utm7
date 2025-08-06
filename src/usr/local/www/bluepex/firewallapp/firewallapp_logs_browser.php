<?php
/*
 * suricata_logs_browser.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2006-2020 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2003-2004 Manuel Kasper
 * Copyright (c) 2005 Bill Marquette
 * Copyright (c) 2009 Robert Zelaya Sr. Developer
 * Copyright (c) 2018 Bill Meeks
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("guiconfig.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("firewallapp.inc");
require_once("firewallapp_functions.inc");

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

if (isset($_POST['instance']) && is_numericint($_POST['instance']))
	$instanceid = $_POST['instance'];
elseif (isset($_GET['instance']) && is_numericint($_GET['instance']))
	$instanceid = htmlspecialchars($_GET['instance']);
if (empty($instanceid))
	$instanceid = 0;

if (!is_array($config['installedpackages']['suricata']['rule'])) {
	$config['installedpackages']['suricata']['rule'] = array();
}

$a_instance = $config['installedpackages']['suricata']['rule'];
$suricata_uuid = $a_instance[$instanceid]['uuid'];
$if_real = get_real_interface($a_instance[$instanceid]['interface']);

// Construct a pointer to the instance's logging subdirectory
$suricatalogdir = SURICATALOGDIR . "suricata_{$if_real}{$suricata_uuid}/";

// Limit all file access to just the currently selected interface's logging subdirectory
if (basename($_POST['file']) == 'firewallapp.log') {
		$logfile_ac = 'suricata.log';
} else {
	$logfile_ac = basename($_POST['file']);
}

$logfile = htmlspecialchars($suricatalogdir . $logfile_ac);

if ($_POST['action'] == 'load') {
	if(!is_file($logfile)) {
		echo "|3|" . gettext("Log does not exist or is not enabled") . ".|";
	} else {
		$data1 = file_get_contents($logfile);
		if (isset($_POST['logFilter']) && !empty($_POST['logFilter'])) {
			file_put_contents("/tmp/filter_fapp_log", $data1);
			$data1 = trim(shell_exec("grep -r '{$_POST['logFilter']}' /tmp/filter_fapp_log"));
			unlink("/tmp/filter_fapp_log");
		}
		$data2 = str_replace("Suricata", "FirewallApp", $data1);
		$data = str_replace("suricata", "FirewallApp", $data2);
		if($data === false) {
			echo "|1|" . gettext("Failed to read Log") . ".|";
		} else {
			$data = base64_encode($data);
			echo "|0|{$logfile}|{$data}|";
		}
	}

	exit;
}

if ($_POST['action'] == 'clear') {
	if (basename($logfile) == "sid_changes.log") {
		file_put_contents($logfile, "");
	}

	exit;
}

$pgtitle = array(gettext("FirewallApp"), gettext("Logs"));
$pglinks = array("./firewallapp/services.php", "@self");
include_once("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

function build_instance_list() {
	global $a_instance, $all_gtw;

	$list = array();

	foreach ($a_instance as $id => $instance) {
		if (!in_array(get_real_interface($instance['interface']), $all_gtw)) {
			$list[$id] = '(' . convert_friendly_interface_to_friendly_descr($instance['interface']) . ') ' . $instance['descr'];
		}
	}
	return($list);
}

function build_logfile_list() {
	global $suricatalogdir;

	$list = array();

	$logs = array(
		"alerts.log" => "alerts.log",
		"http.log" => "http.log",
		"alerts_dash_acp.log" => "alerts_dash_acp.log",
		"block.log" => "block.log",
		"eve.json" => "eve.json",
		"suricata.log" => "interface_status.log",
		"suricata2.log" => "interface_status2.log",
		"tls.log" => "tls.log");

	foreach ($logs as $key => $log) {
		$list[$suricatalogdir . $key] = $log;
	}

	return($list);
}

if ($savemsg) {
	print_info_box($savemsg);
}

$form = new Form(false);

$section = new Form_Section('Selecionar Interface');

$section->addInput(new Form_Select(
	'instance',
	"Interface to View",
	$instanceid,
	build_instance_list()
))->setHelp("Choose the interface to view Logs.");

$section->addInput(new Form_Select(
	'logFile',
	"Log Type to View",
	basename($logfile),
	build_logfile_list()
))->setHelp("Choose the Log Type.");

$section->addInput(new Form_Input(
	'logFilter',
	gettext('Filter'),
	'text',
	''
))->setHelp(gettext("Filter LOG file information.<br><p style='color:red;'>NOTE: Click update for the filter to apply to the file if it is already open;</p>"));

// Build the HTML text to display in the StaticText control
$staticContent = '<span style="display:none; " id="fileStatusBox">' .
		'<strong id="fileStatus"></strong>' .
		'</span>' . '<br>' .
		'<p style="padding-right:15px; display:none;" id="fileRefreshBtn">' . 
		'<button type="button" class="btn btn-sm btn-info" name="refresh" id="refresh" onclick="loadFile1();" title="' . 
		gettext("Refresh current display") . '"><i class="fa fa-repeat icon-embed-btn"></i>' . gettext("Refresh") . '</button>&nbsp;&nbsp;' . 
		'<button type="button" class="btn btn-sm btn-danger hidden no-confirm" name="fileClearBtn" id="fileClearBtn" ' . 
		'onclick="clearFile();" title="' . gettext("Clear selected log file contents") . '"><i class="fa fa-trash icon-embed-btn"></i>' . 
		gettext("Clear") . '</button></p>';

$section->addInput(new Form_StaticText(
	'Status',
	$staticContent
));

$form->add($section);

print($form);
?>

<script>
//<![CDATA[
	function loadFile1() {
		$("#fileStatus").html("<?=gettext("Loading ..."); ?>");
		$("#fileStatusBox").show(250);
		$("#filePathBox").show(250);
		$("#fbTarget").html("");

		$.ajax(
				"<?=$_SERVER['SCRIPT_NAME'];?>",
				{
					type: 'post',
					data: {
						instance:  $("#instance").find('option:selected').val(),
						action:    'load',
						file: $("#logFile").val(),
						logFilter: $("#logFilter").val()
					},
					complete: loadComplete1
				}
		);
	}

	function loadComplete1(req) {
		$("#fileContent").show(250);
		var values = req.responseText.split("|");
		values.shift(); values.pop();

		if(values.shift() == "0") {
			var file = values.shift();
			var fileContent = atob(values.join("|"));
			$("#fileStatus").removeClass("text-danger");
			$("#fileStatus").addClass("text-success");
			$("#fileStatus").html("<?=gettext("Log loaded successfully! ");?>");
			$("#fbTarget").removeClass("text-danger");
			$("#fbTarget").html(file);
			$("#fileRefreshBtn").show();
			if (basename(file) == "sid_changes.log") {
				$("#fileClearBtn").removeClass("hidden");
			}
			else {
				$("#fileClearBtn").addClass("hidden");
			}
			$("#fileContent").prop("disabled", false);
			$("#fileContent").val(fileContent);
		}
		else {
			$("#fileStatus").addClass("text-danger");
			$("#fileStatus").html(values[0]);
			$("#fbTarget").addClass("text-danger");
			$("#fbTarget").html("<?=gettext("Not Available"); ?>");
			$("#fileRefreshBtn").hide();
			$("#fileContent").val("");
			$("#fileContent").prop("disabled", true);
		}
	}

	function clearFile() {
		if (confirm("<?=gettext('Are you sure want to erase the log contents?'); ?>")) {
			$.ajax(
				"<?=$_SERVER['SCRIPT_NAME'];?>",
				{
					type: 'post',
					data: {
						instance:  $("#instance").find('option:selected').val(),
						action:    'clear',
						file: $("#logFile").val()
					},
				}
			);
			$("#fileContent").val("");
		}
	}

	function basename(path) {
		return path.replace( /\\/g, '/' ).replace( /.*\//, '' );
	}

events.push(function() {

    //-- Click handlers -----------------------------
    $('#logFile').on('change', function() {
	$("#fbTarget").html("");
        loadFile1();
    });

    $('#instance').on('change', function() {
	$("#fbTarget").html("");
        loadFile1();
    });

    $('#refresh').on('click', function() {
        loadFile1();
    });

    //-- Show nothing on initial page load -----------
<?php if(empty($_POST['file'])): ?>
	document.getElementById("logFile").selectedIndex=-1;
<?php endif; ?>

});
//]]>
</script>

<div class="panel panel-default" id="fileOutput">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("LOG CONTENT")?></h2></div>
		<div class="panel-body">
			<textarea id="fileContent" name="fileContent" style="width:100%;" rows="20" wrap="off" disabled></textarea>
		</div>
</div>

<?php include("foot.inc"); ?>

