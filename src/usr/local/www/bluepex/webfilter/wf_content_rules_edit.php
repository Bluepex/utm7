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

require_once("guiconfig.inc");
require_once("classes/Form.class.php");
require_once("nf_defines.inc");
require_once("nf_config.inc");

$input_errors = array();
$savemsg = "";

init_config_arr(array('system', 'webfilter', 'instance', 'config'));
init_config_arr(array('system', 'webfilter', 'nf_content_rules', 'element0', 'item'));
init_config_arr(array('system', 'webfilter', 'nf_content_custom', 'element0', 'item'));

$wf_instance = &$config['system']['webfilter']['instance']['config'];

if (isset($_GET['instance_id'], $wf_instance[$_GET['instance_id']]) ) {
	$instance_id = (int)$_GET['instance_id'];
} else {
	set_flash_message("error", dgettext("BluePexWebFilter", "Could not to create the rule."));
	header("Location: /webfilter/wf_content_rules.php");
	exit;
}

$customlist = &$config['system']['webfilter']['nf_content_custom']['element0']['item'];

$rules = &$config['system']['webfilter']['nf_content_rules']['element0']['item'];

$disableCustomList = [];
if (file_exists('/etc/disableCustomListWebfilter')) {
	foreach (array_unique(explode(';',file_get_contents('/etc/disableCustomListWebfilter'))) as $line_now) {
		$disableCustomList[] = $line_now . "_customlist";
	}
}

$rule_edit = array();
if (isset($_GET['act'], $_GET['id'], $_GET['instance_id']) && $_GET['act'] == "edit") {
	$rule_id = (int)$_GET['id'];
	$instance_id = (int)$_GET['instance_id'];
	$rule_edit = $rules[$rule_id];
}

if (isset($_POST['save'])) {
	if (!isset($_POST['instance_id']) || !is_numeric($_POST['instance_id'])) {
		set_flash_message("error", dgettext("BluePexWebFilter", "Could not to create the rule."));
		header("Location: /webfilter/wf_content_rules.php");
		exit;
	}
	$instance_id = $_POST['instance_id'];
	$new_rule = array();
	$new_rule['instance_id'] = $instance_id;
	$new_rule['disabled'] = isset($_POST['disabled']) ? "on" : "off";
	$new_rule['type'] = $_POST['type'];
	$new_rule['action'] = $_POST['action'];

	$regex_address = [
	    'single_address' => '^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$',
	    'mult_address' => '^(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(?:,(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))*$',
	    'description' => '^[a-zA-Z0-9]+$'
	];

	$msg_error_return = [
	    'msg1' => dgettext('BluePexWebFilter', 'Address IP List invalid! Use with Ex: 192.168.100.100 or 192.168.0.100,192.168.0.101'),
	    'msg2' => dgettext('BluePexWebFilter', 'IP Range invalid! Ex: 192.168.0.100 and 192.168.0.101'),
	    'msg3' => dgettext('BluePexWebFilter', 'Subnet address is invalid, confirm the address for it to be applied! Ex: 192.168.0.100'),
	    'msg4' => dgettext('BluePexWebFilter', 'The description of a rule can only contain lowercase characters, uppercase characters and numbers, and cannot contain any characters other than these. Please adapt the title of the description to save the changes. Ex: RuleFilterNoticias1')
	];

	$return_error_address = false;
	$errors_msg = [];

	switch ($_POST['type']) {
		case "users":
			$new_rule['users'] = implode(",", $_POST['users_selected']);
			break;
		case "groups":
			$new_rule['groups'] = implode(",", $_POST['groups_selected']);
			break;
		case "ip":
			$return_error_address = (!preg_match("/{$regex_address['mult_address']}/", $_POST['ipaddress']));
			if ($return_error_address) { $errors_msg[] = $msg_error_return["msg1"]; }
			$new_rule['ip'] = $_POST['ipaddress'];
			break;
		case "range":
			$return_error_address = ((!preg_match("/{$regex_address['single_address']}/", $_POST['start_range'])) ||
			    (!preg_match("/{$regex_address['single_address']}/", $_POST['end_range'])) ||
			    intval(str_replace(".", "", $_POST['start_range'])) >= intval(str_replace(".", "", $_POST['end_range'])));
			if ($return_error_address) { $errors_msg[] = $msg_error_return["msg2"]; }
			$new_rule['range'] = $_POST['start_range'] . "-" . $_POST['end_range'];
			break;
		case "subnet":
			$return_error_address = (!preg_match("/{$regex_address['single_address']}/", $_POST['subnet_ipaddress']));
			if ($return_error_address) { $errors_msg[] = $msg_error_return["msg3"]; }
			$new_rule['subnet'] = $_POST['subnet_ipaddress'] . "/" . $_POST['srcmask'];
			break;
		default:
			break;
	}

	$return_error_msg = (!preg_match("/{$regex_address['description']}/", $_POST['description']));
	if ($return_error_msg) { $errors_msg[] = $msg_error_return["msg4"]; }
	$new_rule['description'] = $_POST['description'];

	if ($return_error_address ||
	    $return_error_msg) {
		$return_error = "<h5>" . dgettext('BluePexWebFilter', "Invalid values:") . "</h5>";
		$return_error .= "<ul>";
		foreach ($errors_msg as $line_error) { $return_error .= "<li>" . $line_error . "</li>"; }
		$return_error .= "</ul>";

		set_flash_message("danger", $return_error);
		header("Location: /webfilter/wf_content_rules.php");
		exit;
	}

	if ($_POST['action'] == "selected" && isset($_POST['categories_selected']))
		$new_rule['categories'] = implode(",", $_POST['categories_selected']);

	$new_rule['whitelist'] = isset($_POST['whitelist']) ? "on" : "off";
	$new_rule['blacklist'] = isset($_POST['blacklist']) ? "on" : "off";

	if (isset($_POST['customlist_action'])) {
		$custom_allow = array();
		$custom_block = array();
		foreach ($_POST['customlist_action'] as $custom => $action) {
			if ($action == "allow") {
				$custom_allow[] = $custom;
			} elseif ($action == "block") {
				$custom_block[] = $custom;
			}
		}
		// Left side will be allowed | Right side will be blocked
		$new_rule['custom_lists'] = implode(",", $custom_allow) . "|" . implode(",", $custom_block);
	}
	if (isset($_POST['timeselect_enable'])) {
		$new_rule['time_match'] = $_POST['timeselect_values'];
	}

	if (isset($_POST['rule_id'], $rules[$_POST['rule_id']]) && $rules[$_POST['rule_id']]['instance_id'] == $instance_id) {
		$rules[$_POST['rule_id']] = $new_rule;
	} else {
		$rules[] = $new_rule;
	}

	$savemsg = dgettext("BluePexWebFilter", "Content Rule inserted successfully!");
	set_flash_message("success", $savemsg);
	write_config($savemsg);
	NetfilterContentRulesResync();
	header("Location: /webfilter/wf_content_rules.php");
	exit;
}

function custom_list_table($instance_id, $selected_customlist = "", $disableCustomList) {
	global $customlist;

	if (empty($customlist)) {
		return "<h3>" . dgettext("BluePexWebFilter", "No custom lists configured!") . "</h3>";
	}

	$customlist_allowed = array(); 
	$customlist_blocked = array();
	if (!empty($selected_customlist)) {
		list($allowed, $blocked) = explode("|", $selected_customlist);
		$customlist_allowed = explode(",", $allowed);
		$customlist_blocked = explode(",", $blocked);
	}

	$table = "<table class='table table-hover table-striped table-condensed'>";
	$table .= "<thead><tr><th>Description</th><th colspan='3'>Permission</th></tr></thead>";
	$table .= "<tbody>";
	foreach ($customlist as $id => $list) {
		if (!in_array("{$id}_customlist", $disableCustomList)) {
			if (isset($list['novisible']) || !isset($list['instance_id']) || ($list['instance_id'] != $instance_id)) {
				continue;
			}
			$value = "{$id}:{$list['name']}";
			$selected = "";
			if (in_array($value, $customlist_allowed)) {
				$selected = "allow";
			} elseif (in_array($value, $customlist_blocked)) {
				$selected = "block";
			}
			$table .= "<tr>";
			$table .= "<td class='customlist'>{$list['name']}</td>";
			$disableAction="";
			$checked = ($selected == "allow") ? "checked" : "";
			$table .= "<td class='customlist'><input name='customlist_action[{$value}]' value='allow' type='radio' {$checked} {$disableAction}/><b class='permissionList'>" . dgettext("BluePexWebFilter", "Allow") . "</b></td>";
			$checked = ($selected == "block") ? "checked" : "";
			$table .= "<td class='customlist'><input name='customlist_action[{$value}]' value='block' type='radio' {$checked} {$disableAction}/><b class='permissionList'>" . dgettext("BluePexWebFilter", "Block") . "</b></td>";
			$checked = ($selected != "allow" && $selected != "block") ? "checked" : "";
			$table .= "<td class='customlist'><input name='customlist_action[{$value}]' value='ignore' type='radio' {$checked} {$disableAction}/><b class='permissionList'>" . dgettext("BluePexWebFilter", "Ignore") . "</b></td>";
			$table .= "</tr>";
		}
	}
	$table .= "</tbody>";
	$table .= "</table>";
	return $table;
}

function time_select_table($time_selected = "") {
	$table .= "<input type='hidden' name='timeselect_values' value='{$time_selected}' id='RegraHorarios' />";
	$table .= "<table class='horarios'>";
	$table .= "<tr>";
	$table .= "<th></th>";
	for ($i = 0; $i < 24; ++$i) {
		if ($i < 10) {
			$i = "0{$i}";
		}
		$table .= "<th class='hora' colspan='2'>{$i}</th>";
	}
	$table .= "</tr>";

	$dias = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
	for ($i = 0; $i < 7; ++$i) {
		$table .= "<tr class='dia_{$i}'><th class='dia'>{$dias[$i]}</th>";
		for ($j = 0; $j < 24; ++$j) {
			$table .= "<td class='hora_{$j} min_0'></td>";
			$table .= "<td class='hora_{$j} min_30'></td>";
		}
		$table .= "</tr>";
	}
	$table .= "</table>";
	return $table;
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Content Rule'));

include("head.inc");
echo '<link rel="stylesheet" href="netfilter.css" type="text/css" />';
?>
<style>
.table > thead > tr > th {
    border-bottom-width: 2px;
    background: #108ad0;
    color: #fff;
    text-align: center!important;
    padding: 7px;
    font-size: 14px!important;
}
.table > tbody > tr > td {
	border: 1px solid white;
}
.permissionList {
    margin-left: 4px;
    vertical-align: text-bottom;
    font-weight: normal;
}
</style>
<?php

if ($input_errors)
	print_input_errors($input_errors); 
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Rules'), true, '/webfilter/wf_content_rules.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'White/Black lists'), false, '/webfilter/wf_whitelist_blacklist.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Custom lists'), false, '/webfilter/wf_custom_list.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Extensions'), false, '/webfilter/wf_block_ext.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Block ADS'), false, '/webfilter/wf_ad_block.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Settings'), false, '/webfilter/wf_content_settings.php');
display_top_tabs($tab_array);

$form = new Form();
$section = new Form_Section(dgettext("BluePexWebFilter", 'Content Rules'));

$section->addInput(new Form_Checkbox(
	'disabled',
	gettext('Disable '),
	dgettext("BluePexWebFilter", 'Check to disable this rule.'),
	(isset($rule_edit['disabled']) && $rule_edit['disabled'] == "on"),
	'on'
));

$type_rule = array(
	"default" => dgettext('BluePexWebFilter', "All"),
	"users" => dgettext('BluePexWebFilter', "User names"),
	"groups" => dgettext('BluePexWebFilter', "User groups"),
	"ip" => dgettext('BluePexWebFilter', "IP address"),
	"range" => dgettext('BluePexWebFilter', "IP range"),
	"subnet" => dgettext('BluePexWebFilter', "Subnet")
);
$section->addInput(new Form_Select(
	'type',
	'Match',
	(isset($rule_edit['type']) ? $rule_edit['type'] : ""),
	$type_rule
))->setHelp(dgettext('BluePexWebFilter', 'Select how this rule will match the connections.'));

$group = new Form_Group('Users');

$users_selected = isset($rule_edit['users']) ? explode(",", $rule_edit['users']) : array();
$_users = array();
$_users_selected = array();

foreach ($config['system']['user'] as $usr) {
	if ($usr['scope'] == 'system')
		continue;

	$descr = $usr['name'] . (!empty($usr['descr']) ? " - {$usr['descr']}" : "");
	$value = !isset($usr['objectguid']) ? $usr['uid'] : $usr['objectguid'];
	if (!in_array($value, $users_selected)) {
		$_users[$value] = $descr;
	} else {
		$_users_selected[$value] = $descr;
	}
}
$group->add(new Form_Select(
	'users_disabled',
	null,
	array(),
	$_users,
	true
))->setHelp(gettext('Disabled '));

$group->add(new Form_Select(
	'users_selected',
	null,
	$_users_selected,
	$_users_selected,
	true
))->setHelp(gettext('Enabled (Default) '));

$section->add($group);

$group = new Form_Group('');

$group->add(new Form_Button(
	'move_users_toenabled',
	dgettext('BluePexWebFilter', 'Move to enabled list >')
))->removeClass('btn-primary')->addClass('btn-default btn-sm btn-success');

$group->add(new Form_Button(
	'move_users_todisabled',
	dgettext('BluePexWebFilter', '< Move to disabled list')
))->removeClass('btn-primary')->addClass('btn-default btn-sm btn-warning');

$section->add($group);

$group = new Form_Group(dgettext('BluePexWebFilter', 'Groups'));

$groups_selected = isset($rule_edit['groups']) ? explode(",", $rule_edit['groups']) : array();
$_groups = array();
$_groups_selected = array();

foreach ($config['system']['group'] as $grp) {
	if ($grp['scope'] == 'system') {
		continue;
	}
	$descr = $grp['name'] . (!empty($grp['description']) ? " - {$grp['description']}" : "");
	$value = !isset($grp['objectguid']) ? $grp['gid'] : $grp['objectguid'];
	if (!in_array($value, $groups_selected)) {
		$_groups[$value] = $descr;
	} else {
		$_groups_selected[$value] = $descr;
	}
}
$group->add(new Form_Select(
	'groups_disabled',
	null,
	array(),
	$_groups,
	true
))->setHelp('Disabled');

$group->add(new Form_Select(
	'groups_selected',
	null,
	$_groups_selected,
	$_groups_selected,
	true
))->setHelp('Enabled (Default)');

$section->add($group);

$group = new Form_Group('');

$group->add(new Form_Button(
	'move_groups_toenabled',
	dgettext('BluePexWebFilter', 'Move to enabled list >')
))->removeClass('btn-primary')->addClass('btn-default btn-sm btn-success');

$group->add(new Form_Button(
	'move_groups_todisabled',
	dgettext('BluePexWebFilter', '< Move to disabled list')
))->removeClass('btn-primary')->addClass('btn-default btn-sm btn-warning');

$section->add($group);

$section->addInput(new Form_Input(
	'ipaddress',
	dgettext('BluePexWebFilter', 'IP Address'),
	'text',
	(isset($rule_edit['ip']) ? $rule_edit['ip'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'For matching by IP, specify the IP that should match this rule.'));

$group = new Form_Group('Range');

$start_range = $end_range = "";
if (isset($rule_edit['range'])) {
	list($start_range, $end_range) = explode("-", $rule_edit['range']);
}
$group->add(new Form_Input(
	'start_range',
	null,
	'text',
	$start_range,
	["placeholder" => dgettext('BluePexWebFilter', "Enter the start range")]
));

$group->add(new Form_Input(
	'end_range',
	null,
	'text',
	$end_range,
	["placeholder" => dgettext('BluePexWebFilter', "Enter the end range")]
));

$group->setHelp(dgettext('BluePexWebFilter', 'If you selected matching by IP range, you can specify here the range that will match this rule (note that both the start and the end of the range are considered part of the range).'));
$section->add($group);

$ipaddress = "";
$mask = "";
if (isset($rule_edit['subnet'])) {
	list($ipaddress, $mask) = explode("/", $rule_edit['subnet']);
}
$section->addInput(new Form_IpAddress(
	'subnet_ipaddress',
	'Subnet',
	(!empty($ipaddress) ? $ipaddress : '')
))->setPattern('[.a-zA-Z0-9_]+')->addMask('srcmask', $mask)->setWidth('5')->setHelp(dgettext('BluePexWebFilter', 'If you selected matching by subnet, you can specify here the subnet that will match this rule.'));

$actions_rule = array(
	"allow" => dgettext('BluePexWebFilter', "Allow all content"),
	"block" => dgettext('BluePexWebFilter', "Block all content"),
	"selected" => dgettext('BluePexWebFilter', "Block the selected categories")
);

$section->addInput(new Form_Select(
	'action',
	dgettext('BluePexWebFilter', 'Action'),
	(isset($rule_edit['action']) ? $rule_edit['action'] : ""),
	$actions_rule
))->setHelp(dgettext('BluePexWebFilter', 'Select the action for this rule.'));

$group = new Form_Group('Categories');

$cats_selected = array();
if (isset($rule_edit['categories'])) {
	$cats_selected = explode(",", $rule_edit['categories']);
}
$_cats = array();
$_cats_selected = array();
$categories = NetfilterGetContentCategories();
foreach ($categories as $id => $cat) {
	if (!in_array($id, $cats_selected)) {
		$_cats[$id] = $cat;
	} else {
		$_cats_selected[$id] = $cat;
	}
}
$group->add(new Form_Select(
	'categories_disabled',
	null,
	array(),
	$_cats,
	true
))->setHelp(dgettext('BluePexWebFilter', 'Disabled'));

$group->add(new Form_Select(
	'categories_selected',
	null,
	$_cats_selected,
	$_cats_selected,
	true
))->setHelp(dgettext('BluePexWebFilter', 'Enabled (Default)'));

$section->add($group);

$group = new Form_Group('');

$group->add(new Form_Button(
	'move_categories_toenabled',
	dgettext('BluePexWebFilter', 'Move to enabled list >')
))->removeClass('btn-primary')->addClass('btn-default btn-sm btn-success');

$group->add(new Form_Button(
	'move_categories_todisabled',
	dgettext('BluePexWebFilter', '< Move to disabled list')
))->removeClass('btn-primary')->addClass('btn-default btn-sm btn-warning');

$section->add($group);

$section->addInput(new Form_Checkbox(
	'whitelist',
	dgettext('BluePexWebFilter', 'Whitelist'),
	dgettext("BluePexWebFilter", 'Apply the whitelist to this rule'),
	(isset($rule_edit['whitelist']) && $rule_edit['whitelist'] == "on"),
	'on'
))->setHelp(dgettext('BluePexWebFilter', 'If those checkboxes are enabled, the whitelist will be used for this rule.'));

$section->addInput(new Form_Checkbox(
	'blacklist',
	dgettext('BluePexWebFilter', 'Blacklist'),
	dgettext("BluePexWebFilter", 'Apply the blacklist to this rule'),
	(isset($rule_edit['blacklist']) && $rule_edit['blacklist'] == "on"),
	'on'
))->setHelp(dgettext('BluePexWebFilter', 'If those checkboxes are enabled, the blacklist will be used for this rule. Note that the whitelist takes precedence over the blacklist'));

$customlists = isset($rule_edit['custom_lists']) ? $rule_edit['custom_lists'] : "";
$section->addInput(new Form_StaticText(
	dgettext('BluePexWebFilter', 'Custom List'),
	custom_list_table($instance_id, $customlists, $disableCustomList)
))->setHelp(dgettext('BluePexWebFilter', 'Select how each custom list will affect this rule (as a whitelist, a blacklist or not affect at all).') . '<br>' .  dgettext('BluePexWebFilter', '<b style="color:red;font-weight:normal;">If the field is disabled, check if the custom list is disabled in the <a href="./wf_custom_list.php">custom list screen</a>.</b>'));

$section->addInput(new Form_Checkbox(
	'timeselect_enable',
	dgettext('BluePexWebFilter', 'Enable Period'),
	dgettext("BluePexWebFilter", 'Check to enable Period.'),
	isset($rule_edit['time_match']),
	''
));

$section->addInput(new Form_StaticText(
	'',
	time_select_table($rule_edit['time_match'])
))->setHelp('');

$section->addInput(new Form_Input(
	'description',
	dgettext('BluePexWebFilter', 'Description'),
	'text',
	(isset($rule_edit['description']) ? $rule_edit['description'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'You may enter a description here, for your reference (not parsed).'));

if (isset($rule_id)) {
	$section->addInput(new Form_Input(
		'rule_id',
		null,
		'hidden',
		$rule_id
	));
}

$form->add($section);

$form->addGlobal(new Form_Input(
	'instance_id',
	'',
	'hidden',
	$instance_id
));

print $form;
?>
<script type='text/javascript'>
window.onload = function(){
	function MoveElementToBox(id, selectAll) {
		for (i = 0; i < id.length; i++) {
			id.eq(i).prop('selected', selectAll);
		}
	}
	function moveOptions(From, To)	{
		var len = From.length;
		var option;

		if(len > 0) {
			for(i=0; i<len; i++) {
				if(From.eq(i).is(':selected')) {
					text = From.eq(i).text();
					option = From.eq(i).val();
					To.append(new Option(text, option));
					From.eq(i).remove();
				}
			}
		}
	}

	$("input[id$=todisabled]").prop('type','button');
	$("input[id$=toenabled]").prop('type','button');

	$("input[id$=todisabled]").click(function() {
		var move_element = $(this).attr("name");
		if (move_element == "move_users_todisabled") {
			moveOptions($('[name="users_selected[]"] option'), $('[name="users_disabled[]"]'));
		} else if (move_element == "move_groups_todisabled") {
			moveOptions($('[name="groups_selected[]"] option'), $('[name="groups_disabled[]"]'));
		} else if (move_element == "move_categories_todisabled") {
			moveOptions($('[name="categories_selected[]"] option'), $('[name="categories_disabled[]"]'));
		}
	});

	$("input[id$=toenabled]").click(function() {
		var move_element = $(this).attr("name");
		if (move_element == "move_users_toenabled") {
			moveOptions($('[name="users_disabled[]"] option'), $('[name="users_selected[]"]'));
		} else if (move_element == "move_groups_toenabled") {
			moveOptions($('[name="groups_disabled[]"] option'), $('[name="groups_selected[]"]'));
		} else if (move_element == "move_categories_toenabled") {
			moveOptions($('[name="categories_disabled[]"] option'), $('[name="categories_selected[]"]'));
		}
	});

	$('form').submit(function(){
		var type_rule = $("#type").val();
		if (type_rule == "users") {
			MoveElementToBox($('[name="users_selected[]"] option'), true);
		} else if (type_rule == "groups") {
			MoveElementToBox($('[name="groups_selected[]"] option'), true);
		}
		MoveElementToBox($('[name="categories_selected[]"] option'), true);
		calcular_horarios_salvamento();
	});

	var box_elements = [
		["users", $("select[name='users_disabled[]']").parents(".form-group"), $("input[name='move_users_toenabled']").parents(".form-group") ],
		["groups", $("select[name='groups_disabled[]']").parents(".form-group"), $("input[name='move_groups_toenabled']").parents(".form-group") ],
		["ip", $("input[name='ipaddress']").parents(".form-group")],
		["range", $("input[name='start_range']").parents(".form-group")],
		["subnet", $("input[name='subnet_ipaddress']").parents(".form-group")],
		["categories", $("select[name='categories_disabled[]']").parents(".form-group"), $("input[name='move_categories_toenabled']").parents(".form-group") ],
	];

	for (var i = 0; i < box_elements.length; i++) {
		if ($("#type").val() == box_elements[i][0]) {
			continue;
		} else if (box_elements[i][0] == "categories" && $("#action").val() == "selected") {
			continue;
		}
		box_elements[i][1].hide();
		if (box_elements[i][2])
			box_elements[i][2].hide();
	}
	$("#type").change(function() {
		var type = $(this).val();
		for (var i = 0; i < box_elements.length; i++) {
			if (box_elements[i][0] == type) {
				box_elements[i][1].show();
				if (box_elements[i][2])
					box_elements[i][2].show();
			} else {
				box_elements[i][1].hide();
				if (box_elements[i][2])
					box_elements[i][2].hide();
			}
		}
	});

	$("#action").change(function() {
		var action = $(this).val();
		if (action == "selected") {
			box_elements[5][1].show();
			box_elements[5][2].show();
		} else {
			box_elements[5][1].hide();
			box_elements[5][2].hide();
		}
	});

	function decodificar_horario(item)
	{
		var horario = 0;
		var classes = item.attr('class').split(' ');
		$.each(classes, function(i, cls) {
			if (cls.indexOf('hora_') == 0)
				horario += parseInt(cls.split('_')[1], 10) * 100;
			else if (cls == 'min_30')
				horario += 30;
		});
		return horario;
	}

	function calcular_horarios_salvamento()
	{
		if ($('#timeselect_enable:checked').length == 0) {
			$('#RegraHorarios').val('');
			return;
		}

		var res = new Array();
		var dia = 0;
		$('table.horarios tr').not(':first').each(function(i, tr) {
			var base = $(tr).children('td.selected:first');
			while (base.length) {
				var prefixo = dia + '-' + decodificar_horario(base) + '-';
				var first_not = base.nextAll().not('.selected').filter(':first');
				if (first_not.length) {
					res.push(prefixo + decodificar_horario(first_not));
					base = first_not.nextAll('.selected:first');
				} else {
					res.push(prefixo + '2400');
					break;
				}
			}
			++dia;
		});
		$('#RegraHorarios').val(res.join(','));
	}

	function atualizar_horarios()
	{
		if ($('#timeselect_enable:checked').length)
			$('table.horarios').removeClass('disabled');
		else
			$('table.horarios').addClass('disabled');
	}
	$('#timeselect_enable').change(atualizar_horarios);
	atualizar_horarios();

	$('table.horarios td').click(function() {
	if (!$('table.horarios').hasClass('disabled'))
		$(this).toggleClass('selected');
	});
	$('table.horarios th.dia').click(function() {
		if (!$('table.horarios').hasClass('disabled')) {
			if ($(this).parent().children('td.selected:first').length)
				$(this).parent().children('td').removeClass('selected');
			else
				$(this).parent().children('td').addClass('selected');
		}
	});
	$('table.horarios th.hora').click(function() {
		if (!$('table.horarios').hasClass('disabled')) {
			var hora = 'hora_' + parseInt($(this).html(), 10);
			if ($('table.horarios td.' + hora).filter('.selected:first').length)
				$('table.horarios td.' + hora).removeClass('selected');
			else
				$('table.horarios td.' + hora).addClass('selected');
		}
	});

	$.each($('#RegraHorarios').val().split(','), function(i, horario) {
		var tmp = horario.split('-');
		var start = parseInt(tmp[1], 10);
		start = Math.floor(start / 100) * 2 + (start % 100 ? 1 : 0);
		var end = parseInt(tmp[2], 10);
		end = Math.floor(end / 100) * 2 + (end % 100 ? 1 : 0);
		$('table.horarios tr.dia_' + tmp[0] + ' td').each(function(i, td) {
			if (i >= start && i < end)
				$(td).addClass('selected');
		});
	});
};
</script>
<?php include("foot.inc"); ?>
