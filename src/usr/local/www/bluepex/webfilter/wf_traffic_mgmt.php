<?php
/*
 *====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2016
 *
 * ====================================================================
 */

require_once("guiconfig.inc");
require_once("classes/Form.class.php");
require_once("squid.inc");
require_once("gwlb.inc");
require_once("util.inc");

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instance = &$config['system']['webfilter']['instance']['config'];

$wf_traffic = array();
if (isset($_GET['act']) && $_GET['act'] == "edit") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['squidtraffic'])) {
		$wf_traffic = $wf_instance[$instance_id]['squidtraffic'];
	}
}

if (isset($_GET['act']) && $_GET['act'] == "del") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['squidtraffic'])) {
		unset($wf_instance[$instance_id]['squidtraffic']);
		$savemsg = sprintf(dgettext("BluePexWebFilter", "Traffic Management Settings for the instance '%s' removed successfully!"), $wf_instance[$instance_id]['server']['name']);
		write_config($savemsg);
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_traffic_mgmt.php");
		exit;
	}
}

$ls_get_gateways = return_gateways_array(true, false, true);

function get_lb_gateways($lb_options) {
	global $input_errors, $ls_get_gateways;

	$ls_gateways = array();
	foreach($ls_get_gateways as $gw_key => $gw_info) {
		$ls_gateways[$gw_key] = $gw_info['gateway'];
	}

	$lb_array_list = array();
	foreach ($ls_gateways as $g_k => $g_v) {
		foreach ($lb_options as $k => $v) {
			if (!preg_match("/load_balances_gw_{$g_k}/", $k)) {
				continue;
			}
			$gw_ip = get_interface_ip($ls_get_gateways[$g_k]['friendlyiface']);
			$lb_array_list[] = array(
				"gateway_name" => $g_k,
				"monitor" => $ls_get_gateways[$g_k]['monitor'],
				"gateway" => $ls_get_gateways[$g_k]['gateway'],
				"weight" => $lb_options["set_balance_weight_{$g_k}"],
				"ip" => $gw_ip
			);
		}
	}

	$sum_weight = 0;
	foreach($lb_array_list as $gw) {
		$get_status = get_dpinger_status($gw['gateway_name']);
		if ($get_status['status'] == "down") {
			$input_errors[] = dgettext("BluePexWebFilter", "Gateway {$gw['gateway_name']} is down");
		}
		if ($gw['weight'] == 0) {
			$input_errors[] = dgettext("BluePexWebFilter", "Weight of gateway can't be 0 neither 0");
		} else if ($gw['weight'] == 100) {
			$input_errors[] = dgettext("BluePexWebFilter", "Weight of gateway can't be 0 neither 100");
		}
		$sum_weight += $gw['weight'];
	}

	if (count($lb_array_list) < 2 || $sum_weight != 100) {
		$input_errors[] = dgettext("BluePexWebFilter", "You must choose unless one of gateways to configure load balance and the sum of total weights must be 100");
	}
	return $lb_array_list;
}

if (isset($_POST['save'])) {

	$wf_traffic = array();

	squid_validate_traffic($_POST, $input_errors);

	if ($_POST['enable_load_balance'] == "on") {
		$wf_traffic['enable_load_balance'] = "yes";
		$lb_gateways = get_lb_gateways($_POST);

		// Load Balance Settings
		if (!empty($lb_gateways)) {
			$wf_traffic['lb_gateways']['item'] = $lb_gateways;
		}
	} else {
		$wf_traffic['enable_load_balance'] = "off";
	}

	$wf_traffic['max_download_size'] = !empty($_POST['max_download_size']) ? $_POST['max_download_size'] : 0;
	$wf_traffic['max_upload_size'] = !empty($_POST['max_upload_size']) ? $_POST['max_upload_size'] : 0;
	$wf_traffic['overall_throttling'] = !empty($_POST['overall_throttling']) ? $_POST['overall_throttling'] : 0;
	$wf_traffic['perhost_throttling'] = !empty($_POST['perhost_throttling']) ? $_POST['perhost_throttling'] : 0;

	// Transfer Extension Settings
	$wf_traffic['throttle_specific'] = $_POST['throttle_specific'];
	$wf_traffic['throttle_binaries'] = $_POST['throttle_binaries'];
	$wf_traffic['throttle_cdimages'] = $_POST['throttle_cdimages'];
	$wf_traffic['throttle_multimedia'] = $_POST['throttle_multimedia'];
	$wf_traffic['throttle_others'] = $_POST['throttle_others'];

	// Transfer Quick Abort Settings
	$wf_traffic['quick_abort_min'] = !empty($_POST['quick_abort_min']) ? $_POST['quick_abort_min'] : 0;
	$wf_traffic['quick_abort_max'] = !empty($_POST['quick_abort_max']) ? $_POST['quick_abort_max'] : 0;
	$wf_traffic['quick_abort_pct'] = !empty($_POST['quick_abort_pct']) ? $_POST['quick_abort_pct'] : 0;

	if (empty($input_errors)) {
		$instance_id = $_POST['instance_id'];

		if (is_numeric($instance_id)) {
			$wf_instance[$instance_id]['squidtraffic'] = $wf_traffic;
			$savemsg = dgettext("BluePexWebFilter", "Traffic Settings applied successfully!");
			write_config($savemsg);
			set_flash_message("success", $savemsg);
			squid_resync($instance_id);
			if (is_process_running_match("wf_monitor")) {
				$wf_pid = exec('/bin/pgrep -f wf_monitor');
				mwexec_bg("/bin/kill -s KILL {$wf_pid}");
				mwexec_bg("/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py");
			}
		} else {
			set_flash_message("success", dgettext("BluePexWebFilter", "Could not to add/edit the traffic settings"));
		}
		header("Location: /webfilter/wf_traffic_mgmt.php");
		exit;
	}
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Traffic Management'));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors); 
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), false, '/webfilter/wf_server.php');
//$tab_array[] = array(dgettext('BluePexWebFilter', 'Upstream Proxy'), false, '/webfilter/wf_upstream.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Cache Mgmt'), false, '/webfilter/wf_cache_mgmt.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Access Control'), false, '/webfilter/wf_access_control.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Traffic Mgmt'), true, '/webfilter/wf_traffic_mgmt.php');

display_top_tabs($tab_array);
if (!isset($instance_id)) :
?>
<form action="/webfilter/wf_traffic_mgmt.php" method="POST">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=dgettext("BluePexWebFilter", "Traffic Management")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive col-md-12">
			<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th><?=dgettext("BluePexWebFilter", "Instance"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Max Download Size");?></th>
					<th><?=dgettext("BluePexWebFilter", "Max Upload Size");?></th>
					<th><?=dgettext("BluePexWebFilter", "Overall bandwidth throttling");?></th>
					<th><?=dgettext("BluePexWebFilter", "Per-host throttling");?></th>
					<th><?=dgettext("BluePexWebFilter", "Load Balance");?></th>
					<th></th>
				</tr>
			</thead>
			<tbody class="instance-entries">
			<?php 
			if (!empty($wf_instance)) :
				foreach ($wf_instance as $id => $instance_config) :
			?>
				<tr>
					<td><?=$instance_config['server']['name'];?></td>
					<td><?php if (isset($instance_config['squidtraffic'])) echo $instance_config['squidtraffic']['max_download_size']; ?></td>
					<td><?php if (isset($instance_config['squidtraffic'])) echo $instance_config['squidtraffic']['max_upload_size']; ?></td>
					<td><?php if (isset($instance_config['squidtraffic'])) echo $instance_config['squidtraffic']['overall_throttling']; ?></td>
					<td><?php if (isset($instance_config['squidtraffic'])) echo $instance_config['squidtraffic']['perhost_throttling']; ?></td>
					<td><?php if (isset($instance_config['squidtraffic'])) echo $instance_config['squidtraffic']['enable_load_balance']; ?></td>
					<td>
						<a href="/webfilter/wf_traffic_mgmt.php?act=edit&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Edit Traffic Management"); ?>">
							<i class="fa fa-cog"></i>
						</a>
						<?php if (isset($instance_config['squidtraffic'])) : ?>
						&nbsp;
						<a href="/webfilter/wf_traffic_mgmt.php?act=del&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Remove Traffic Management"); ?>">
							<i class="fa fa-trash"></i>
						</a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
			</table>
		</div>
	</div>
</div>
</form>
<?php
endif;

if (isset($instance_id)) {
$instance_name = $wf_instance[$instance_id]['server']['name'];

$form = new Form();
$section = new Form_Section(sprintf(dgettext("BluePexWebFilter", 'Traffic Managment Settings (%s)'), $instance_name));

$section->addInput(new Form_Input(
	'max_download_size',
	dgettext('BluePexWebFilter', 'Maximum download size'),
	'text',
	(isset($wf_traffic['max_download_size']) ? $wf_traffic['max_download_size'] : 0)
))->setHelp(dgettext('BluePexWebFilter', 'Limit the maximum total download size to the size specified here (in kilobytes). Set to 0 to disable.'));

$section->addInput(new Form_Input(
	'max_upload_size',
	dgettext('BluePexWebFilter', 'Maximum upload size'),
	'text',
	(isset($wf_traffic['max_upload_size']) ? $wf_traffic['max_upload_size'] : 0)
))->setHelp(dgettext('BluePexWebFilter', "Limit the maximum total upload size to the size specified here (in kilobytes). Set to 0 to disable."));

$section->addInput(new Form_Input(
	'overall_throttling',
	dgettext('BluePexWebFilter', 'Overall bandwidth throttling'),
	'text',
	(isset($wf_traffic['overall_throttling']) ? $wf_traffic['overall_throttling'] : 0)
))->setHelp(dgettext('BluePexWebFilter', 'This value specifies (in kilobytes per second) the bandwidth throttle for downloads. Users will gradually have their download speed increased according to this value. Set to 0 to disable bandwidth throttling.'));

$section->addInput(new Form_Input(
	'perhost_throttling',
	dgettext('BluePexWebFilter', 'Per-host throttling'),
	'text',
	(isset($wf_traffic['perhost_throttling']) ? $wf_traffic['perhost_throttling'] : 0)
))->setHelp(dgettext('BluePexWebFilter', 'This value specifies the download throttling per host. Set to 0 to disable this.'));

$form->add($section);

$section = new Form_Section(dgettext("BluePexWebFilter", 'Transfer Extension Settings'));

$section->addInput(new Form_Checkbox(
	'throttle_specific',
	dgettext('BluePexWebFilter', 'Throttle only specific extensions'),
	dgettext("BluePexWebFilter", "Leave this checked to be able to choose the extensions that throttling will be applied to. Otherwise, all files will be throttled."),
	(isset($wf_traffic['throttle_specific']) && $wf_traffic['throttle_specific'] == "on"),
	'on'
));

$section->addInput(new Form_Checkbox(
	'throttle_binaries',
	dgettext('BluePexWebFilter', 'Throttle binary files'),
	dgettext("BluePexWebFilter", "Check this to apply bandwidth throttle to binary files. This includes compressed archives and executables."),
	(isset($wf_traffic['throttle_binaries']) && $wf_traffic['throttle_binaries'] == "on"),
	'on'
));

$section->addInput(new Form_Checkbox(
	'throttle_cdimages',
	dgettext('BluePexWebFilter', 'Throttle CD images'),
	dgettext("BluePexWebFilter", "Check this to apply bandwidth throttle to CD image files."),
	(isset($wf_traffic['throttle_cdimages']) && $wf_traffic['throttle_cdimages'] == "on"),
	'on'
));

$section->addInput(new Form_Checkbox(
	'throttle_multimedia',
	dgettext('BluePexWebFilter', 'Throttle multimedia files'),
	dgettext("BluePexWebFilter", "Check this to apply bandwidth throttle to multimedia files, such as movies or songs."),
	(isset($wf_traffic['throttle_multimedia']) && $wf_traffic['throttle_multimedia'] == "on"),
	'on'
));

$section->addInput(new Form_Input(
	'throttle_others',
	dgettext('BluePexWebFilter', 'Throttle other extensions'),
	'text',
	(isset($wf_traffic['throttle_others']) ? $wf_traffic['throttle_others'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Comma-separated list of extensions to apply bandwidth throttle to.'));

$form ->add($section);
$section = new Form_Section(dgettext("BluePexWebFilter", 'Transfer Quick Abort Settings'));

$section->addInput(new Form_Input(
	'quick_abort_min',
	dgettext('BluePexWebFilter', 'Finish transfer if less than x KB remaining'),
	'text',
	(isset($wf_traffic['quick_abort_min']) ? $wf_traffic['quick_abort_min'] : 0)
))->setHelp(dgettext('BluePexWebFilter', 'If the transfer has less than x KB remaining, it will finish the retrieval. Set to 0 to abort the transfer immediately.'));

$section->addInput(new Form_Input(
	'quick_abort_max',
	dgettext('BluePexWebFilter', 'Abort transfer if more than x KB remaining'),
	'text',
	(isset($wf_traffic['quick_abort_max']) ? $wf_traffic['quick_abort_max'] : 0)
))->setHelp(dgettext('BluePexWebFilter', "If the transfer has more than x KB remaining, it will abort the retrieval. Set to 0 to abort the transfer immediately."));

$section->addInput(new Form_Input(
	'quick_abort_pct',
	dgettext('BluePexWebFilter', 'Finish transfer if more than x% finished'),
	'text',
	(isset($wf_traffic['quick_abort_pct']) ? $wf_traffic['quick_abort_pct'] : 0)
))->setHelp(dgettext('BluePexWebFilter', "If more than x % of the transfer has completed, it will finish the retrieval."));

$form -> add($section);
$section = new Form_Section(dgettext("BluePexWebFilter", 'Set Load Balance'));

$section->addInput(new Form_Checkbox(
	"enable_load_balance",
	dgettext('BluePexWebFilter', "Enable Load Balance"),
	dgettext("BluePexWebFilter", "Check this option to enable WF Load Balance"),
	(isset($wf_traffic['enable_load_balance']) && $wf_traffic['enable_load_balance'] == "yes"),
	'on'
));

$section->addInput(new Form_StaticText(
	dgettext('BluePexWebFilter', "WF Load Balance"),
	dgettext('BluePexWebFilter', "<b> Choose Gateways and your weight for load balance </b>")
))->setHelp(dgettext('BluePexWebFilter', "Load balance on proxy does not work with weigth 0, values must be set between 10 and 90, the sum of links must be 100"));

if (!empty($ls_get_gateways)) {
	foreach($ls_get_gateways as $gw_key => $gw_info) {
		$if_with_gw = get_interfaces_with_gateway();

		if (!is_ipaddr($gw_info['gateway'])) {
			continue;
		}

		foreach($if_with_gw as $ifgw) {
			if ($gw_info['friendlyiface'] != $ifgw) {
				continue;
			}

			$new_lb_group = new Form_Group('');

			$gw_selected = "";
			if (isset($wf_traffic['lb_gateways']['item'])) {
				foreach ($wf_traffic['lb_gateways']['item'] as $item) {
					if ($item['gateway_name'] == $gw_key) {
						$gw_selected = $item;
						break;
					}
				}
			}

			$new_lb_group->add(new Form_Checkbox(
				"load_balances_gw_{$gw_key}",
				dgettext('BluePexWebFilter', "Load Balance GW")."{$gw_key}",
				"{$gw_info['gateway']} -> {$gw_key}",
				(!empty($gw_selected)),
				'on'
			));

			$new_lb_group->add(new Form_Select(
				"set_balance_weight_{$gw_key}",
				dgettext('BluePexWebFilter', "Set Balance Weight")."{$gw_key}",
				(isset($gw_selected['weight']) ? $gw_selected['weight'] : 0),
				array_combine(range(0,100,10), range(0,100,10))
			));

			$new_lb_group->add(new Form_StaticText(
				'',
				dgettext('BluePexWebFilter', 'Total of all gateways weight  must be 100')
			));

			$section->add($new_lb_group);
		}
	}
}

$form->addGlobal(new Form_Input(
	'instance_id',
	'',
	'hidden',
	$instance_id
));

$form ->add($section);
print $form;
}
include("foot.inc");
?>
