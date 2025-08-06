<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Silvio Giunge <silvio.aparecido@bluepex.com>, 2014
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("squid.inc");
require_once("webfilter.inc");
require('../classes/Form.class.php');

define("ADS_FILE_PATH", "/usr/local/etc/ads_server_list.gz");
define("ADS_FILE_ACL", "/var/squid/acl/ads_server_list.acl");

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instance = &$config['system']['webfilter']['instance']['config'];

$input_errors = array();
$savemsg = "";

$server_list_file = array();
$help_message = sprintf(dgettext("BluePexWebFilter","The Ads Blocked will be done by servers lists, that contain this kind of information. %s The advertising hosted on main website will not be blocked, because these advertisings are part of website.%s None blocked page will be showed to users."),'</br>','</br>');

function clean_item($item) {
	if (strlen($item) > 3) {
		return trim($item);
	}
}

if (!file_exists(ADS_FILE_PATH)){
	$gc = exec('/usr/local/bin/python /usr/local/bin/get_ads_servers.py', $error);
	if (!empty($error)) {
		$input_errors[] = dgettext("BluePexWebFilter", "Cannot create the ads server list, check your internet connection!");
	}
} else {
	$server_list_file = array_map("clean_item", gzfile(ADS_FILE_PATH));
}

$wf_ads_edit = array();
if (isset($_GET['act']) && $_GET['act'] == "edit") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['ads_allowed'])) {
		$wf_ads_edit = $wf_instance[$instance_id]['ads_allowed'];
	}
}

if (isset($_GET['act']) && $_GET['act'] == "del") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['ads_allowed'])) {
		unset($wf_instance[$instance_id]['ads_allowed']);
		$savemsg = sprintf(dgettext("BluePexWebFilter", "Extensions Group Settings for the instance '%s' removed successfully!"), $wf_instance[$instance_id]['server']['name']);
		write_config($savemsg);
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_ad_block.php");
		exit;
	}
}

if (isset($_POST['update_servers'])) {
	$gc = exec('/usr/local/bin/python /usr/local/bin/get_ads_servers.py', $error);
	if (!empty($error)) {
		$input_errors[] = implode("<br />", $error);
	} else {
		$savemsg = dgettext("BluePexWebFilter", "ADS Server list updated successfully!");
	}
}

if (isset($_POST['save'])) {
	$instance_id = $_POST['instance_id'];
	$ads_allowed = array();
	$ads_allowed['ads_block_active'] = isset($_POST['enable']) ? "on" : "";
	$ads_allowed['ads_servers'] = "";
	if (!empty($_POST['selected_ads'])) {
		$ads_blocked = array();
		foreach ($server_list_file as $server){
			if (!empty($server) && !in_array($server, $_POST['selected_ads']))
				array_push($ads_blocked, $server . "\n");
		}
		if (!empty($ads_blocked)){
			file_put_contents(ADS_FILE_ACL, $ads_blocked);
		}
		$selected_ads = array_map("clean_item", $_POST['selected_ads']);
		$ads_allowed['ads_servers'] = implode(",", $selected_ads);
	} elseif (!empty($server_list_file)) {
		file_put_contents(ADS_FILE_ACL, implode("\n", $server_list_file));
	}

	if (is_numeric($instance_id)) {
		$wf_instance[$instance_id]['ads_allowed'] = $ads_allowed;
		$savemsg = dgettext("BluePexWebFilter", "ADS Server list changed successfully!");
		write_config($savemsg);
		set_flash_message("success", $savemsg);
		squid_resync($instance_id);
	} else {
		set_flash_message("success", dgettext("BluePexWebFilter", "Could not to add the ADS block"));
	}
	header("Location: /webfilter/wf_ad_block.php");
	exit;
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'ADS Servers Manager'));
include('head.inc');

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Rules'), false, '/webfilter/wf_content_rules.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'White/Black lists'), false, '/webfilter/wf_whitelist_blacklist.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Custom lists'), false, '/webfilter/wf_custom_list.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Extensions'), false, '/webfilter/wf_block_ext.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Block ADS'), true, '/webfilter/wf_ad_block.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Settings'), false, '/webfilter/wf_content_settings.php');
display_top_tabs($tab_array);

print_info_box(dgettext('BluePexWebFilter', $help_message),'warning');

if (!isset($instance_id)) :
?>
<form action="/webfilter/wf_ad_block.php" method="POST">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=dgettext("BluePexWebFilter", gettext("ADS Blocked List"))?></h2></div>
	<div class="panel-body">
		<div class="table-responsive col-md-12">
			<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th><?=dgettext("BluePexWebFilter", gettext("Instance")); ?></th>
					<th><?=dgettext("BluePexWebFilter", gettext("Enabled")); ?></th>
					<th><?=dgettext("BluePexWebFilter", gettext("Servers"));?></th>
					<th></th>
				</tr>
			</thead>
			<tbody class="instance-entries">
			<?php 
			if (isset($config['system']['webfilter']['instance']['config'])) :
				foreach ($config['system']['webfilter']['instance']['config'] as $id => $instance_config) :
			?>
				<tr>
					<td><?=$instance_config['server']['name'];?></td>
					<?php if (isset($instance_config['server']['parent_rules']) && is_numeric($instance_config['server']['parent_rules'])) : ?>
					<td colspan="3"><strong><?=sprintf(dgettext("BluePexWebFilter", "Using rules of the '%s' proxy instance."), $wf_instance[$instance_config['server']['parent_rules']]['server']['name'])?></strong></td>
					<?php else : ?>
					<td><?php if (isset($instance_config['ads_allowed'])) echo $instance_config['ads_allowed']['ads_block_active']; ?></td>
					<td><?php if (isset($instance_config['ads_allowed'])) echo $instance_config['ads_allowed']['ads_servers']; ?></td>
					<td>
						<a href="/webfilter/wf_ad_block.php?act=edit&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Edit ADS Block"); ?>">
							<i class="fa fa-cog"></i>
						</a>
						<?php if (isset($instance_config['ads_allowed'])) : ?>
						&nbsp;
						<a href="/webfilter/wf_ad_block.php?act=del&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Remove ADS Block"); ?>">
							<i class="fa fa-trash"></i>
						</a>
						<?php endif; ?>
					</td>
					<?php endif; ?>
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

$form = new Form;
$section = new Form_Section(dgettext('BluePexWebFilter', 'ADS Servers Manager'));

$section->addInput(new Form_Checkbox(
	'enable',
	'Enable',
	dgettext('BluePexWebFilter', 'Check this option to enable WF ADS blocker.'),
	($wf_ads_edit['ads_block_active'] == "on"),
	'on'
));

$group = new Form_Group('');

$group->add(new Form_Button(
	'update_servers',
	dgettext('BluePexWebFilter', 'Update ADS Servers')
))->removeClass('btn-primary')->addClass('btn-warning btn-sm');

$section->add($group);

$group = new Form_Group(dgettext('BluePexWebFilter', 'ADS Servers'));

$ads_selected = isset($wf_ads_edit['ads_servers']) ? explode(",", $wf_ads_edit['ads_servers']) : array();
$_ads = array();
$_ads_selected = array();

foreach ($server_list_file as $server) {
	if (!in_array($server, $ads_selected)) {
		$_ads[$server] = $server;
	} else {
		$_ads_selected[$server] = $server;
	}
}

$group->add(new Form_Select(
	'adsdisabled',
	null,
	array(),
	$_ads,
	true
))->setHelp(dgettext('BluePexWebFilter', 'Disabled (Default)'));

$group->add(new Form_Select(
	'selected_ads',
	null,
	$_ads_selected,
	$_ads_selected,
	true
))->setHelp(dgettext('BluePexWebFilter', 'Enabled'));

$section->add($group);

$group = new Form_Group('');

$group->add(new Form_Button(
	'movetoenabled',
	dgettext('BluePexWebFilter', 'Move to enabled list >')
))->removeClass('btn-primary')->addClass('btn-default btn-sm');

$group->add(new Form_Button(
	'movetodisabled',
	dgettext('BluePexWebFilter', '< Move to disabled list')
))->removeClass('btn-primary')->addClass('btn-default btn-sm');

$section->add($group);

$form->add($section);

$form->addGlobal(new Form_Input(
	'instance_id',
	'',
	'hidden',
	$instance_id
));

print $form;
}
?>
<script type="text/javascript">
window.onload = function() {
	// Select every option in the specified multiselect
	function AllAdsServers(id, selectAll) {
		for (i = 0; i < id.length; i++) {
			id.eq(i).prop('selected', selectAll);
		}
	}
	// Move all selected options from one multiselect to another
	function moveOptions(From, To) {
		var len = From.length;
		var option;
		if (len > 0) {
			for (i=0; i<len; i++) {
				if (From.eq(i).is(':selected')) {
					text = From.eq(i).text();
					option = From.eq(i).val();
					To.append(new Option(text, option));
					From.eq(i).remove();
				}
			}
		}
	}

	// Make buttons plain buttons, not submit
	$("#movetodisabled").prop('type','button');
	$("#movetoenabled").prop('type','button');

	// On click . .
	$("#movetodisabled").click(function() {
		moveOptions($('[name="selected_ads[]"] option'), $('[name="adsdisabled[]"]'));
	});

	$("#movetoenabled").click(function() {
		moveOptions($('[name="adsdisabled[]"] option'), $('[name="selected_ads[]"]'));
	});

	// On submit mark all the extensions as "selected"
	$('form').submit(function(){
		AllAdsServers($('[name="selected_ads[]"] option'), true);
	});
};
</script>
<?php include('../foot.inc'); ?>
