<?php
/*
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 *
 * ====================================================================
 */

require_once("guiconfig.inc");
require_once('nf_config.inc');

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['nf_reports_settings']['element0'])) {
	$config['system']['webfilter']['nf_reports_settings']['element0'] = array();
}
$settings = &$config['system']['webfilter']['nf_reports_settings']['element0'];

if (isset($_POST['save'])) {
	$settings['remote_reports'] = isset($_POST['remote_reports']) ? "on" : "off";
	$settings['log_referer'] = isset($_POST['log_referer']) ? "on" : "off";
	$settings['reports_ip'] = $_POST['reports_ip'];
	$settings['reports_port'] = $_POST['reports_port'];
	$settings['reports_user'] = $_POST['reports_user'];
	$settings['reports_password'] = $_POST['reports_password'];
	$settings['reports_db'] = $_POST['reports_db'];

	$savemsg = dgettext('BluePexWebFilter', 'Report Settings changed successfully!');
	write_config($savemsg);
}

if (isset($_POST['test_connection'])) {
	$db = new NetfilterDatabase();
	if (!$db->backend) {
		$input_errors[] = dgettext('BluePexWebFilter', 'Could not to connect to the database!');
	} else {
		$res = $db->Query("SELECT COUNT(*) as total FROM accesses");
		if ($res) {
			$result = $db->fetchAssoc($res);
			$savemsg = sprintf(dgettext('BluePexWebFilter', 'Total registers on the database: %s entries.'), $result['total']);
		} else {
			$input_errors[] = dgettext('BluePexWebFilter', 'Could not to get registers in the database!');
		}
	}
}


$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Database Settings'));
include('head.inc');

if ($input_errors)
        print_input_errors($input_errors);
if ($savemsg)
        print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'DataClick'), false, '/webfilter/wf_dataclick_settings.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Database Settings'), true, '/webfilter/wf_report_settings.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Informativo'), false, '/webfilter/wf_status_data_tables.php');
display_top_tabs($tab_array);

$form = new Form;
$section = new Form_Section('Report Settings');

$section->addInput(new Form_Checkbox(
	'remote_reports',
	'Enable',
	'',
	($settings['remote_reports'] == "on"),
	'on'
))->setHelp(sprintf(dgettext("BluePexWebFilter", 'Enable this if you want to archive the content filter reports in a remote database.%s Download the script to create the database schemaCheck this to enable the BluePex content filter.'), '<br />'));

$section->addInput(new Form_Checkbox(
	'log_referer',
	dgettext('BluePexWebFilter', 'URL References'),
	'',
	($settings['log_referer'] == "on"),
	'on'
))->setHelp(sprintf(dgettext("BluePexWebFilter", 'Save the reference of urls on the database.%s Enable webfitler to save the reference of urls on database.%s This logs are for the objects loaded on the web page accessed by the user.%s The principal url still will be saved on the database.'), '<br />', '<br />', '<br />'));

$section->addInput(new Form_Input(
       	'reports_ip',
       	dgettext('BluePexWebFilter', 'Server IP Address'),
       	'text',
	$settings['reports_ip']
))->setHelp(dgettext('BluePexWebFilter', 'Enter the IP address of the remote server.'));

$section->addInput(new Form_Input(
       	'reports_port',
       	dgettext('BluePexWebFilter', 'Server Port'),
       	'text',
	$settings['reports_port']
))->setHelp(dgettext('BluePexWebFilter', 'Enter the port to connect in the database.'));

$section->addInput(new Form_Input(
       	'reports_user',
       	dgettext('BluePexWebFilter', 'Server User'),
       	'text',
	$settings['reports_user']
))->setHelp(dgettext('BluePexWebFilter', 'Enter the username of the Database.'));

$section->addInput(new Form_Input(
       	'reports_password',
       	dgettext('BluePexWebFilter', 'Server Password'),
       	'password',
	$settings['reports_password']
))->setHelp(dgettext('BluePexWebFilter', 'Enter the password of the user to connect to the database.')); 

$section->addInput(new Form_Input(
       	'reports_db',
       	dgettext('BluePexWebFilter', 'Server Database Name'),
       	'text',
	$settings['reports_db']
))->setHelp(dgettext('BluePexWebFilter', 'Enter the database name.')); 

$group = new Form_Group("Downloads");
$group->add(new Form_StaticText(
	'Download Dataclick',
	sprintf(dgettext("BluePexWebFilter", "%sDatabase schema UTM 4%s %sDatabase schema UTM 5%s %sDatabase schema UTM 6%s"), "<a href='/webfilter/webfilter_mysql.sql' download='webfilter_utm4.sql'><span class='badge'>", "</span></a>", "<a href='/webfilter/webfilter_mysql_upgrade.sql' download='webfilter_utm5.sql'><span class='badge'>", "</span></a>", "<a href='/webfilter/webfilter_utm6.sql'><span class='badge'>", "</span></a>")
))->addClass('pull-left');

$section->add($group);

$form->addGlobal(new Form_Button(
	'test_connection',
	dgettext('BluePexWebFilter', 'Check Connection')
))->removeClass('btn-primary')->addClass('btn-success');

$form->add($section);

print $form;
include('../foot.inc');
?>
