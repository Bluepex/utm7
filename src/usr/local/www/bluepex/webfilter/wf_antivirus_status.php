<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */
require_once("/etc/inc/util.inc");
require_once("/etc/inc/functions.inc");
require_once("/etc/inc/pkg-utils.inc");
require_once("/etc/inc/globals.inc");
require_once("guiconfig.inc");

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Antivirus'), dgettext('BluePexWebFilter', 'Status'));

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg, 'success');
}

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Antivirus'), false, '/webfilter/wf_antivirus.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Antivirus Status'), true, '/webfilter/wf_antivirus_status.php');
display_top_tabs($tab_array);

?>

<div class="panel panel-default" id="filter">
	<div class="panel-heading">
		<h2 class="panel-title"><?=dgettext('BluePexWebFilter', 'Filtering'); ?><span class="widget-heading-icon"><a data-toggle="collapse" href="#filter_panel-body"><i class="fa fa-plus-circle"></i></a></span></h2>
	</div>
	<div id="filter_panel-body" class="panel-body collapse out">
	<div class="panel-body">
		<div class="table-responsive">
			<form id="paramsForm" name="paramsForm" method="post" action="">
			<table class="table table-hover table-condensed">
				<tbody>
				<tr>
					<td width="22%" valign="top" class="vncellreq"><?=dgettext('BluePexWebFilter', 'Max lines:')?></td>
					<td width="78%" class="vtable">
						<select name="maxlines" id="maxlines">
							<option value="5">5 lines</option>
							<option value="10" selected="selected">10 lines</option>
							<option value="15">15 lines</option>
							<option value="20">20 lines</option>
							<option value="25">25 lines</option>
							<option value="100">100 lines</option>
							<option value="200">200 lines</option>
						</select>
						<br/>
						<span class="vexpl">
							<?=dgettext('BluePexWebFilter', "Max. lines to be displayed.")?>
						</span>
					</td>
				</tr>
				<tr>
					<td width="22%" valign="top" class="vncellreq"><?=dgettext('BluePexWebFilter', 'String filter:')?></td>
					<td width="78%" class="vtable">
						<input name="strfilter" type="text" class="formfld search" id="strfilter" size="50" value="" />
						<br/>
						<span class="vexpl">
							<?=dgettext('BluePexWebFilter', "Enter a grep-like string/pattern to filter the log entries.")?><br/>
							<?=dgettext('BluePexWebFilter', "E.g.: username, IP address, URL.")?><br/>
							<?=dgettext('BluePexWebFilter', "Use <strong>!</strong> to invert the sense of matching (to select non-matching lines).")?>
						</span>
					</td>
				</tr>
				</tbody>
			</table>
			</form>
		</div>
	</div>
	</div>
</div>
<br />

<?php if ($_REQUEST["menu"] != "reverse") {?>
<div class="panel panel-default" id="virus">
	<div class="panel-heading">
		<h2 class="panel-title" id="teste"><?=dgettext('BluePexWebFilter', "Antivirus Virus Table"); ?><span class="widget-heading-icon"><a data-toggle="collapse" href="#virus_panel-body"><i class="fa fa-plus-circle"></i></a></span></h2>
	</div>
	<div id="virus_panel-body" class="panel-body collapse in">
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-hover table-condensed">
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
				<tbody>
				<tr><td>
					<table class="tabcont" width="100%" border="0" cellspacing="0" cellpadding="0">
						<thead><tr>
							<td colspan="6" class="listtopic" align="center"><?=dgettext('BluePexWebFilter', "WF Antivirus - Virus Logs"); ?></td>
						</tr></thead>
						<tbody id="CICIAPVirusView">
						<tr><td></td></tr>
						</tbody>
					</table>
				</td></tr>
				</tbody>
			</table>
		</div>
	</div>
	</div>
</div>
<br />

<div class="panel panel-default" id="access">
	<div class="panel-heading">
		<h2 class="panel-title"><?=dgettext('BluePexWebFilter', "Antivirus Access Table"); ?><span class="widget-heading-icon"><a data-toggle="collapse" href="#access_panel-body"><i class="fa fa-plus-circle"></i></a></span></h2>
	</div>
	<div id="access_panel-body" class="panel-body collapse in">
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-hover table-condensed">
				<tbody>
				<tr><td>
					<table class="tabcont" width="100%" border="0" cellspacing="0" cellpadding="0">
						<thead><tr>
							<td colspan="2" class="listtopic" align="center"><?=dgettext('BluePexWebFilter', "WF Antivirus - Access Logs"); ?></td>
						</tr></thead>
						<tbody id="CICAPAccessView">
						<tr><td></td></tr>
						</tbody>
					</table>
				</td></tr>
				</tbody>
			</table>
		</div>
	</div>
	</div>
</div>
<br />

	<div class="panel panel-default" id="server">
	<div class="panel-heading">
		<h2 class="panel-title"><?=dgettext('BluePexWebFilter', "Antivirus Server Table"); ?><span class="widget-heading-icon"><a data-toggle="collapse" href="#server_panel-body"><i class="fa fa-plus-circle"></i></a></span></h2>
	</div>
	<div id="server_panel-body" class="panel-body collapse in">
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-hover table-condensed">
				<tbody>
				<tr><td>
					<table class="tabcont" width="100%" border="0" cellspacing="0" cellpadding="0">
						<thead><tr>
							<td colspan="2" class="listtopic" align="center"><?=dgettext('BluePexWebFilter', "WF - Antivirus Server Logs"); ?></td>
						</tr></thead>
						<tbody id="CICAPServerView">
						<tr><td></td></tr>
						</tbody>
					</table>
				</td></tr>
				</tbody>
			</table>
		</div>
	</div>
	</div>
</div>
<br />

<div class="panel panel-default" id="updates">
	<div class="panel-heading">
		<h2 class="panel-title"><?=dgettext('BluePexWebFilter', "Antivirus Updates Table"); ?><span class="widget-heading-icon"><a data-toggle="collapse" href="#updates_panel-body"><i class="fa fa-plus-circle"></i></a></span></h2>
	</div>
	<div id="updates_panel-body" class="panel-body collapse in">
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-hover table-condensed">
				<tbody>
				<tr><td>
					<table class="tabcont" width="100%" border="0" cellspacing="0" cellpadding="0">
						<thead><tr>
							<td colspan="1" class="listtopic" align="center"><?=dgettext('BluePexWebFilter', "WF Antivirus - Updates Logs"); ?></td>
						</tr></thead>
						<tbody id="freshclamView">
						<tr><td></td></tr>
						</tbody>
					</table>
				</td></tr>
				</tbody>
			</table>
		</div>
	</div>
	</div>
</div>
<br />

<div class="panel panel-default" id="service">
	<div class="panel-heading">
		<h2 class="panel-title" id="teste2"><?=dgettext('BluePexWebFilter', "Antivirus Service Table"); ?><span class="widget-heading-icon"><a data-toggle="collapse" href="#service_panel-body"><i class="fa fa-plus-circle"></i></a></span></h2>
	</div>
	<div id="service_panel-body" class="panel-body collapse in">
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-hover table-condensed">
				<tbody>
				<tr><td>
					<table class="tabcont" width="100%" border="0" cellspacing="0" cellpadding="0">
						<thead><tr>
							<td colspan="1" class="listtopic" align="center"><?=dgettext('BluePexWebFilter', "WF Antivirus - Service Logs"); ?></td>
						</tr></thead>
						<tbody id="clamdView">
						<tr><td></td></tr>
						</tbody>
					</table>
				</td></tr>
				</tbody>
			</table>
		</div>
	</div>
	</div>
</div>
<?php }?>

<!-- Function to call programs logs -->
<script type="text/javascript">
function showLog(content, url, program) {
	jQuery.ajax(url,
		{
		type: 'post',
		data: {
			maxlines: $('#maxlines').val(),
			strfilter: $('#strfilter').val(),
			program: program,
			content: content
			},
		success: function(ret){
			$('#' + content).html(ret);
			}
		}
		);
}

function updateAllLogs() {
<?php if ($_REQUEST["menu"] != "reverse") {?>
	showLog('CICIAPVirusView', 'squid_monitor_data.php', 'cicap_virus');
	showLog('CICAPAccessView', 'squid_monitor_data.php', 'cicap_access');
	showLog('CICAPServerView', 'squid_monitor_data.php', 'cicap_server');
	showLog('freshclamView', 'squid_monitor_data.php', 'freshclam');
	showLog('clamdView', 'squid_monitor_data.php', 'clamd');
<?php }?>
	setTimeout(updateAllLogs, 5000);
}

window.onload = function() {
	updateAllLogs();
};
</script>
<?php include("foot.inc"); ?>
