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

require_once('guiconfig.inc');
require_once('util.inc');
require_once('services.inc');
require_once('webfilter.inc');
require_once("bp_webservice.inc");
require_once("firewallapp_functions.inc");
require_once("webfilter.inc");

#redirect_licensed_area('Reports');

if (file_exists("/etc/inc/dataclick_report.inc")) {
	ini_set('default_socket_timeout', 5);
	require_once('dataclick_report.inc');
}

$input_errors = array();
$savemsg = "";
$ifaces = get_configured_interface_with_descr();
$ifaces["lo0"] = "loopback";

init_config_arr(array('system', 'webfilter', 'nf_reports_settings', 'element0'));

$settings = &$config['system']['webfilter']['nf_reports_settings']['element0'];

$ifaces = get_configured_interface_list();

$all_gtw = getInterfacesInGatewaysWithNoExceptions();


if (isset($_POST['save'])) {

	init_config_arr(array('system', 'webfilter'));
	$rotate_services = &$config['system']['webfilter'];

	$statusOperationWebfilter = $_POST['rotate_webfilter_service_enable'] == "yes" ? "on" : "off";
	$statusOperationSSH = $_POST['rotate_ssh_service_enable'] == "yes" ? "on" : "off";
	$statusOperationACP = $_POST['rotate_acp_service_enable'] == "yes" ? "on" : "off";
	$statusOperationFAPP = $_POST['rotate_fapp_service_enable'] == "yes" ? "on" : "off";
	
	$rotate_services['rotate_webfilter_service']['rotate_webfilter_service_enable'] = $statusOperationWebfilter;
	$rotate_services['rotate_ssh_service']['rotate_ssh_service_enable'] = $statusOperationSSH;
	
	$rotate_services['rotate_acp_service']['rotate_acp_service_enable'] = $statusOperationACP;
	$rotate_services['rotate_acp_service']['rotate_acp_service_interfaces'] = implode(",",$_POST['rotate_acp_service_interfaces']);
	
	$rotate_services['rotate_fapp_service']['rotate_fapp_service_enable'] = $statusOperationFAPP;
	$rotate_services['rotate_fapp_service']['rotate_fapp_service_interfaces'] = implode(",",$_POST['rotate_fapp_service_interfaces']);

	if (
		($statusOperationWebfilter == "on") ||
		($statusOperationSSH == "on") ||
		($statusOperationACP == "on") ||
		($statusOperationFAPP == "on")
	) {
		$savemsg = "Save option in mode 'Activate by interfaces'.";
	}

	if (
		($statusOperationWebfilter == "off") &&
		($statusOperationSSH == "off") &&
		($statusOperationACP == "off") &&
		($statusOperationFAPP == "off")
	) {
		$savemsg = "'Activate by interfaces' mode is disabled.";
	}

	init_config_arr(array('system', 'webfilter', 'nf_reports_settings', 'element0'));
	$settings_old_operation = &$config['system']['webfilter']['nf_reports_settings']['element0'];
	if ($settings_old_operation['enable'] == "on") {
		if (
			($statusOperationWebfilter == "on") ||
			($statusOperationSSH == "on") ||
			($statusOperationACP == "on") ||
			($statusOperationFAPP == "on")
		) {
			$settings_old_operation['enable'] = "";	
			$savemsg += "<br>'Configuration' page operating mode has been disabled.";
		}
	}
	
	write_config("Bluepex: Save options to wf register");	
	filter_configure();
        shell_exec("/usr/local/bin/python3.8 /usr/local/sbin/wfrotated restart");
}

$pgtitle = array(dgettext(gettext("BluePexReports"), gettext('FirewallApp')), dgettext(gettext("BluePexReports"), gettext('By services')));
$pglinks = array("./firewallapp/services.php", "@self");
include('head.inc');

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box(gettext($savemsg), 'success');

echo '<div class="infoblock">';
print_info_box(gettext("Service enabled gives you the option to select the information to be collected to generate future reporting data.<br><br>NOTE: It is preferred when any option below is enabled, that is, if any option below is enabled, the general capture mode will be disabled."), 'info', false);
echo '</div>';

$tab_array = array();
$tab_array[] = array(gettext('General'), false, '/firewallapp/report_settings.php');
$tab_array[] = array(gettext('By services'), true, '/firewallapp/enable_by_interface_rotate.php');
$tab_array[] = array(gettext('Informative'), false, '/firewallapp/firewallapp_status_data_tables.php');
display_top_tabs($tab_array);

$form = new Form;
$section = new Form_Section(dgettext(gettext("BluePexReports"), gettext('Report Settings')));


init_config_arr(array('installedpackages', 'suricata', 'rule'));

if (!is_array($config['installedpackages']['suricata']['rule']))
$config['installedpackages']['suricata']['rule'] = array();

$a_rule = &$config['installedpackages']['suricata']['rule'];

$fapp_interfaces = [];
$acp_interfaces = [];

for ($id = 0; $id <= count($a_rule)-1; $id++) {
	$if_real = get_real_interface($a_rule[$id]['interface']);
	$suricata_uuid = $a_rule[$id]['uuid'];
	foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
		$if = get_real_interface($suricatacfg['interface']);
		$uuid = $suricatacfg['uuid'];
		if (!in_array($if, $all_gtw,true)) {
			$fapp_interfaces[$suricatacfg['interface']] = $suricatacfg['interface'];
		}
		if (in_array($if, $all_gtw,true)) {
			$acp_interfaces[$suricatacfg['interface']] = $suricatacfg['interface'];
		}
	}
}

init_config_arr(array('system', 'webfilter',));
$settings_rotate = &$config['system']['webfilter'];

$section = new Form_Section(gettext('Webfilter Rotation Logs'));

$section->addInput(new Form_Checkbox(
	'rotate_webfilter_service_enable',
	dgettext(gettext("BluePexReports"), 'Enable'),
	'',
	($settings_rotate['rotate_webfilter_service']['rotate_webfilter_service_enable'] == "on")
));

$form->add($section);

$section = new Form_Section(gettext('SSH Rotation Logs'));

$section->addInput(new Form_Checkbox(
	'rotate_ssh_service_enable',
	dgettext(gettext("BluePexReports"), 'Enable'),
	'',
	($settings_rotate['rotate_ssh_service']['rotate_ssh_service_enable'] == "on")
));

$form->add($section);

$section = new Form_Section(gettext('Active Protection Rotation Logs'));

$section->addInput(new Form_Checkbox(
	'rotate_acp_service_enable',
	dgettext(gettext("BluePexReports"), 'Enable'),
	'',
	($settings_rotate['rotate_acp_service']['rotate_acp_service_enable'] == "on")
));

$section->addInput(new Form_Select(
	'rotate_acp_service_interfaces',
	dgettext(gettext("BluePexReports"), gettext('Interfaces')),
	(isset($settings_rotate['rotate_acp_service']['rotate_acp_service_interfaces']) ? explode(",", $settings_rotate['rotate_acp_service']['rotate_acp_service_interfaces']) : ""),
	$acp_interfaces,
	true
));

$form->add($section);

$section = new Form_Section(gettext('FirewallApp Rotation Logs'));

$section->addInput(new Form_Checkbox(
	'rotate_fapp_service_enable',
	dgettext(gettext("BluePexReports"), 'Enable'),
	'',
	($settings_rotate['rotate_fapp_service']['rotate_fapp_service_enable'] == "on")
));

$section->addInput(new Form_Select(
	'rotate_fapp_service_interfaces',
	dgettext(gettext("BluePexReports"), gettext('Interfaces')),
	(isset($settings_rotate['rotate_fapp_service']['rotate_fapp_service_interfaces']) ? explode(",", $settings_rotate['rotate_fapp_service']['rotate_fapp_service_interfaces']) : ""),
	$fapp_interfaces,
	true
));

$form->add($section);

print $form;
include('../foot.inc');
?>
