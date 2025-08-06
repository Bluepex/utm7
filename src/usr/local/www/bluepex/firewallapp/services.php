<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Wesley F. Peres <wesley.peres@bluepex.com>, 2019
 * Rewritten by Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2023
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("bluepex/bp_webservice.inc");
require_once("bluepex/firewallapp_webservice.inc");
require_once("bluepex/firewallapp.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("bluepex/bp_cron_control.inc");

define("FILE_IF_EX_LAN", "/etc/if_ex_lan.conf");
define("LIMIT_HOSTS_FILE", "/etc/capacity-utm");
define("ARP_HOSTS", "/tmp/arp_hosts");
define("TMP_SURICATA_FAPP" ,"/etc/interfaces_suricata_fapp");
define("PATH_RULES_SURICATA", "/usr/local/share/suricata/rules/");
define("PATH_RULES_FAPP_SURICATA", "/usr/local/share/suricata/rules_fapp/");
define("FILE_FAPP_YAML_TEMPLATE", "/usr/local/pkg/suricata/yalm/fapp/suricata_yaml_template.inc");
define("FILE_YAML_TEMPLATE", "/usr/local/pkg/suricata/suricata_yaml_template.inc");

$suricata_rules_dir = SURICATA_RULES_DIR;
$suricatalogdir = SURICATALOGDIR;

$get_interface_new_fapp = getInterfaceNewFapp();
$get_status_new_fapp = getStatusNewFapp();
$get_interface_suricata_fapp = file_get_contents(TMP_SURICATA_FAPP);

if (!file_exists($TMP_SURICATA_FAPP)) {
	file_put_contents("/etc/interfaces_suricata_fapp", $get_status_new_fapp);
} else {
	if ($get_status_new_fapp > 0) {
		file_put_contents("/etc/interfaces_suricata_fapp", $get_status_new_fapp);
	} else {
		if ((strtotime("now") - filemtime($TMP_SURICATA_FAPP)) > 60) {
			file_put_contents("/etc/interfaces_suricata_fapp", $get_status_new_fapp);
		}
	}
}

init_config_arr(array('installedpackages', 'suricata', 'rule'));
init_config_arr(array('system', 'firewallapp'));

$all_gtw = getInterfacesInGatewaysWithNoExceptions();
$act = $_REQUEST['act'];
$a_rule = &$config['installedpackages']['suricata']['rule'];

if (!isset($config['system']['firewallapp']['type'])) {
	$config['system']['firewallapp']['type'] = 0;
	write_config(gettext("Setting default firewallapp"));
}

$a_profile = isset($config['system']['firewallapp']['profile']) ? $config['system']['firewallapp']['profile'] : [];

unset($profile);
$profile = $_REQUEST['profile'];
$act = (isset($_REQUEST['act']) ? $_REQUEST['act'] : '');

if ($_POST['act'] == "delete") {
	if (!isset($_REQUEST['profile'])) {
		pfSenseHeader("firewallapp/services.php");
		exit;
	}

	$profile = (int)$_REQUEST['profile'];

	unset($a_profile[$profile]);

	$savemsg = sprintf(gettext("Successfully deleted profile: %s"), $profile);

	write_config($savemsg);
}

if ($act == "edit" && isset($id) && isset($a_profile[$id])) {
	$pconfig['name'] = $a_profile[$id]['name'];
	$pconfig['gid'] = $a_profile[$id]['gid'];
	$pconfig['gtype'] = empty($a_profile[$id]['scope']) ? "local" : $a_profile[$id]['scope'];
	$pconfig['description'] = $a_profile[$id]['description'];
	$pconfig['members'] = $a_profile[$id]['member'];
	$pconfig['priv'] = $a_profile[$id]['priv'];
}

if (isset($_POST['save'])) {
	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "groupname");
	$reqdfieldsn = array(gettext("Group Name"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (preg_match("/[^a-zA-Z0-9\.\- _]/", $_POST['groupname'])) {
		$input_errors[] = sprintf(gettext(
		    "The (%s) group name contains invalid characters."),
		    $_POST['gtype']);
	}

	if (strlen($_POST['groupname']) > 16) {
		$input_errors[] = gettext(
		    "The group name is longer than 16 characters.");
	}

	/* Check the POSTed members to ensure they are valid and exist */
	if (is_array($_POST['members'])) {
		foreach ($_POST['members'] as $newmember) {
			if (!is_numeric($newmember) ||
			    empty(getUserEntryByUID($newmember))) {
				$input_errors[] = gettext("One or more " .
				    "invalid group members was submitted.");
			}
		}
	}

	if (!$input_errors && !(isset($id) && $a_profile[$id])) {
		/* make sure there are no dupes */
		foreach ($a_profile as $group) {
			if ($group['name'] == $_POST['groupname']) {
				$input_errors[] = gettext("Another entry " .
				    "with the same group name already exists.");
				break;
			}
		}
	}

	if (!$input_errors) {
		$group = array();
		if (isset($id) && $a_profile[$id]) {
			$group = $a_profile[$id];
		}

		$group['name'] = $_POST['groupname'];
		$group['description'] = $_POST['description'];
		$group['scope'] = $_POST['gtype'];

		if (empty($_POST['members'])) {
			unset($group['member']);
		} else if ($group['gid'] != 1998) { // all group
			$group['member'] = $_POST['members'];
		}

		if (isset($id) && $a_profile[$id]) {
			$a_profile[$id] = $group;
		} else {
			$group['gid'] = $config['system']['nextgid']++;
			$a_profile[] = $group;
		}

		admin_groups_sort();

		local_group_set($group);

		/*
		 * Refresh users in this group since their privileges may have
		 * changed.
		 */
		if (is_array($group['member'])) {
			init_config_arr(array('system', 'user'));
			$a_user = &$config['system']['user'];
			foreach ($a_user as & $user) {
				if (in_array($user['uid'], $group['member'])) {
					local_user_set($user);
				}
			}
		}

		/* Sort it alphabetically */
		usort($config['system']['group'], function($a, $b) {
			return strcmp($a['name'], $b['name']);
		});

		$savemsg = sprintf(gettext("Successfully %s group %s"),
		    (strlen($id) > 0) ? gettext("edited") : gettext("created"),
		    $group['name']);
		write_config($savemsg);
		syslog($logging_level, "{$logging_prefix}: {$savemsg}");

		header("Location: system_groupmanager.php");
		exit;
	}

	$pconfig['name'] = $_POST['groupname'];
}

$empty = (count(glob(PATH_RULES_SURICATA."/*")) === 0);
if ($empty){ 
	mwexec("cd /usr/local/share/suricata/ && cp rules_fapp/* rules", true);
}


$pgtitle = array(gettext("FirewallApp"), gettext("Settings"));
$pglinks = array("", "firewallapp/services.php");

if ($act == "new" || $act == "edit") {
	$pgtitle[] = gettext('Edit');
	$pglinks[] = "@self";
}

$ruledir = "{$suricata_rules_dir}";
$currentfile = $_POST['currentfile'];
$rulefile = "{$ruledir}{$currentfile}";

init_config_arr(array('system', 'user'));
$a_user = &$config['system']['user'];

$pconfig['usernamefld'] = $a_user[$id]['name'];
$pconfig['descr'] = $a_user[$id]['descr'];
$pconfig['groups'] = local_user_get_groups($a_user[$id]);

$rules_map = suricata_load_rules_map($rulefile);

/* Load up our rule action arrays with manually changed SID actions */
$alertsid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_alert']);
$dropsid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_drop']);
$passid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_pass']);

/*Get method pass fitler interface*/
$method_interface_pass = $a_rule[$id]["ips_mode"];

$rcdir = RCFILEPREFIX;
$suri_starting = array();
$by2_starting = array();

if ($_POST['id']) {
	$id = $_POST['id'];
	/*If post exist, use ID of post*/
	$method_interface_pass = $a_rule[$id]['ips_mode'];
} else { 
	$id = 0;
}

/*The last confirmation, if id is an interface wan, change for interface 1 of rule, usually is fapp lan*/
if ($a_rule[$id]["interface"] == "wan") {
	$method_interface_pass = $a_rule[1]['ips_mode'];
}

init_config_arr(array('installedpackages', 'suricata', 'rule'));
init_config_arr(array('system', 'firewallapp', 'profile'));

// Get list of configured firewall interfaces
$ifaces = get_configured_interface_list();

/* Ajax call to periodically check Firewallapp status */
/* on each configured interface. */
if ($_POST['status'] == 'check') {
	$list = array();

	// Iterate configured Firewallapp interfaces and get status of each
	// into an associative array.  Return the array to the Ajax
	// Iterate configured Barnyard2 interfaces and add status of each
	// caller as a JSON object.
	foreach ($a_rule as $natent) {
		$list[$intf_key_suricata] = "DISABLED";
		$intf_key_suricata = "suricata_" . get_real_interface($natent['interface']) . $natent['uuid'];
		if ($natent['enable'] == "on") {
			if (suricata_is_running($natent['uuid'], get_real_interface($natent['interface']))) {
				$list[$intf_key_suricata] = "RUNNING";
			} elseif (file_exists("{$g['varrun_path']}/{$intf_key_suricata}_starting.lck") || file_exists("{$g['varrun_path']}/suricata_pkg_starting.lck")) {
				$list[$intf_key_suricata] = "STARTING";
			} else {
				$list[$intf_key_suricata] = "STOPPED";
			}
			if (file_exists("{$g['varrun_path']}/suricata_updating.lck")) {
				$list[$intf_key_suricata] = "UPDATING";
			}
		}

		$list[$intf_key_barnyard] = "DISABLED";
		$intf_key_barnyard = "barnyard2_" . get_real_interface($natent['interface']) . $natent['uuid'];
		if ($natent['barnyard_enable'] == "on") {
			if (suricata_is_running($natent['uuid'], get_real_interface($natent['interface']), 'barnyard2')) {
				$list[$intf_key_barnyard] = "RUNNING";
			} elseif (file_exists("{$g['varrun_path']}/{$intf_key_barnyard}_starting.lck") || file_exists("{$g['varrun_path']}/suricata_pkg_starting.lck")) {
				$list[$intf_key_barnyard] = "STARTING";
			} else {
				$list[$intf_key_barnyard] = "STOPPED";
			}
		}
	}

	// Return a JSON encoded array as the page output
	echo json_encode($list);
	exit;
}

if (isset($_POST['interfaceFAPP']) && !empty($_POST['interfaceFAPP']) && isset($_POST['ipsMode']) && !empty($_POST['ipsMode'])) {
	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	file_put_contents('/tmp/interfaces_suricata_fapp', '1');

	if (!is_dir("/usr/local/share/suricata/acp/") || !is_dir("/usr/local/share/suricata/rules_acp/") || !is_dir(PATH_RULES_FAPP_SURICATA))  {
		unlink_if_exists("/usr/local/share/suricata/fapp_version");
		unlink_if_exists("/usr/local/share/suricata/fapp_install");
		mwexec("/usr/local/bin/php /usr/local/www/firewallapp/get_rules_lists_webservice.php update_fapp");
		$force_update_rules_action = true;
	}

	clean();

	file_put_contents("{$g['varrun_path']}/suricata_start_all.lck", '');
	copy(FILE_FAPP_YAML_TEMPLATE, FILE_YAML_TEMPLATE);

	#Delete files lck
	#New line for destroy lck files
	$if_rm = get_real_interface(strtolower(explode("||", $_POST['interfaceFAPP'])[0]));
	unlink_if_exists("{$g['varrun_path']}/suricata_{$if_rm}*.lck");
	unlink_if_exists(PATH_RULES_FAPP_SURICATA."_emerging.rules");
	unlink_if_exists(PATH_RULES_FAPP_SURICATA."_ameacas_ext.rules");
	unlink_if_exists(PATH_RULES_FAPP_SURICATA."_ameacas.rules");
	foreach (glob(PATH_RULES_SURICATA."*") as $file) {
		unlink_if_exists($file);
	}
	foreach (glob(PATH_RULES_FAPP_SURICATA."*") as $file) {
		copy($file, PATH_RULES_SURICATA.basename($file));
	}
	unlink_if_exists(PATH_RULES_SURICATA."_emerging.rules");
	unlink_if_exists(PATH_RULES_SURICATA."_ameacas_ext.rules");
	unlink_if_exists(PATH_RULES_SURICATA."_ameacas.rules");

	enable_limit_logs_acp_fapp();

	if (!is_array($config['installedpackages']['suricata']['rule'])) {
		$config['installedpackages']['suricata']['rule'] = array();
	}	
		
	$a_rule = &$config['installedpackages']['suricata']['rule'];
	for ($id = 0; $id <= count($a_rule)-1; $id++) {
		$if_real = get_real_interface($a_rule[$id]['interface']);
		$suricata_uuid = $a_rule[$id]['uuid'];
		unlink_if_exists("{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck");
		unlink_if_exists("{$g['etc_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck");
	}
	$log_write = "Starting Suricata on {$if_friendly}({$if_real}) per user request...";
	sleep(2);
	control_state_interface($_POST['interfaceFAPP'], $_POST['ipsMode']);
	sleep(28);
	auto_db_install();

	if (suricata_is_running($suricatacfg['uuid'], $if_real)) {
		$log_write = "Restarting Suricata on {$if_friendly}({$if_real}) per user request...";
		$suri_starting[$id] = 'TRUE';
		if ($suricatacfg['barnyard_enable'] == 'on' && !isvalidpid("{$g['varrun_path']}/barnyard2_{$if_real}{$suricata_uuid}.pid")) {
			$by2_starting[$id] = 'TRUE';
		}
	}

	log_error($log_write);

	mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');

	foreach (glob(PATH_RULES_SURICATA."*") as $file) {
		unlink_if_exists($file);
	}
	foreach (glob(PATH_RULES_FAPP_SURICATA."*") as $file) {
		copy($file, PATH_RULES_SURICATA.basename($file));
	}

	mwexec("/usr/local/bin/php -f /usr/local/www/cron_install_acp_fapp_services.php");

	sleep(2);
	$retval = 0;
	$retval |= filter_configure();
	clear_subsystem_dirty('filter');
}

$error_show = [];

$amount_interfaces_avaliable_simple = intval(explode("___", return_option_mult_interfaces_fapp_acp())[1]);

if (isset($_POST['enable'])) {
	switch ($_POST['enable']) {
		case 'enable':

			if ($amount_interfaces_avaliable_simple != -1) {

				global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

				file_put_contents('/etc/interfaces_suricata_fapp', '1');

				if (!is_dir("/usr/local/share/suricata/acp/") || !is_dir("/usr/local/share/suricata/rules_acp/") || !is_dir(PATH_RULES_FAPP_SURICATA))  {
					unlink_if_exists("/usr/local/share/suricata/fapp_version");
					unlink_if_exists("/usr/local/share/suricata/fapp_install");
					mwexec("/usr/local/bin/php /usr/local/www/firewallapp/get_rules_lists_webservice.php update_fapp");
					$force_update_rules_action = true;
				}

				clean();

				file_put_contents("{$g['varrun_path']}/suricata_start_all.lck", '');

				copy(FILE_FAPP_YAML_TEMPLATE, FILE_YAML_TEMPLATE);

				#Delete files lck
				#New line for destroy lck files
				unlink_if_exists(PATH_RULES_FAPP_SURICATA."_emerging.rules");
				unlink_if_exists(PATH_RULES_FAPP_SURICATA."_ameacas_ext.rules");
				unlink_if_exists(PATH_RULES_FAPP_SURICATA."_ameacas.rules");
				foreach (glob(PATH_RULES_SURICATA."*") as $file) {
					unlink_if_exists($file);
				}
				foreach (glob(PATH_RULES_FAPP_SURICATA."*") as $file) {
					copy($file, PATH_RULES_SURICATA.basename($file));
				}
				unlink_if_exists(PATH_RULES_SURICATA."_emerging.rules");
				unlink_if_exists(PATH_RULES_SURICATA."_ameacas_ext.rules");
				unlink_if_exists(PATH_RULES_SURICATA."_ameacas.rules");

				enable_limit_logs_acp_fapp();

				if (!is_array($config['installedpackages']['suricata']['rule'])) {
					$config['installedpackages']['suricata']['rule'] = array();
				}	
					
				$a_rule = &$config['installedpackages']['suricata']['rule'];
				for ($id = 0; $id <= count($a_rule)-1; $id++) {
					$if_real = get_real_interface($a_rule[$id]['interface']);
					$suricata_uuid = $a_rule[$id]['uuid'];
					if (!in_array($if_real,$all_gtw,true)) {
						unlink_if_exists("{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck");
						unlink_if_exists("{$g['etc_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck");
					}
				}

				$log_write = "Starting Suricata on {$if_friendly}({$if_real}) per user request...";
				sleep(2);
				control_state_interface();
				sleep(28);
				auto_db_install();

				if (suricata_is_running($suricatacfg['uuid'], $if_real)) {
					$log_write = "Restarting Suricata on {$if_friendly}({$if_real}) per user request...";
					$suri_starting[$id] = 'TRUE';
					if ($suricatacfg['barnyard_enable'] == 'on' && !isvalidpid("{$g['varrun_path']}/barnyard2_{$if_real}{$suricata_uuid}.pid")) {
						$by2_starting[$id] = 'TRUE';
					}
				}

				log_error($log_write);

				mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');

				foreach (glob(PATH_RULES_SURICATA."*") as $file) {
					unlink_if_exists($file);
				}
				foreach (glob(PATH_RULES_FAPP_SURICATA."*") as $file) {
					copy($file, PATH_RULES_SURICATA.basename($file));
				}

				mwexec("/usr/local/bin/php -f /usr/local/www/cron_install_acp_fapp_services.php");

				sleep(2);
				$retval = 0;
				$retval |= filter_configure();
				clear_subsystem_dirty('filter');
			} else {
				$error_show = "Simple interfaces limit reached, can't start a new interface.";
			}

			break;

		case 'on':

			if (!is_dir("/usr/local/share/suricata/acp/") ||
			    !is_dir("/usr/local/share/suricata/rules_acp/") ||
			    !is_dir(PATH_RULES_FAPP_SURICATA))  {
				unlink_if_exists("/usr/local/share/suricata/fapp_version");
				unlink_if_exists("/usr/local/share/suricata/fapp_install");
				mwexec("/usr/local/bin/php /usr/local/www/firewallapp/get_rules_lists_webservice.php update_fapp");
				$force_update_rules_action = true;
			}

			clean();

			global $g, $config, $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

			#Delete files lck
			#New line for destroy lck files
			file_put_contents("{$g['varrun_path']}/suricata_start_all.lck", '');

			copy(FILE_FAPP_YAML_TEMPLATE, FILE_YAML_TEMPLATE);

			unlink_if_exists(PATH_RULES_FAPP_SURICATA."_emerging.rules");
			unlink_if_exists(PATH_RULES_FAPP_SURICATA."_ameacas_ext.rules");
			unlink_if_exists(PATH_RULES_FAPP_SURICATA."_ameacas.rules");

			foreach (glob(PATH_RULES_SURICATA."*") as $files) {
				unlink_if_exists($files);
			}

			foreach (glob(PATH_RULES_FAPP_SURICATA."*") as $files) {
				copy($files, PATH_RULES_SURICATA.basename($files));
			}

			unlink_if_exists(PATH_RULES_SURICATA."_ameacas.rules");
			unlink_if_exists(PATH_RULES_SURICATA."_ameacas_ext.rules");
			unlink_if_exists(PATH_RULES_SURICATA."_emerging.rules");

			enable_limit_logs_acp_fapp();

			if (!is_array($config['installedpackages']['suricata']['rule'])) {
				$config['installedpackages']['suricata']['rule'] = array();
			}

			$a_rule = $config['installedpackages']['suricata']['rule'];

			foreach ($a_rule as $key => $suricatacfg) {
				$if = get_real_interface(strtolower($suricatacfg['interface']));
				$uuid = $suricatacfg['uuid'];

				if (in_array($if, $all_gtw, true) ||
				    $suricatacfg['enable'] != 'on' ||
				    get_real_interface(strtolower($suricatacfg['interface'])) == "" ||
				    (file_exists("{$g['varrun_path']}/suricata_{$if}{$uuid}.pid") &&
				    isvalidpid("{$g['varrun_path']}/suricata_{$if}{$uuid}.pid"))
				) {
					continue;
				}

				unlink_if_exists("{$g['varrun_path']}/suricata_{$if}{$uuid}_stop.lck");
				unlink_if_exists("/etc/suricata_{$if}{$uuid}_stop.lck");
				unlink_if_exists("{$g['varrun_path']}/suricata_{$if}{$uuid}.pid");
				file_put_contents("{$g['varrun_path']}/suricata_{$if}{$uuid}_starting", "");

				$ruledir = "{$suricata_rules_dir}";
				$currentfile = $_POST['currentfile'];
				$rulefile = "{$ruledir}{$currentfile}";
				$rules_map = suricata_load_rules_map($rulefile);
				suricata_modify_sids_action($rules_map, $a_rule[$key]);

				$rebuild_rules = true;
				suricata_generate_yaml($a_rule[$key]);
				$rebuild_rules = false;

				/* Signal Suricata to "live reload" the rules */
				suricata_reload_config($a_rule[$key]);

				// Sync to configured CARP slaves if any are enabled
				suricata_sync_on_changes();

				sleep(2);
				suricata_start($suricatacfg, get_real_interface($suricatacfg['interface']));
				sleep(28);
			}

			mwexec_bg('/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py');

			foreach (glob(PATH_RULES_FAPP_SURICATA."*") as $files) {
				copy($files, PATH_RULES_SURICATA.basename($files));
			}

			sleep(2);
			$retval = 0;
			$retval |= filter_configure();
			clear_subsystem_dirty('filter');

			break;

		case 'off':
			
			file_put_contents('/etc/interfaces_suricata_fapp', '0');

			clean();

			unlink_if_exists("{$g['varrun_path']}/suricata_start_all.lck");

			#Delete files lck
			#New line for destroy lck files
			global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

			if (!is_array($config['installedpackages']['suricata']['rule'])) {
				$config['installedpackages']['suricata']['rule'] = array();
			}

			$a_rule = &$config['installedpackages']['suricata']['rule'];

			for ($id = 0; $id <= count($a_rule)-1; $id++) {

				$if_real = get_real_interface($a_rule[$id]['interface']);

				$suricata_uuid = $a_rule[$id]['uuid'];				

				unlink_if_exists("{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_starting");

				foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
					$if = get_real_interface($suricatacfg['interface']);

					if (!in_array($if, $all_gtw,true)) {
						suricata_stop($suricatacfg, get_real_interface($suricatacfg['interface']));
						sleep(1);
						unlink_if_exists("{$g['varrun_path']}/suricata_{$if}{$uuid}.pid");
						unlink_if_exists("/var/suricata_{$if}{$uuid}_heuristic.pid");
					}
				}

			}

			mwexec("pkill -9 -af wf", true);
			mwexec('pkill -9 -af "tls|http|alerts"', true);
			break;
	}

	unset($suricata_start_cmd);
}

$force_update_rules_action = false;

function reloadInterfacesFAPP() {
	global $g, $config;

	copy(FILE_FAPP_YAML_TEMPLATE, FILE_YAML_TEMPLATE);
	unlink_if_exists(PATH_RULES_FAPP_SURICATA."_emerging.rules");
	unlink_if_exists(PATH_RULES_FAPP_SURICATA."_ameacas_ext.rules");
	unlink_if_exists(PATH_RULES_FAPP_SURICATA."_ameacas.rules");
	foreach (glob(PATH_RULES_SURICATA."*") as $files) {
		unlink_if_exists($files);
	}
	foreach (glob(PATH_RULES_FAPP_SURICATA."*") as $files) {
		copy($files, PATH_RULES_SURICATA.basename($files));
	}
	unlink_if_exists(PATH_RULES_SURICATA."_ameacas.rules");
	unlink_if_exists(PATH_RULES_SURICATA."_ameacas_ext.rules");
	unlink_if_exists(PATH_RULES_SURICATA."_emerging.rules");
		
	foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
		$if = get_real_interface($suricatacfg['interface']);
		$uuid = $suricatacfg['uuid'];
		if (suricata_is_running($uuid, $if) && !in_array($if_real, $all_gtw,true)) {
			if ($suricatacfg['enable'] != 'on' || get_real_interface($suricatacfg['interface']) == "") {
				continue;
			}
			$ruledir = "{$suricata_rules_dir}";
			$currentfile = $_POST['currentfile'];
			$rulefile = "{$ruledir}{$currentfile}";
			$a_rule = &$config['installedpackages']['suricata']['rule'][$key];
			$rules_map = suricata_load_rules_map($rulefile);
			suricata_modify_sids_action($rules_map, $a_rule);
			$rebuild_rules = true;
			suricata_generate_yaml($a_rule);
			$rebuild_rules = false;
			/* Signal Suricata to "live reload" the rules */
			suricata_reload_config($a_rule);
			// Sync to configured CARP slaves if any are enabled
			suricata_sync_on_changes();
		}
	}
}

if (isset($_POST['reloadInterfacesACP'])) {
	reloadInterfacesFAPP();
	$force_update_rules_action = true;
}

if (isset($_POST['force_update_rules'])) {
	switch ($_POST['force_update_rules']) {
		case 'on':

			//Customize timeout for post update rules
			set_time_limit(300);

			unlink_if_exists("/usr/local/share/suricata/fapp_version");
			unlink_if_exists("/usr/local/share/suricata/fapp_install");
			mwexec("/usr/local/bin/php /usr/local/www/firewallapp/get_rules_lists_webservice.php update_fapp");

			reloadInterfacesFAPP();

			$force_update_rules_action = true;
			break;
	}
}

if (isset($_POST['stats_clean'])) {
	switch ($_POST['stats_clean']) {
		case 'on':
			exec("/sbin/pfctl -sT | grep fapp2c", $out, $err);
			if (!empty($err) || empty($out)) {
				break;
			}
			foreach ($out as $table) {
				mwexec("/sbin/pfctl -t ${table} -T flush");
			}
			break;
	}
}

$ignore_phisical_interfaces_vlan = isset($config['vlans']['vlan']) ? array_filter(array_unique(array_column($config['vlans']['vlan'], "if"))) : [];
$ignore_phisical_interfaces_vlan = array_filter(array_unique($ignore_phisical_interfaces_vlan));
$exceptions_in_file = (file_exists(FILE_IF_EX_LAN)) ? array_unique(array_filter(explode(",", file_get_contents(FILE_IF_EX_LAN)))) : [];
$exceptionInterfaces = array_merge($exceptions_in_file, $ignore_phisical_interfaces_vlan);
$a_rule = $config['installedpackages']['suricata']['rule'];

include("head.inc");
?>

<style>
	table { background-color:#fff }
	table tr:hover { background-color:#f9f9f9 }
	.btn-disabled { opacity:0.3; }
	.checked { opacity:1 }
	.btn-group-vertical > .btn.active,
	.btn-group-vertical > .btn:active,
	.btn-group-vertical > .btn:focus,
	.btn-group-vertical > .btn:hover,
	.btn-group > .btn.active,
	.btn-group > .btn:active,
	.btn-group > .btn:focus,
	.btn-group > .btn:hover { outline:none }
	.btn-group .btn { margin:0 }
	.panel .panel-body { padding:10px }

	.btn-primary:focus {
		background-color: #286090 !important;
		border-color: transparent !important;
	}

	.status-running {
		color: #43A047;
		font-weight: bold;
		font-size: 14px;
	}

	.status-stopped {
		color:	#f00;
		font-weight: bold;
		font-size: 14px;
	}

	.close-field {
		max-width:100px;
		min-width:100px;
		width:100%;
		background-color: red;
		text-shadow: 1px 1px black;
		color: white;
		border:1px solid black;
		border-top-right-radius: 5px;
		border-bottom-right-radius: 5px;
	}

	.btn-descr {
		border-radius: 20px;
		padding: 5px;
		font-size: 10px;
		margin: 2px;
		margin-left: auto;
	}
</style>
<div onmouseover="mOver(this)">
<?php
if ($savemsg) {
	print_info_box($savemsg, 'success');
}

if ($error_show) {
	print_info_box($error_show, 'danger');
}
?>
<?php if ($act == "") { ?>
<form action="services.php" method="POST">
	<div class="col-sm-12" >
		<div class="panel panel-default">
			<div class="row">
				<div id="status-bar-fapp" class="col-12 bg-success3 py-3 color-white">
					<div class="row">
						<div class="col-12 col-md-9">
							<div class="row" id="status-firewall">
								<h6 for="service_status" class="mb-3 mb-sm-0 mb-md-0 mt-0 pt-2"><i id="buton_status" class="fa fa-check px-3 ml-1 fa-4x border-right" aria-hidden="true"></i> <span class="mx-2"><?=gettext("Status")?>: </span> <span id="status-info" style="display:none; color:white"><?=gettext("Running")?></span></span></h6>

								<?php if (file_exists("{$g['varrun_path']}/suricata_updating.lck")) :  ?>
									<button class="btn btn-primary btn-sm ml-3 mx-md-5" type="submit" name="enable" value="enable" disabled="disabled"><i class="fa fa-refresh"></i> <?=gettext("Updating Rules...")?></button>
								<?php else : ?>
									<?php if (intval($get_interface_new_fapp) == 0): ?>
										<? if (intval(trim(file_get_contents('/etc/interfaces_suricata_fapp'))) == 0): ?>
											<?php
											if (count($exceptionInterfaces) == 0) {
											?>
												<button id="click_ativa" class="btn btn-success btn-sm ml-3 mx-md-5" type="submit" onclick="document.getElementById('click_ativa').style.display = 'none';" name="enable" value="enable"><i class="fa fa-check"></i> <?=gettext("Enable")?></button>
											<?php } else { ?>
												<button class="btn btn-success btn-sm ml-3 mx-md-5" onclick="event.preventDefault(); helpInformationNotEnableSimple();" ><i class="fa fa-question"></i> <?=gettext("Help")?></button>
											<?php } ?>
										<?php endif; ?>
									<?php endif; ?>
								<?php endif; ?>
							</div>
						</div>
						<input type="hidden" id="current_file" value="" />
						<div class="col-12 col-md-3 mt-2 mt-md-0 d-md-flex justify-content-end">
							<button id="status-button-fapp" type="button" onclick="window.open('http://wsutm.bluepex.com/docs/FirewallAPP.pdf')" class="btn btn-primary btn-sm ml-3 mx-md-5"><i class="fa fa-book" aria-hidden="true"></i> <?=gettext("How to Configure FirewallAPP?")?></button>
							<button id="status-button-fapp2" type="button" class="btn btn-success dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<?=gettext("Settings")?>
							</button>
							<div class="dropdown-menu dropdown-menu-right">
								<?php
								$status_iface = $get_status_new_fapp;
								$status_iface2 = $get_interface_new_fapp;
								$status_iface3 = $get_interface_suricata_fapp;
								if (($status_iface == 0) && ($status_iface2 == 0) && ($status_iface3 == 0)) {
								?>
									<button class="dropdown-item" data-toggle="modal" data-target="#modal_enable_advanced_FAPP" type="button"><i class="fa fa-check"></i> Habilitar Avançado</button>
								<?php } ?>
								<?php $status_ = $get_interface_new_fapp; $status2_ = $get_status_new_fapp;
									if ($status_ != 0) : ?>
									<?php if (($status_ != 0) && ($status2_ != 0)) : ?>
										<button class="dropdown-item" data-toggle="modal" data-target="#modal_disabled_interfaces" type="button"><i class="fa fa-ban"></i> <?=gettext("Deactivate")?></button>
									<?php else: ?>
										<?php
										//If only interface is enable, continue operation
										$alert_interface = true;
										foreach ($a_rule as $rule_interface) {
											if (($rule_interface["interface"] != "wan") && ($rule_interface["enable"] == "on")) {
												$alert_interface = false;
											}
										}
										if ($alert_interface) { 
											?>
											<button class="dropdown-item" id="verify_all_interfaces" data-toggle="modal" data-target="#modal_alert_interfaces" type="button"><i class="fa fa-check"></i> <?=gettext("Activate")?></button>

										<?php } else { ?>
											<?php
											$sub = $config['interfaces']['lan']['subnet'];
											if (($capacity_percent >= 90) || ($sub < 24)) :
											?>
													<button class="dropdown-item" id="verify_tunning11" data-toggle="modal" data-target="#modal_mask_enable" type="button" name="verify_tunning11" value="1"><i class="fa fa-check"></i> <?=gettext("Activate")?></button>
											<?php else: ?>
													<a href="./firewallapp_interfaces.php" class="dropdown-item"><i class="fa fa-check"></i> <?=gettext("Activate")?></a>

											<?php endif; ?>
										<?php } ?>
									<?php endif;
								?>
							<?php
									$arp_hosts = 0;
									$capacity_percent = 0;
									$ifdescrs = get_configured_interface_with_descr(true);
									$if = "lan";
									$sub = $config['interfaces']['lan']['subnet'];
									$limit_hosts = (file_exists(LIMIT_HOSTS_FILE)) ? trim(file_get_contents(LIMIT_HOSTS_FILE)) : 0;
									$arp_hosts = (file_exists(ARP_HOSTS)) ? trim(file_get_contents(ARP_HOSTS)) : 0;
									$capacity_percent = ($arp_hosts * 100) / $limit_hosts;
								?>
								<?php endif; ?>
								<button class="dropdown-item" data-toggle="modal" data-target="#modal_clean_stats11" type="button" id="clean_stats1" name="clean_stats1" value="1"><i class="fa fa-ban"></i> <?=gettext("Clear Blocked States")?> </button>
								<a href="firewallapp_passlist.php" class="dropdown-item"><i class="fa fa-unlock" aria-hidden="true"></i> <?=gettext("Pass")?></a>
								<a href="diag_backup.php" class="dropdown-item"><i class="fa fa-file"></i> <?=gettext("Backup/Restore Rules")?></a>
								<a href="firewallapp_interfaces.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("Edit Interfaces")?></a>
								<a href="firewallapp_logs_mgmt.php" class="dropdown-item"><i class="fa fa-hand-paper-o"></i> <?=gettext("Limits")?></a>
								<a href="firewallapp_logs_browser.php" class="dropdown-item"><i class="fa fa-binoculars"></i> <?=gettext("Logs & Status")?></a>
								<a href="report_settings.php" class="dropdown-item"><i class="fa fa-book"></i> <?=gettext("Reports")?></a>
								<a href="custom_rule_fapp.php" class="dropdown-item"><i class="fa fa-gear"></i> <?=gettext("Custom rule")?></a>
								<a href="firewallapp_traffic_analysis.php" class="dropdown-item"><i class="fa fa-search"></i> <?=gettext("Analyze signature/traffic")?></a>
								<a href="table_extra_protection.php" class="dropdown-item"><i class="fa fa-search"></i> <?=gettext("Related Traffic")?></a>
								<a href="firewallapp_block_address.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("Blocks in performance mode")?></a>
								<a href="control_ignore_block_ugidlocal.php" class="dropdown-item"><i class="fa fa-lock"></i> <?=gettext("Address ignore Block")?></a>
								<a href="no_gtw_expt.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("Exception Interfaces")?></a>
								<a href="../ssl_inspect/ssl_inspect.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("SSL Inspect")?></a>
								<button class="dropdown-item" data-toggle="modal" data-target="#modal_force_update11" type="button" id="modal_force_update1" name="modal_force_update1" value="1"><i class="fa fa-refresh"></i> <?=gettext("Update Subscriptions")?> </button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="col-sm-12 text-center">
		<hr />
		<input type="hidden" id="sid" name="sid" value="">
		<input type="hidden" id="state" name="state" value="">
		<input type="hidden" id="currentfile" name="currentfile" value="">
	</div>

	<!-- modal disabled interfaces -->
	<div id="modal_disabled_interfaces" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<font color="black"><h4 class="modal-title"><?=gettext("Do you want to disable the FirewallAPP service")?></h4></font>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<p><?=gettext("Confirming this action will cause the FirewallAPP service to be stopped on all operating interfaces.")?></p>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal"><?=gettext("Cancel")?></button>
					<button class="btn btn-warning" type="submit" id="click_disable5" name="enable" value="off"><i class="fa fa-refresh"></i> <?=gettext("Deactivate")?> </button>
				</div>
			</div>
		</div>
	</div>

	<!-- modal confirmation type -->
	<div id="modal_type" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title"><?=gettext("Change of FirewallAPP Type")?></h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<?php if ($method_interface_pass == "ips_mode_inline") { ?>
						<center><h5><?=gettext("ATTENTION, READ CAREFULLY BEFORE CONTINUING:")?></h5></center>
								<small id="save_type_msg_1" style="display:none">
									<center><p><?=gettext("After confirming this change, all FirewallApp settings and adjustments")?></b></p></center>
									<center><p><?=gettext("will be reprocessed and it will be necessary to reconfigure again if you choose to return")?></b></p></center>
									<center><p><?=gettext("to current FirewallApp mode. Be sure and aware before proceeding.")?></b></p></center>
								</small>
								<div id="save_type_msg_2" style="display:none">
									<center><p class="text-danger"><b><?=gettext("Are you sure about that?")?></b></p></center>
									<center><p class="text-danger"><b><?=gettext("When proceeding you will lose all previous Firewallapp settings")?></b></p></center>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Cancel")?></button>
							<button type="button" class="btn btn-default" id="save_type" name="save_type" date-done="false"><?=gettext("Confirm")?></button>
						</div>
					<?php } else { ?>
								<div id="save_type_msg_1" style="display:none">
									<center><p class="text-danger"><b><?=gettext("Cannot use FirewallApp Per User in this mode")?></b></p></center>
									<center><p class="text-danger"><b><?=gettext("FirewallApp per User technology requires the execution mode to be Heuristic, to activate this option, change the mode in the corresponding interface")?></b></p></center>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Exit")?></button>
						</div>
					<?php } ?>
			</div>
		</div>
	</div>
	<!-- modal confirmation type  -->

	<!-- modal modal_alert_interfaces -->
	<div id="modal_alert_interfaces" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<font color="black"><h4 class="modal-title"><?=gettext("ATTENTION TO THIS INFORMATION:")?></h4></font>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<center><small id="save_mask_enable_msg_1">
								<font color="black"><h5><?=gettext("ATTENTION, TO AVOID ERRORS:")?></h5></font>
								<font color="black"><p><?=gettext("To activate FirewallApp, at least one active interface is required to enable the service.")?> </b></p></font>
								<font color="black"><p><?=gettext("To enable an interface, go to the side menu, select edit interfaces,")?> </b></p></font> 
								<font color="black"><p><?=gettext("in the list of possible interfaces, select edit an interface and enable it,")?> </b></p></font>
								<font color="black"><p><?=gettext("apply your changes and with that it is possible to start the service.")?> </b></p></font>
							</small>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Cancel")?></button>
				</div>
			</div>
		</div>
	</div>
	<!-- modal submask -->

	<!-- modal modal_inter_fapp -->
		<div id="modal_clean_stats11" class="modal fade" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<font color="black"><h4 class="modal-title"><?=gettext("ATTENTION TO THIS INFORMATION:")?></h4></font>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="form-group">
							<center><small id="save_mask_enable_msg_1">
									<font color="black"><h5><?=gettext("ATTENTION, TO AVOID ERRORS:")?></h5></font>
									<font color="black"><p><?=gettext("This routine is useful to release some access that may be blocking")?> </b></p></font>
									<font color="black"><p><?=gettext("erroneously, as a false positive. But it is important to be aware that")?> </b></p></font>
									<font color="black"><p><?=gettext("the other assigned locks will be cleared after running, performing the")?> </b></p></font>
									<font color="black"><p><?=gettext("block again as soon as FirewallApp identifies the threat.")?> </b></p></font>
							</small>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Cancel")?></button>
						<button class="btn btn-primary" type="submit" id="stats_clean" name="stats_clean" value="on" ><i class="fa fa-ban"></i> <?=gettext("Clear Blocked States")?> </button>
					</div>
				</div>
			</div>
		</div>
		<!-- modal submask -->

		<!-- modal modal_inter_fapp -->
		<div id="modal_force_update11" class="modal fade" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<font color="black"><h4 class="modal-title"><?=gettext("ATTENTION TO THIS INFORMATION:")?></h4></font>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="form-group">
							<center><small id="save_mask_enable_msg_11">
									<font color="black"><h5><?=gettext("ATTENTION, TO AVOID ERRORS:")?></h5></font>
									<font color="black"><p><?=gettext("This routine forces FirewallApp signatures to be updated and should NOT")?> </b></p></font>
									<font color="black"><p><?=gettext("run sequentially or many times a day. the system updates")?>  </b></p></font>
									<font color="black"><p><?=gettext("automatically when it identifies changes to the rules on our servers.")?> </b></p></font>
									<font color="black"><p><?=gettext("Use this tool with caution and when you need to force the update.")?> </b></p></font>
							</small>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Cancel")?></button>
						<button class="btn btn-primary" type="submit" id="force_reload_rules_fapp" name="force_reload_rules_fapp"><i class="fa fa-refresh"></i> <?=gettext("Reload Rules")?> </button>
						<button class="btn btn-primary" type="submit" id="force_update_rules" name="force_update_rules" value="on" onclick="showDisplayNoneUpdate11()"><i class="fa fa-refresh"></i> <?=gettext("Update Subscriptions")?> </button>
					</div>
				</div>
			</div>
		</div>
		<!-- modal submask -->

	<!-- modal submask -->
	<div id="modal_mask" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title"><?=gettext("HARDWARE IDENTIFIED IN OVERLOAD")?></h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<center><small id="save_mask_msg_1">
							<?php
								$limit_hosts = 0;
								$arp_hosts = 0;
								$capacity_percent = 0;
								// Get configured interface list
								$ifdescrs = get_configured_interface_with_descr(true);
								$if = "lan";
								$sub = $config['interfaces']['lan']['subnet'];
								if (file_exists(LIMIT_HOSTS_FILE)) {
									$limit_hosts = trim(file_get_contents(LIMIT_HOSTS_FILE));
								}
								if (file_exists(ARP_HOSTS)) {
									$arp_hosts = trim(file_get_contents(ARP_HOSTS));
								}
								$limit_hosts = intval($limit_hosts);
								if ($limit_hosts <= 0) {
								    $limit_hosts = 1;
								}
								$capacity_percent = ($arp_hosts * 100) / $limit_hosts;
							?>
							<?php if ($sub < 24) : ?>
								<h5><?=gettext("ATTENTION, READ CAREFULLY BEFORE CONTINUING:")?></h5>
								<p><?=gettext("It was identified that the equipment's network mapping is configured")?> </b></p>
								<p><?=gettext("to service devices ABOVE the capacity limit, this may result in")?></b></p>
								<p><?=gettext("Hardware lockup and/or limitation of other UTM services.")?> </b></p>
							<?php else : ?>
								<?php if ($capacity_percent <= 99 && $capacity_percent >= 80) : ?>
									<h5><?=gettext("ATTENTION, READ CAREFULLY BEFORE CONTINUING:")?></h5>
									<p><?=gettext("It was identified that the equipment capacity is above 90% of use,")?></b></p>
									<p><?=gettext("when activating the tool with the capacity NEAR the limit, it may cause")?></b></p>
									<p><?=gettext("Hardware lockup and/or limitation of other UTM services.")?> </b></p>
								<?php endif; ?>
								<?php if ($capacity_percent >= 100) : ?>

									<h5><?=gettext("ATTENTION, READ CAREFULLY BEFORE CONTINUING:")?></h5>
									<p><?=gettext("It was identified that the equipment capacity is above 100% of use,")?></b></p>
									<p><?=gettext("when activating the tool with the capacity ABOVE the limit, it may cause")?></b></p>
									<p><?=gettext("Hardware lockup and/or limitation of other UTM services.")?> </b></p>
								<?php endif; ?>
							<?php endif; ?>
						</small>
						<div id="save_mask_msg_2" style="display:none">
							<p class="text-danger"><b><?=gettext("Are you sure about that?")?></b></p>
							<p class="text-danger"><b><?=gettext("By proceeding, you AGREE and ACKNOWLEDGE the RISKS of Activating with Non-Recommended conditions.")?></b></p>
							<button id="click_ativa" class="btn btn-success btn-sm ml-3 mx-md-5" type="submit" onclick="document.getElementById('click_ativa').style.display = 'none';document.getElementById('verify_tunning').style.display = 'none';" name="enable" value="enable"><i class="fa fa-check"></i> <?=gettext("Enable")?></button>
						</div></center>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Cancel")?></button>
					<button type="button" class="btn btn-default" id="verify_tunning2" name="verify_tunning2" date-done="false"><?=gettext("Confirm")?></button>
				</div>
			</div>
		</div>
	</div>
	<!-- modal submask -->

	<!-- modal modal_mask_enable -->
	<div id="modal_mask_enable" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title"><?=gettext("HARDWARE IDENTIFIED IN OVERLOAD")?></h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<center><small id="save_mask_enable_msg_1">
							<?php
								$limit_hosts = 0;
								$arp_hosts = 0;
								$capacity_percent = 0;
								// Get configured interface list
								$ifdescrs = get_configured_interface_with_descr(true);
								$if = "lan";
								$sub = $config['interfaces']['lan']['subnet'];
								if (file_exists(LIMIT_HOSTS_FILE)) {
									$limit_hosts = trim(file_get_contents(LIMIT_HOSTS_FILE));
								}
								if (file_exists(ARP_HOSTS)) {
									$arp_hosts = trim(file_get_contents(ARP_HOSTS));
								}$limit_hosts = intval($limit_hosts);
								if ($limit_hosts <= 0) {
								    $limit_hosts = 1;
								}
								$capacity_percent = ($arp_hosts * 100) / $limit_hosts;
							?>
							<?php if ($sub < 24) : ?>
								<h5><?=gettext("ATTENTION, READ CAREFULLY BEFORE CONTINUING:")?></h5>
								<p><?=gettext("It was identified that the equipment's network mapping is configured")?> </b></p>
								<p><?=gettext("to service devices ABOVE the capacity limit, this may result in")?></b></p>
								<p><?=gettext("Hardware lockup and/or limitation of other UTM services.")?> </b></p>
							<?php else : ?>
								<?php if ($capacity_percent <= 99 && $capacity_percent >= 80) : ?>
									<h5><?=gettext("ATTENTION, READ CAREFULLY BEFORE CONTINUING:")?></h5>
									<p><?=gettext("It was identified that the equipment capacity is above 90% of use,")?></b></p>
									<p><?=gettext("when activating the tool with the capacity NEAR the limit, it may cause")?></b></p>
									<p><?=gettext("Hardware lockup and/or limitation of other UTM services.")?> </b></p>
								<?php endif; ?>
								<?php if ($capacity_percent >= 100) : ?>

									<h5><?=gettext("ATTENTION, READ CAREFULLY BEFORE CONTINUING:")?></h5>
									<p><?=gettext("It was identified that the equipment capacity is above 100% of use,")?></b></p>
									<p><?=gettext("when activating the tool with the capacity ABOVE the limit, it may cause")?></b></p>
									<p><?=gettext("Hardware lockup and/or limitation of other UTM services.")?> </b></p>
								<?php endif; ?>
							<?php endif; ?>
						</small>
						<div id="save_mask_enable_msg_2" style="display:none">
							<p class="text-danger"><b><?=gettext("Are you sure about that?")?></b></p>
							<p class="text-danger"><b><?=gettext("By proceeding, you AGREE and ACKNOWLEDGE the RISKS of Activating with Non-Recommended conditions.")?></b></p>
							<button class="btn btn-success btn-sm ml-3 mx-md-5" id="click_enable" type="submit" name="enable" value="on"><i class="fa fa-check"></i> <?=gettext("Start Interfaces")?></button>
						</div></center>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Cancel")?></button>
					<button type="button" class="btn btn-default" id="verify_tunning21" name="verify_tunning21" date-done="false"><?=gettext("Confirm")?></button>
				</div>
			</div>
		</div>
	</div>
	<!-- modal submask -->

</form>

<!-- modal modal_inter_fapp -->
<div id="modal_enable_advanced_FAPP" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title"><?=gettext("Habilitar FirewallApp - Avançado")?></h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<form action="./services.php" method="POST" style="border: 0px solid transparent;margin-top:0px;">
				<div class="modal-body">
					<?php

					$selectInterfacesFAPP = [];
					foreach (get_configured_interface_with_descr(true) as $key_interface => $interface_now) {
						if (!in_array(get_real_interface(strtolower($key_interface)), $all_gtw, true)) {
							if (!in_array(get_real_interface(strtolower($key_interface)), $ignore_phisical_interfaces_vlan)) {
								$interface_now_temp = strtolower($key_interface);
								$selectInterfacesFAPP[] = "<option value='{$interface_now_temp}||{$interface_now}'>{$interface_now}</option>";
							}
						}
					}

					if (count($selectInterfacesFAPP) > 0) {

						$showActiveAdvanced = false;
						if (!limit_mult_interfaces_fapp_acp()) {
							$amountInterfacesAvaliable = explode("___", return_option_mult_interfaces_fapp_acp());
							$showActiveAdvanced = true;
							$showHeuristicMode = true;
							if (!isset($config['system']['disablechecksumoffloading']) || !isset($config['system']['disablesegmentationoffloading']) || !isset($config['system']['disablelargereceiveoffloading'])) {
							?>
								<div class="alert alert-warning clearfix">
									<p>Heuristic mode requires that Hardware Checksum, Hardware TCP Segmentation and Hardware Large Receive Offloading all be disabled on the <b>System > Advanced > Networking</b> tab.</p>
								</div>
							<?php
								$showHeuristicMode = false;
							}
							if (intval($amountInterfacesAvaliable[0]) != -1 || intval($amountInterfacesAvaliable[1]) != -1) {
								?>
								<p style="margin-top: 10px;margin-bottom: 10px;">Selecione uma interface:</p>
								<select name="interfaceFAPP" id="interfaceFAPP" class="form-control">
									<?php
									foreach ($selectInterfacesFAPP as $lineInterfaceFAPP) {
										echo $lineInterfaceFAPP;
									}
									?>
								</select>
								<p style="margin-top: 10px;margin-bottom: 10px;">Selecione o modo de operação:</p>
								<select name="ipsMode" id="ipsMode" class="form-control">
									<?php if (intval($amountInterfacesAvaliable[1]) != 0) { ?>
										<option value='ips_mode_legacy'><?=gettext('Performance')?></option>
									<?php } ?>
									<?php if ($showHeuristicMode) { ?>
										<?php if (intval($amountInterfacesAvaliable[0]) != 0) { ?>
											<option value='ips_mode_inline'><?=gettext('Heuristic')?></option>
										<?php } ?>
									<?php } ?>
								</select>
								<p style="color: red;margin-top: 20px;">Aviso: Selecione uma interface para ativar o serviço de FirewallApp, lembrando que essa opção somente está disponível quando não existe nenhum serviço de FirewallApp no equipamento</p>
							<?php } else { ?>
								<p style="color: red;margin-top: 20px;">Aviso: Não há mais interfaces disponíveis ao equipamento para iniciar uma nova instancia.</p>
							<?php } ?>
						<?php } else { ?>
							<p style="color: red;margin-top: 20px;">Aviso: Não há mais interfaces disponíveis ao equipamento para iniciar uma nova instancia.</p>
						<?php } ?>
					<?php } else { ?>
						<p style="color: red;margin-top: 20px;">Aviso: Não há mais interfaces disponíveis ao equipamento para iniciar uma nova instância.</p>
						<p style="color: red;margin-top: 20px;">Todas as interfaces possíveis para ativar o FirewallAPP estão com uma VLAN ativada ou como exceção de interface, favor verificar estes pontos para continuar a ativação do serviço.</p>
					<?php } ?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
					<?php if ($showActiveAdvanced) { ?>
						<button class="btn btn-success" type="submit" onclick="habilitarInterfaceModalAtiva()"><i class="fa fa-check"></i> <?=gettext("Enable")?> </button>
					<?php } ?>
				</div>
			</form>
		</div>
	</div>
</div>
<!-- modal submask -->

<script>
function habilitarInterfaceModalAtiva() {
	$("#modal_enable_advanced_FAPP").modal("hide");
	setTimeout(() => {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Actived FirewallApp, please a moment')?>");
		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/spinner.gif');
		$('#modal_ativa').modal('show');
	}, 500);
}

function helpInformationNotEnableSimple() {
	$("#modal_enable_advanced_FAPP").modal("hide");
	setTimeout(() => {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Attention')?>");
		$('#modal_ativa .txt_modal_ativa').append("<p style='font-size: 18px;margin-top:10px;margin-bottom:10px;'><?=gettext("It is not possible to enable the FirewallApp service in a performance way, the existence of VLAN's and/or interfaces with exceptions was identified in the UTM settings, please enable the advanced option on the side menu to proceed with the activation of the service.")?></p>");
		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/bp-logout.png');
		$('#modal_ativa #loader_modal_ativa').attr('style', 'width:32px; heigth:32px; margin:auto;');
		$('#modal_ativa').modal('show');
	}, 500);
}
</script>

<?php } ?>
<script>
function setarCampo(entrada) {
	document.getElementById("title-regra").innerHTML = "<?=gettext("Rules")?>" + " - " + entrada.split('_').join(' ').split('-').join(' ');
}
</script>
<?php
function getStatusInterfaceFapp($rule_file) {
	global $all_gtw, $config, $suricata_rules_dir, $a_rule;
	$return_interface = "";
	$rule_file_base = "{$suricata_rules_dir}".strtolower($rule_file).".rules";
	$rule_file = array_unique(array_filter(explode("\n", trim(shell_exec("awk -F'sid:' '{print $2}' {$rule_file_base} | awk -F';' '{print $1}'")))));
	foreach ($a_rule as $key => $interface) {
		$if_now = get_real_interface($interface['interface']);
		if (!in_array($if_now,$all_gtw,true)) {
			$no_instance = true;

			if (isset($interface['rule_sid_force_pass']) && !empty($interface['rule_sid_force_pass'])) {
				foreach (explode(":",$interface['rule_sid_force_pass']) as $force_now) {
					if (in_array($force_now, $rule_file)){
						$return_interface .= "<p class='btn btn-warning btn-descr' title='Interface {$interface['descr']}({$interface['interface']}) with ignore rule'>{$interface['descr']}({$interface['interface']})</p>";
						$no_instance = false;
						break;
					}
				}
			}
			if (isset($interface['rule_sid_force_drop']) && !empty($interface['rule_sid_force_drop'])) {
				foreach (explode(":",$interface['rule_sid_force_drop']) as $force_now) {
					if (in_array($force_now, $rule_file)){
						$return_interface .= "<p class='btn btn-danger btn-descr'  title='Interface {$interface['descr']}({$interface['interface']}) with drop rule'>{$interface['descr']}({$interface['interface']})</p>";
						$no_instance = false;
						break;
					}
				}
			}
			if (isset($interface['rule_sid_force_alert']) && !empty($interface['rule_sid_force_alert'])) {
				foreach (explode(":", $interface['rule_sid_force_alert']) as $force_now) {
					if (in_array($force_now, $rule_file)){
						$return_interface .= "<p class='btn btn-success btn-descr' title='Interface {$interface['descr']}({$interface['interface']}) with pass rule'>{$interface['descr']}({$interface['interface']})</p>";
						$no_instance = false;
						break;
					}
				}
			}
			if ($no_instance) {
				$return_interface .= "<p class='btn btn-success btn-descr' title='Interface {$interface['descr']}({$interface['interface']}) with pass rule'>{$interface['descr']}({$interface['interface']})</p>";
			}
			if (isset($config['ezshaper']['step2'])) {
				for($counterInterfaces=0;$counterInterfaces<=count($config['interfaces'])-1;$counterInterfaces++) {
					if (isset($config['ezshaper']['step2']["local{$counterInterfaces}interface"]) && !empty($config['ezshaper']['step2']["local{$counterInterfaces}interface"])) {
						if ($if_now == get_real_interface($config['ezshaper']['step2']["local{$counterInterfaces}interface"])) {
							foreach ($config['ezshaper']['step7'] as $key => $line) {
								if (($line != "D") && $key != "enable") {
									if (intval(trim(shell_exec("grep -rc 'msg:\"{$key}\"' {$rule_file_base}"))) > 0) {
										$return_interface .= "<p class='btn btn-primary btn-descr' title='Interface {$interface['descr']}({$interface['interface']}) with QoS service'>{$interface['descr']}({$interface['interface']})</p>";
										break;
									}
								}
							}
						}
					}
				}
			}
		}
	}


	if (!empty($return_interface)) {
		return "<div style='margin-left:auto;'>" . $return_interface . "</div>";
	} else {
		$return_interface = "";
		foreach ($a_rule as $key => $interface) {
			$if_now = get_real_interface($interface['interface']);
			if (!in_array($if_now,$all_gtw,true)) {
				$return_interface .= "<p class='btn btn-success btn-descr' title='Interface {$interface['descr']}({$interface['interface']}) with pass rule'>{$interface['descr']}({$interface['interface']})</p>";
			}
		}
		return "<div style='margin-left:auto;'>" . $return_interface . "</div>";
	}
}
?>
<div class="outer-container">
	<div id="wizard" class="aiia-wizard" style="display:none;">
		<div class="aiia-wizard-step">
			<h1><?=gettext("Subscription Categories")?></h1>
			<div class="step-content">
				<div class="col-12">
					<div class="row">
						<div class="col-md-6 pl-1"></div>
						<div class="col-md-6 pr-2" style="display:flex!important;margin-left:auto!important;">
							<input type="text" class="form-control find-values field-grap-tables" id="searchRulesAdvancedFapp" name="searchRulesAdvancedFapp" placeholder="<?=gettext("Search for...")?>" onkeydown="searchDataFapp()" onkeyup="searchDataFapp()" >
							<button type="click" class="form-control find-values btn-clear-search close-field" id="closeAdvanceSearchFapp" onclick="closeAdvanceSearchFapp()"><i class="fa fa-times"></i></button>
						</div>
					</div>
				</div>
				<div id="tablelinks">
				<?php
				$files = glob("{$suricata_rules_dir}[A-Za-z0-9]*.rules");
				if (count($files) > 0) {
					foreach ($files as $file) {
						$filename = basename($file, ".rules");
				?>
						<div class="list-group">
							<a href="javascript:void(0);" onclick="setarCampo('<?=$filename?>')" class="list-group-item list-group-item-action" data-category="<?php echo $filename . '.rules';?>"><div class='d-flex'><p style="margin: auto;margin-left:0px;"><?php echo strtoupper($filename);?></p><?=getStatusInterfaceFapp($filename)?></div></a>
						</div>
					<?php } ?>
				<?php } else { ?>
					<div class="list-group text-center">
						<p><?=gettext("No categories found.")?></p>
					</div>
				<?php } ?>
			</div>
		</div>
		<div class="aiia-wizard-step">
			<h1><?=gettext("Subscriptions")?></h1>
			<div class="step-content">
				<div class="panel-body">
					<div class="col-sm-12">
						<div class="panel panel-default" style="margin-top:20px">
						<div class="panel-heading"><h2 class="panel-title" id="title-regra"><?=gettext("Rules")?> <?php echo strtoupper(substr($files[$j], 0, strlen($files[$j]) - 6));?></h2></div>
							<div class="panel-body">
								<div class="col-12">
									<div class="row">
										<div class="col-md-6 pl-1">
										</div>
										<div class="col-md-6 pr-2" style="display:flex!important;margin-left:auto!important;">
											<input type="text" class="form-control find-values field-grap-tables" id="searchRulesAdvanced" name="searchRulesAdvanced" placeholder="<?=gettext("Search for...")?>" onkeydown="searchData()" onkeyup="searchData()" >
											<button type="click" class="form-control find-values btn-clear-search close-field" id="closeAdvanceSearch" onclick="closeAdvanceSearch()"><i class="fa fa-times"></i></button>
										</div>
									</div>
								</div>
								<div class="table-responsive">
									<table class="table table-bordered" id="tablefapp">
										<thead>
											<tr>
												<th style="vertical-align: inherit !important;"><i class="fa fa-navicon"></i> <?=gettext("Websites/Applications/Services")?></th>
												<th><?=gettext("Actions")?>:<br>
													
													<select name="interfaces_select" class="form-control" style="margin-top:5px; width: 90%; border-radius: 15px; text-align: center; margin: auto; margin-top: 10px; margin-bottom: 10px">
														<option value="100"><?=gettext("ALL")?></option>
														<?php
														$estado_interface = [];
														foreach ($a_rule as $key => $interface) {
															$if_r = get_real_interface($interface['interface']);
															if (!in_array($if_r, $all_gtw,true)) {
														?>
																<option value="<?=$key?>"><?=strtoupper($interface['descr'])?></option>
														<?php 
															}
														}
														?>
													</select>

													<button name="ignore_all" class="btn btn-warning no-confirm"><i class="fa fa-info"></i><?=gettext("Ignore")?></button>
													<button name="pass_all" class="btn btn-success no-confirm"><i class="fa fa-check"></i><?=gettext("Pass")?></button>
													<button name="block_all" class="btn btn-danger no-confirm"><i class="fa fa-times"></i><?=gettext("Block")?></button>
													<button name="save_all" class="btn btn-secondary no-confirm"><i class="fa fa-check"></i><?=gettext("Save")?></button></th>
											</tr>
										</thead>
										<tbody id="rules-list">
										</tbody>
									</table>
									<table class="table table-bordered">
										<thead>
											<tr>
												<th style="text-align: right !important; padding-right: 5% !important; display: flex !important;">
													<select name="interfaces_select" class="form-control" style="margin-top:5px; width: 50%; border-radius: 15px; text-align: center; margin: auto; margin-top: 10px; margin-bottom: 10px">
														<option value="100"><?=gettext("ALL")?></option>
														<?php
														$estado_interface = [];
														foreach ($a_rule as $key => $interface) {
															$if_r = get_real_interface($interface['interface']);
															if (!in_array($if_r, $all_gtw,true)) {
														?>
																<option value="<?=$key?>"><?=strtoupper($interface['descr'])?></option>
														<?php 
															}
														}
														?>
													</select>

													<button name="ignore_all" class="btn btn-warning no-confirm" style="margin: 5px"><i class="fa fa-info"></i><?=gettext("Ignore")?></button>
													<button name="pass_all" class="btn btn-success no-confirm" style="margin: 5px"><i class="fa fa-check"></i><?=gettext("Pass")?></button>
													<button name="block_all" class="btn btn-danger no-confirm" style="margin: 5px"><i class="fa fa-times"></i><?=gettext("Block")?></button>
													<button name="save_all" class="btn btn-secondary no-confirm" style="margin: 5px"><i class="fa fa-check"></i><?=gettext("Save")?></button>
												</th>
											</tr>
										</thead>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- modal settings -->
		<div id="modal_avancado" class="modal fade" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title"><?=gettext("Advanced Settings")?></h4>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<div class="form-group">
							<small>
								<p><?=gettext("Delete Single IP")?> = <b>!1.1.1.1</b></p>
								<p><?=gettext("Delete Multiple IPs")?> = <b>![1.1.1.1, 1.1.1.2]</b></p>
								<p><?=gettext("Add Single IP")?> = <b>1.1.1.1</b></p>
								<p><?=gettext("Add Multiple IPs")?> = <b>[1.1.1.1, 1.1.1.2]</b></p>
							</small>
							<hr>
							<label><?=gettext("Select the Interface")?></label>
							<select id="firewallapp_interface" class="form-control" style="margin-top:5px; width:70%;">
								<option value="100"><?=gettext("ALL")?></option>
								<?php
								$estado_interface = [];
								foreach ($a_rule as $key => $interface) {
									$if_r = get_real_interface($interface['interface']);
									if (!in_array($if_r, $all_gtw,true)) {
								?>
										<option value="<?=$key?>"><?=strtoupper($interface['descr'])?></option>
								<?php 
										$estado_interface[$key] = $interface['ips_mode'];
									}
								}
								?>
							</select>

							<label><?=gettext("Source IP | Port Origin | Direction")?></label>
							<div class="form-inline" id="div-sumir">
								<input type="text" id="ip_source" name="ip_source" class="form-control" value="" style="width:70%" placeholder=".<?=gettext("Source IP")?>.">
								<input type="text" id="port_source" name="port_source" class="form-control ml-2" value="" style="width:11%" placeholder=".<?=gettext("Port")?>.">
								<select id="direction" name="direction" class="form-control mx-2">
									<option value="->">-></option>
									<option value="<>"><></option>
									<option value="*">*</option>
								</select>
								<input type="text" id="ip_destination" name="ip_destination" class="form-control" value="" style="width:30%;display:none;" placeholder=".<?=gettext("Destination IP")?>.">
								<input type="text" id="port_destination" name="port_destination" class="form-control mx-2" value="" style="width:11%;display:none;" placeholder=".<?=gettext("Port")?>.">
							</div>
							<div class="form-inline">
								<small>
									<hr>
									<p><b><?=gettext("Note:")?></b></p>
									<p><ul><li><b><?=gettext("It is only possible to change the advanced options if you enable the extended performance mode or if you are running the interface in heuristic mode.")?></b></li></ul></p>
									<p><ul><li><b><?=gettext("Be aware that if you operate multiple interfaces in different modes, you will need to select the target interface before saving.")?></b></li></ul></p>
								</small>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal"><?=gettext("Close")?></button>
						<?php
						?>
							<button type="button" class="btn btn-primary" id="btn-save-ip"><?=gettext("Save")?></button>
							<input type="hidden" id="has_group" name="has_group" value="">
						<?php
						?>
					</div>
				</div>
			</div>
		</div>
		<!-- modal settings -->
	</div>
</div>
<br>

<!-- Modal -->
<div class="modal fade" id="modal_update_rules_fapp" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_update_rules" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_update_rules" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<!-- Modal Ativa -->
<div class="modal fade" id="modal_ativa" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_ativa" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_ativa" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<!-- Modal Enable -->
<div class="modal fade" id="modal_enable" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_enable" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_enable" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<!-- Modal Disable -->
<div class="modal fade" id="modal_disable" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_disable" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_disable" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>
</div>
</div>

<form action="./services.php" method="POST" id="formReloadInterfacesACP" name="formReloadInterfacesACP" style="display:none;">
	<input type="hidden" value="true" id="reloadInterfacesACP" name="reloadInterfacesACP">
</form>

<?php include("foot.inc"); ?>

<script language="javascript" type="text/javascript">

$("#force_reload_rules_fapp").click(function(event){
  	event.preventDefault();
	setTimeout(() => {
		$('#modal_force_update11').modal('hide');
	}, 100);
	setTimeout(() => {
		$('#modal_enable .txt_modal_enable').text($('#force_update_rules').text() + " <?=gettext("Reload rules is FirewallApp")?>");
		$('#modal_enable').modal('show');
	}, 150);
	setTimeout(() => {
		$("#formReloadInterfacesACP").submit();
	}, 200);
	setTimeout(function() {$('#modal_enable').modal('hide')}, 5000);
});

function showDisplayNoneUpdate11() {
	document.getElementById('force_update_rules').style.display = 'none'
	setTimeout(() => {
		$('#modal_force_update11').modal('hide');	
	}, 100);
	setTimeout(() => {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Updating FirewallApp subscriptions, please wait until the end of the operation')?>");
		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/spinner.gif');
		$('#modal_ativa').modal('show');
	}, 200);
}

function clear_ip_fields() {
	$("#ip_source").val("");
	$("#port_source").val("");
	$("#direction").val("");
	$("#ip_destination").val("");
	$("#port_destination").val("");
}

$("#rules-list").on('click', '.btn-avancado', function() {
	$("#modal_avancado").modal();
	$("#currentfile").val($(this).attr("data-current-file"));
	$("#state").val($(this).attr("data-current-state"));
	$("#sid").val($(this).attr("data-sid"));
	$("#gid").val($(this).attr("data-gid"));
	$("#has_group").val($(this).attr("data-has-group"));

	clear_ip_fields();

	$("#ip_source").val($(this).attr("data-ip-source"));
	$("#port_source").val($(this).attr("data-port-source"));
	$("#direction").val($(this).attr("data-direction"));
	$("#ip_destination").val($(this).attr("data-ip-destination"));
	$("#port_destination").val($(this).attr("data-port-destination"));
});

function clear_ip_fields_qos() {
	$("#qos_download").val("");
	$("#qos_upload").val("");
}

ips_mode_interfaces = <?=json_encode($estado_interface)?>;

$('#firewallapp_interface').change(function() {

	if ($('#firewallapp_interface').val() == 100) {
		$("#ip_source").attr("disabled",true);
		$("#port_source").attr("disabled",true);
		$("#direction").attr("disabled",true);
		$("#btn-save-ip").attr("disabled",false);
	} else {
		if (ips_mode_interfaces[$('#firewallapp_interface').val()] == "ips_mode_legacy") {
			$("#ip_source").attr("disabled",true);
			$("#port_source").attr("disabled",true);
			$("#direction").attr("disabled",true);
			$("#btn-save-ip").attr("disabled",false);
		} else {
			$("#ip_source").attr("disabled",false);
			$("#port_source").attr("disabled",false);
			$("#direction").attr("disabled",false);
			$("#btn-save-ip").attr("disabled",false);
		}
	}
});

$("#ip_source").attr("disabled",true);
$("#port_source").attr("disabled",true);
$("#direction").attr("disabled",true);
$("#btn-save-ip").attr("disabled",false);

$('#btn-save-ip').click(function() {

	$('#modal_avancado').modal('hide');
	
	setTimeout(() => {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Applying rules in FirewallApp!")?>");
		$('#modal_ativa .txt_modal_ativa').append("<p id='comentario' style='font-size: 18px;margin-top:10px;margin-bottom:10px;'><?=gettext("This operation may take some time, thanks for your patience.")?></p>");
		$('#modal_ativa #loader_modal_ativa').attr('style', 'width:100px;height:auto');
		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/spinner.gif');
		$('#modal_ativa').modal('show');
	}, 100);
	
	setTimeout(() => {

		var url_ajax = "./ajax_save_rule.php";


		if ($('#has_group').val() == 'true') {
			url_ajax = "./ajax_save_group_rule.php";
		}

		if ($('#firewallapp_interface').val() == "100") {

			$('#firewallapp_interface').find('option').each(function() {

			var valor = $(this).val();
			var text  = $(this).text();
			if (valor != 100) {

				$("#firewallapp_interface").val(valor);

				alert('<?=gettext("Interface")?> ' + text + ' <?=gettext(".Successfully saved!")?>');

				$.ajax({
					data: {
						gid: [$('#gid').val()],
						sid: [$('#sid').val()],
						state: [$('#state').val()],
						currentfile: [$('#currentfile').val()],
						ip_source: ['any'],
						port_source: ['any'],
						direction: ['->'],
						ip_destination: [$('#ip_destination').val()],
						port_destination: [$('#port_destination').val()],
						interface: [$('#firewallapp_interface').val()],
					},
					method: "post",
					url: url_ajax,
					async: false,
				}).done(function() {
					if ($('#has_group').val() == 'true') {
						$("#rules-list .btn-avancado[data-gid='" + $('#gid').val() + "']").each(function() {
							var btn = $(this);
							$(btn).attr("data-ip-source", $('#ip_source').val());
							$(btn).attr("data-port-source", $('#port_source').val());
							$(btn).attr("data-direction", $('#direction').val());
							$(btn).attr("data-ip-destination", $('#ip_destination').val());
							$(btn).attr("data-port-destination", $('#port_destination').val());	
						});
					} else {
						var btn = $(".btn-avancado[data-sid='" + $('#sid').val() + "']");
						$(btn).attr("data-ip-source", $('#ip_source').val());
						$(btn).attr("data-port-source", $('#port_source').val());
						$(btn).attr("data-direction", $('#direction').val());
						$(btn).attr("data-ip-destination", $('#ip_destination').val());
						$(btn).attr("data-port-destination", $('#port_destination').val());
					}

					$('#modal_avancado').modal('hide');

				});

			}

			});

		} else {

			$.ajax({
				data: {
					gid: [$('#gid').val()],
					sid: [$('#sid').val()],
					state: [$('#state').val()],
					currentfile: [$('#currentfile').val()],
					ip_source: [$('#ip_source').val()],
					port_source: [$('#port_source').val()],
					direction: [$('#direction').val()],
					ip_destination: [$('#ip_destination').val()],
					port_destination: [$('#port_destination').val()],
					interface: [$('#firewallapp_interface').val()],
				},
				method: "post",
				url: url_ajax,
				async: false,
			}).done(function() {
				if ($('#has_group').val() == 'true') {
					$("#rules-list .btn-avancado[data-gid='" + $('#gid').val() + "']").each(function() {
						var btn = $(this);
						$(btn).attr("data-ip-source", $('#ip_source').val());
						$(btn).attr("data-port-source", $('#port_source').val());
						$(btn).attr("data-direction", $('#direction').val());
						$(btn).attr("data-ip-destination", $('#ip_destination').val());
						$(btn).attr("data-port-destination", $('#port_destination').val());	
					});
				} else {
					var btn = $(".btn-avancado[data-sid='" + $('#sid').val() + "']");
					$(btn).attr("data-ip-source", $('#ip_source').val());
					$(btn).attr("data-port-source", $('#port_source').val());
					$(btn).attr("data-direction", $('#direction').val());
					$(btn).attr("data-ip-destination", $('#ip_destination').val());
					$(btn).attr("data-port-destination", $('#port_destination').val());
				}

				alert('<?=gettext("Advanced Settings saved successfully!")?>');
			});

		};

		$('#modal_ativa').modal('hide');

		//Modal error
		$.ajax({
			data: {
				Error_Find: true,
				interface: $('#firewallapp_interface').val(),
			},
			method: "post",
			url: url_ajax,		
			async: false,
		}).done(function(data) {
			if (parseInt(data) > 0 && data != "") {
				$('#modal_ativa').modal('show');
				$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Error about rules currently applied or already applied!")?>");
				$('#modal_ativa .txt_modal_ativa').append("<p id='comentario' style='font-size: 18px;margin-top:10px;margin-bottom:10px;'><?=gettext("Please check or restore the modified rules, preferably at the first appearance of this message, if it persists with the current configuration, it will be displayed continuously until its solution, if the message persists even after the correction or restoration of the rules, enter contact technical support.")?></p>");
				$('#modal_ativa #loader_modal_ativa').attr('style', 'width:100px;height:auto');
				$('#modal_ativa #loader_modal_ativa').attr('src', '../images/bp-logout.png');
			}
		});

	}, 500);

});



function toggleState(sid, gid, btn) {
	$('#gid').val(gid);
	$('#sid').val(sid);

	var action = $(btn).val();
	var parent_element = $(btn).parent();
	var btn_advanced = parent_element.find(".btn-avancado");
	var btn_pass = parent_element.find("button[value='pass']");
	var btn_alert = parent_element.find("button[value='alert']");
	var btn_block = parent_element.find("button[value='drop']");

	$('#state').val(action);
	$(parent_element).find(".btn-avancado").attr('data-current-state', action);
	$('#currentfile').val($(btn).attr('data-current-file'));

	if ((action != "!alert") || (action != "!drop") || (action != "!pass")) {
		if (action == "alert") {
			btn_pass.addClass('btn-disabled');
			btn_block.addClass('btn-disabled');
			if (btn_alert.hasClass('btn-disabled')) {
				btn_alert.removeClass("btn-disabled");
			}

			$('#ip_source').val($(btn_advanced).attr("data-ip-source"));
			$('#port_source').val($(btn_advanced).attr("data-port-source"));
			$('#direction').val($(btn_advanced).attr("data-direction"));
			$('#ip_destination').val($(btn_advanced).attr("data-ip-destination"));
			$('#port_destination').val($(btn_advanced).attr("data-port-destination"));
		} else if (action == "drop") {
			btn_alert.addClass('btn-disabled');
			btn_pass.addClass('btn-disabled');
			if (btn_block.hasClass('btn-disabled')) {
				btn_block.removeClass("btn-disabled");
			}

			$('#ip_source').val($(btn_advanced).attr("data-ip-source"));
			$('#port_source').val($(btn_advanced).attr("data-port-source"));
			$('#direction').val($(btn_advanced).attr("data-direction"));
			$('#ip_destination').val($(btn_advanced).attr("data-ip-destination"));
			$('#port_destination').val($(btn_advanced).attr("data-port-destination"));
		} else if (action == "pass") {
			btn_alert.addClass('btn-disabled');
			btn_block.addClass('btn-disabled');
			if (btn_pass.hasClass('btn-disabled')) {
				btn_pass.removeClass("btn-disabled");
			}

			$('#ip_source').val($(btn_advanced).attr("data-ip-source"));
			$('#port_source').val($(btn_advanced).attr("data-port-source"));
			$('#direction').val($(btn_advanced).attr("data-direction"));
			$('#ip_destination').val($(btn_advanced).attr("data-ip-destination"));
			$('#port_destination').val($(btn_advanced).attr("data-port-destination"));
		}

		$('#firewallapp_interface').find('option').each(function() {
			var valor = $(this).val();
			var text  = $(this).text();
			if (valor != 100) {
				$("#firewallapp_interface").val(valor);
			}
		});
	}
}

function toggleStateGroup(gid, btn) {
	$('#gid').val(gid);

	var url_ajax = '';
	var action = $(btn).val();
	var parent_element = $(btn).parent();
	var btn_advanced = parent_element.find("button[value='advanced']");
	var btn_pass = parent_element.find("button[value='pass']");
	var btn_alert = parent_element.find("button[value='alert']");
	var btn_block = parent_element.find("button[value='drop']");

	$('#state').val(action);
	$(parent_element).find(".btn-avancado").attr('data-current-state', action);
	$('#currentfile').val($(btn).attr('data-current-file'));

	if ((action != "!alert") || (action != "!drop") || (action != "!pass")) {
		// Toggle (enable/disable) the state of button
		if (action == "advanced") {
			obj = $(parent_element).closest('tbody');

			$(obj).find('tr td .btn').each(function() {
				$(this).addClass('btn-disabled');
			});

			$(obj).find('tr td .btn-avancado').each(function() {
				$(this).removeClass('btn-disabled');
			});
		} if (action == "alert") {
			obj = $(parent_element);
			$(obj).find('.btn').not('.btn-avancado').each(function() {
				$(this).addClass('btn-disabled');
			});
			$(btn).removeClass('btn-disabled');
			
			$('#ip_source').val($(btn_advanced).attr("data-ip-source"));
			$('#port_source').val($(btn_advanced).attr("data-port-source"));
			$('#direction').val($(btn_advanced).attr("data-direction"));
			$('#ip_destination').val($(btn_advanced).attr("data-ip-destination"));
			$('#port_destination').val($(btn_advanced).attr("data-port-destination"));
		} else if (action == "drop") {
			obj = $(parent_element);
			$(obj).find('.btn').not('.btn-avancado').each(function() {
				$(this).addClass('btn-disabled');
			});
			$(btn).removeClass('btn-disabled');

			$(obj).find('tr td .btn').not('.btn-avancado').each(function() {
				$(this).addClass('btn-disabled');
			});

			$(obj).find('tr td .btn-danger').each(function() {
				$(this).removeClass('btn-disabled');
			});

			$('#ip_source').val($(btn_advanced).attr("data-ip-source"));
			$('#port_source').val($(btn_advanced).attr("data-port-source"));
			$('#direction').val($(btn_advanced).attr("data-direction"));
			$('#ip_destination').val($(btn_advanced).attr("data-ip-destination"));
			$('#port_destination').val($(btn_advanced).attr("data-port-destination"));
		} else if (action == "pass") {
			obj = $(parent_element);
			$(obj).find('.btn').not('.btn-avancado').each(function() {
				$(this).addClass('btn-disabled');
			});
			$(btn).removeClass('btn-disabled');

			$(obj).find('tr td .btn').not('.btn-avancado').each(function() {
				$(this).addClass('btn-disabled');
			});

			$(obj).find('tr td .btn-warning').each(function() {
				$(this).removeClass('btn-disabled');
			});

			$('#ip_source').val($(btn_advanced).attr("data-ip-source"));
			$('#port_source').val($(btn_advanced).attr("data-port-source"));
			$('#direction').val($(btn_advanced).attr("data-direction"));
			$('#ip_destination').val($(btn_advanced).attr("data-ip-destination"));
			$('#port_destination').val($(btn_advanced).attr("data-port-destination"));
		}

		$('#firewallapp_interface').find('option').each(function() {
			var valor = $(this).val();
			var text  = $(this).text();
			if (valor != 100) {
				$("#firewallapp_interface").val(valor);
			}
		});
	}
}

//Change all status buttons 
function click_all_button_type(class_element) {
	type_buttons = ["btn-success", "btn-warning", "btn-danger"];
	for(count_buttons = 0; count_buttons <= (type_buttons.length-1); count_buttons++) {
		elements_buttons = document.getElementById("rules-list").getElementsByClassName(type_buttons[count_buttons]);
		btn_avancado = document.getElementById("rules-list").getElementsByClassName("btn btn-default-custom btn-avancado")
		for(count = 0;count <= (elements_buttons.length-1);count++) {
			if (class_element == type_buttons[count_buttons]) {
				elements_buttons[count].click();
			}
		}
	}
}

$("button[name='ignore_all']").on("click", function () {
	click_all_button_type("btn-warning");
});

$("button[name='pass_all']").on("click", function () {
	click_all_button_type("btn-success");
});

$("button[name='block_all']").on("click", function () {
	click_all_button_type("btn-danger");
});

$('select[name=interfaces_select]').change(function() {
	$('select[name=interfaces_select]').val($(this).val());
	var item = $('#current_file').val();

	$.ajax({
		data: {
			category: item,
			interface: $(this).val()
		},
		method: 'post',
		url: "./ajax_categorie_rules.php",
		dataType: 'html',
	}).done(function(data) {
		$('#interfaces_select').show();
		$('#rules-list').empty();
		$('#rules-list').delay(5000).append(data);
	});
});


$("button[name='save_all']").on("click", function(){

	$('#modal_ativa').modal('show').delay(0);
	$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Applying rules in FirewallApp!")?>");
	$('#modal_ativa .txt_modal_ativa').append("<p id='comentario' style='font-size: 18px;margin-top:10px;margin-bottom:10px;'><?=gettext("This operation may take some time, thanks for your patience.")?></p>");
	$('#modal_ativa #loader_modal_ativa').attr('style', 'width:100px;height:auto');
	$('#modal_ativa #loader_modal_ativa').attr('src', '../images/spinner.gif');

	var interface_excessao = $('select[name=interfaces_select]').val();

	setTimeout(function() {
		$('#firewallapp_interface').find('option').each(function() {

			var valor = $(this).val();
			var text  = $(this).text();

			if ((valor != 100) && (interface_excessao == 100)) {

				$("#firewallapp_interface").val(valor);

				var todos_btn_avancado = document.getElementsByClassName("btn-avancado");

				var gid = [];
				var sid = [];
				var state = [];
				var currentfile = [];
				var ip_source = [];
				var port_source = [];
				var direction = [];
				var ip_destination = [];
				var port_destination = [];
				var interfaces = [];
				var url_ajax = "./ajax_save_rule.php";

				// Put this in ajax to make the operation synchronous -> async: false,
				// This means that the page cannot be reloaded during the process and guarantees Ajax integration

				for(var i=0;i<=(todos_btn_avancado.length-1);i++) {
					var gid_atual = todos_btn_avancado[i].dataset['gid'];
					if ((gid_atual == 1)) {
						gid.push(todos_btn_avancado[i].dataset['gid']);
						sid.push(todos_btn_avancado[i].dataset['sid']);
						state.push(todos_btn_avancado[i].dataset['currentState']);
						currentfile.push(todos_btn_avancado[i].dataset['currentFile']);
						ip_source.push(todos_btn_avancado[i].dataset['ipSource']);
						port_source.push(todos_btn_avancado[i].dataset['portSource']);
						direction.push(todos_btn_avancado[i].dataset['direction']);
						ip_destination.push(todos_btn_avancado[i].dataset['ipDestination']);
						port_destination.push(todos_btn_avancado[i].dataset['portDestination']);
						interfaces.push($(this).val());
					}
				}
				$.ajax({
						data: {
							gid: gid,
							sid: sid,
							state: state,
							currentfile: currentfile,
							ip_source: ip_source,
							port_source: port_source,
							direction: direction,
							ip_destination: ip_destination,
							port_destination: port_destination,
							interface: interfaces,
						},
						method: "post",
						url: url_ajax,
						async: false,
				});

				todos_btn_avancado = document.getElementsByClassName("btn-avancado");

				gid = [];
				sid = [];
				state = [];
				currentfile = [];
				ip_source = [];
				port_source = [];
				direction = [];
				ip_destination = [];
				port_destination = [];
				interfaces = [];
				url_ajax = "./ajax_save_group_rule.php";

				for(var i=0;i<=(todos_btn_avancado.length-1);i++) {
					var gid_atual = todos_btn_avancado[i].dataset['gid'];
					if ((gid_atual != 1)) {
						gid.push(todos_btn_avancado[i].dataset['gid']);
						sid.push(todos_btn_avancado[i].dataset['sid']);
						state.push(todos_btn_avancado[i].dataset['currentState']);
						currentfile.push(todos_btn_avancado[i].dataset['currentFile']);
						ip_source.push(todos_btn_avancado[i].dataset['ipSource']);
						port_source.push(todos_btn_avancado[i].dataset['portSource']);
						direction.push(todos_btn_avancado[i].dataset['direction']);
						ip_destination.push(todos_btn_avancado[i].dataset['ipDestination']);
						port_destination.push(todos_btn_avancado[i].dataset['portDestination']);
						interfaces.push($(this).val());
					}
				}
				$.ajax({
					data: {
						gid: gid,
						sid: sid,
						state: state,
						currentfile: currentfile,
						ip_source: ip_source,
						port_source: port_source,
						direction: direction,
						ip_destination: ip_destination,
						port_destination: port_destination,
						interface: interfaces,
					},
					method: "post",
					url: url_ajax,
					async: false,
				});
			}

			if ((valor != 100) && (interface_excessao != 100)) {
				if (valor == interface_excessao) {

					$("#firewallapp_interface").val(valor);

					var todos_btn_avancado = document.getElementsByClassName("btn-avancado");

					var gid = [];
					var sid = [];
					var state = [];
					var currentfile = [];
					var ip_source = [];
					var port_source = [];
					var direction = [];
					var ip_destination = [];
					var port_destination = [];
					var interfaces = [];
					var url_ajax = "./ajax_save_rule.php";

					// Put this in ajax to make the operation synchronous -> async: false,
					// This means that the page cannot be reloaded during the process and guarantees Ajax integration

					for(var i=0;i<=(todos_btn_avancado.length-1);i++) {
						var gid_atual = todos_btn_avancado[i].dataset['gid'];
						if ((gid_atual == 1)) {
							gid.push(todos_btn_avancado[i].dataset['gid']);
							sid.push(todos_btn_avancado[i].dataset['sid']);
							state.push(todos_btn_avancado[i].dataset['currentState']);
							currentfile.push(todos_btn_avancado[i].dataset['currentFile']);
							ip_source.push(todos_btn_avancado[i].dataset['ipSource']);
							port_source.push(todos_btn_avancado[i].dataset['portSource']);
							direction.push(todos_btn_avancado[i].dataset['direction']);
							ip_destination.push(todos_btn_avancado[i].dataset['ipDestination']);
							port_destination.push(todos_btn_avancado[i].dataset['portDestination']);
							interfaces.push($(this).val());
						}
					}
					$.ajax({
						data: {
							gid: gid,
							sid: sid,
							state: state,
							currentfile: currentfile,
							ip_source: ip_source,
							port_source: port_source,
							direction: direction,
							ip_destination: ip_destination,
							port_destination: port_destination,
							interface: interfaces,
						},
						method: "post",
						url: url_ajax,
						async: false,
					});

					todos_btn_avancado = document.getElementsByClassName("btn-avancado");

					gid = [];
					sid = [];
					state = [];
					currentfile = [];
					ip_source = [];
					port_source = [];
					direction = [];
					ip_destination = [];
					port_destination = [];
					interfaces = [];
					url_ajax = "./ajax_save_group_rule.php";

					for(var i=0;i<=(todos_btn_avancado.length-1);i++) {
						var gid_atual = todos_btn_avancado[i].dataset['gid'];
						if ((gid_atual != 1)) {
							gid.push(todos_btn_avancado[i].dataset['gid']);
							sid.push(todos_btn_avancado[i].dataset['sid']);
							state.push(todos_btn_avancado[i].dataset['currentState']);
							currentfile.push(todos_btn_avancado[i].dataset['currentFile']);
							ip_source.push(todos_btn_avancado[i].dataset['ipSource']);
							port_source.push(todos_btn_avancado[i].dataset['portSource']);
							direction.push(todos_btn_avancado[i].dataset['direction']);
							ip_destination.push(todos_btn_avancado[i].dataset['ipDestination']);
							port_destination.push(todos_btn_avancado[i].dataset['portDestination']);
							interfaces.push($(this).val());
						}
					}
					$.ajax({
						data: {
							gid: gid,
							sid: sid,
							state: state,
							currentfile: currentfile,
							ip_source: ip_source,
							port_source: port_source,
							direction: direction,
							ip_destination: ip_destination,
							port_destination: port_destination,
							interface: interfaces,
						},
						method: "post",
						url: url_ajax,
						async: false,
					});
				}
			}

		});

		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/update_rules_ok.png');
		$('#modal_ativa .txt_modal_ativa').remove('#comentario');
		setTimeout(function() {$('#modal_ativa').modal('hide');}, 1000);

	}, 1000);

});


$('.panel-heading').click(function() {
	$(this).closest('.panel-default').find('.panel-body').toggle();
});

$('#firewallapp_interface').change(function() {
	if ($('firewallapp_interface').val() == "100") {
			return;
	}

	var item = $('#current_file').val();

	$.ajax({
		data: {
			interface: $('#firewallapp_interface').val(), 
			state: $('#state').val(),
			sid: $('#sid').val() 
		},
		method: "POST",
		url: "./ajax_get_interface_setting.php",
		dataType: "json"
	}).done(function(data) {
		$('#ip_source').val(data.ip_source);
		$('#port_source').val(data.port_source);
		$('#direction').val(data.direction);
	});
});

function mOver(obj){

	var status_iface = <?php echo $get_status_new_fapp; ?>;
	var status_iface2 = <?php echo $get_interface_new_fapp; ?>;
	var status_iface3 = <?php echo $get_interface_suricata_fapp; ?>;

	if (((status_iface == 0) && (status_iface2 > 0) && (status_iface3 > 0)) || 
		((status_iface == 0) && (status_iface2 > 0) && (status_iface3 == 0))) {
		$('#disable').removeClass('active');
		$('#enable').addClass('active');
		$('#status-info').text('<?=gettext("Stopped")?>');
		$('#status-info').show();
		$('#status-info').removeClass('status-stopped');
		$('#status-info').addClass('status-running');
		$('#status-bar-fapp').addClass('bg-disabled3');
		$('#status-button-fapp').addClass('btn-disabled3');
		$('#status-button-fapp2').addClass('btn-disabled3');
		$('#buton_status').addClass('fa fa-ban');
		$('#click_ativa').hide();
	} else if ((status_iface == 0) && (status_iface2 == 0) && (status_iface3 == 0)) {
		$('#enable').removeClass('active');
		$('#disable').addClass('active');
		$('#status-info').text('<?=gettext("Disabled")?>');
		$('#status-info').show();
		$('#status-info').removeClass('status-running');
		$('#status-info').addClass('status-stopped');
		$('#status-bar-fapp').addClass('bg-warning3');
		$('#status-button-fapp').addClass('btn-danger');
		$('#buton_status').addClass('fa fa-ban');
	} else if ((status_iface == 0) && (status_iface2 == 0) && (status_iface3 > 0)) {
		$('#enable').removeClass('active');
		$('#disable').addClass('active');
		$('#status-info').text('<?=gettext("Starting")?>');
		$('#status-info').show();
		$('#status-info').removeClass('status-stopped');
		$('#status-info').addClass('status-running');
		$('#status-bar-fapp').addClass('bg-disabled3');
		$('#status-button-fapp').addClass('btn-disabled3');
		$('#status-button-fapp2').addClass('btn-disabled3');
		$('#buton_status').addClass('fa fa-ban');
		$('#click_ativa').hide();
	} else if ((status_iface >= 1) && (status_iface2 >= 1) && (status_iface3 >= 1)) {
		$('#disable').removeClass('active');
		$('#enable').addClass('active');
		$('#status-info').text('<?=gettext("Running")?>');
		$('#status-info').show();
		$('#status-info').addClass('status-running');
		$('#status-bar-fapp').removeClass('bg-disabled3');
		$('#status-button-fapp').removeClass('btn-disabled3');
		$('#status-button-fapp2').removeClass('btn-disabled3');
		$('#status-bar-fapp').addClass('bg-success2');
		$('#status-button-fapp').addClass('btn-success');
		$('#status-button-fapp2').addClass('btn-success');
		$('#buton_status').removeClass('fa fa-ban');
		$('#buton_status').addClass('fa fa-check');
		$('#click_ativa').hide();
	}
}



<?php if ($act == "") { ?>
function check_status() {
	// This function uses Ajax to post a query to
	// this page requesting the status of each
	// configured interface.  The result is returned
	// as a JSON array object.  A timer is set upon
	// completion to call the function again in
	// 2 seconds.  This allows dynamic updating
	// of interface status in the GUI.
	$.ajax(
		"<?=$_SERVER['SCRIPT_NAME'];?>",
		{
			type: 'post',
			data: {
				status: 'check'
			},
			success: showStatus,
			complete: function() {
				setTimeout(check_status, 2500);
			}
		}
	);
	showStatus();
}

function hide(){
	$("#status-button-fapp2").hide();
}

function showStatus(responseData) {
	// The JSON object returned by check_status() is an associative array
	// of interface unique IDs and corresponding service status.  The
	// "key" is the service name followed by the physical interface and a UUID.
	// The "value" of the key is either "DISABLED, STOPPED, STARTING, or RUNNING".
	//
	// Example keys:  suricata_em1998 or barnyard2_em1998
	//
	// Within the HTML of this page, icon controls for displaying status
	// and for starting/restarting/stopping the service are tagged with
	// control IDs using "key" followed by the icon's function.  These
	// control IDs are used in the code below to alter icon appearance
	// depending on the service status.

	var status_iface = <?php echo $get_status_new_fapp;?>;
	var status_iface2 = <?php echo $get_interface_new_fapp;?>;
	var status_iface3 = <?php echo $get_interface_suricata_fapp?>;

	if ((status_iface == 0) && (status_iface2 > 0) && (status_iface3 > 0)) {
		$('#disable').removeClass('active');
		$('#enable').addClass('active');
		$('#status-info').text('<?=gettext("Stopped")?>');
		$('#status-info').show();
		$('#status-info').removeClass('status-stopped');
		$('#status-info').addClass('status-running');
		$('#status-bar-fapp').addClass('bg-disabled3');
		$('#status-button-fapp').addClass('btn-disabled3');
		$('#status-button-fapp2').addClass('btn-disabled3');
		$('#buton_status').addClass('fa fa-ban');
		$('#click_ativa').hide();
	} else if ((status_iface == 0) && (status_iface2 == 0) && (status_iface3 == 0)) {
		$('#enable').removeClass('active');
		$('#disable').addClass('active');
		$('#status-info').text('<?=gettext("Disabled")?>');
		$('#status-info').show();
		$('#status-info').removeClass('status-running');
		$('#status-info').addClass('status-stopped');
		$('#status-bar-fapp').addClass('bg-warning3');
		$('#status-button-fapp').addClass('btn-danger');
		$('#buton_status').addClass('fa fa-ban');
	} else if ((status_iface == 0) && (status_iface2 == 0) && (status_iface3 > 0)) {
		$('#enable').removeClass('active');
		$('#disable').addClass('active');
		$('#status-info').text('<?=gettext("Starting")?>');
		$('#status-info').show();
		$('#status-info').removeClass('status-stopped');
		$('#status-info').addClass('status-running');
		$('#status-bar-fapp').addClass('bg-disabled3');
		$('#status-button-fapp').addClass('btn-disabled3');
		$('#status-button-fapp2').addClass('btn-disabled3');
		$('#buton_status').addClass('fa fa-ban');
		$('#click_ativa').hide();
	} else if ((status_iface >= 1) && (status_iface2 >= 1) && (status_iface3 >= 1)) {
		$('#disable').removeClass('active');
		$('#enable').addClass('active');
		$('#status-info').text('<?=gettext("Running")?>');
		$('#status-info').show();
		$('#status-info').addClass('status-running');
		$('#status-bar-fapp').removeClass('bg-disabled3');
		$('#status-button-fapp').removeClass('btn-disabled3');
		$('#status-button-fapp2').removeClass('btn-disabled3');
		$('#status-bar-fapp').addClass('bg-success2');
		$('#status-button-fapp').addClass('btn-success');
		$('#status-button-fapp2').addClass('btn-success');
		$('#buton_status').removeClass('fa fa-ban');
		$('#buton_status').addClass('fa fa-check');
		$('#click_ativa').hide();
	}
	
}

// Set a timer to call the check_status()
// function in two seconds.
setTimeout(check_status, 2500);
<?php } ?>
</script>

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

			if (len > 0) {
				for(i=0; i<len; i++) {
					if (From.eq(i).is(':selected')) {
						text = From.eq(i).text();
						option = From.eq(i).val();
						To.append(new Option(text, option));
						From.eq(i).remove();
					}
				}
			}
		}

		$("#move_groups_todisabled").click(function() {
			var move_element = $(this).attr("name");
			if (move_element == "move_users_todisabled") {
				moveOptions($('[name="users_selected[]"] option'), $('[name="users_disabled[]"]'));
			} else if (move_element == "move_groups_todisabled") {
				moveOptions($('[name="groups_selected[]"] option'), $('[name="groups_disabled[]"]'));
			} else if (move_element == "move_categories_todisabled") {
				moveOptions($('[name="categories_selected[]"] option'), $('[name="categories_disabled[]"]'));
			}
		});

		$("#move_groups_toenabled").click(function() {
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
		});

		var box_elements = [
			["groups", $("select[name='groups_disabled[]']").parents(".form-group"), $("input[name='move_groups_toenabled']").parents(".form-group") ]
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

		$("#profile").change(function() {
			window.location.replace("?act=wizard&step=profile-group&profile="+this.value);
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

		$('input[name="customRadioInline"]').change(function() {
			if ($(this).is(':checked') && $(this).val() == '0') {
				$('#save_type_msg_2').hide();
				$('#save_type_msg_1').show();
				$('#modal_type').modal('show');
			}
			if ($(this).is(':checked') && $(this).val() == '1') {
				$('#save_type_msg_2').hide();
				$('#save_type_msg_1').show();
				$('#modal_type').modal('show');
			}
		});

		$('input[name="verify_tunning"]').change(function() {
			if ($(this).val() == '1') {
				$('#modal_mask').attr('data-done', 'true');
				$('#save_mask_msg_1').hide();
				$('#save_mask_msg_2').show();
				$('#modal_mask').modal('show');
			}
			if ($(this).val() == '2') {
				$('#modal_mask').attr('data-done', 'true');
				$('#save_mask_msg_1').hide();
				$('#save_mask_msg_2').show();
				$('#modal_mask').modal('show');
			}
		});

		$('#verify_tunning2').click(function(e) {
			$('#verify_tunning2').attr('data-done', 'true');
			$('#save_mask_msg_1').hide();
			$('#verify_tunning2').hide();
			$('#save_mask_msg_2').show();
		});

		$('#verify_tunning21').click(function(e) {
			$('#verify_tunning21').attr('data-done', 'true');
			$('#save_mask_enable_msg_1').hide();
			$('#verify_tunning21').hide();
			$('#save_mask_enable_msg_2').show();
		});

		$('#save_type').click(function(e) {
			$('#save_type').attr('data-done', 'true');
			$('#save_type_msg_1').hide();
			$('#save_type_msg_2').show();
		});

	};

</script>

<script>
function mySearch() {
	// Declare variables
	var input, filter, table, tr, td, i, txtValue;
	input = document.getElementById("myInput");
	filter = input.value.toUpperCase();
	table = document.getElementById("myTable");
	tr = table.getElementsByTagName("tr");

	// Loop through all table rows, and hide those who don't match the search query
	for (i = 0; i < tr.length; i++) {
		td = tr[i].getElementsByTagName("td")[0];
		if (td) {
			txtValue = td.textContent || td.innerText;
			if (txtValue.toUpperCase().indexOf(filter) > -1) {
				td.parentElement.style.display = "";
				if (tr[i].className == "tr_line")
					tr[i].style.display = "";
			} else {
				if (tr[i].className == "tr_line")
					tr[i].style.display = "";
				else
					tr[i].style.display = "none";
			}
		}
	}
}

<?php if ($force_update_rules_action) { ?>
	$('#modal_ativa .txt_modal_ativa').text($('#force_update_rules').text() + " <?=gettext("completed successfully")?>");
	$('#modal_ativa #loader_modal_ativa').attr('style', 'width:100px;height:auto');
	$('#modal_ativa #loader_modal_ativa').attr('src', '../images/update_rules_ok.png');	
	$('#modal_ativa').modal('show');
	setTimeout(function() {$('#modal_ativa').modal('hide')}, 5000);
<?php } ?>


</script>
<!-- script select all release/block -->
<script>
	function selectAllFields(example){
		$('.' + example).prop('checked', true);
	}
</script>
<!-- script select all release / block -->

<script>
function searchDataFapp() {
	var $rows = $('#tablelinks div a');
	var val = $.trim($('#searchRulesAdvancedFapp').val()).replace(/ +/g, ' ').toLowerCase();
	$rows.show().filter(function() {
		var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
		return !~text.indexOf(val);
	}).hide();
}

$(document).ready(function () {
	$("#closeAdvanceSearchFapp").click(function (event) {
		event.preventDefault();
		$("#searchRulesAdvancedFapp").val("");
		searchDataFapp();
    });
});
</script>

<script>
function searchData() {
	var $rows = $('#tablefapp tbody tr');
	var val = $.trim($('#searchRulesAdvanced').val()).replace(/ +/g, ' ').toLowerCase();
	$rows.show().filter(function() {
		var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
		return !~text.indexOf(val);
	}).hide();
}

$(document).ready(function () {
	$("#closeAdvanceSearch").click(function (event) {
		event.preventDefault();
		$("#searchRulesAdvanced").val("");
		searchData();
    });
});

function showAttentionMsg(valueSIGGID) {
	if ($("#"+valueSIGGID).is(":hidden")) {
		$("#"+valueSIGGID).attr("style", "margin:10px;margin-bottom:0px;color:red;");
	} else {
		$("#"+valueSIGGID).attr("style", "margin:0px;display:none;");
	}
}
</script>
