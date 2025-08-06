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
require_once("extensions.inc");
require('../classes/Form.class.php');

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instance = &$config['system']['webfilter']['instance']['config'];

$help_message = sprintf(
	dgettext("BluePexWebFilter", "Set up this resource will affect all users on the proxy network. 
	    If you set the extension file 'jpg' for example will block all image file that terminate with this extension. %s
	    The users will be unable to see the image file with this extension on browser. %s
	    Choose a group of extensions file or enter a specific file extension to deny the access to the file. %s
	    To choose more than one group use the Ctrl key or 'Command' key on MAC. %s
	    To enter more than one scpecific type of extension file, enter with the extension separated by commas(,)."), 
	"<br />", "<br />", "<br />", "<br />"
);

$ext_files = get_ext_files();
ksort($ext_files);

$wf_bex_edit = array();
if (isset($_GET['act']) && $_GET['act'] == "edit") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['wf_block_ext'])) {
		$wf_bex_edit = $wf_instance[$instance_id]['wf_block_ext'];
	}
}

if (isset($_GET['act']) && $_GET['act'] == "del") {
	$instance_id = (int)$_GET['id'];
	if (isset($wf_instance[$instance_id]['wf_block_ext'])) {
		unset($wf_instance[$instance_id]['wf_block_ext']);
		$savemsg = sprintf(dgettext("BluePexWebFilter", "Extensions Group Settings for the instance '%s' removed successfully!"), $wf_instance[$instance_id]['server']['name']);
		write_config($savemsg);
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_block_ext.php");
		exit;
	}
}

if (isset($_POST['save'])) {
	$instance_id = $_POST['instance_id'];
	$wf_block_ext = array();

	$wf_block_ext['ext_group'] = "";
	if (!empty($_POST['extensions'])) {
		$wf_block_ext['ext_group'] = implode(",", $_POST['extensions']);
	}
	$wf_block_ext['ext_single'] = "";
	if (!empty($_POST['custom_extensions'])) {
		$wf_block_ext['ext_single'] = trim($_POST['custom_extensions']);
	}
	if (is_numeric($instance_id)) {
		$wf_instance[$instance_id]['wf_block_ext'] = $wf_block_ext;
		$savemsg = dgettext("BluePexWebFilter", "Extensions Block Settings applied successfully!");
		write_config($savemsg);
		set_flash_message("success", $savemsg);
		squid_resync($instance_id);
	} else {
		set_flash_message("success", dgettext("BluePexWebFilter", "Could not to add the extensions block"));
	}
	header("Location: /webfilter/wf_block_ext.php");
	exit;
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Block Extensions'));
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
$tab_array[] = array(dgettext('BluePexWebFilter', 'Extensions'), true, '/webfilter/wf_block_ext.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Block ADS'), false, '/webfilter/wf_ad_block.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Settings'), false, '/webfilter/wf_content_settings.php');
display_top_tabs($tab_array);

print_info_box(dgettext('BluePexWebFilter', $help_message),'warning');

if (!isset($instance_id)) :
?>
<form action="/webfilter/wf_block_ext.php" method="POST">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=dgettext("BluePexWebFilter", "Extension Group List")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive col-md-12">
			<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th><?=dgettext("BluePexWebFilter", "Instance"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Extensions");?></th>
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
					<td><?php if (isset($instance_config['wf_block_ext'])) echo $instance_config['wf_block_ext']['ext_group']; ?></td>
					<td>
						<a href="/webfilter/wf_block_ext.php?act=edit&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Edit Extension Group"); ?>">
							<i class="fa fa-cog"></i>
						</a>
						<?php if (isset($instance_config['wf_block_ext'])) : ?>
						&nbsp;
						<a href="/webfilter/wf_block_ext.php?act=del&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Remove Extension Group"); ?>">
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
$section = new Form_Section(dgettext('BluePexWebFilter', 'Group of extensions to block'));
$group = new Form_Group(dgettext('BluePexWebFilter', 'Extensions'));

$extensions_selected = isset($wf_bex_edit['ext_group']) ? explode(",", $wf_bex_edit['ext_group']) : array();
$_extensions = array();
$_extensions_selected = array();

foreach($ext_files as $descr => $ext) {
	if (!in_array($descr, $extensions_selected)) {
		$_extensions[$descr] = $descr;
	} else {
		$_extensions_selected[$descr] = $descr;
	}
}

$group->add(new Form_Select(
	'extdisabled',
	null,
	array(),
	$_extensions,
	true
))->setHelp('Disabled');

$group->add(new Form_Select(
	'extensions',
	null,
	$_extensions_selected,
	$_extensions_selected,
	true
))->setHelp('Enabled (Default)');

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

$section->addInput(new Form_Input(
	'custom_extensions',
	dgettext('BluePexWebFilter', 'Custom Extensions'),
	'text',
	$wf_bex_edit['ext_single'],
	["placeholder" => dgettext('BluePexWebFilter', 'Entry with and specific extension of file.')]
))->setHelp(sprintf(dgettext("BluePexWebFilter", 'For more than one type of extension, enter withi the extensions separated by commas (,). Eg: jpg,png,jpeg %s %s If the file extension is within a protected area (E.g: FTP, HTTPS), the download will be done.'), "<br />", "<b>" . dgettext('BluePexWebFilter', 'Note:') . "</b>"));

$form->addGlobal(new Form_Input(
	'instance_id',
	'',
	'hidden',
	$instance_id
));

$form->add($section);

print $form;
}
?>
<script>
window.onload = function(){
	// Select every option in the specified multiselect
	function AllExtensions(id, selectAll) {
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
		moveOptions($('[name="extensions[]"] option'), $('[name="extdisabled[]"]'));
	});

	$("#movetoenabled").click(function() {
		moveOptions($('[name="extdisabled[]"] option'), $('[name="extensions[]"]'));
	});

	// On submit mark all the extensions as "selected"
	$('form').submit(function(){
		AllExtensions($('[name="extensions[]"] option'), true);
	});
};
</script>
<?php include('../foot.inc'); ?>
