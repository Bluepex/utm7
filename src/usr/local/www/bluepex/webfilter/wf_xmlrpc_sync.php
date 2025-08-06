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
require_once("config.inc");
require_once("webfilter.inc");
require_once("services.inc");
require('../classes/Form.class.php');

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['squidsync']['config'][0])) {
	$config['system']['webfilter']['squidsync']['config'][0] = array();
}
$sync =& $config['system']['webfilter']['squidsync']['config'][0];

$notsyncsections = array(
	"nf_content_rules" => dgettext('BluePexWebFilter', 'Content - Rules'),
	"nf_content_whitelist" => dgettext('BluePexWebFilter', 'Content - Whitelist'),
	"nf_content_blacklist" => dgettext('BluePexWebFilter', 'Content - Blacklist'),
	"nf_content_custom" => dgettext('BluePexWebFilter', 'Content - Custom List'),
	"nf_content_settings" => dgettext('BluePexWebFilter', 'Content - Settings'),
	"webfilter" => dgettext('BluePexWebFilter', 'Proxy Server - General'),
	"squidremote" => dgettext('BluePexWebFilter', 'Proxy Server - Upstream Proxy'),
	"squidcache" => dgettext('BluePexWebFilter', 'Proxy Server - Cache Mgmt'),
	"squidnac" => dgettext('BluePexWebFilter', 'Proxy Server - Access Control'),
	"squidtraffic" => dgettext('BluePexWebFilter', 'Proxy Server - Traffic Mgmt'),
	"squidantivirus" => dgettext('BluePexWebFilter', 'Antivirus'),
	"quarantine" => dgettext('BluePexWebFilter', 'Quarantine'),
	"nf_reports_settings" => dgettext('BluePexWebFilter', 'Report Settings'),
	"bluepexdataclickagent" => dgettext('BluePexWebFilter', 'DataClick Settings'),
	"squidreversegeneral" => dgettext('BluePexWebFilter', 'Reverse Proxy - General Settings'),
	"squidreversepeer" => dgettext('BluePexWebFilter', 'Reverse Proxy - Web Servers'),
	"squidreverseuri" => dgettext('BluePexWebFilter', 'Reverse Proxy - Mappings'),
	"squidreverseredir" => dgettext('BluePexWebFilter', 'Reverse Proxy - Redirects'),
);

$syncs = array(
	"disabled" => dgettext('BluePexWebFilter', 'Do not sync this package'),
	"auto" => dgettext('BluePexWebFilter', 'Sync Carp Settings'),
	"manual" => dgettext('BluePexWebFilter', 'Sync to host(s) defined below')
);
$timeout = array(
	"250" => 250,
	"120" => 120,
	"90" => 90,
	"60" => 60,
	"30" => 30
);

if (isset($_POST['save'])) {
	$sync['synconchanges'] = $_POST['synconchanges'];
	$sync['notsyncsection'] = isset($_POST['notsyncsection']) ? implode(",", $_POST['notsyncsection']) : "";
	$sync['synctimeout'] = $_POST['synctimeout'];

	$total_sync = count($_POST['ipaddress']);
	if ($total_sync > 0) {
		$sync['row'] = array();
		for($i = 0; $i < $total_sync; $i++) {
			if (!empty($_POST['ipaddress'][$i]) && !empty($_POST['password'][$i])) {
				$sync['row'][] = array(
					"ipaddress" => $_POST['ipaddress'][$i],
					"password" => $_POST['password'][$i]
				);
			}
		}
	} else {
		if (isset($sync['row']))
			unset($sync['row']);
	}
	$savemsg = dgettext('BluePexWebFilter', 'Settings of sync of the Webfilter changed successfully!');
	write_config($savemsg);
}

$pgtitle = array(dgettext("BluePexWebFilter", "WebFilter"), dgettext("BluePexWebFilter", "Synchronize"));
include('head.inc');

if ($input_errors)
        print_input_errors($input_errors);
if ($savemsg)
        print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Realtime'), false, '/webfilter/wf_realtime.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Webfilter Sync'), true, '/webfilter/wf_xmlrpc_sync.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Update Content'), false, '/webfilter/wf_updatecontent.php');
display_top_tabs($tab_array);

$form = new Form();

$section = new Form_Section('Settings', 'settings');

$section->addInput(new Form_Select(
	'synconchanges',
	dgettext('BluePexWebFilter', 'Sync Type'),
	(isset($sync['synconchanges']) ? $sync['synconchanges'] : ""),
	$syncs
))->setHelp(dgettext('BluePexWebFilter', 'Select a sync method for WebFilter'));

$section->addInput(new Form_Select(
	'notsyncsection',
	dgettext('BluePexWebFilter', 'Not Sync'),
	(isset($sync['notsyncsection']) ? explode(",", $sync['notsyncsection']) : ""),
	$notsyncsections,
	true
))->setHelp(dgettext('BluePexWebFilter', 'Select the sections that will not be synchronized.'));

$section->addInput(new Form_Select(
	'synctimeout',
	dgettext('BluePexWebFilter', 'Timeout'),
	(isset($sync['synctimeout']) ? $sync['synctimeout'] : ""),
	$timeout
))->setHelp(dgettext('BluePexWebFilter', 'Select sync max wait time '));

$total = !isset($sync['row']) ? 1 : count($sync['row']);
for ($i=0; $i < $total; $i++) {
        $group = new Form_Group($i == 0 ? dgettext('BluePexWebFilter', "Remote Servers") : "");
        $group->addClass('repeatable');

	$group->add(new Form_Input(
		'ipaddress[]',
		dgettext('BluePexWebFilter', 'Enter with IP Address'),
		'text',
		(isset($sync['row'][$i]) ? $sync['row'][$i]['ipaddress'] : "")
	))->setWidth(3)->setPattern('[0-9, a-z, A-Z and .');

	$group->add(new Form_Input(
		'password[]',
		dgettext('BluePexWebFilter', 'Enter with Password'),
		'password',
		(isset($sync['row'][$i]) ? $sync['row'][$i]['password'] : "")
	))->setWidth(3);

	$disabled = ($i == 0) ? " disabled" : "";
       	$group->add(new Form_Button(
               	'rm_row',
               	'Delete'
       	))->removeClass('btn-primary')->addClass('btn-danger btn-sm no-confirm' . $disabled);

	$group->add(new Form_StaticText(
		'status',
		(isset($sync['row'][$i]['status']) ? $sync['row'][$i]['status'] : sprintf(dgettext("BluePexWebFilter", "%swaiting next sync attempt!%s"), "<span class='badge'>", "</span>"))
	))->addClass('pull-left');

        $section->add($group);
}

$form->addGlobal(new Form_Button(
	'clone_row',
	dgettext('BluePexWebFilter', 'Add')
))->removeClass('btn-primary')->addClass('btn-success addbtn');

$form->add($section);

print $form;
?>
<script>
//<![CDATA[
events.push(function(){
	$("input[name='clone_row']").click(function(e) {
		e.preventDefault();
		var clone = $("#settings .panel-body .repeatable").first().clone(true);
		clone.find("label").text("");
		clone.find("input[name='rm_row']").removeClass("disabled");
		$("#settings .repeatable:last").after(clone);
		return;
	});
	$("input[name='rm_row']").click(function(e) {
		e.preventDefault();
		if ($("input[name='rm_row']").index(this) != 0) {
			$(this).parents(".repeatable").remove();
		}
		return;
	});
});
//]]>
</script>
<?php include("foot.inc"); ?>
