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

if (isset($_POST['save'])) {

	$rotate_services = &$config['system']['webfilter'];
	$rotate_services_webfilter_status = $rotate_services['rotate_webfilter_service']['rotate_webfilter_service_enable'];
	$rotate_services_ssh_status = $rotate_services['rotate_ssh_service']['rotate_ssh_service_enable'];
	$rotate_services_acp_status = $rotate_services['rotate_acp_service']['rotate_acp_service_enable'];
	$rotate_services_fapp_status = $rotate_services['rotate_fapp_service']['rotate_fapp_service_enable'];
	
	if (empty($_POST['interfaces'])) {
		$input_errors[] = dgettext(gettext("BluePexReports"), gettext("Select the interfaces to allow the access."));
	} else {
		$report_time = implode(":", array($_POST['report_hour'], $_POST['report_minute']));
		if ($report_time == "00:00") {
			$input_errors[] = dgettext(gettext("BluePexReports"), gettext("Time interval invalid '00:00'."));
		}

		$cleanup_db_month = $_POST['cleanup_db_month'];
		if ($month > 6) {
			$input_errors[] = dgettext(gettext("BluePexReports"), gettext("Month range can not be greater than 6."));
		}

		$cleanup_db_time = "{$_POST['cleanup_db_hour']}:{$_POST['cleanup_db_minute']}";
		if ($cleanup_db_time == "00:00") {
			$input_errors[] = dgettext(gettext("BluePexReports"), gettext("Time interval invalid '00:00'."));
		}

		if (
			($rotate_services_webfilter_status == "on") ||
			($rotate_services_ssh_status == "on") ||
			($rotate_services_acp_status == "on") ||
			($rotate_services_fapp_status == "on")
		) {
			$input_errors[] = dgettext(gettext("BluePexReports"), gettext("There are active options in 'By service', disable options for saving and starting 'Configuration' mode."));
		}

		if (empty($input_errors)) {
			$settings['enable']     = ($_POST['enable'] == "yes") ? "on" : "";
			$settings['interfaces'] = implode(",",$_POST['interfaces']);
			$settings['gen_report_time'] = $report_time;
			$settings['cleanup_db_month'] = $cleanup_db_month;
			$settings['cleanup_db_time'] = $cleanup_db_time;
			if (function_exists("createRelatoriosEventToClearlog")) {
				createRelatoriosEventToClearlog();
			}

			// Create webfilter database
			$settings['remote_reports'] = "on";
			$settings['log_referer'] = "off";
			$settings['reports_ip'] = "127.0.0.1";
			$settings['reports_port'] = "3306";
			$settings['reports_user'] = "webfilter";
			$settings['reports_password'] = "webfilter";
			$settings['reports_db'] = "webfilter";
			$db = new NetfilterDatabase();
			if (!$db->backend) {
				$input_errors[] = dgettext(gettext('BluePexWebFilter'), gettext('Could not to connect to the database!'));
			} else {
				$res = $db->Query("SELECT COUNT(*) as total FROM accesses");
				if ($res) {
					$result = $db->fetchAssoc($res);
					$msgShow = ($settings['enable'] == 'on') ? gettext("Enabled general analysis on interface.") : gettext("Disabled general analysis on interface.");
					$savemsg = sprintf(dgettext(gettext('BluePexWebFilter'), gettext('Total registers on the database: %s entries.')) . "<br>{$msgShow}", $result['total']);
				} else {
					$input_errors[] = dgettext(gettext('BluePexWebFilter'), gettext('Could not to get registers in the database!'));
				}
			}

			if (!$input_errors) {
				$savemsg = "Settings of the Reports Agend applied successfully!";
				write_config($savemsg);
				$savemsg = dgettext(gettext("BluePexReports"), gettext($savemsg));
				filter_configure();
				mwexec("/usr/local/bin/python3.8 /usr/local/sbin/wfrotated restart");
			}
		}
	}
}

$pgtitle = array(dgettext(gettext("BluePexReports"), gettext('FirewallApp')), dgettext(gettext("BluePexReports"), gettext('Reports')));
$pglinks = array("./firewallapp/services.php", "@self");
include('head.inc');

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

echo '<div class="infoblock">';
print_info_box(gettext("General configuration is the default operating mode for capturing logs, where it will capture all information from interfaces and services in order to obtain data to return in reports.<br><br>Note: If the service enabled has something active, it will not be possible to activate this page."), 'info', false);
echo '</div>';

$tab_array = array();
$tab_array[] = array(gettext('General'), true, '/firewallapp/report_settings.php');
$tab_array[] = array(gettext('By services'), false, '/firewallapp/enable_by_interface_rotate.php');
$tab_array[] = array(gettext('Informative'), false, '/firewallapp/firewallapp_status_data_tables.php');
display_top_tabs($tab_array);

$form = new Form;
$section = new Form_Section(dgettext(gettext("BluePexReports"), gettext('Report Settings')));

$section->addInput(new Form_Checkbox(
	'enable',
	dgettext(gettext("BluePexReports"), 'Enable'),
	'',
	($settings['enable'] == "on")
));

$section->addInput(new Form_Select(
	'interfaces',
	dgettext(gettext("BluePexReports"), gettext('Interfaces (generally LAN)')),
	(isset($settings['interfaces']) ? explode(",", $settings['interfaces']) : ""),
	(array_combine($ifaces, $ifaces)),
	true
));

$group = new Form_Group('Logo');

if (isset($config['logo']['name'])) {
	$image = "<p><img src=\"/images/{$config['logo']['name']}\" class=\"img-responsive img-thumbnail\" style=\"height:100px\"></p>";
} else {
	$image = "<p><img src=\"/images/logo-default.png\" class=\"img-responsive img-thumbnail\" style=\"height:100px\"></p>";
}
$group->add(new Form_StaticText(
	null,
	$image
))->setHelp(gettext('To change the current system logo, go to System/General Setup'));

$prepend = array('00','01','02','03','04','05','06','07','08','09');
$hours   = array_merge($prepend, range(10, 23));
$minutes = array_merge($prepend, range(10, 59));

$section->add($group);

$form->add($section);

print $form;
include('../foot.inc');
?>
