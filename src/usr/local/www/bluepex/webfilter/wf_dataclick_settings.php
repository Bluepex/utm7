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

if (file_exists("/etc/inc/dataclick_report.inc")) {
	ini_set('default_socket_timeout', 5);
	require_once('dataclick_report.inc');
}

$input_errors = array();
$savemsg = "";
$ifaces = get_configured_interface_with_descr();
$ifaces["lo0"] = "loopback";

if (!isset($config['system']['webfilter']['bluepexdataclickagent']['config'][0])) {
	$config['system']['webfilter']['bluepexdataclickagent']['config'][0] = array();
}
$settings = &$config['system']['webfilter']['bluepexdataclickagent']['config'][0];

if (isset($_POST['save'])) {
	if (empty($_POST['interfaces'])) {
		$input_errors[] = dgettext("BluePexWebFilter","Select the interfaces to allow the access.");
	} else {
		$report_time = "{$_POST['report_hour']}:{$_POST['report_minute']}";

		if ($report_time == "00:00") {
			$input_errors[] = dgettext("BluePexWebFilter", "Time interval invalid '00:00'.");
		}

		$cleanup_db_month = $_POST['cleanup_db_month'];

		if ($month > 6) {
			$input_errors[] = dgettext("BluePexWebFilter", "Month range can not be greater than 6.");
		}

		$cleanup_db_time = "{$_POST['cleanup_db_hour']}:{$_POST['cleanup_db_minute']}";

		if ($cleanup_db_time == "00:00") {
			$input_errors[] = dgettext("BluePexWebFilter", "Time interval invalid '00:00'.");
		}

		if (empty($input_errors)) {
			$settings['enable'] = ($_POST['enable'] == "yes") ? "on" : "";
			$settings['interfaces'] = implode(",",$_POST['interfaces']);
			$settings['gen_report_time'] = $report_time;

			if (function_exists("createEventToReports")) {
				createEventToReports($report_time);
			}

			$settings['cleanup_db_month'] = $cleanup_db_month;
			$settings['cleanup_db_time'] = $cleanup_db_time;

			if (function_exists("createEventToClearlog")) {
				createEventToClearlog($cleanup_db_time, $cleanup_db_month);
			}

			$settings['cleanup_db_mb'] = $_POST['cleanup_db_mb'];
			$savemsg = dgettext("BluePexWebFilter", "Settings of the DataClick Agend applied successfully!");

			write_config($savemsg);
			mwexec("/usr/local/bin/php -f /usr/local/www/webfilter/webfilter_install_action.php");
			filter_configure();
		}
	}
}

$pgtitle = array(dgettext("BluePexWebFilter",'WebFilter'), dgettext("BluePexWebFilter", 'Reports'));
include('head.inc');

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg, 'success');
}

if (empty($conn)) {
	print_input_errors([dgettext("BluePexWebFilter", "Could not communicate with the database")]);
}

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'DataClick'), true, '/webfilter/wf_dataclick_settings.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Database Settings'), false, '/webfilter/wf_report_settings.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Informativo'), false, '/webfilter/wf_status_data_tables.php');
display_top_tabs($tab_array);

$form = new Form;
$section = new Form_Section(dgettext('BluePexWebFilter', 'Report Settings'));

$section->addInput(new Form_Checkbox(
	'enable',
	dgettext('BluePexWebFilter', 'Enable'),
	'',
	($settings['enable'] == "on")
))->setHelp(dgettext('BluePexWebFilter', 'If checked, allows users with DataClick installed in their computers to collect data in this BluePex UTM.'));

$section->addInput(new Form_Select(
	'interfaces',
	dgettext('BluePexWebFilter', 'Interfaces (generally LAN)'),
	(isset($settings['interfaces']) ? explode(",", $settings['interfaces']) : ""),
	(array_combine($ifaces, $ifaces)),
	true
))->setHelp(sprintf(dgettext("BluePexWebFilter", 'Select the interfaces where you want to allow users access.%sYou can use the CTRL or COMMAND key to select multiple interfaces.'), '<br />'));

$group = new Form_Group('Logo');

if (isset($config['logo']['name'])) {
	$image = "<p><img src=\"/images/{$config['logo']['name']}\" class=\"img-responsive img-thumbnail\" style=\"height:100px\"></p>";
} else {
	$image = "<p><img src=\"/images/logo-default.png\" class=\"img-responsive img-thumbnail\" style=\"height:100px\"></p>";
}
$group->add(new Form_StaticText(
	null,
	$image
))->setHelp(sprintf(dgettext("BluePexWebFilter", gettext("To change logo %s System/General Setup %s")), "<a href=\"../system.php\">", "</a>"))->setWidth(3);

$prepend = array('00','01','02','03','04','05','06','07','08','09');
$hours   = array_merge($prepend, range(10, 23));
$minutes = array_merge($prepend, range(10, 59));

$section->add($group);

$group = new Form_Group(dgettext('BluePexWebFilter', 'Schedule time to generate reports'));

list($report_hour, $report_min) = explode(":", $settings['gen_report_time']);

$group->add(new Form_Select(
	'report_hour',
	null,
	!empty($report_hour) ? $report_hour : '23',
	array_combine($hours, $hours)
))->setWidth(1)->setHelp(dgettext('BluePexWebFilter', 'Stop Hrs'));

$group->add(new Form_Select(
	'report_minute',
	null,
	!empty($report_min) ? $report_min : '59',
	array_combine($minutes, $minutes)
))->setWidth(1)->setHelp(dgettext('BluePexWebFilter', 'Stop Mins'));

$group->setHelp(dgettext('BluePexWebFilter', 'Select the time range to generate daily all reports. Default is 23:59:00.'));

$section->add($group);

$group = new Form_Group(dgettext('BluePexWebFilter', 'Schedule DB Cleanup'));

list($report_hour, $report_min) = explode(":", $settings['cleanup_db_time']);

$group->add(new Form_Select(
	'cleanup_db_month',
	null,
	!empty($settings['cleanup_db_month']) ? $settings['cleanup_db_month'] : '3',
	array('1' => '1', '2' => '2', '3' => '3'),
))->setWidth(1)->setHelp(dgettext('BluePexWebFilter','Every month'));

list($cleanup_db_hour, $cleanup_db_minute) = explode(":", $settings['cleanup_db_time']);
$group->add(new Form_Select(
	'cleanup_db_hour',
	null,
	!empty($cleanup_db_hour) ? $cleanup_db_hour : '23',
	array_combine($hours, $hours)
))->setWidth(1)->setHelp(dgettext('BluePexWebFilter', 'Stop Hrs'));

$group->add(new Form_Select(
	'cleanup_db_minute',
	null,
	!empty($cleanup_db_minute) ? $cleanup_db_minute : '59',
	array_combine($minutes, $minutes)
))->setWidth(1)->setHelp(dgettext('BluePexWebFilter', 'Stop Mins'));

$group->setHelp(dgettext('BluePexWebFilter', 'Select the time range to schedule db cleanup. Default is 23:59:00 every 3 months.'));

$section->add($group);

$group = new Form_Group(dgettext('BluePexWebFilter', 'SCHEDULE DATABASE CLEANING BY TABLE SIZE'));

$group->add(new Form_Select(
	'cleanup_db_mb',
	null,
	!empty($settings['cleanup_db_mb']) ? $settings['cleanup_db_mb'] : '5120',
	array(
		'100' => '100 (MB)',
		'512' => '500 (MB)',
		'1024' => '1000 (MB)',
		'2048' => '2000 (MB)',
		'3072' => '3000 (MB)',
		'4096' => '4000 (MB)',
		'5120' => '5000 (MB)',
	)
))->setWidth(1)->setHelp(dgettext('BluePexWebFilter', 'Size per table (MB)'));

$group->setHelp(dgettext('BluePexWebFilter', 'Select the maximum data retention size for each table. By default it is 5000(MB).<br>The cleaning will only delete the oldest records with an average of 10% of the total number of records.'));

$section->add($group);

$form->add($section);

print $form;

include('../foot.inc');
?>
