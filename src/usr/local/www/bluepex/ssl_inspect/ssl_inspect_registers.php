<?php
/*
 * ssl_inspect.php
 * 
 * Copyright (C) 2022 Guilherme R.Brechot <guilherme.brechot@bluepex.com>
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

require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");
require_once("guiconfig.inc");
require_once("bluepex/firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

init_config_arr(array('installedpackages', 'suricata', 'rule'));
$suricataglob = $config['installedpackages']['suricata'];
$a_rule = &$config['installedpackages']['suricata']['rule'];
init_config_arr(array('gateways', 'gateway_item'));
$a_gtw = &$config['gateways']['gateway_item'];
$a_gateways = return_gateways_array(true, false, true, true);

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

$pgtitle = array(gettext("Services"), gettext("SSL Inspect - Registers"));
$pglinks = array("", "@self");
include("head.inc");

if (is_array($input_erros) && count($input_erros)) {
    print_input_errors($input_erros);
}

$tab_array = array();
$tab_array[] = array(gettext("Real Time"), false, "./ssl_inspect.php");
$tab_array[] = array(gettext("Registers"), true, "./ssl_inspect_registers.php");
$tab_array[] = array(gettext("Tables Custom Rules"), false, "./tables_custom.php");
$tab_array[] = array(gettext("Status"), false, "./netify-fwa_status.php");
$tab_array[] = array(gettext("Applications"), false, "./netify-fwa_apps.php");
$tab_array[] = array(gettext("Protocols"), false, "./netify-fwa_protos.php");
$tab_array[] = array(gettext("Whitelist"), false, "./netify-fwa_whitelist.php");

display_top_tabs($tab_array);

?>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
			<?=gettext("Analysis already performed by SSL inspection.")?>
			<br>
			<?=gettext("The data presented is constantly cleaned according to the default values ​​set within the limits of Active Protection/FirewallAPP, if you want the data to remain longer, increase the file limit.")?>
			<br>
			<br>
			<p><i class="fa fa-times"></i> - <?=gettext("Clears search fields;")?></p>
			<p><i class="fa fa-pause"></i> - <?=gettext("Pauses updating table information automatically;")?></p>
			<p><i class="fa fa-refresh"></i> - <?=gettext("Updates the information displayed in the table based on the values ​​set within the \"search\" and \"display limit\" field, it is worth mentioning that if the pause button is pressed, the update action will not occur;")?></p>
			<p><i class="fa fa-search"></i> - <?=gettext("Search for values ​​presented within the table based on the \"search\" field;")?></p>
			<p><i class='fa fa-plus-circle'></i> - <?=gettext("Action to generate a rule for Active Protection/FirewallAPP, this option is only available for traffic like HTTP and HTTP/S")?></p>
		</div>
	</div>
</div>

<hr>

<?php
if (file_exists('/usr/local/sbin/netifyd')) {
?>
	<style>
	.table > thead > tr > th {
		border-bottom-width: 2px;
		background: #108ad0;
		color: #fff;
		text-align: center!important;
		padding: 7px;
		font-size: 14px!important;
	}
	</style>

	<div id="divProcess"></div>
	<div style="margin-bottom: 60px;">
		<button type="click" onclick="clearSearchInspect()" class="btn btn-danger form-control find-values" style="width: auto; float:right;"><i class="fa fa-times"></i> </button>
		<button type="click" onclick="StopSearchInspect()" id="StopSearchInspect" class="btn btn-secondary form-control find-values" style="width: auto; float:right;"><i class="fa fa-pause"></i> </button>
		<button type="click" onclick="showProcess()" class="btn btn-success form-control find-values" style="width: auto; float:right;"><i class="fa fa-refresh"></i> </button>
		<button type="click" onclick="searchTableInspect()" class="btn btn-primary form-control find-values" style="width: auto; float:right;"><i class="fa fa-search"></i> </button>
		<input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:400px;" id="search-inspect" placeholder="<?=gettext("Search for...")?>">
		<select class="form-control" style="float:right;width:200px;" id="search-inspect-select">
			<option value="100">100</option>
			<option value="250">250</option>
			<option value="500">500</option>
			<option value="1000">1000</option>
		</select>
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th><?=gettext("Date")?></th>
					<th><?=gettext("IP Source")?></th>
					<th><?=gettext("Port Source")?></th>
					<th><?=gettext("IP External")?></th>
					<th><?=gettext("Port External")?></th>
					<th><?=gettext("Protocol")?></th>
					<th><?=gettext("Address")?></th>
					<th><?=gettext("Action")?></th>
				</tr>
			</thead>
			<tbody id="showProcess">
			</tbody>
		</table>
	</div>

	<form action="ssl_inspect.php" method="POST" style="display:none;" id="submitStopProcess">
		<input type="hidden" value="" name="stopProcessNetifyd" id="stopProcessNetifyd"/>
	</form>

	<form action="custom_rule_ssl.php" method="POST" style="display:none;" id="createRule">
		<input type="hidden" value="" name="desc_interface" id="desc_interface"/>
		<input type="hidden" value="" name="interface" id="interface"/>
		<input type="hidden" value="" name="ip_source" id="ip_source"/>
		<input type="hidden" value="" name="port_source" id="port_source"/>
		<input type="hidden" value="" name="ip_external" id="ip_external"/>
		<input type="hidden" value="" name="port_external" id="port_external"/>
		<input type="hidden" value="" name="protocol" id="protocol"/>
		<input type="hidden" value="" name="address" id="address"/>
	</form>

<?php
} else {
	echo "<p>" . gettext("SSL Inspect package is not installed on the device.") . "</p>";
}
include("foot.inc");
?>
<script>
let timeGenericProcess = 5000;

function getAllProcessNetifyd() {
	const queryString = window.location.search;
	const urlParams = new URLSearchParams(queryString);
	let showInterface = "";
	if (urlParams.has('show')) {
		showInterface = urlParams.get('show');
	}
	$.post("./ajax_ssl_inspect.php", {'getAllProcessNetifyd':showInterface,'typeTable':'all'}, function(data) {
		$("#divProcess").html(data);
	});
	setTimeout(getAllProcessNetifyd, timeGenericProcess);
}

let StopSearchInspectState = true;
function StopSearchInspect() {
	if (StopSearchInspectState == true) {
		StopSearchInspectState = false;
		$("#StopSearchInspect").removeAttr("class").attr('class', 'btn btn-warning form-control find-values');
	} else {
		StopSearchInspectState = true;
		$("#StopSearchInspect").removeAttr("class").attr('class', 'btn btn-secondary form-control find-values');
	}
}

function showProcess() {
	const queryString = window.location.search;
	const urlParams = new URLSearchParams(queryString);
	let showInterface = "";
	var limiteTailShow = $("#search-inspect-select").val();
	if (limiteTailShow == "") {
		limiteTailShow = 100;
	}
	if (StopSearchInspectState) {
		if (urlParams.has('show')) {
			showInterface = urlParams.get('show');
			$.post("./ajax_ssl_inspect.php", {'showProcessNetifyd':showInterface, 'limiteTailShow':limiteTailShow}, function(data) {
				$("#showProcess").html(data);				
				setTimeout(() => {
					searchTableInspect();	
				}, 150);
			});
		}
	}
	setTimeout(showProcess, 30000);
}

getAllProcessNetifyd();
showProcess();

function searchTableInspect() {
	var $rows = $('#showProcess tr');
	var val = $.trim($('#search-inspect').val()).replace(/ +/g, ' ').toLowerCase();
	$rows.show().filter(function() {
		var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
		return !~text.indexOf(val);
	}).hide();
}

function clearSearchInspect() {
	$("#search-inspect").val("");
	$("#search-inspect-select").val("100");
	searchTableInspect();
}

function completSearchInspect(value) {
	$("#search-inspect").val(value);
	searchTableInspect();	
}

function insertRuleACP(desc_interface, interface, ip_source, port_source, ip_external, port_external, protocol, address) {
	$("#desc_interface").val(desc_interface);
	$("#interface").val(interface);
	$("#ip_source").val(ip_source);
	$("#port_source").val(port_source);
	$("#ip_external").val(ip_external);
	$("#port_external").val(port_external);
	$("#protocol").val(protocol);
	$("#address").val(address);
	$("#createRule").submit();
}

</script>