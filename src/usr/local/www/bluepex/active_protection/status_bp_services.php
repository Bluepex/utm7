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
require_once("shortcuts.inc");

global $config;

// monitor services
init_config_arr(array('system', 'bluepex_stats', 'monitor_services'));
$p_config = &$config['system']['bluepex_stats']['monitor_services'];
$s_config = $config['installedpackages']['suricata']['rule'];


init_config_arr(array('gateways', 'gateway_item'));
$a_gtw = &$config['gateways']['gateway_item'];
$a_gateways = return_gateways_array(true, false, true, true);
$all_gtw = [];
if (file_exists('/etc/if_ex_wan.conf')) {
	$if_ex_wan = explode(",",file_get_contents("/etc/if_ex_wan.conf"));
} else {
	file_put_contents("/etc/if_ex_wan.conf", "");
	$if_ex_wan = explode(",",file_get_contents("/etc/if_ex_wan.conf"));
}
foreach($a_gateways as $gtw_rules) {
	$if = get_real_interface($gtw_rules['interface']);
	if (!in_array($if, $if_ex_wan,true)) {
		$all_gtw[] = $if;
	}
}


if ($_POST['save']) {

	foreach ($_POST['name'] as $key => $field) {
		if (isset($_POST['check'][$key])) {
			$_POST['check'][$key] = "true";
		} else {
			$_POST['check'][$key] = "false";
		}

		if (isset($config['system']['bluepex_stats']['monitor_services']['item'][$key])) {			
			$p_config['item'][$key]['check'] = $_POST['check'][$key];
		}
	}

	$p_config['autoload'] = $_POST['autoload'] ? 'on' : 'off';

	write_config("BluePex Stats: monitor_services");
	header("Location: /status_bp_services.php");

}

$pgtitle = array(gettext("Diagnostics"), gettext("Services"));
include("head.inc");
?>
<style>
.border-box.mt-5.pt-2 {
	margin-bottom: 10px;	
}
</style>
<?php
if ($savemsg) {
	print_info_box($savemsg, 'success');
}

function get_service_stat($service, $withtext = true, $smallicon = false, $withthumbs = false, $title = "service_state") {
	$output = "";

	if (get_service_status($service)) {
		$statustext = gettext("Running");
		$text_class = "text-success";
		$fa_class = "fa fa-check-circle";
		$fa_class_thumbs = "fa fa-thumbs-o-up";
		$Thumbs_UpDown = "Thumbs up";
	} else {
		if (is_service_enabled($service['name'])) {
			$statustext = gettext("Stopped");
			$text_class = "text-danger";
			$fa_class = "fa fa-times-circle";
		} else {
			$statustext = gettext("Disabled");
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
			}
			
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


$form->add($section);

if (1 == 1) {//(count($services) > 0) {
?>
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
						<td>
							<?=$service['name']?>
							<input type="hidden" name="name[<?=$key?>]" value="<?=$service['name']?>"/>
						</td>
						<td>
							<?=$service['description']?>
							<input type="hidden" name="description[<?=$key?>]" value="<?=$service['description'];?>"/>							
						</td>
						<td>
							<?php
							if ($service['name'] == "firewallapp") {
								$display_line = 'block';
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
								echo "<i class='text-danger fa fa-times-circle fa-1x' title='Stopped' style='display:{$display_line}'><span style='display:none'>Stopped</span></i>";
									
							} elseif ($service['name'] == "active-protection") {
								$display_line = 'block';
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
								echo "<i class='text-danger fa fa-times-circle fa-1x' title='Stopped' style='display:{$display_line}'><span style='display:none'>Stopped</span></i>";
							} else {
								?>
								<?=get_service_status_icon($service, false, true, false, "state");?>
							<?php
							}
							?>
						</td>
						<td>
							<?php
							if ($service['name'] == "firewallapp") {
								$display_line = 'block';
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
								echo "<p style='display: {$display_line}'>" . gettext("Service not started") . "</p>";
									
							} elseif ($service['name'] == "active-protection") {
								$display_line = 'block';
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
$(document).ready(function() {
        $(".fapp_enable_bt_switch").bootstrapSwitch('size', 'mini');
        $(".fapp_enable_bt_switch").bootstrapSwitch('state', <?=$p_config['autoload'] == 'on' ? 'true' : 'false';?>);
});
</script>
