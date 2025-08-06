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

require_once("nf_config.inc");
require("guiconfig.inc");
require('../classes/Form.class.php');

$input_errors = array();
$savemsg = "";

init_config_arr(array('system', 'webfilter', 'instance', 'config'));
init_config_arr(array('system', 'webfilter', 'nf_content_custom', 'element0', 'item'));

$wf_instance = &$config['system']['webfilter']['instance']['config'];

if (isset($_GET['instance_id'], $wf_instance[$_GET['instance_id']])) {
	$instance_id = (int)$_GET['instance_id'];
} else {
	set_flash_message('success', dgettext('BluePexWebFilter', 'Could not edit the custom list!'));
	header("Location: /webfilter/wf_custom_list.php");
	exit;
}

$customlist = &$config['system']['webfilter']['nf_content_custom']['element0']['item'];
$customlist_id = "";
$customlist_edt = array();

if (isset($_GET['act']) && $_GET['act'] == "edt") {
	$customlist_id = $_GET['id'];
	$instance_id = $_GET['instance_id'];
	if (isset($customlist[$customlist_id]) && !isset($customlist[$customlist_id]['novisible'])) {
		$customlist_edt = $customlist[$customlist_id];
	} else {
		set_flash_message('success', dgettext('BluePexWebFilter', 'Could not edit the custom list!'));
		header("Location: /webfilter/wf_custom_list.php");
		exit;
	}
}

if (isset($_POST['save'])) {
	if (!isset($_POST['instance_id']) || !is_numeric($_POST['instance_id'])) {
		set_flash_message('danger', dgettext('BluePexWebFilter', 'Could not add/edit the custom list!'));
		header("Location: /webfilter/wf_custom_list.php");
		exit;
	}
	if (!preg_match("/^[a-zA-Z0-9]+$/", $_POST['name'])) {
		$input_errors[] = dgettext("BluePexWebFilter", "Only letters, numbers and dot are valid characters for custom list names.");
	}

	$instance_id = $_POST['instance_id'];
	$customlist_edt = array(
		"instance_id" => $instance_id,
		"name" => $_POST['name'],
		"urls" => base64_encode($_POST['urls']),
		"descr" => $_POST['descr']
	);

	if (empty($input_errors)) {
		if (isset($_POST['customlist_id'])) {
			$customlist_id = $_POST['customlist_id'];
			$savemsg = dgettext('BluePexWebFilter', 'Custom Lists updated successfully!');
			set_flash_message('success', $savemsg);
			$customlist[$customlist_id] = $customlist_edt;
		} else {
			$customlist[] = $customlist_edt;
			$savemsg = dgettext('BluePexWebFilter', 'Custom Lists added successfully!');
			set_flash_message('success', $savemsg);
		}
		write_config($savemsg);
		NetfilterCustomListsResync();
		header("Location: /webfilter/wf_custom_list.php");
		exit;
	}
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Whitelist'));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Rules'), false, '/webfilter/wf_content_rules.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'White/Black lists'), false, '/webfilter/wf_whitelist_blacklist.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Custom lists'), true, '/webfilter/wf_custom_list.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Extensions'), false, '/webfilter/wf_block_ext.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Block ADS'), false, '/webfilter/wf_ad_block.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Settings'), false, '/webfilter/wf_content_settings.php');
display_top_tabs($tab_array);

$form = new Form;
$section = new Form_Section('Custom List');

$section->addInput(new Form_Input(
	'name',
	'Name',
	'text',
	$customlist_edt['name']
))->setHelp(dgettext('BluePexWebFilter', 'Enter the name of this list. You will refer to the list by this name.')); 

$section->addInput(new Form_Textarea(
	'urls',
	'URL\'s',
	isset($customlist_edt['urls']) ? base64_decode($customlist_edt['urls']) : ""
))->setHelp(dgettext('BluePexWebFilter', 'Enter the URL\'s you want this list to apply to, one in each line. The asterisk character (*) is a valid wildcard.'));

$section->addInput(new Form_Textarea(
	'descr',
	'Description',
	$customlist_edt['descr']
))->setHelp(dgettext('BluePexWebFilter', 'You may enter a description here, for your reference.'));

if (!empty($customlist_id) || $customlist_id == "0") {
	$form->addGlobal(new Form_Input(
		'customlist_id',
		null,
		'hidden',
		$customlist_id
	));
}

$form->addGlobal(new Form_Input(
	'instance_id',
	'',
	'hidden',
	$instance_id
));

$form->add($section);
print $form;

include("foot.inc");
?>
