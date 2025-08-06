<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */
require_once("guiconfig.inc");
require_once("squid.inc");
require_once('squid_reverse.inc');

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['squidreversepeer'])) {
	$config['system']['webfilter']['squidreversepeer']['config'] = array();
}

if (isset($_REQUEST['id']) && is_numericint($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
}

if (isset($_POST['id']) && is_numericint($_POST['id'])) {
	$id = $_POST['id'];
}

$wf_revPeerConfID = $config['system']['webfilter']['squidreversepeer']['config'][$id];
if (isset($id) && $wf_revPeerConfID) {
	$wf_revPeerConf = &$wf_revPeerConfID;
}

if (isset($_POST['save'])) {
	unset($input_errors);
	squid_validate_reverse($_POST, $input_errors);

	if (!$input_errors) {
		$wf_revPeer = array();

		// General Config
		$wf_revPeer['enable'] = $_POST['enable'];
		$wf_revPeer['name'] = $_POST['name'];
		$wf_revPeer['ip'] = $_POST['ip'];
		$wf_revPeer['port'] = $_POST['port'];
		$wf_revPeer['protocol'] = $_POST['protocol'];
		$wf_revPeer['description'] = $_POST['description'];

		$config['system']['webfilter']['squidreversepeer']['config'][$id] = $wf_revPeer;
		$savemsg = dgettext("BluePexWebFilter", "Reverse Peer Settings applied successfully!");
		write_config($savemsg);
		squid_resync();
		header("Location: wf_reverse_peer.php");
		exit;
	}
}

$pgtitle = array(
	dgettext('BluePexWebFilter', 'WebFilter'), 
	dgettext('BluePexWebFilter', 'Reverse Proxy'), 
	dgettext('BluePexWebFilter', 'Web Servers'),
	dgettext('BluePexWebFilter', 'Edit')
);

include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), false, '/webfilter/wf_reverse_general.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Web Servers'), true, '/webfilter/wf_reverse_peer.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Mappings'), false, '/webfilter/wf_reverse_uri.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Redirects'), false, '/webfilter/wf_reverse_redir.php');
display_top_tabs($tab_array);

$form = new Form();
$section = new Form_Section(dgettext("BluePexWebFilter", 'WF Reverse Peer Mappings'));

$section->addInput(new Form_Checkbox(
	'enable',
	dgettext('BluePexWebFilter', 'Enable this peer'),
	dgettext('BluePexWebFilter', 'If this field is checked, then this peer will be available for reverse config.'),
	(isset($wf_revPeerConf['enable'])) ? $wf_revPeerConf['enable'] : '',
	'on'
));

$section->addInput(new Form_Input(
	'name',
	dgettext('BluePexWebFilter', 'Peer Alias'),
	'text',
	(isset($wf_revPeerConf['name']) ? $wf_revPeerConf['name'] : "")
))->setHelp(sprintf(dgettext('BluePexWebFilter', "Name to identify this peer on webfilter reverse conf %s example: HOST1"), '<br />'));

$section->addInput(new Form_Input(
	'ip',
	dgettext('BluePexWebFilter', 'Peer IP'),
	'text',
	(isset($wf_revPeerConf['ip']) ? $wf_revPeerConf['ip'] : "")
))->setHelp(sprintf(dgettext('BluePexWebFilter', "Ip Address of this peer. %s example: 192.168.0.1"), '<br />'));

$section->addInput(new Form_Input(
	'port',
	dgettext('BluePexWebFilter', 'Peer Port'),
	'text',
	(isset($wf_revPeerConf['port']) ? $wf_revPeerConf['port'] : "")
))->setHelp(sprintf(dgettext('BluePexWebFilter', "Listening port of this peer. %s example: 80"), '<br />'));

$section->addInput(new Form_Select(
	'protocol',
	dgettext('BluePexWebFilter', 'Peer Protocol'),
	(isset($wf_revPeerConf['protocol']) ? $wf_revPeerConf['protocol'] : ""),
	['HTTP' => 'HTTP', 'HTTPS' => 'HTTPS']
))->setHelp(dgettext('BluePexWebFilter', "Select protocol listening on this peer port."));

$section->addInput(new Form_Input(
	'description',
	dgettext('BluePexWebFilter', 'Peer Description'),
	'text',
	(isset($wf_revPeerConf['description']) ? $wf_revPeerConf['description'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Peer Description (optional)'));

$form ->add($section);
print $form;

include("foot.inc");
