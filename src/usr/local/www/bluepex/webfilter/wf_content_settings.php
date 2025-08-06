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
require_once('nf_config.inc');
require('../classes/Form.class.php');
require_once("bp_auditing.inc");

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instance = &$config['system']['webfilter']['instance']['config'];

$wf_cs_edit = array();
if (isset($_GET['act']) && $_GET['act'] == "edit") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['nf_content_settings'])) {
		$wf_cs_edit = $wf_instance[$instance_id]['nf_content_settings'];
	}
}

if (isset($_GET['act']) && $_GET['act'] == "del") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['nf_content_settings'])) {
		bp_write_report_db("report_0008_webfilter_setting_remove", $wf_instance[$instance_id]['server']['name']);
		unset($wf_instance[$instance_id]['nf_content_settings']);
		$savemsg = sprintf(dgettext("BluePexWebFilter", "Content Filter Settings for the instance '%s' removed successfully!"), $wf_instance[$instance_id]['server']['name']);
		write_config($savemsg);
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_content_settings.php");
		exit;
	}
}

if ($_POST['save']) {
	$instance_id = $_POST['instance_id'];
	$settings = array();
	$settings['enable'] = isset($_POST['enable']) ? "on" : "off";
	$settings['use_custom_urls'] = "";
	$settings['remote_interface'] = "";
	$settings['custom_content_url'] = "";
	$settings['content_filter_processes'] = "5";
	$log_text = (isset($_GET['act']) && $_GET['act'] == "edit") ? "report_0008_webfilter_setting_edit" : "report_0008_webfilter_setting_new";

	if (isset($_POST['remote_interface'])) {
		$settings['remote_interface'] = "on";
	}

	if (isset($_POST['redirector_process'])) {
		if ($_POST['redirector_process'] > 15 && $g['platform'] == "nanobsd") {
			$input_errors[] = dgettext("BluePexWebFilter", "Embeeded appliances must be set max of 15 process.");
		} else {
			$settings['content_filter_processes'] = $_POST['redirector_process'];
		}
	}
	if (isset($_POST['use_custom_urls'])) {
		$settings['use_custom_urls'] = "on";
		if (!isset($_POST['custom_content_url'])) {
			$input_errors[] = dgettext("BluePexWebFilter", "Custom url can't be empty.");
		} else {
			if (preg_match('/^https?:\/\/.*/', $_POST['custom_content_url'])) {
				$settings['custom_content_url'] = $_POST['custom_content_url'];
			} else {
				$input_errors[] = dgettext("BluePexWebFilter", "Url must start with http://");
			}
		}
	}
	if (empty($input_errors)) {
		if (is_numeric($instance_id)) {
			$wf_instance[$instance_id]['nf_content_settings'] = $settings;
			$savemsg = dgettext("BluePexWebFilter", "Content Filter settings applied successfully!");
			write_config($savemsg);
			$rotate_services = &$config['system']['webfilter'];
                        $rotate_services['rotate_webfilter_service']['rotate_webfilter_service_enable'] = "on";
			write_config($savemsg);
			bp_write_report_db($log_text, $wf_instance[$instance_id]['server']['name']);
			NetfilterContentSettingsResync($instance_id);
		} else {
			$savemsg = dgettext("BluePexWebFilter", "Proxy Instance not found!");
		}
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_content_settings.php");
		exit;
	}
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Content Filter Settings'));
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
$tab_array[] = array(dgettext('BluePexWebFilter', 'Block ADS'), false, '/webfilter/wf_ad_block.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Settings'), true, '/webfilter/wf_content_settings.php');
display_top_tabs($tab_array);

if (!isset($instance_id)) :
?>
<form action="/webfilter/wf_content_settings.php" method="POST">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=dgettext("BluePexWebFilter", "Content Filter Settings")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive col-md-12">
			<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th><?=dgettext("BluePexWebFilter", "Instance"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Enabled");?></th>
					<th><?=dgettext("BluePexWebFilter", "Content Filter Process");?></th>
					<th><?=dgettext("BluePexWebFilter", "Custom URL");?></th>
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
					<td><?php if (isset($instance_config['nf_content_settings'])) echo "<span class='badge'>" . $instance_config['nf_content_settings']['enable'] . "</span>"; ?></td>
					<td><?php if (isset($instance_config['nf_content_settings'])) echo $instance_config['nf_content_settings']['content_filter_processes']; ?></td>
					<td><?php if (isset($instance_config['nf_content_settings'])) echo $instance_config['nf_content_settings']['custom_content_url']; ?></td>
					<td>
						<a href="/webfilter/wf_content_settings.php?act=edit&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Edit Content Filter Settings"); ?>">
							<i class="fa fa-cog"></i>
						</a>
						<?php if (isset($instance_config['nf_content_settings'])) : ?>
						&nbsp;
						<a href="/webfilter/wf_content_settings.php?act=del&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Remove Content Filter Settings"); ?>">
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

$form = new Form;
$section = new Form_Section(dgettext('BluePexWebFilter', 'Content Settings'));

$section->addInput(new Form_Checkbox(
	'enable',
	'Enable',
	'',
	($wf_cs_edit['enable'] == "on")
))->setHelp(dgettext('BluePexWebFilter', 'Check this to enable the BluePex content filter. Note that you most likely also need to configure the content filter rules.'));

$section->addInput(new Form_Input(
	'redirector_process',
	dgettext('BluePexWebFilter', 'Content filter number processes'),
	'text',
	$wf_cs_edit['content_filter_processes']
))->setHelp(sprintf(dgettext('BluePexWebFilter', 'Enter with number process Content Filter. %s The appliance UTM 1000 must be set max of 5 process.'), '<br />'));

$section->addInput(new Form_Checkbox(
	'use_custom_urls',
	dgettext('BluePexWebFilter', 'Redirect URL'),
	'',
	($wf_cs_edit['use_custom_urls'] == "on")
))->setHelp(sprintf(dgettext('BluePexWebFilter', 'If you enable this, you will be allowed to specify the page that users will be redirected when their access is denied. %s Note that the URL you specify must be accessible by all users that are subject to filtering. %s You might want to add those URLs to the whitelist to ensure this is always the case.'), '<br />', '<br />'));

$section->addInput(new Form_Input(
	'custom_content_url',
	dgettext('BluePexWebFilter', 'Custom URL'),
	'text',
	$wf_cs_edit['custom_content_url']
))->setHelp(dgettext('BluePexWebFilter', 'Enter the URL that users will be redirected to when trying to access a blocked URL.'));

$section->addInput(new Form_Checkbox(
	'remote_interface',
	dgettext('BluePexWebFilter', 'Use Remote Interface'),
	'',
	($wf_cs_edit['remote_interface'] == "on")
))->setHelp(dgettext('BluePexWebFilter', 'Check this option to use a remote interface on the path updates.bluepex.com.'));

$form->add($section);

$form->addGlobal(new Form_Input(
	'instance_id',
	'',
	'hidden',
	$instance_id
));

print $form;
}
include('../foot.inc');
?>
