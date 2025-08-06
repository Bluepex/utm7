<?php
/*
 * status_services.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2019 Rubicon Communications, LLC (Netgate)
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

##|+PRIV
##|*IDENT=page-status-services
##|*NAME=Status: Services
##|*DESCR=Allow access to the 'Status: Services' page.
##|*MATCH=status_services.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("service-utils.inc");
require_once("bluepex/service-utils.inc");
require_once("shortcuts.inc");
require_once("bluepex/bp_auditing.inc");
require_once("bluepex/dnsprotection.inc");

global $config;

// monitor services
init_config_arr(array('system', 'bluepex_stats', 'monitor_services'));
$p_config = &$config['system']['bluepex_stats']['monitor_services'];
$s_config = [];

if (is_array($config['installedpackages']['suricata']) &&
    is_array($config['installedpackages']['suricata']['rule'])) {

    $s_config = $config['installedpackages']['suricata']['rule'];
}

//Function referente to /etc/inc/firewallapp_functions.inc
//$all_gtw = getInterfacesInGatewaysWithNoExceptions();
//This snippet does exactly what is inside the function, but the file is not imported due to performance problems in rendering the page
//Be aware that it is necessary to update this snippet if the function is changed
//-------------------------------------------------------------------------------
init_config_arr(array('installedpackages', 'suricata', 'rule'));
init_config_arr(array('gateways', 'gateway_item'));
$a_gtw = &$config['gateways']['gateway_item'];
$a_gateways = return_gateways_array(true, false, true, true);

$all_gtw = [];
if (file_exists('/etc/if_ex_wan.conf')) {
	$if_ex_wan = explode(",",file_get_contents("/etc/if_ex_wan.conf"));
	foreach($a_gateways as $gtw_rules) {
		$if = get_real_interface($gtw_rules['interface']);
		if (!in_array($if, $if_ex_wan,true)) {
			$all_gtw[] = $if;
		}
	}
}
if (file_exists("/etc/if_ex_lan.conf")) {
	$if_ex_lan = explode(",",file_get_contents("/etc/if_ex_lan.conf"));
	foreach($if_ex_lan as $if_ex_lan_now) {
		$all_gtw[] = $if_ex_lan_now;
	}
}
$all_gtw = array_unique(array_filter($all_gtw));
//-------------------------------------------------------------------------------

if ($_POST['save']) {

	foreach ($_POST['name'] as $key => $field) {
		if (isset($_POST['check'][$key])) {
			$_POST['check'][$key] = "true";
		} else {
			$_POST['check'][$key] = "false";
		}

		if (isset($_POST['priority'][$key])) {
			$p_config['item'][$key]['priority'] = $_POST['priority'][$key];
		}

		if (isset($config['system']['bluepex_stats']['monitor_services']['item'][$key])) {			
			$p_config['item'][$key]['check'] = $_POST['check'][$key];
		}
	}

	$p_config['autoload'] = $_POST['autoload'] ? 'on' : 'off';
	$p_config['reloadservice1'] = $_POST['reloadService1'];
	$p_config['reloadservice2'] = $_POST['reloadService2'];
	$p_config['reloadservice3'] = $_POST['reloadService3'];
	$p_config['reloadservice4'] = $_POST['reloadService4'];

	bp_write_report_db("report_0008_services_db_update");
	write_config("BluePex Stats: monitor_services");
	header("Location: /status_bp_services.php");

}

$pgtitle = array(gettext("Diagnostics"), gettext("Services"));
include("head.inc");

?>
<style>
	.bottom-space-autoload {
		margin-bottom: 10px !important;
	}
	.row {
	    margin-bottom: 20px !important;
	}
</style>
<?php

if ($savemsg) {
	print_info_box($savemsg, 'success');
}

function get_service_stat($service, $withtext = true, $smallicon = false, $withthumbs = false, $title = "service_state") {
	$output = "";

	if (get_service_status($service)) {
		$statustext = "Running";
		$text_class = "text-success";
		$fa_class = "fa fa-check-circle";
		$fa_class_thumbs = "fa fa-thumbs-o-up";
		$Thumbs_UpDown = "Thumbs up";
	} else {
		if (is_service_enabled($service['name'])) {
			$statustext = "Stopped";
			$text_class = "text-danger";
			$fa_class = "fa fa-times-circle";
		} else {
			$statustext = "Disabled";
			$text_class = "text-warning";
			$fa_class = "fa fa-ban";
		}
		$fa_class_thumbs = "fa fa-thumbs-o-down";
		$Thumbs_UpDown = "Thumbs down";
	}
	$fa_size = ($smallicon) ? "fa-1x" : "fa-lg";

	if ($title == "state") {
		$title = $statustext;
	} elseif ($title == "service_state") {
		$title = sprintf(gettext('%1$s Service is %2$s'), $service["name"], $statustext);
	} elseif ($title == "description_state") {
		$title = sprintf(gettext('%1$s Service is %2$s'), $service["description"], $statustext);
	} elseif ($title == "description_service_state") {
		$title = sprintf(gettext('%1$s, %2$s Service is %3$s'), $service["description"], $service["name"], $statustext);
	}

	$spacer = ($withthumbs || $withtext) ? " " : "";

	$output = $statustext;

	return $output;
}

$services1 = get_services_bp();

if (count($services1) > 0) {

	if (!isset($config['system']['bluepex_stats']['monitor_services']['autoload'])) {
		//$item_service['autoload'] = 'off';
		$item_service['autoload'] = 'on';
		$p_config = $item_service;
	}
	if (empty($hide_services)) {
		$hide_services = [];
	}

	$counter_itens = 0;

	foreach ($services1 as $key => $service) {
			if (empty($service['name']) || in_array($service['name'], $hide_services)) {
				continue;
			}

			if (!isset($config['system']['bluepex_stats']['monitor_services']['item'][$key])) {
				$item_service = array();		
			    $item_service['name'] = $service['name'];
			    $item_service['description'] = $service['description'];
			    $item_service['status'] = get_service_stat($service, false, true, false, "state");
				if (
					($service['name'] == "active-protection")
					|| ($service['name'] == "firewallapp")
				) {
					$item_service['check'] = 'false';
				} else {
					$item_service['check'] = 'true';
				}
			    
			    $p_config['item'][$key] = $item_service;
			    $counter_itens++;
			}
			
	}

	if (count($services1) == $counter_itens) {
		bp_write_report_db("report_0008_services_db_create");
	} elseif (count($services1) > $counter_itens &&
	    $counter_itens != 0) {
		bp_write_report_db("report_0008_services_db_add");
	}

	write_config("BluePex Stats: monitor_services");

}

$services = $config['system']['bluepex_stats']['monitor_services']['item'];

$form = new Form;

$section = new Form_Section(gettext('Auto Load Services'));
$section->addInput(new Form_Checkbox(
	'autoload',
	gettext('AutoLoad'),
	'',
	$pconfig['autoload'] == 'on' ? true:false,
	'on'
))->addClass('fapp_enable_bt_switch');

$hourReload = array(
	'' => '---',
	'00:01:00_00:07:00' => '00:00:00 (00:00 AM)',
	'01:00:00_01:06:00' => '01:00:00 (01:00 AM)',
	'02:00:00_02:06:00' => '02:00:00 (02:00 AM)',
	'03:00:00_03:06:00' => '03:00:00 (03:00 AM)',
	'04:00:00_04:06:00' => '04:00:00 (04:00 AM)',
	'05:00:00_05:06:00' => '05:00:00 (05:00 AM)',
	'06:00:00_06:06:00' => '06:00:00 (06:00 AM)',
	'07:00:00_07:06:00' => '07:00:00 (07:00 AM)',
	'08:00:00_08:06:00' => '08:00:00 (08:00 AM)',
	'09:00:00_09:06:00' => '09:00:00 (09:00 AM)',
	'10:00:00_10:06:00' => '10:00:00 (10:00 AM)',
	'11:00:00_11:06:00' => '11:00:00 (11:00 AM)',
	'12:00:00_12:06:00' => '12:00:00 (12:00 AM)',
	'13:00:00_13:06:00' => '13:00:00 (01:00 PM)',
	'14:00:00_14:06:00' => '14:00:00 (02:00 PM)',
	'15:00:00_15:06:00' => '15:00:00 (03:00 PM)',
	'16:00:00_16:06:00' => '16:00:00 (04:00 PM)',
	'17:00:00_17:06:00' => '17:00:00 (05:00 PM)',
	'18:00:00_18:06:00' => '18:00:00 (06:00 PM)',
	'19:00:00_19:06:00' => '19:00:00 (07:00 PM)',
	'20:00:00_20:06:00' => '20:00:00 (08:00 PM)',
	'21:00:00_21:06:00' => '21:00:00 (09:00 PM)',
	'22:00:00_22:06:00' => '22:00:00 (10:00 PM)',
	'23:00:00_23:06:00' => '23:00:00 (11:00 PM)'
);

$section->addInput(new Form_Select(
	'reloadService1',
	"Recarregar serviço (Agendamento 1)",
	$config['system']['bluepex_stats']['monitor_services']['reloadservice1'] != "" ? $config['system']['bluepex_stats']['monitor_services']['reloadservice1'] : '',
	$hourReload
))->setHelp(gettext('1° Agendamento para recarregar os serviços do UTM'));

$section->addInput(new Form_Select(
	'reloadService2',
	"Recarregar serviço (Agendamento 2)",
	$config['system']['bluepex_stats']['monitor_services']['reloadservice2'] != "" ? $config['system']['bluepex_stats']['monitor_services']['reloadservice2'] : '',
	$hourReload
))->setHelp(gettext('2° Agendamento para recarregar os serviços do UTM'));

$section->addInput(new Form_Select(
	'reloadService3',
	"Recarregar serviço (Agendamento 3)",
	$config['system']['bluepex_stats']['monitor_services']['reloadservice3'] != "" ? $config['system']['bluepex_stats']['monitor_services']['reloadservice3'] : '',
	$hourReload
))->setHelp(gettext('3° Agendamento para recarregar os serviços do UTM'));

$section->addInput(new Form_Select(
	'reloadService4',
	"Recarregar serviço (Agendamento 4)",
	$config['system']['bluepex_stats']['monitor_services']['reloadservice4'] != "" ? $config['system']['bluepex_stats']['monitor_services']['reloadservice4'] : '',
	$hourReload
))->setHelp(gettext('4° Agendamento para recarregar os serviços do UTM'));

$form->add($section);

if (1 == 1) {//(count($services) > 0) {
?>
<div style='margin: auto; text-align:end;'>
	<img style='width: 32px;' src='./images/bp-gear.png' onclick="showColunmPriority();" id="changePriority">
</div>

<div class="modal fade" id="showColunmPriority" tabindex="-1" role="dialog" aria-labelledby="TituloModalCentralizado" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="TituloModalCentralizado"><?=gettext("Priority Services")?> </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="POST" action="" style="margin-top: 10px !important;border:0px solid transparent;">
					<div class="form-row">
						<div class="col">					
							<label for="recipient-name" class="col-form-label"><?=gettext("Enter password:")?></label>
							<input type="password" class="form-control" id="senhaLiberar" name="senhaLiberar" maxlength="50">
						</div>
					</div>
					<hr>
					<button type="button" class="btn btn-secondary" data-dismiss="modal"><?=gettext("Close")?></button>
					<button type="submit" name="confirmar_senha" id="confirmar_senha" class="btn btn-primary"><?=gettext("Continue")?></button>
				</form>
			</div>
		</div>
	</div>
</div>

<form id="frm_status_bp_service" action="status_bp_services.php" method="post">
	

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('Services BluePex')?></h2></div>
	<div class="panel-body">

	<div class="panel-body panel-default">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
				<thead>
					<tr>
						<th><?=gettext("Service")?></th>
						<th><?=gettext("Description")?></th>
						<th><?=gettext("Priority")?></th>
						<th><?=gettext("Status")?></th>
						<th><?=gettext("Service Monitor")?></th>
					</tr>
				</thead>
				<tbody>
<?php
	uasort($services, "service_name_compare");
	$hide_services = array("sshd");
	
	foreach ($services as $key => $service) {
		//if (empty($service['name']) || in_array($service['name'], $hide_services)) {
		//	continue;
		//}
		$chk = ($service['check'] == "true") ? "checked" : null;
		if (empty($service['description'])) {
			$service['description'] = get_pkg_descr($service['name']);
		}
?>
					<tr>
						<td style="vertical-align:middle;">
							<?php if ($service['name'] == "smbd"): ?>
								samba
							<?php else: ?>
								<?=$service['name']?>
							<?php endif; ?>
							<input type="hidden" name="name[<?=$key?>]" value="<?=$service['name']?>"/>
						</td>
						<td style="vertical-align:middle;">
							<?=$service['description']?>
							<input type="hidden" name="description[<?=$key?>]" value="<?=$service['description'];?>"/>							
						</td>
						<td style="vertical-align:middle;">
							<?php if (($service['name'] == "active-protection") || ($service['name'] == "firewallapp")): ?>
								<select name="priority[<?=$key?>]" id="priority[<?=$key?>]" class="form form-control" style="text-align:center;">
									<option value="0"><?=gettext("Default")?></option>
									<option value="-5"><?=gettext("Simple")?></option>
									<option value="-10"><?=gettext("Moderate")?></option>
									<option value="-15"><?=gettext("Priority")?></option>
									<option value="-20"><?=gettext("Top Priority")?></option>
								</select>
								<?php if (isset($service['priority'])): ?>
									<script>
										document.getElementById("priority[<?=$key?>]").value="<?=$service['priority']?>";
									</script>
								<?php endif; ?>
							<?php endif; ?>
						</td>
						<td style="vertical-align:middle;">
							<?php
							if ($service['name'] == "firewallapp") {
								$display_line = 'block';
								if (!empty($s_config)) {
									foreach ($s_config as $interfaces_suricata) {
										$if_w = get_real_interface($interfaces_suricata['interface']);
										if (!in_array($if_w, $all_gtw,true)) {
										//if (strtolower($interfaces_suricata['interface']) != 'wan') {
											$display_line = 'none';
											?>
											<?=get_service_status_icon($service, false, true, false, "state");?>
											<?php
											break;
										}
									}
								}
								echo "<i class='text-danger fa fa-times-circle fa-1x' title='Stopped' style='display:{$display_line}'><span style='display:none'>Stopped</span></i>";
									
							} elseif ($service['name'] == "active-protection") {
								$display_line = 'block';
								if (!empty($s_config)) {
									foreach ($s_config as $interfaces_suricata) {
										$if_w = get_real_interface($interfaces_suricata['interface']);
										if (in_array($if_w, $all_gtw,true)) {
										//if (strtolower($interfaces_suricata['interface']) != 'wan') {
											$display_line = 'none';
											?>
											<?=get_service_status_icon($service, false, true, false, "state");?>
											<?php
											break;
										}
									}
									/*if (strtolower($interfaces_suricata['interface']) == 'wan') {
									/*	$display_line = 'none';
									/*	?>
									/*	<?=get_service_status_icon($service, false, true, false, "state");?>
									/*	<?php
									}*/
								}
								echo "<i class='text-danger fa fa-times-circle fa-1x' title='Stopped' style='display:{$display_line}'><span style='display:none'>Stopped</span></i>";
							} elseif ($service['name'] == 'dnsprotection') {
								[$processCounter, $serviceProcess, $pidProcess, $haveRunning] = returnStatusProcessDNSProtection();
								if ($haveRunning == "false") {
									echo "<i class='text-danger fa fa-times-circle fa-1x' title='Stopped' style='display:block;'><span style='display:none'>Stopped</span></i>";
								} else {
									if ($pidProcess > 0) {
										echo "<i class='text-success fa fa-check-circle fa-1x' title='Stopped' style='display:block;'><span style='display:none'>Stopped</span></i>";
									} else {
										echo "<i class='text-danger fa fa-times-circle fa-1x' title='Stopped' style='display:block;'><span style='display:none'>Stopped</span></i>";
									}
								}
							} elseif ($service['name'] == "swapfile_upgrade") {
								if (file_exists('/tmp/swapfile_upgraded')) {
									if (intval(trim(file_get_contents('/tmp/swapfile_upgraded'))) == 1) {
										echo "<i class='text-success fa fa-check-circle fa-1x' title='Stopped' style='display:block;'><span style='display:none'>Stopped</span></i>";
									} else {
										echo "<i class='text-danger fa fa-times-circle fa-1x' title='Stopped' style='display:block;'><span style='display:none'>Stopped</span></i>";
									}
								} else {
									echo "<i class='text-danger fa fa-times-circle fa-1x' title='Stopped' style='display:block;'><span style='display:none'>Stopped</span></i>";
								}
							} else {
								?>
								<?=get_service_status_icon($service, false, true, false, "state");?>
							<?php
							}
							?>
						</td>
						<td style="vertical-align:middle;">
							<?php
							if ($service['name'] == "firewallapp") {
								$display_line = 'block';
								if (!empty($s_config)) {
									foreach ($s_config as $interfaces_suricata) {

										$if_w = get_real_interface($interfaces_suricata['interface']);
										if (!in_array($if_w, $all_gtw,true)) {
										#if (strtolower($interfaces_suricata['interface']) != 'wan') {
											$display_line = 'none';
											?>
											<input type='checkbox' name="check[<?=$key?>]" value="<?=$service['check'];?>" <?=$chk;?> />
											<?php
											break;
										}

										/*if (strtolower($interfaces_suricata['interface']) != 'wan') {
										/*	$display_line = 'none';
										/*	?>
										/*	<input type='checkbox' name="check[<?=$key?>]" value="<?=$service['check'];?>" <?=$chk;?> />
										/*	<?php
										}*/
									}
								}
								echo "<p style='display: {$display_line}'>" . gettext("Service not started") . "</p>";
									
							} elseif ($service['name'] == "active-protection") {
								$display_line = 'block';

								if (!empty($s_config)) {
									foreach ($s_config as $interfaces_suricata) {

										$if_w = get_real_interface($interfaces_suricata['interface']);
										if (in_array($if_w, $all_gtw,true)) {
										#if (strtolower($interfaces_suricata['interface']) != 'wan') {
											$display_line = 'none';
											?>
											<input type='checkbox' name="check[<?=$key?>]" value="<?=$service['check'];?>" <?=$chk;?> />
											<?php
											break;
										}


										/*if (strtolower($interfaces_suricata['interface']) == 'wan') {
											$display_line = 'none';
											?>
											<input type='checkbox' name="check[<?=$key?>]" value="<?=$service['check'];?>" <?=$chk;?> />
											<?php
										}*/
									}
								}
								echo "<p style='display: {$display_line}'>" . gettext("Service not started") . "</p>";							
							} else {
								?>
								<input type='checkbox' name="check[<?=$key?>]" value="<?=$service['check'];?>" <?=$chk;?> />
							<?php
							}
							?>

							<!--<?php if (get_pkg_descr($service['name'] === "firewallapp")) { ?> 
								<input type="hidden" name="check[]" value="<?=$service['check'];?>"/>
							<?php } ?>-->
						</td>
					</tr>
<?php	
	}
?>
				</tbody>
			</table>
		</div>
		<!--<div class="col-12 d-flex justify-content-center mt-3 mb-2">
			<button type="submit" name="save" value="salvar" class="btn btn-success btn mt-5"><i class="fa fa-save"></i> Salvar</button>
		</div>-->
	</div>

	</div>
</div>
<? print($form); ?>
</form>
<?php
} else {
	print_info_box(gettext("No services found."), 'danger');
}

include("foot.inc"); ?>

<script>
$(".border-box.mt-5.pt-2").addClass("bottom-space-autoload");
$(document).ready(function() {
        $(".fapp_enable_bt_switch").bootstrapSwitch('size', 'mini');
        $(".fapp_enable_bt_switch").bootstrapSwitch('state', <?=$p_config['autoload'] == 'on' ? 'true' : 'false';?>);
});

var liberarAcesso = true;

function showColunmPriority() {
	if (liberarAcesso) {
		$('#showColunmPriority').modal('show');
	} else {
		$("#changePriority").attr("src", "./images/bp-gear.png");
		$('td:nth-child(3)').hide();
		$('th:nth-child(3)').hide();
		$($(".form-group.col-11.left-border-blue.box-white.p-6")[1]).hide();
		$($(".form-group.col-11.left-border-blue.box-white.p-6")[2]).hide();
		$($(".form-group.col-11.left-border-blue.box-white.p-6")[3]).hide();
		$($(".form-group.col-11.left-border-blue.box-white.p-6")[4]).hide();
		liberarAcesso = true;
	}
}

$("#confirmar_senha").click(function (event) {
	$.ajax({
		data: { pass_modal: $('#senhaLiberar').val() },
		method: "post",
		url: "ajax_pass_modal.php",
		success: function(data) {
			if (data == "ok") {
				liberarAcesso = false;
				$("#senhaLiberar").val("");
				$('td:nth-child(3)').show();
				$('th:nth-child(3)').show();
				$($(".form-group.col-11.left-border-blue.box-white.p-6")[1]).show();
				$($(".form-group.col-11.left-border-blue.box-white.p-6")[2]).show();
				$($(".form-group.col-11.left-border-blue.box-white.p-6")[3]).show();
				$($(".form-group.col-11.left-border-blue.box-white.p-6")[4]).show();
				$("#changePriority").attr("src", "./images/bp-logout.png");
				$('#showColunmPriority').modal('hide');
			} else {
				$("#senhaLiberar").val("");
				alert("<?=gettext("Incorrect pasasword!")?>");
			}
		}
	});
	event.preventDefault();
});

$('td:nth-child(3)').hide();
$('th:nth-child(3)').hide();
$($(".form-group.col-11.left-border-blue.box-white.p-6")[1]).hide();
$($(".form-group.col-11.left-border-blue.box-white.p-6")[2]).hide();
$($(".form-group.col-11.left-border-blue.box-white.p-6")[3]).hide();
$($(".form-group.col-11.left-border-blue.box-white.p-6")[4]).hide();
</script>
