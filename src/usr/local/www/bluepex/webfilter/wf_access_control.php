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

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instance = &$config['system']['webfilter']['instance']['config'];

$wf_ac_edit = array();
if (isset($_GET['act']) && $_GET['act'] == "edit") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['squidnac'])) {
		$wf_ac_edit = $wf_instance[$instance_id]['squidnac'];
	}
}

if (isset($_GET['act']) && $_GET['act'] == "del") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['squidnac'])) {
		unset($wf_instance['config'][$instance_id]['squidnac']);
		$savemsg = sprintf(dgettext("BluePexWebFilter", "Access Control Settings for the instance '%s' removed successfully!"), $wf_instance[$instance_id]['server']['name']);
		write_config($savemsg);
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_access_control.php");
		exit;
	}
}

if (isset($_POST['save'])) {
	squid_validate_nac($_POST, $input_errors);
	if (empty($input_errors)) {
		$instance_id = $_POST['instance_id'];

		// Access Control Lists
		$cf_access_control = array();
		$cf_access_control['allowed_subnets'] = !empty($_POST['allowed_subnets']) ? base64_encode($_POST['allowed_subnets']) : "";
		$cf_access_control['unrestricted_hosts'] = !empty($_POST['unrestricted_hosts']) ? base64_encode($_POST['unrestricted_hosts']) : "";
		$cf_access_control['banned_hosts'] = !empty($_POST['banned_hosts']) ? base64_encode($_POST['banned_hosts']) : "";
		$cf_access_control['whitelist'] = !empty($_POST['whitelist']) ? base64_encode($_POST['whitelist']) : "";
		$cf_access_control['blacklist'] = !empty($_POST['blacklist']) ? base64_encode($_POST['blacklist']) : "";
		$cf_access_control['ext_cachemanager'] = $_POST['ext_cachemanager'];
		$cf_access_control['block_user_agent'] = !empty($_POST['block_user_agent']) ? base64_encode($_POST['block_user_agent']) : "";
		$cf_access_control['block_reply_mime_type'] = !empty($_POST['block_reply_mime_type']) ? base64_encode($_POST['block_reply_mime_type']) : "";

		// Filter white/black lists
		$filter_regex = "/\*/";
		$return_error_white = preg_match($filter_regex, $_POST['whitelist']);
		$return_error_black = preg_match($filter_regex, $_POST['blacklist']);

		if ($return_error_white ||
		    $return_error_black) {
			set_flash_message("danger", dgettext("BluePexWebFilter", "Whitelist and/or blacklist have invalid characters in their composition, correct the values ​​to save the changes, Ex: *;"));
			header("Location: /webfilter/wf_access_control.php");
			exit;
		}

		// Allowed Ports
		$cf_access_control['addtl_ports'] = $_POST['addtl_ports'];
		$cf_access_control['addtl_sslports'] = $_POST['addtl_sslports'];

		if (is_numeric($instance_id)) {
			$wf_instance[$instance_id]['squidnac'] = $cf_access_control;
			$savemsg = dgettext("BluePexWebFilter", "Access Control Settings applied successfully!");
			write_config($savemsg);
			set_flash_message("success", $savemsg);
			squid_resync($instance_id);
		} else {
			set_flash_message("success", dgettext("BluePexWebFilter", "Could not to add the access control list"));
		}
		header("Location: /webfilter/wf_access_control.php");
		exit;
	}
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Access Control'));
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
$tab_array[] = array(dgettext('BluePexWebFilter', 'Access Control'), true, '/webfilter/wf_access_control.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Traffic Mgmt'), false, '/webfilter/wf_traffic_mgmt.php');
display_top_tabs($tab_array);
if (!isset($instance_id)) :
?>
<form action="/webfilter/wf_access_control.php" method="POST">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=dgettext("BluePexWebFilter", gettext("Access Control List"))?></h2></div>
	<div class="panel-body">
		<div class="table-responsive col-md-12">
			<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th><?=dgettext("BluePexWebFilter", gettext("Instance"));?></th>
					<th><?=dgettext("BluePexWebFilter", gettext("Allow Subnets"));?></th>
					<th><?=dgettext("BluePexWebFilter", gettext("Unrestricted Hosts"));?></th>
					<th><?=dgettext("BluePexWebFilter", gettext("Whitelist"));?></th>
					<th><?=dgettext("BluePexWebFilter", gettext("Blacklist"));?></th>
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
					<td><?php if (isset($instance_config['squidnac'])) echo base64_decode($instance_config['squidnac']['allowed_subnets']); ?></td>
					<td><?php if (isset($instance_config['squidnac'])) echo base64_decode($instance_config['squidnac']['unrestricted_hosts']); ?>
					<td><?php if (isset($instance_config['squidnac'])) echo base64_decode($instance_config['squidnac']['whitelist']); ?></td>
					<td><?php if (isset($instance_config['squidnac'])) echo base64_decode($instance_config['squidnac']['blacklist']); ?></td>
					<td>
						<a href="/webfilter/wf_access_control.php?act=edit&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Edit Access Control"); ?>">
							<i class="fa fa-cog"></i>
						</a>
						<?php if (isset($instance_config['squidnac'])) : ?>
						&nbsp;
						<a href="/webfilter/wf_access_control.php?act=del&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Remove Access Control"); ?>">
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
$section = new Form_Section(sprintf(dgettext("BluePexWebFilter", 'Access Control Lists (%s)'), $instance_name));

$section->addInput(new Form_Textarea(
	'allowed_subnets',
	dgettext('BluePexWebFilter', 'Allowed subnets'),
	(isset($wf_ac_edit['allowed_subnets']) ? base64_decode($wf_ac_edit['allowed_subnets']) : "")
))->setHelp(dgettext('BluePexWebFilter', "Enter each subnet on a new line that is allowed to use the proxy. The subnets must be expressed as CIDR ranges (e.g.: 192.168.1.0/24). Note that the proxy interface subnet is already an allowed subnet. All the other subnets won't be able to use the proxy."));

$section->addInput(new Form_Textarea(
	'unrestricted_hosts',
	dgettext('BluePexWebFilter', 'Unrestricted IPs'),
	(isset($wf_ac_edit['unrestricted_hosts']) ? base64_decode($wf_ac_edit['unrestricted_hosts']) : "")
))->setHelp(dgettext('BluePexWebFilter', "Enter unrestricted IP address / network(in CIDR format) on a new line that is not to be filtered out by the other access control directives set in this page."));

$section->addInput(new Form_Textarea(
	'banned_hosts',
	dgettext('BluePexWebFilter', 'Banned host addresses'),
	(isset($wf_ac_edit['banned_hosts']) ? base64_decode($wf_ac_edit['banned_hosts']) : "")
))->setHelp(dgettext('BluePexWebFilter', "Enter each IP address / network(in CIDR format) on a new line that is not to be allowed to use the proxy."));

$section->addInput(new Form_Textarea(
	'whitelist',
	dgettext('BluePexWebFilter', 'Whitelist'),
	(isset($wf_ac_edit['whitelist']) ? base64_decode($wf_ac_edit['whitelist']) : "")
))->setHelp(dgettext('BluePexWebFilter', "Enter each destination domain on a new line that will be accessable to the users that are allowed to use the proxy. You also can use regular expressions."));

$section->addInput(new Form_Textarea(
	'blacklist',
	dgettext('BluePexWebFilter', 'Blacklist'),
	(isset($wf_ac_edit['blacklist']) ? base64_decode($wf_ac_edit['blacklist']) : "")
))->setHelp(dgettext('BluePexWebFilter', "Enter each destination domain on a new line that will be blocked to the users that are allowed to use the proxy. You also can use regular expressions."));

$section->addInput(new Form_Input(
	'ext_cachemanager',
	dgettext('BluePexWebFilter', 'External Cache-Managers'),
	'text',
	(isset($wf_ac_edit['ext_cachemanager']) ? $wf_ac_edit['ext_cachemanager'] : "")
))->setHelp(dgettext('BluePexWebFilter', "Enter the IPs for the external Cache Managers to be allowed here, separated by semi-colons (;)."));

$section->addInput(new Form_Textarea(
	'block_user_agent',
	dgettext('BluePexWebFilter', 'Block user agents'),
	(isset($wf_ac_edit['block_user_agent']) ? base64_decode($wf_ac_edit['block_user_agent']) : "")
))->setHelp(dgettext('BluePexWebFilter', "Enter each user agent on a new line that will be blocked to the users that are allowed to use the proxy. You also can use regular expressions."));

$section->addInput(new Form_Textarea(
	'block_reply_mime_type',
	dgettext('BluePexWebFilter', 'Block MIME types (reply only)'),
	(isset($wf_ac_edit['block_reply_mime_type']) ? base64_decode($wf_ac_edit['block_reply_mime_type']) : "")
))->setHelp(dgettext('BluePexWebFilter', "Enter each MIME type on a new line that will be blocked to the users that are allowed to use the proxy. You also can use regular expressions. Useful to block javascript (application/x-javascript)."));

$form ->add($section);
$section = new Form_Section(dgettext("BluePexWebFilter", 'Allowed Ports'));

$section->addInput(new Form_Input(
	'addtl_ports',
	dgettext('BluePexWebFilter', 'acl safeports'),
	'text',
	(isset($wf_ac_edit['addtl_ports']) ? $wf_ac_edit['addtl_ports'] : "")
))->setHelp(dgettext('BluePexWebFilter', "This is a space-separated list of 'safe ports' in addition to the already defined list: 21 70 80 210 280 443 488 563 591 631 777 901 1025-65535"));

$section->addInput(new Form_Input(
	'addtl_sslports',
	dgettext('BluePexWebFilter', 'acl sslports'),
	'text',
	(isset($wf_ac_edit['addtl_sslports']) ? $wf_ac_edit['addtl_sslports'] : "")
))->setHelp(dgettext('BluePexWebFilter', "This is a space-separated list of ports to allow SSL 'CONNECT' in addition to the already defined list: 443 563"));

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
