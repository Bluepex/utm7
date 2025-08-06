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
require_once("config.inc");
require_once("webfilter.inc");
require_once("services.inc");
require('../classes/Form.class.php');

if ($_POST) {
	if (isset($_POST['start'])) {
		mwexec("rm -rf /var/squid/cache/*");
		squid_dash_z();
		if (!is_process_running('squid'))
			mwexec('/usr/local/etc/rc.d/squid.sh start');

		$savemsg = dgettext('BluePexWebFilter', 'Clean Cache executed successfully!');
	}
	if (isset($_POST['save']) && $g['platform'] != "nanobsd") {
		$command = "/usr/local/bin/php /usr/local/bin/clean_squid_cache.php";
		if ($_POST['enable'] == "yes") {
			install_cron_job($command, true, "0", $_POST['hour'], "*", "*", $_POST['wday']);
			$savemsg = dgettext('BluePexWebFilter', 'Added config to clean the webfilter cache in the cron');
		} else {
			install_cron_job($command, false);
			$savemsg = dgettext('BluePexWebFilter', 'Clean Cache disabled successfully!');
		}
		configure_cron();
	}
}

$cron_days = array(
	"*" => dgettext('BluePexWebFilter', "every days"),
	"0" => dgettext('BluePexWebFilter', "sunday"),
	"1" => dgettext('BluePexWebFilter', "monday"),
	"2" => dgettext('BluePexWebFilter', "tuesday"),
	"3" => dgettext('BluePexWebFilter', "wednesday"),
	"4" => dgettext('BluePexWebFilter', "thursday"),
	"5" => dgettext('BluePexWebFilter', "friday"),
	"6" => dgettext('BluePexWebFilter', "saturday")
);

$clean_cache = array();
if (isset($config['cron']['item'])) {
	foreach ($config['cron']['item'] as $cron) {
		if (stristr($cron['command'], "clean_squid_cache")) {
			$clean_cache = $cron;
			break;
		}
	}
}

$pgtitle = array(dgettext("BluePexWebFilter", "WebFilter"), dgettext("BluePexWebFilter", "Clean Cache"));
include('head.inc');

if ($input_errors)
        print_input_errors($input_errors);
if ($savemsg)
        print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter','Realtime'),false,'/webfilter/wf_realtime.php');
$tab_array[] = array(dgettext('BluePexWebFilter','Clean Cache'),true,'/webfilter/wf_cleancache.php');
$tab_array[] = array(dgettext('BluePexWebFilter','Webfilter Sync'),false,'/webfilter/wf_xmlrpc_sync.php');
$tab_array[] = array(dgettext('BluePexWebFilter','Update Content'),false,'/webfilter/wf_updatecontent.php');
display_top_tabs($tab_array);

$form = new Form();
  
$section = new Form_Section(dgettext('BluePexWebFilter', 'Clean Cache'));

$section->addInput(new Form_Checkbox(
        'enable',
        'Enable',
        dgettext('BluePexWebFilter', 'Check this option to enable the Clean Cache.'),
        !empty($clean_cache)
));
  
$section->addInput(new Form_Select(
	'wday',
	dgettext('BluePexWebFilter', 'Day'),
	(isset($clean_cache['wday']) ? $clean_cache['wday'] : ""),
	$cron_days
))->setHelp(dgettext('BluePexWebFilter', 'Select a day to clean webfilter cache.'));

$section->addInput(new Form_Select(
	'hour',
	dgettext('BluePexWebFilter', 'Hour'),
	(isset($clean_cache['hour']) ? $clean_cache['hour'] : ""),
	range(0,23)
))->setHelp(dgettext('BluePexWebFilter', 'Select a hour to clean webfilter cache'));

$form->addGlobal(new Form_Button(
	'start',
	dgettext('BluePexWebFilter', 'Start Now')
))->removeClass('btn-primary')->addClass('btn-success');

$form->add($section);

print $form;

include("foot.inc");
?>
