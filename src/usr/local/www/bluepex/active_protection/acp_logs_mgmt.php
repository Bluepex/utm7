<?php
/*
 * suricata_logs_mgmt.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2006-2018 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2003-2004 Manuel Kasper
 * Copyright (c) 2005 Bill Marquette
 * Copyright (c) 2009 Robert Zelaya Sr. Developer
 * Copyright (c) 2018 Bill Meeks
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("guiconfig.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

global $g;

$suricatadir = SURICATADIR;

$pconfig = array();

// Grab saved settings from configuration
/*
$pconfig['enable_log_mgmt'] = $config['installedpackages']['suricata']['config'][0]['enable_log_mgmt'] == 'off' ? 'off' : 'on';
$pconfig['clearlogs'] = $config['installedpackages']['suricata']['config'][0]['clearlogs'] == 'on' ? 'on' : 'off';
$pconfig['suricataloglimit'] = $config['installedpackages']['suricata']['config'][0]['suricataloglimit'] == 'on' ? 'on' : 'off';
$pconfig['suricataloglimitsize'] = $config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'];
$pconfig['alert_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['alert_log_limit_size'];
$pconfig['alert_log_retention'] = $config['installedpackages']['suricata']['config'][0]['alert_log_retention'];
$pconfig['block_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['block_log_limit_size'];
$pconfig['block_log_retention'] = $config['installedpackages']['suricata']['config'][0]['block_log_retention'];
$pconfig['files_json_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['files_json_log_limit_size'];
$pconfig['files_json_log_retention'] = $config['installedpackages']['suricata']['config'][0]['files_json_log_retention'];
$pconfig['http_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['http_log_limit_size'];
$pconfig['http_log_retention'] = $config['installedpackages']['suricata']['config'][0]['http_log_retention'];
$pconfig['stats_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['stats_log_limit_size'];
$pconfig['stats_log_retention'] = $config['installedpackages']['suricata']['config'][0]['stats_log_retention'];
$pconfig['tls_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['tls_log_limit_size'];
$pconfig['tls_log_retention'] = $config['installedpackages']['suricata']['config'][0]['tls_log_retention'];
$pconfig['unified2_log_limit'] = $config['installedpackages']['suricata']['config'][0]['unified2_log_limit'];
$pconfig['u2_archive_log_retention'] = $config['installedpackages']['suricata']['config'][0]['u2_archive_log_retention'];
$pconfig['file_store_retention'] = $config['installedpackages']['suricata']['config'][0]['file_store_retention'];
$pconfig['tls_certs_store_retention'] = $config['installedpackages']['suricata']['config'][0]['tls_certs_store_retention'];
$pconfig['eve_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['eve_log_limit_size'];
$pconfig['eve_log_retention'] = $config['installedpackages']['suricata']['config'][0]['eve_log_retention'];
$pconfig['sid_changes_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['sid_changes_log_limit_size'];
$pconfig['sid_changes_log_retention'] = $config['installedpackages']['suricata']['config'][0]['sid_changes_log_retention'];
*/

$pconfig['enable_log_mgmt'] = $config['installedpackages']['suricata']['config'][0]['enable_log_mgmt'] == 'off' ? 'off' : 'on';
$pconfig['suricataloglimit'] = $config['installedpackages']['suricata']['config'][0]['suricataloglimit'] == 'on' ? 'on' : 'off';
$pconfig['clearlogs'] = $config['installedpackages']['suricata']['config'][0]['clearlogs'] == 'on' ? 'on' : 'off';
$pconfig['alert_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['alert_log_limit_size'];
$pconfig['block_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['block_log_limit_size'];
$pconfig['eve_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['eve_log_limit_size'];
$pconfig['http_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['http_log_limit_size'];
$pconfig['suricataloglimitsize'] = $config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'];
$pconfig['tls_log_limit_size'] = $config['installedpackages']['suricata']['config'][0]['tls_log_limit_size'];

// Load up some arrays with selection values (we use these later).
// The keys in the $retentions array are the retention period
// converted to hours.  The keys in the $log_sizes array are
// the file size limits in KB.
//$retentions = array( '0' => gettext('KEEP ALL'), '24' => gettext('1 DAY'), '168' => gettext('7 DAYS'), '336' => gettext('14 DAYS'),
//			 '720' => gettext('30 DAYS'), '1080' => gettext("45 DAYS"), '2160' => gettext('90 DAYS'), '4320' => gettext('180 DAYS'),
//			 '8766' => gettext('1 YEAR'), '26298' => gettext("3 YEARS") );
//$log_sizes = array( '0' => gettext('NO LIMIT'), '50' => gettext('50 KB'), '150' => gettext('150 KB'), '250' => gettext('250 KB'),
//			'500' => gettext('500 KB'), '750' => gettext('750 KB'), '1000' => gettext('1 MB'), '2000' => gettext('2 MB'),
//			'5000' => gettext("5 MB"), '10000' => gettext("10 MB") );

$log_sizes = array(
	"0" => "default",
	"1048576" => "1 MB",
	"2097152" => "2 MB",
	"5242880" => "5 MB",
	"10485760" => "10 MB",
	"52428800" => "50 MB",
	"104857600" => "100 MB"
);

// Set sensible defaults for any unset parameters
if (empty($pconfig['enable_log_mgmt']))
	$pconfig['enable_log_mgmt'] = 'on';
if (empty($pconfig['suricataloglimit']))
	$pconfig['suricataloglimit'] = 'on';
if (empty($pconfig['suricataloglimitsize'])) {
	// Set limit to 1% of slice that is unused */
	$pconfig['suricataloglimitsize'] = round(exec('df -k /var | grep -v "Filesystem" | awk \'{print $4}\'') * .1 / 1024);
}

// Set default retention periods for rotated logs
//if (!isset($pconfig['alert_log_retention']))
//	$pconfig['alert_log_retention'] = "336";
//if (!isset($pconfig['block_log_retention']))
//	$pconfig['block_log_retention'] = "336";
//if (!isset($pconfig['files_json_log_retention']))
//	$pconfig['files_json_log_retention'] = "168";
//if (!isset($pconfig['http_log_retention']))
//	$pconfig['http_log_retention'] = "168";
//if (!isset($pconfig['stats_log_retention']))
//	$pconfig['stats_log_retention'] = "168";
//if (!isset($pconfig['tls_log_retention']))
//	$pconfig['tls_log_retention'] = "336";
//if (!isset($pconfig['u2_archive_log_retention']))
//	$pconfig['u2_archive_log_retention'] = "168";
//if (!isset($pconfig['file_store_retention']))
//	$pconfig['file_store_retention'] = "168";
//if (!isset($pconfig['tls_certs_store_retention']))
//	$pconfig['tls_certs_store_retention'] = "168";
//if (!isset($pconfig['eve_log_retention']))
//	$pconfig['eve_log_retention'] = "168";
//if (!isset($pconfig['sid_changes_log_retention']))
//	$pconfig['sid_changes_log_retention'] = "336";

// Set default log file size limits
//if (!isset($pconfig['alert_log_limit_size']))
//	$pconfig['alert_log_limit_size'] = "500";
//if (!isset($pconfig['block_log_limit_size']))
//	$pconfig['block_log_limit_size'] = "500";
//if (!isset($pconfig['files_json_log_limit_size']))
//	$pconfig['files_json_log_limit_size'] = "1000";
//if (!isset($pconfig['http_log_limit_size']))
//	$pconfig['http_log_limit_size'] = "1000";
//if (!isset($pconfig['stats_log_limit_size']))
//	$pconfig['stats_log_limit_size'] = "500";
//if (!isset($pconfig['tls_log_limit_size']))
//	$pconfig['tls_log_limit_size'] = "500";
//if (!isset($pconfig['unified2_log_limit']))
//	$pconfig['unified2_log_limit'] = "32";
//if (!isset($pconfig['eve_log_limit_size']))
//	$pconfig['eve_log_limit_size'] = "5000";
//if (!isset($pconfig['sid_changes_log_limit_size']))
//	$pconfig['sid_changes_log_limit_size'] = "250";

// Set default log file size limits
if (!isset($pconfig['alert_log_limit_size']))
	$pconfig['alert_log_limit_size'] = 0;
if (!isset($pconfig['block_log_limit_size']))
	$pconfig['block_log_limit_size'] = 0;
if (!isset($pconfig['eve_log_limit_size']))
	$pconfig['eve_log_limit_size'] = 0;
if (!isset($pconfig['http_log_limit_size']))
	$pconfig['http_log_limit_size'] = 0;
if (!isset($pconfig['suricataloglimitsize']))
	$pconfig['suricataloglimitsize'] = 0;
if (!isset($pconfig['tls_log_limit_size']))
	$pconfig['tls_log_limit_size'] = 0;

//if (isset($_POST['ResetAll'])) {
//
//	// Reset all settings to their defaults
//	$pconfig['alert_log_retention'] = "336";
//	$pconfig['block_log_retention'] = "336";
//	$pconfig['files_json_log_retention'] = "168";
//	$pconfig['http_log_retention'] = "168";
//	$pconfig['stats_log_retention'] = "168";
//	$pconfig['tls_log_retention'] = "336";
//	$pconfig['u2_archive_log_retention'] = "168";
//	$pconfig['file_store_retention'] = "168";
//	$pconfig['tls_certs_store_retention'] = "168";
//	$pconfig['eve_log_retention'] = "168";
//	$pconfig['sid_changes_log_retention'] = "336";
//
//	$pconfig['alert_log_limit_size'] = "500";
//	$pconfig['block_log_limit_size'] = "500";
//	$pconfig['files_json_log_limit_size'] = "1000";
//	$pconfig['http_log_limit_size'] = "1000";
//	$pconfig['stats_log_limit_size'] = "500";
//	$pconfig['tls_log_limit_size'] = "500";
//	$pconfig['unified2_log_limit'] = "32";
//	$pconfig['eve_log_limit_size'] = "5000";
//	$pconfig['sid_changes_log_limit_size'] = "250";
//
//	/* Log a message at the top of the page to inform the user */
//	$savemsg = gettext("All log management settings on this page have been reset to their defaults.  Click APPLY if you wish to keep these new settings.");
//}

if (isset($_POST['save']) || isset($_POST['apply'])) {
	//if ($_POST['enable_log_mgmt'] != 'on') {
	$config['installedpackages']['suricata']['config'][0]['enable_log_mgmt'] = $_POST['enable_log_mgmt'] ? 'on' :'off';
	$config['installedpackages']['suricata']['config'][0]['suricataloglimit'] = $_POST['suricataloglimit'] ? 'on' :'off';
	$config['installedpackages']['suricata']['config'][0]['clearlogs'] = $_POST['clearlogs'] ? 'on' :'off';
	$config['installedpackages']['suricata']['config'][0]['alert_log_limit_size'] = $_POST['alert_log_limit_size'];
	$config['installedpackages']['suricata']['config'][0]['block_log_limit_size'] = $_POST['block_log_limit_size'];
	$config['installedpackages']['suricata']['config'][0]['eve_log_limit_size'] = $_POST['eve_log_limit_size'];
	$config['installedpackages']['suricata']['config'][0]['http_log_limit_size'] = $_POST['http_log_limit_size'];
	$config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'] = $_POST['suricataloglimitsize'];
	$config['installedpackages']['suricata']['config'][0]['tls_log_limit_size'] = $_POST['tls_log_limit_size'];

	write_config("Suricata pkg: saved updated configuration for LOGS MGMT.");
	sync_suricata_package_config();

	/* forces page to reload new settings */
	header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate' );
	header( 'Cache-Control: post-check=0, pre-check=0', false );
	header( 'Pragma: no-cache' );
	header("Location: /active_protection/acp_logs_mgmt.php");
	exit;
	//}

	//if ($_POST['suricataloglimit'] == 'on') {
	//	if (!is_numericint($_POST['suricataloglimitsize']) || $_POST['suricataloglimitsize'] < 1)
	//		$input_errors[] = gettext("The 'Log Directory Size Limit' must be an integer value greater than zero.");
	//}

	// Validate unified2 log file limit
	//if (!is_numericint($_POST['unified2_log_limit']) || $_POST['unified2_log_limit'] < 1)
	//		$input_errors[] = gettext("The value for 'Unified2 Log Limit' must be an integer value greater than zero.");

	//if (!$input_errors) {
	//	$config['installedpackages']['suricata']['config'][0]['enable_log_mgmt'] = $_POST['enable_log_mgmt'] ? 'on' :'off';
	//	$config['installedpackages']['suricata']['config'][0]['clearlogs'] = $_POST['clearlogs'] ? 'on' : 'off';
	//	$config['installedpackages']['suricata']['config'][0]['suricataloglimit'] = $_POST['suricataloglimit'] ? 'on' :'off';
	//	$config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'] = $_POST['suricataloglimitsize'];
	//	$config['installedpackages']['suricata']['config'][0]['alert_log_limit_size'] = $_POST['alert_log_limit_size'];
	//	$config['installedpackages']['suricata']['config'][0]['alert_log_retention'] = $_POST['alert_log_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['block_log_limit_size'] = $_POST['block_log_limit_size'];
	//	$config['installedpackages']['suricata']['config'][0]['block_log_retention'] = $_POST['block_log_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['files_json_log_limit_size'] = $_POST['files_json_log_limit_size'];
	//	$config['installedpackages']['suricata']['config'][0]['files_json_log_retention'] = $_POST['files_json_log_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['http_log_limit_size'] = $_POST['http_log_limit_size'];
	//	$config['installedpackages']['suricata']['config'][0]['http_log_retention'] = $_POST['http_log_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['stats_log_limit_size'] = $_POST['stats_log_limit_size'];
	//	$config['installedpackages']['suricata']['config'][0]['stats_log_retention'] = $_POST['stats_log_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['tls_log_limit_size'] = $_POST['tls_log_limit_size'];
	//	$config['installedpackages']['suricata']['config'][0]['tls_log_retention'] = $_POST['tls_log_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['unified2_log_limit'] = $_POST['unified2_log_limit'];
	//	$config['installedpackages']['suricata']['config'][0]['u2_archive_log_retention'] = $_POST['u2_archive_log_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['file_store_retention'] = $_POST['file_store_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['tls_certs_store_retention'] = $_POST['tls_certs_store_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['eve_log_limit_size'] = $_POST['eve_log_limit_size'];
	//	$config['installedpackages']['suricata']['config'][0]['eve_log_retention'] = $_POST['eve_log_retention'];
	//	$config['installedpackages']['suricata']['config'][0]['sid_changes_log_limit_size'] = $_POST['sid_changes_log_limit_size'];
	//	$config['installedpackages']['suricata']['config'][0]['sid_changes_log_retention'] = $_POST['sid_changes_log_retention'];

	//	write_config("Suricata pkg: saved updated configuration for LOGS MGMT.");
	//	sync_suricata_package_config();

	//	/* forces page to reload new settings */
	//	header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
	//	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	//	header( 'Cache-Control: no-store, no-cache, must-revalidate' );
	//	header( 'Cache-Control: post-check=0, pre-check=0', false );
	//	header( 'Pragma: no-cache' );
	//	header("Location: /firewallapp/firewallapp_logs_mgmt.php");
	//	exit;
	//}
}

$pgtitle = array(gettext("Active Protection"), gettext("Limits"));
$pglinks = array("./active_protection/ap_services.php", "@self");
include_once("head.inc");

/* Display Alert message, under form tag or no refresh */
if ($input_errors)
	print_input_errors($input_errors);

if ($savemsg) {
	/* Display save message */
	print_info_box($savemsg);
}
?>
<div class="infoblock">
	<?=print_info_box(gettext('<strong>Note:</strong> The actions configured on this page will affect all Active Protection and FirewallApp interfaces. <br>
	This page is displayed both in Active Protection and in FirewallAPP, both pages work with the same values, in this way, any change here will be possible to visualize with the other page in addition to affecting the same interfaces.'), gettext('info'))?>
</div>
<?php
$form = new Form;

$section = new Form_Section(gettext('Enable Log Cleaning'));
$section->addInput(new Form_Checkbox(
	'clearlogs',
	gettext('Clear Logs'),
	gettext('Clear logs for all Active Protection and FirewallApp interfaces frequently.'),
	$pconfig['clearlogs'] == 'on' ? true:false,
	'on'
))->setHelp(gettext('This option enables checking every 5 minutes on the size of the logs, if not enabled, the parsing spacing is every 30 minutes.'));

$section->addInput(new Form_Checkbox(
	'enable_log_mgmt',
	gettext('Self Management of Logs'),
	gettext('Enable automatic log management using the parameters specified below.'),
	$pconfig['enable_log_mgmt'] == 'on' ? true:false,
	'on'
))->setHelp(gettext('If you do not enable this option, all values ​​below will be disregarded and cleaning will follow the default value (10MB).'));
$form->add($section);

$section = new Form_Section(gettext("Limits of log files"));

$group = new Form_Group('alert');
$group->add(new Form_Select(
	'alert_log_limit_size',
	gettext('Max Size'),
	$pconfig['alert_log_limit_size'],
	$log_sizes
))->setHelp(gettext('Default is 10MB.'));
$section->add($group);

$group = new Form_Group('block');
$group->add(new Form_Select(
	'block_log_limit_size',
	gettext('Max Size'),
	$pconfig['block_log_limit_size'],
	$log_sizes
))->setHelp(gettext('Default is 10MB.'));
$section->add($group);

$group = new Form_Group('EVE');
$group->add(new Form_Select(
	'eve_log_limit_size',
	gettext('Max Size'),
	$pconfig['eve_log_limit_size'],
	$log_sizes
))->setHelp(gettext('Default is 10MB.'));
$section->add($group);

$group = new Form_Group('HTTP');
$group->add(new Form_Select(
	'http_log_limit_size',
	gettext('Max Size'),
	$pconfig['http_log_limit_size'],
	$log_sizes
))->setHelp(gettext('Default is 10MB.'));
$section->add($group);

$group = new Form_Group('Log Interfaces');
$group->add(new Form_Select(
	'suricataloglimitsize',
	gettext('Max Size'),
	$pconfig['suricataloglimitsize'],
	$log_sizes
))->setHelp(gettext('Default is 10MB.'));
$section->add($group);

$group = new Form_Group('https/tls');
$group->add(new Form_Select(
	'tls_log_limit_size',
	gettext('Max Size'),
	$pconfig['tls_log_limit_size'],
	$log_sizes
))->setHelp(gettext('Default is 10MB.'));
$section->add($group);

$form->add($section);
print($form);

/*
$section = new Form_Section(gettext("Directory Limit"));
$section->addInput(new Form_Checkbox(
	'suricataloglimit',
	gettext('Directory MB limit'),
	gettext('Activate'),
	$pconfig['suricataloglimit'] == 'on' ? true:false,
	'on'
));
$section->addInput(new Form_Input(
	'suricataloglimitsize',
	gettext('Limit in MB'),
	'text',
	$pconfig['suricataloglimitsize']
))->setHelp(gettext('This setting imposes a limit on the size of the log directory for all interfaces. When the defined size limit is reached, logs generated for all interfaces will be removed. (default is 1% of available free disk space)'));
$form->add($section);

$group->add(new Form_Select(
	'alert_log_retention',
	gettext('Retention'),
	$pconfig['alert_log_retention'],
	$retentions
))->setHelp(gettext('7 DAYS Standard.'));
$group->setHelp(gettext('Alert Details'));
$section->add($group);

$group = new Form_Group('block','',0,true);
$group->add(new Form_Select(
	'block_log_limit_size',
	gettext('Max Size'),
	$pconfig['block_log_limit_size'],
	$log_sizes
))->setHelp(gettext('Default is 500KB.'));
$group->add(new Form_Select(
	'block_log_retention',
	gettext('Retention'),
	$pconfig['block_log_retention'],
	$retentions
))->setHelp(gettext('7 DAYS Standard.'));
$group->setHelp(gettext('Lock Details'));
$section->add($group);

$group = new Form_Group('eve-json','',0,true);
$group->add(new Form_Select(
	'eve_log_limit_size',
	gettext('Max Size'),
	$pconfig['eve_log_limit_size'],
	$log_sizes
),true)->setHelp(gettext('Default is 5MB.'));
$group->add(new Form_Select(
	'eve_log_retention',
	gettext('Retention'),
	$pconfig['eve_log_retention'],
	$retentions
),true)->setHelp(gettext('7 DAYS Standard.'));
$group->setHelp(gettext('JavaScript Details'));
$section->add($group);

$group = new Form_Group('files-json','',0,true);
$group->add(new Form_Select(
	'files_json_log_limit_size',
	gettext('Max Size'),
	$pconfig['files_json_log_limit_size'],
	$log_sizes
),true)->setHelp(gettext('Default is 1MB.'));
$group->add(new Form_Select(
	'files_json_log_retention',
	gettext('Retention'),
	$pconfig['files_json_log_retention'],
	$retentions
),true)->setHelp(gettext('7 DAYS Standard.'));
$group->setHelp(gettext('JSON captures'));
$section->add($group);

$group = new Form_Group(gettext('http'));
$group->add(new Form_Select(
	'http_log_limit_size',
	gettext('Max Size'),
	$pconfig['http_log_limit_size'],
	$log_sizes
))->setHelp(gettext('Default is 1MB.'));
$group->add(new Form_Select(
	'http_log_retention',
	gettext('Retention'),
	$pconfig['http_log_retention'],
	$retentions
))->setHelp(gettext('7 DAYS Standard.'));
$group->setHelp('HTTP Details');
$section->add($group);

$group = new Form_Group('sid_changes','',0,true);
$group->add(new Form_Select(
	'sid_changes_log_limit_size',
	gettext('Max Size'),
	$pconfig['sid_changes_log_limit_size'],
	$log_sizes
),true)->setHelp(gettext('Default is 250 KB.'));
$group->add(new Form_Select(
	'sid_changes_log_retention',
	gettext('Retention'),
	$pconfig['sid_changes_log_retention'],
	$retentions
),true)->setHelp(gettext('7 DAYS Standard.'));
$group->setHelp('SID logs');
$section->add($group);

$group = new Form_Group('stats');
$group->add(new Form_Select(
	'stats_log_limit_size',
	gettext('Max Size'),
	$pconfig['stats_log_limit_size'],
	$log_sizes
))->setHelp(gettext('Default is 500KB.'));
$group->add(new Form_Select(
	'stats_log_retention',
	gettext('Retention'),
	$pconfig['stats_log_retention'],
	$retentions
))->setHelp(gettext('7 DAYS Standard.'));
$group->setHelp(gettext('FirewallApp Statistics'));
$section->add($group);

$group = new Form_Group(gettext('tls'));
$group->add(new Form_Select(
	'tls_log_limit_size',
	gettext('Max Size'),
	$pconfig['tls_log_limit_size'],
	$log_sizes
))->setHelp(gettext('Default is 500 KB.'));
$group->add(new Form_Select(
	'tls_log_retention',
	gettext('Retention'),
	$pconfig['tls_log_retention'],
	$retentions
))->setHelp(gettext('7 DAYS Standard.'));
$group->setHelp(gettext('TLS Details'));
$section->add($group);

$section->addInput(new Form_StaticText(
	'',
	'Settings will be ignored for any log in the list above not enabled on the Interface Settings tab. When a log reaches the Max Size limit, it will be rotated and tagged with a timestamp. The Retention period determines how long rotated logs are kept before they are automatically deleted.'
),true);

$section->addInput(new Form_Input(
	'unified2_log_limit',
	gettext('Unified2 Log Limit'),
	'text',
	$pconfig['unified2_log_limit']
),true)->setHelp(gettext('Log file size limit in megabytes (MB). Default is 32 MB. This sets the maximum size for a unified2 log file before it is rotated and a new one created.'));
$section->addInput(new Form_Select(
	'u2_archive_log_retention',
	gettext('Unified2 Archived Log Retention Period'),
	$pconfig['u2_archive_log_retention'],
	$retentions
),true)->setHelp(gettext('Choose retention period for archived Barnyard2 binary log files. Default is 1 DIA. When file capture and store is enabled, Suricata captures downloaded files from HTTP sessions and stores them, along with metadata, for later analysis. This setting determines how long files remain in the File Store folder before they are automatically deleted.'));
$section->addInput(new Form_Select(
	'file_store_retention',
	gettext('Captured Files Retention Period'),
	$pconfig['file_store_retention'],
	$retentions
),true)->setHelp(gettext('Choose retention period for captured files in File Store. Default is 1 DIA. When file capture and store is enabled, Suricata captures downloaded files from HTTP sessions and stores them, along with metadata, for later analysis. This setting determines how long files remain in the File Store folder before they are automatically deleted.'));
$section->addInput(new Form_Select(
	'tls_certs_store_retention',
	gettext('Captured TLS Certs Retention Period'),
	$pconfig['tls_certs_store_retention'],
	$retentions
),true)->setHelp(gettext('Choose retention period for captured TLS Certs. Default is 1 DIA. When custom rules with tls.store are enabled, Suricata captures Certificates, along with metadata, for later analysis. This setting determines how long files remain in the Certs folder before they are automatically deleted.'));
*/


?>

<script language="JavaScript">
////<![CDATA[
//events.push(function(){
//
//	function enable_change() {
//		var hide = ! $('#enable_log_mgmt').prop('checked');
//		disableInput('alert_log_limit_size', hide);
//		disableInput('alert_log_retention', hide);
//		disableInput('block_log_limit_size', hide);
//		disableInput('block_log_retention', hide);
//		disableInput('files_json_log_limit_size', hide);
//		disableInput('files_json_log_retention', hide);
//		disableInput('http_log_limit_size', hide);
//		disableInput('http_log_retention', hide);
//		disableInput('stats_log_limit_size', hide);
//		disableInput('stats_log_retention', hide);
//		disableInput('tls_log_limit_size', hide);
//		disableInput('tls_log_retention', hide);
//		disableInput('unified2_log_limit', hide);
//		disableInput('u2_archive_log_retention', hide);
//		disableInput('eve_log_retention', hide);
//		disableInput('eve_log_limit_size', hide);
//		disableInput('sid_changes_log_retention', hide);
//		disableInput('sid_changes_log_limit_size', hide);
//		disableInput('file_store_retention', hide);
//		disableInput('tls_certs_store_retention', hide);
//	}
//
//	function enable_change_dirSize() {
//		var hide = ! $('#suricataloglimit').prop('checked');
//		//disableInput('suricataloglimitsize', hide);
//	}
//
//	// ---------- Click checkbox handlers -------------------------------------------------------
//	// When 'enable_log_mgmt' is clicked, disable/enable the other page form controls
//	$('#enable_log_mgmt').click(function() {
//		enable_change();
//	});
//
//	// When 'suricataloglimit_on' is clicked, disable/enable the other page form controls
//	$('#suricataloglimit').click(function() {
//		enable_change_dirSize();
//	});
//
//	enable_change();
//	enable_change_dirSize();
//
//});
//]]>
</script>

<?php include("foot.inc"); ?>

