<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */

require("guiconfig.inc");
require('../classes/Form.class.php');
require_once("nf_config.inc");

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instance = &$config['system']['webfilter']['instance']['config'];

$wf_wb_edit = array();
if (isset($_GET['act'], $_GET['id']) && $_GET['act'] == "edit") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['nf_whitelist_blacklist'])) {
		$wf_wb_edit = $wf_instance[$instance_id]['nf_whitelist_blacklist'];
	}
}

if (isset($_GET['act'], $_GET['id']) && $_GET['act'] == "del") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['nf_whitelist_blacklist'])) {
		unset($wf_instance[$instance_id]['nf_whitelist_blacklist']);
		$savemsg = sprintf(dgettext("BluePexWebFilter", "Whitelist/Blacklist Settings for the instance '%s' removed successfully!"), $wf_instance[$instance_id]['server']['name']);
		file_put_contents(NETFILTER_CONF_DIR . "/whitelist{$instance_id}.txt", "");
		file_put_contents(NETFILTER_CONF_DIR . "/blacklist{$instance_id}.txt", "");
		write_config($savemsg);
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_whitelist_blacklist.php");
		exit;
	}
}

if (isset($_POST['save'])) {
	// Valid whitelist
	if (!empty($_POST['wlist_urls'])) {
		$urls_invalid = array();
		foreach (array_map('trim', explode("\n", $_POST['wlist_urls'])) as $wlist_url) {
			if (!preg_match("/^[\w\.-:]+$/", $wlist_url) || !preg_match("#^(http|https):\/\/#", $wlist_url)) {
				$urls_invalid[] = $wlist_url;
			}
		}
		if (!empty($urls_invalid)) {
			$input_errors[] = dgettext("BluePexWebFilter", "Whitelist do not accept meta caracters like * ^ & + $ % # @ [ ] ( ) { }");
			$input_errors[] = implode(", ", $urls_invalid);
		}
	}
	// Valid Blacklist
	if (!empty($_POST['blist_urls'])) {
		$urls_invalid = array();
		foreach (array_map('trim', explode("\n", $_POST['blist_urls'])) as $blist_url) {
			if (!preg_match("/^[\w\.-:]+$/", $blist_url) || !preg_match("#^(http|https):\/\/#", $blist_url)) {
				$urls_invalid[] = $blist_url;
			}
		}
		if (!empty($urls_invalid)) {
			$input_errors[] = dgettext("BluePexWebFilter", "Blacklist do not accept meta caracters like * ^ & + $ % # @ [ ] ( ) { }");
			$input_errors[] = implode("\n", $urls_invalid);
		}
	}
	if (empty($input_errors)) {
		$wf_instance[$instance_id]['nf_whitelist_blacklist'] = array(
			"whitelist" => !empty($_POST['wlist_urls']) ? base64_encode($_POST['wlist_urls']) : "",
			"blacklist" => !empty($_POST['blist_urls']) ? base64_encode($_POST['blist_urls']) : ""
		);
		$savemsg = dgettext('BluePexWebFilter', 'Whitelist/Blacklist updated successfully!');
		set_flash_message('success', $savemsg);
		write_config($savemsg);
		NetfilterWhitelistResync($instance_id);
		NetfilterBlacklistResync($instance_id);
		header("Location: /webfilter/wf_whitelist_blacklist.php");
		exit;
	}
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Whitelist & Blacklist'));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Rules'), false, '/webfilter/wf_content_rules.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'White/Black lists'), true, '/webfilter/wf_whitelist_blacklist.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Custom lists'), false, '/webfilter/wf_custom_list.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Extensions'), false, '/webfilter/wf_block_ext.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Block ADS'), false, '/webfilter/wf_ad_block.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Settings'), false, '/webfilter/wf_content_settings.php');
display_top_tabs($tab_array);

if (!isset($instance_id)) :
?>
<form action="/webfilter/wf_whitelist_blacklist.php" method="POST">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=dgettext("BluePexWebFilter", "White/Black Lists")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive col-md-12">
			<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th><?=dgettext("BluePexWebFilter", "Instance"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Whitelist");?></th>
					<th><?=dgettext("BluePexWebFilter", "Blacklist");?></th>
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
					<?php if (isset($instance_config['server']['parent_rules']) && is_numeric($instance_config['server']['parent_rules'])) : ?>
					<td colspan="3"><strong><?=sprintf(dgettext("BluePexWebFilter", "Using rules of the '%s' proxy instance."), $wf_instance[$instance_config['server']['parent_rules']]['server']['name'])?></strong></td>
					<?php else : ?>
					<td><?php if (isset($instance_config['nf_whitelist_blacklist'])) echo base64_decode($instance_config['nf_whitelist_blacklist']['whitelist']); ?></td>
					<td><?php if (isset($instance_config['nf_whitelist_blacklist'])) echo base64_decode($instance_config['nf_whitelist_blacklist']['blacklist']); ?></td>
					<td>
						<a href="/webfilter/wf_whitelist_blacklist.php?act=edit&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Edit Whitelist/Blacklist Settings"); ?>">
							<i class="fa fa-cog"></i>
						</a>
						<?php if (isset($instance_config['nf_whitelist_blacklist'])) : ?>
						&nbsp;
						<a href="/webfilter/wf_whitelist_blacklist.php?act=del&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Remove Whitelist/Blacklist Settings"); ?>">
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
$section = new Form_Section(dgettext("BluePexWebFilter", "Whitelist/Blacklist"));

$whitelist = "";
if (isset($wf_wb_edit['whitelist'])) {
	$whitelist = base64_decode($wf_wb_edit['whitelist']);
} elseif (isset($_POST['wlist_urls']) && !empty($_POST['wlist_urls'])) {
	$whitelist = $_POST['wlist_urls'];
}
$section->addInput(new Form_Textarea(
	'wlist_urls',
	dgettext("BluePexWebFilter", 'Whitelist'),
	(isset($wf_wb_edit['whitelist']) ? base64_decode($wf_wb_edit['whitelist']) : "")
))->setHelp(dgettext("BluePexWebFilter", 'Enter each destination domain on a new line that will be accessable to the users.'));

$blacklist = "";
if (isset($wf_wb_edit['blacklist'])) { 
	$blacklist = base64_decode($wf_wb_edit['blacklist']);
} elseif (isset($_POST['blist_urls']) && !empty($_POST['blist_urls'])) { 
	$blacklist = $_POST['blist_urls'];
}
$section->addInput(new Form_Textarea(
	'blist_urls',
	dgettext("BluePexWebFilter", 'Blacklist'),
	(isset($wf_wb_edit['blacklist']) ? base64_decode($wf_wb_edit['blacklist']) : "")
))->setHelp(dgettext("BluePexWebFilter", 'Enter each destination domain on a new line that will be blocked to the users.'));

$form->addGlobal(new Form_Input(
	'instance_id',
	'',
	'hidden',
	$instance_id
));

$form->add($section);
print $form;
}

include("foot.inc");
?>
