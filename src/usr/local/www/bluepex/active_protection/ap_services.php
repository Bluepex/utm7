<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */

require_once("config.inc");
require_once('guiconfig.inc');
require_once('functions.inc');
require_once('notices.inc');
require_once("pkg-utils.inc");
require_once("bluepex/bp_webservice.inc");
require_once("bluepex/firewallapp_webservice.inc");
require_once("bluepex/firewallapp.inc");
require_once("util.inc");

require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("/usr/local/pkg/suricata/suricata_acp.inc");
require_once("bluepex/bp_cron_control.inc");

define("FILE_IF_EX_LAN", "/etc/if_ex_wan.conf");

$instanceid = 0;

init_config_arr(array('installedpackages', 'suricata', 'rule'));

$a_instance = &$config['installedpackages']['suricata']['rule'];
$a_rule = &$config['installedpackages']['suricata']['rule'];
$all_gtw = getInterfacesInGatewaysWithNoExceptions();

if ($_POST['modification_status_of_rule']) {	
	
	$values_operation =  explode("__", $_POST['modification_status_of_rule']);
	$operation = $values_operation[0];
	$value = $values_operation[1];
	
	unlink_if_exists('/tmp/sedChangeLine');

	$scriptAction = "";
	if ($operation == "black") {
		$scriptAction .= <<<EOD
		grep -v {$value} /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp
		mv /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt
		uniq /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp
		echo '' >> /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp
		echo {$value} >> /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp
		sed '/^$/d' /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt
		rm /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp
		if [ -e /var/db/clamav/ignore_analisy.sfp ]
		then
			if [ `grep {$value} /var/db/clamav/ignore_analisy.sfp | wc -l` != 0 ]
				grep -v {$value} /var/db/clamav/ignore_analisy.sfp > /var/db/clamav/ignore_analisy.sfp.tmp 
				mv /var/db/clamav/ignore_analisy.sfp.tmp /var/db/clamav/ignore_analisy.sfp
			fi
		fi 
		EOD;
	} elseif ($operation == "white") {
		$scriptAction .= <<<EOD
		grep -v {$value} /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp
		mv /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt
		uniq /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp
		echo '' >> /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp
		echo {$value} >> /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp
		sed '/^$/d' /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt
		rm /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp
		if [ -e /var/db/clamav/ignore_analisy.sfp ]
		then
			if [ `grep {$value} /var/db/clamav/ignore_analisy.sfp | wc -l` != 0 ]
				grep -v {$value} /var/db/clamav/ignore_analisy.sfp > /var/db/clamav/ignore_analisy.sfp.tmp 
				mv /var/db/clamav/ignore_analisy.sfp.tmp /var/db/clamav/ignore_analisy.sfp
			fi
		fi 
		EOD;
	} elseif ($operation == "exception") {
		$scriptAction .= <<<EOD
		if [ `find /var/log/suricata/suricata_*/filestore/*/{$value} | wc -l` != 0 ]
		then
			if [ -e /var/db/clamav/ignore_analisy.sfp ]
			then
				if [ `grep {$value} /var/db/clamav/ignore_analisy.sfp | wc -l` == 0 ]
				then
					sigtool --sha256 `find /var/log/suricata/suricata_*/filestore/*/{$value} | head -n1` >> /var/db/clamav/ignore_analisy.sfp
				fi
			else
				sigtool --sha256 `find /var/log/suricata/suricata_*/filestore/*/{$value} | head -n1` >> /var/db/clamav/ignore_analisy.sfp
			fi
		fi
		if [ `grep {$value} /var/db/clamav/ignore_analisy.sfp | wc -l` != 0 ]
		then
			grep -v {$value} /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp
			mv /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt
			grep -v {$value} /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp
			mv /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt
			if [ -e /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt ]
			then
				grep -v {$value} /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt.tmp
				mv /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt
			fi
			if [ -e /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt ]
			then
				grep -v {$value} /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt.tmp
				mv /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt
			fi
		fi
		EOD;
	}

	file_put_contents("/tmp/sedChangeLine", $scriptAction);
	mwexec("/bin/sh /tmp/sedChangeLine && /bin/rm /tmp/sedChangeLine");	
	
	if (file_exists("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt") || file_exists("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt")) {
		        
		//Generate custom bk
		$values_blacklist_file_custom = [];
		if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt')) {
			mwexec("/usr/bin/sort /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt | /usr/bin/uniq | /usr/bin/grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt.tmp"); 
			rename("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt.tmp", "/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt");
			$values_blacklist_file_custom = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt'))));
		}
		$values_whitelist_file_custom = [];
		if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt')) {
			mwexec("/usr/bin/sort /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt | /usr/bin/uniq | /usr/bin/grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt.tmp");
			rename("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt.tmp", "/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt");
			$values_whitelist_file_custom = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt'))));
		}
		$values_blacklist_file = [];
		if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt')) {
			mwexec("/usr/bin/sort /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt | /usr/bin/uniq | /usr/bin/grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp");
			rename("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp", "/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt");
			$values_blacklist_file = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt'))));
		}
		$values_whitelist_file = [];
		if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt')) {
			mwexec("/usr/bin/sort /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt | /usr/bin/uniq | /usr/bin/grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp");
			rename("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp", "/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt");
			$values_whitelist_file = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt'))));
		}
		
		//Create a black clean - merge
		//-------------------------------------------------------------------------
		foreach ($values_blacklist_file_custom as $values_blacklist_now) {
			if (!in_array($values_blacklist_now, $values_whitelist_file)) {
				if (!in_array($values_blacklist_now, $values_blacklist_file)) {
					file_put_contents("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt", "{$values_blacklist_now}\n", FILE_APPEND);
				}
			}
		}
		//-------------------------------------------------------------------------
		
		//Create a black clean - merge
		//-------------------------------------------------------------------------
		foreach ($values_whitelist_file_custom as $values_whitelist_now) {
			if (!in_array($values_whitelist_now, $values_blacklist_file)) {
				if (!in_array($values_whitelist_now, $values_whitelist_file)) {
					file_put_contents("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt", "{$values_whitelist_now}\n", FILE_APPEND);
				}
			}
		}
		//-------------------------------------------------------------------------
		
		//Clear variables
		unset($values_blacklist_file_custom);
		unset($values_whitelist_file_custom);
		unset($values_blacklist_file);
		unset($values_whitelist_file);

		mwexec("/usr/bin/sort /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt | /usr/bin/uniq | /usr/bin/grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp");
		rename("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp", "/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt");
		
		mwexec("/usr/bin/sort /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt | /usr/bin/uniq | /usr/bin/grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp");
		rename("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp", "/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt");

		copy("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt", "/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt");
		copy("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt", "/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt");

	} else {
		if (!file_exists("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt")) {
			copy("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt", "/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt");
		}
		if (!file_exists("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt")) {
			copy("cp /usr/local/share/suricata/otx/ransomd5/clamav_whilelist_256.txt", "/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt");
		}
	}

	die;
}

function suricata_is_alert_globally_suppressed($list, $gid, $sid) {

	/************************************************/
	/* Checks the passed $gid:$sid to see if it has */
	/* been globally suppressed.  If true, then any */
	/* "track by_src" or "track by_dst" options are */
	/* disabled since they are overridden by the    */
	/* global suppression of the $gid:$sid.         */
	/************************************************/

	/* If entry has a child array, then it's by src or dst ip. */
	/* So if there is a child array or the keys are not set,   */
	/* then this gid:sid is not globally suppressed.           */
	if (is_array($list[$gid][$sid]))
		return false;
	elseif (!isset($list[$gid][$sid]))
		return false;
	else
		return true;
}

$suricata_uuid = $a_instance[$instanceid]['uuid'];
$if_real = get_real_interface($a_instance[$instanceid]['interface']);

global $g, $config;

init_config_arr(array('system', 'bluepex_stats'));

if (isset($_POST['enable_rep'])) {
	switch ($_POST['enable_rep']) {
		case 'on':

			file_put_contents("{$g['varrun_path']}/ip_protection.lck", '');
			file_put_contents("/usr/local/share/suricata/emerging_install", "0.9.9");

			clean();

			control_state_interface_acp();

			break;

		case 'off':

			unlink_if_exists("{$g['varrun_path']}/ip_protection.lck");
			unlink_if_exists("/usr/local/share/suricata/acp/emerging_install");

			clean();

			control_state_interface_acp();

			break;
	}

}

if (isset($_POST['stats_clean'])) {
	switch ($_POST['stats_clean']) {
		case 'on':
			foreach (explode("\n", trim(shell_exec("pfctl -sT | /usr/bin/grep 'acp2c'"))) as $tablesSimple) {
				mwexec("/sbin/pfctl -t ${tablesSimple} -T flush");
			}
			break;
		default:
			break;
	}
}

if (isset($_POST['interfaceACP']) && !empty($_POST['interfaceACP']) && isset($_POST['ipsMode']) && !empty($_POST['ipsMode'])) {
	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	file_put_contents("{$g['varrun_path']}/ip_protection.lck", '');

	#Delete files lck
	#New line for destroy lck files
	file_put_contents("/usr/local/share/suricata/emerging_install", "0.9.9");

	clean();

	file_put_contents("{$g['varrun_path']}/suricata_start_acp.lck", '');	

	foreach (glob("/usr/local/share/suricata/rules/*") as $files) {
		unlink_if_exists($files);
	}
	copy("/usr/local/share/suricata/rules_acp/_ameacas.rules", "/usr/local/share/suricata/rules/_ameacas.rules");
	copy("/usr/local/share/suricata/rules_acp/_ameacas_ext.rules", "/usr/local/share/suricata/rules/_ameacas_ext.rules");
	copy("/usr/local/share/suricata/rules_acp/_emerging.rules", "/usr/local/share/suricata/rules/_emerging.rules");
	copy("/usr/local/share/suricata/rules_fapp/rede_sociais.rules", "/usr/local/share/suricata/rules/rede_sociais.rules");
	copy("/usr/local/share/suricata/rules_fapp/portais.rules", "/usr/local/share/suricata/rules/portais.rules");
	copy("/usr/local/share/suricata/rules_fapp/outros.rules", "/usr/local/share/suricata/rules/outros.rules");
	copy("/usr/local/share/suricata/rules_fapp/streaming.rules", "/usr/local/share/suricata/rules/streaming.rules");

	enable_limit_logs_acp_fapp();

	if (!is_array($config['installedpackages']['suricata']['rule'])) {
		$config['installedpackages']['suricata']['rule'] = array();
	}

	$a_rule = &$config['installedpackages']['suricata']['rule'];
	for ($id = 0; $id <= count($a_rule)-1; $id++) {
		$if_real = get_real_interface($a_rule[$id]['interface']);
		$suricata_uuid = $a_rule[$id]['uuid'];
		if (in_array($if_real,$all_gtw,true)) {
			unlink_if_exists("{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck");
			unlink_if_exists("/etc/suricata_{$if_real}{$suricata_uuid}_stop.lck");
		}
	}

	if (!suricata_is_running($suricatacfg['uuid'], $if_real)) {
		log_error("Starting Suricata on {$if_friendly}({$if_real}) per user request...");
		control_state_interface_acp($_POST['interfaceACP'], $_POST['ipsMode']);
	} else {
		log_error("Restarting Suricata on {$if_friendly}({$if_real}) per user request...");
		$suri_starting[$id] = 'TRUE';
		if ($suricatacfg['barnyard_enable'] == 'on' && !isvalidpid("{$g['varrun_path']}/barnyard2_{$if_real}{$suricata_uuid}.pid")) {
			$by2_starting[$id] = 'TRUE';
		}
	}

	mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');
	bp_cron_acp_fapp_action();

	sleep(2);
	$retval = 0;
	$retval |= filter_configure();
	clear_subsystem_dirty('filter');

}

$amount_interfaces_avaliableSimple = intval(explode("___", return_option_mult_interfaces_fapp_acp())[1]);
$error_show = "";

if (isset($_POST['enable_acp'])) {
	switch ($_POST['enable_acp']) {
		case 'enable':

			if ($amount_interfaces_avaliableSimple != -1) {

				//Limite interfaces possible in hardware
				if (limit_mult_interfaces_fapp_acp()) {
					$errormsg = gettext("The maximum number of interfaces is already in use on the device.");
					break;
				}
				global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

				file_put_contents("{$g['varrun_path']}/ip_protection.lck", '');

				#Delete files lck
				#New line for destroy lck files

				file_put_contents("/usr/local/share/suricata/emerging_install", "0.9.9");

				clean();

				file_put_contents("{$g['varrun_path']}/suricata_start_acp.lck", '');	

				$tw_includes1 = file_exists('/etc/tw_includes') ? file_get_contents("/etc/tw_includes") : "";

				if (($tw_includes1 == "with traffic") || (empty($tw_includes1))) {
					foreach (glob("/usr/local/share/suricata/rules/*") as $files) {
						unlink_if_exists($files);
					}
					copy("/usr/local/share/suricata/rules_acp/_ameacas.rules", "/usr/local/share/suricata/rules/_ameacas.rules");
					copy("/usr/local/share/suricata/rules_acp/_ameacas_ext.rules", "/usr/local/share/suricata/rules/_ameacas_ext.rules"); 
					copy("/usr/local/share/suricata/rules_acp/_emerging.rules", "/usr/local/share/suricata/rules/_emerging.rules");
					copy("/usr/local/share/suricata/rules_fapp/rede_sociais.rules", "/usr/local/share/suricata/rules/rede_sociais.rules"); 
					copy("/usr/local/share/suricata/rules_fapp/portais.rules", "/usr/local/share/suricata/rules/portais.rules"); 
					copy("/usr/local/share/suricata/rules_fapp/outros.rules", "/usr/local/share/suricata/rules/outros.rules"); 
					copy("/usr/local/share/suricata/rules_fapp/streaming.rules", "/usr/local/share/suricata/rules/streaming.rules");
				} else if ($tw_includes1 == "only") {
					foreach (glob("/usr/local/share/suricata/rules/*") as $files) {
						unlink_if_exists($files);
					}
					copy("/usr/local/share/suricata/rules_acp/_ameacas.rules", "/usr/local/share/suricata/rules/_ameacas.rules");
					copy("/usr/local/share/suricata/rules_acp/_ameacas_ext.rules", "/usr/local/share/suricata/rules/_ameacas_ext.rules"); 
					copy("/usr/local/share/suricata/rules_acp/_emerging.rules", "/usr/local/share/suricata/rules/_emerging.rules");
				}

				enable_limit_logs_acp_fapp();

				if (!is_array($config['installedpackages']['suricata']['rule'])) {
					$config['installedpackages']['suricata']['rule'] = array();
				}

				$a_rule = &$config['installedpackages']['suricata']['rule'];
				for ($id = 0; $id <= count($a_rule)-1; $id++) {
					$if_real = get_real_interface($a_rule[$id]['interface']);
					$suricata_uuid = $a_rule[$id]['uuid'];
					if (in_array($if_real,$all_gtw,true)) {
						unlink_if_exists("{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck");
						unlink_if_exists("/etc/suricata_{$if_real}{$suricata_uuid}_stop.lck");
					}
				}

				if (!suricata_is_running($suricatacfg['uuid'], $if_real)) {
					log_error("Starting Suricata on {$if_friendly}({$if_real}) per user request...");
					control_state_interface_acp();
				} else {
					log_error("Restarting Suricata on {$if_friendly}({$if_real}) per user request...");
					$suri_starting[$id] = 'TRUE';
					if ($suricatacfg['barnyard_enable'] == 'on' && !isvalidpid("{$g['varrun_path']}/barnyard2_{$if_real}{$suricata_uuid}.pid")) {
						$by2_starting[$id] = 'TRUE';
					}
				}

				mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');

				//Read first acp create and make a lck
				foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
					$if = get_real_interface($suricatacfg['interface']);
				    if (in_array($if, $all_gtw,true)) {
						file_put_contents("/etc/suricata_{$if}{$suricatacfg['uuid']}_stop.lck", "");
						file_put_contents("{$g['varrun_path']}/suricata_{$if}{$suricatacfg['uuid']}_stop.lck", "");
				    }
				}
				bp_cron_acp_fapp_action();

				sleep(2);
				$retval = 0;
				$retval |= filter_configure();
				clear_subsystem_dirty('filter');
			} else {
				$error_show = "Performance interfaces limit reached, can't start a new interface.";
			}

			break;

		case 'acp_off':

			clean();

			unlink_if_exists("{$g['varrun_path']}/ip_protection.lck");
			unlink_if_exists("/usr/local/share/suricata/emerging_install");
			unlink_if_exists("{$g['varrun_path']}/suricata_start_acp.lck");
			file_put_contents("/var/log/teste_acp.txt", '');

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

				global $g, $config;

				foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
					$if = get_real_interface($suricatacfg['interface']);
					if (in_array($if, $all_gtw,true)) {
						suricata_stop($suricatacfg, get_real_interface($suricatacfg['interface']));
					}
					if ($suricatacfg['mixed_mode'] == 'on') {
						exec("pkill -9 -af suricata_{$if_real}{$suricata_uuid}_2.pid");
						unlink_if_exists("/etc/suricata_{$if_real}{$suricata_uuid}_mix_netmap_on");
					}
				}
			}

			mwexec("pkill -9 -af wf", true);
			mwexec('pkill -9 -af "tls|http|alerts"', true);
			break;
		default:
			break;
	}

	unset($suricata_start_cmd);
}

$force_update_rules_action = false;

if (isset($_POST['force_update_rules'])) {
	switch ($_POST['force_update_rules']) {
		case 'on':

			//Customize timeout for post update rules
			set_time_limit(300);

			unlink_if_exists("/usr/local/share/suricata/emerging_version");
			unlink_if_exists("/usr/local/share/suricata/emerging_install");
			mwexec("/usr/local/bin/php /usr/local/www/firewallapp/get_rules_lists_webservice.php update_acp");

			$force_update_rules_action = true;
			break;
		default:
			break;
	}
}

# --- AJAX REVERSE DNS RESOLVE Start ---
if (isset($_POST['resolve'])) {
	$ip = strtolower($_POST['resolve']);
	$res = (is_ipaddr($ip) ? gethostbyaddr($ip) : '');
	if (strpos($res, 'xn--') !== false) {
		$res = idn_to_utf8($res);
	}

	if ($res && $res != $ip) {
		$response = array('resolve_ip' => $ip, 'resolve_text' => $res);
	} else {
		$response = array('resolve_ip' => $ip, 'resolve_text' => gettext("Cannot resolve"));
	}

	echo json_encode(str_replace("\\","\\\\", $response)); // single escape chars can break JSON decode
	exit;
}
# --- AJAX REVERSE DNS RESOLVE End ---

# --- AJAX GEOIP CHECK Start ---
if (isset($_POST['geoip'])) {
	$ip = strtolower($_POST['geoip']);
	if (is_ipaddr($ip)) {
		$url = "https://api.hackertarget.com/geoip/?q={$ip}";
		$conn = curl_init("https://api.hackertarget.com/geoip/?q={$ip}");
		curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($conn, CURLOPT_FRESH_CONNECT,  true);
		curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
		set_curlproxy($conn);
		$res = curl_exec($conn);
		curl_close($conn);
	} else {
		$res = '';
	}

	if ($res && $res != $ip && !preg_match('/error/', $res)) {
		$response = array('geoip_text' => $res);
	} else {
		$response = array('geoip_text' => gettext("Cannot check {$ip}"));
	}

	echo json_encode(str_replace("\\","\\\\", $response)); // single escape chars can break JSON decode
	exit;
}
# --- AJAX GEOIP CHECK End ---


$ignore_phisical_interfaces_vlan = isset($config['vlans']['vlan']) ? array_filter(array_unique(array_column($config['vlans']['vlan'], "if"))) : [];
$exceptions_in_file = file_exists(FILE_IF_EX_LAN) ? array_unique(array_filter(explode(",", file_get_contents(FILE_IF_EX_LAN)))) : [];
$exceptionInterfaces = array_merge($exceptions_in_file, $ignore_phisical_interfaces_vlan);

$pgtitle = array(gettext("Active Protection"), gettext("Monitoring"));
$pglinks = array("", "@self");
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
	body { padding: 0px !important; }

	button.interface_target {
		text-transform: uppercase;
		margin-top: 10px;
		padding-left: 50px;
		padding-right: 50px;
		border-radius: 5px;
		width: 100%;
	}

	/*Alteracoes no visual da pagina*/
	.table-origins {
		width: 100% !important;
	}

	.tables-inspect {
		margin: auto !important;
	}

	.origins-block  {
		margin-bottom: 0px !important;
	}

	.real-time-inspect-body {
		height: 100% !important;
	}

	.mb-5, .my-5 {
		margin-top: 0rem!important;
    	margin-bottom: 2rem !important;
	}

	@media only screen and (max-width: 1200px) {
		.origins-block  {
			padding-right: 15px !important;
			margin-bottom: 10px !important;
		}
	}

</style>

<?php
if ($savemsg) {
	print_info_box(gettext($savemsg), 'success');
}

if ($errormsg) {
	print_info_box(gettext($errormsg), 'danger');
}

if ($error_show) {
	print_info_box($error_show, 'danger');
}

if (file_exists('/etc/showInstableACP')) {

	$show_interfaces = "";
	foreach (glob("/etc/hardwarelimitACP*") as $fileacp) {
		if (file_exists($fileacp) && count(array_filter(explode(";", trim(file_get_contents($fileacp))))) >= 10) {
			$show_interfaces = $show_interfaces . trim(explode("_",$fileacp)[1]) . ", ";
		}
	}

	if (!empty($show_interfaces)) {
		$show_interfaces = rtrim($show_interfaces,", ");
		print_info_box("As seguintes interfaces com o modo de 'Proteção extra' não estão estáveis: $show_interfaces.<br>" .
		"Para o serviço voltar a funcionar, entre no painel de interfaces e inicie o serviço, caso a ocorrência persista, desabilite o modo de 'proteção extra' das interfaces listadas ou entre em contato com o suporte técnico.<br>", 'warning');
	}
}
?>

<!-- Owl Stylesheets -->
<link rel="stylesheet" href="assets/owlcarousel/assets/owl.carousel.min.css">
<link rel="stylesheet" href="assets/owlcarousel/assets/owl.theme.default.min.css">
<!-- javascript -->
<script src="assets/vendors/jquery.min.js"></script>
<script src="assets/owlcarousel/owl.carousel.min.js"></script>

<hr style="border: 1px solid #c5c5c5;padding-right: 10px;padding-left: 10px;margin-top: 35px;margin-bottom: 15px;">

<form action="ap_services.php" class="border-0" method="POST">
	<div class="panel col-12 bg-success3 py-3 color-white" id="title-active-protection">
		<div class="row">
			<div class="col-12 col-md-9">
				<div class="row" id="status-firewall">
					<?php if (file_exists("{$g['varrun_path']}/suricata_updating.lck")) :  ?>
						<button class="btn btn-primary btn-sm ml-3 mx-md-5" type="submit" name="enable" value="enable" disabled="disabled"><i class="fa fa-refresh"></i> <?=gettext("Updating Rules...")?></button>
					<?php else : ?>
						<?php $status_ = getInterfaceNewAcp(); if ($status_ >= 1) : ?>
							<?php if ($status_ == 1) : ?>
								<h6 for="service_status" class="mb-3 mb-sm-0 mb-md-0 mt-0 pt-2"><i id="buton_status" class="fa fa-check px-3 ml-1 fa-4x border-right" aria-hidden="true"></i> <span class="mx-2"><?=gettext("Status")?>: </span> <span id="status-info" style="color:white"> </span></span></h6>
							<?php else : ?>
								<h6 for="service_status" class="mb-3 mb-sm-0 mb-md-0 mt-0 pt-2"><i id="buton_status" class="fa fa-check px-3 ml-1 fa-4x border-right" aria-hidden="true"></i> <span class="mx-2"><?=gettext("Status")?>: </span> <span id="status-info" style="color:white"> </span></span></h6>
							<?php endif; ?>
						<?php else: ?>
							<h6 for="service_status" class="mb-3 mb-sm-0 mb-md-0 mt-0 pt-2"><i id="buton_status" class="fa fa-check px-3 ml-1 fa-4x border-right" aria-hidden="true"></i> <span class="mx-2"><?=gettext("Status")?>: </span> <span id="status-info" style="color:white"> </span></span></h6>
							<?php
							$status_iface_fapp =  getStatusNewFapp();
							$status_iface_fapp2 =  getInterfaceNewFapp();
							if ($status_iface_fapp == 0) {
								if (count($exceptionInterfaces) == 0) {
							?>
									<button id="click_ativa3" class="btn btn-success btn-sm ml-3 mx-md-5" type="submit" onclick="document.getElementById('click_ativa').style.display = 'none';" name="enable_acp" value="enable"><i class="fa fa-check"></i> <?=gettext("Enable")?></button>
							<?php } else { ?>
									<button class="btn btn-success btn-sm ml-3 mx-md-5" onclick="event.preventDefault(); helpInformationNotEnableSimple();" ><i class="fa fa-question"></i> <?=gettext("Help")?></button>
							<?php
								}
							} else {
								if (count($exceptionInterfaces) == 0) {
							?>
								<button class="btn btn-success btn-sm ml-3 mx-md-5" id="verify_tunning11" data-toggle="modal" data-target="#modal_inter_fapp" type="button" name="verify_tunning11" value="1"><i class="fa fa-check"></i> <?=gettext("Enable")?></button>
							<?php } else { ?>
								<button class="btn btn-success btn-sm ml-3 mx-md-5" onclick="event.preventDefault(); helpInformationNotEnableSimple();" ><i class="fa fa-question"></i> <?=gettext("Help")?></button>
								<?php } ?>
							<?php } ?>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="col-12 col-md-3 mt-2 mt-md-0 d-md-flex justify-content-end">
				<button id="status-button-fapp11" type="button" onclick="window.open('http://wsutm.bluepex.com/docs/ActiveProtection.pdf')" class="btn btn-primary btn-sm ml-3 mx-md-5"><i class="fa fa-book" aria-hidden="true"></i> <?=gettext("ActiveProtection Instructions")?></button>
				<button id="status-button-fapp2" type="button" class="btn btn-success dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?=gettext("Settings")?></button>
				<div class="dropdown-menu dropdown-menu-right">
					<?php
					$status_ = getInterfaceNewAcp();
					$status2_ = getStatusNewAcp();
					$statusExistsInterfaceFAPP =  getInterfaceNewFapp();
					$statusInterfacesFAPP =  getStatusNewFapp();
					?>
					<?php if (($statusExistsInterfaceFAPP == 0) && ($status_ == 0) || ($statusExistsInterfaceFAPP > 0) && ($statusInterfacesFAPP == 0) && ($status_ == 0)) : ?>
						<button class="dropdown-item" data-toggle="modal" data-target="#modal_enable_advanced_ACP" type="button"><i class="fa fa-check"></i> Habilitar Avançado</button>
					<?php endif; ?>
					<?php if ($status_ != 0) : ?>
						<?php if (($status_ != 0) && ($status2_ != 0)) : ?>
							<button class="dropdown-item" data-toggle="modal" data-target="#modal_disabled_interfaces" type="button" id="enable_acp" name="enable_acp" value="1"><i class="fa fa-refresh"></i> <?=gettext("Deactivate")?></button>
						<?php else: ?>
							<a href="acp_interfaces.php" class="dropdown-item"><i class="fa fa-check"></i> <?=gettext("Activate")?></a>
						<?php endif; ?>
					<?php endif; ?>
					<button class="dropdown-item" data-toggle="modal" data-target="#modal_clean_stats11" type="button" id="clean_stats1" name="clean_stats1" value="1"><i class="fa fa-ban"></i> <?=gettext("Clear Blocked States")?> </button>
					<a href="acp_interfaces.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("Edit Interfaces")?></a>
					<a href="acp_logs_browser.php" class="dropdown-item"><i class="fa fa-binoculars"></i> <?=gettext("Logs & Status")?></a>
					<a href="acp_logs_mgmt.php" class="dropdown-item"><i class="fa fa-hand-paper-o"></i> <?=gettext("Limits")?></a>
					<a href="acp_traffic_analysis.php" class="dropdown-item"><i class="fa fa-search"></i> <?=gettext("Analyze signature/traffic")?></a>
					<a href="table_extra_protection.php" class="dropdown-item"><i class="fa fa-search"></i> <?=gettext("Related Traffic")?></a>
					<a href="gtw_expt.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("Gateways Exceptions")?></a>
					<a href="../ssl_inspect/ssl_inspect.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("SSL Inspect")?></a>
					<a href="acp_dnsprotection.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("DNS Protection")?></a>
					<?php if (file_exists('/etc/model') && intval(explode(" ", file_get_contents('/etc/model'))[1]) >= 3000): ?>
						<a href="./av/services_files_gateway.php" class="dropdown-item"><i class="fa fa-exclamation-triangle"></i> <?=gettext("Files Parsing Gateway")?></a>
					<?php endif; ?>
					<button class="dropdown-item" data-toggle="modal" data-target="#modal_force_update11" type="button" id="modal_force_update1" name="modal_force_update1" value="1"><i class="fa fa-refresh"></i> <?=gettext("Update Protection")?> </button>
				</div>
			</div>
		</div>
	</div>

	<!-- modal disabled service acp -->
	<div id="modal_disabled_interfaces" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<font color="black"><h4 class="modal-title"><?=gettext("Do you want to disable the Active Protection service")?></h4></font>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<p><?=gettext("Confirming this action will cause the active Protection service to be stopped on all operating interfaces.")?></p>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal"><?=gettext("Cancel")?></button>
					<button class="btn btn-warning" type="submit" id="click_disable4" name="enable_acp" value="acp_off"><i class="fa fa-refresh"></i> <?=gettext("Deactivate")?> </button>
				</div>
			</div>
		</div>
	</div>

	<!-- modal modal_inter_fapp -->
	<div id="modal_inter_fapp" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<font color="black"><h4 class="modal-title"><?=gettext("FIREWALLAPP INTERFACE IDENTIFIED")?></h4></font>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<center><small id="save_mask_enable_msg_1">
							<font color="black"><h5><?=gettext("ATTENTION, TO AVOID ERRORS, DISABLE THE INTERFACE:")?></h5></font>
							<font color="black"><p><?=gettext("Access FirewallApp settings and Disable the ")?></b></p></font>
							<font color="black"><p><?=gettext("Interface through the Menu, in the DISABLE option")?></b></p></font>
							<font color="black"><p><?=gettext("Wait a few moments until the STOPPED information is displayed.")?> </b></p></font>
							<font color="black"><p><?=gettext("The menu should be showing Yellow or Red color.")?> </b></p></font>
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
							<font color="black"><p><?=gettext("block again as soon as Active Protection identifies the threat.")?></b></p></font>
						</small>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Cancel")?></button>
					<button class="btn btn-primary" type="submit" id="stats_clean" name="stats_clean" value="on"><i class="fa fa-ban"></i> <?=gettext("Clear Blocked States")?> </button>
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
							<font color="black"><p><?=gettext("This routine forces Active protection rules to be updated and should NOT")?> </b></p></font>
							<font color="black"><p><?=gettext("run sequentially or many times a day. the system updates")?></b></p></font>
							<font color="black"><p><?=gettext("automatically when it identifies changes to the rules on our servers.")?> </b></p></font>
							<font color="black"><p><?=gettext("Use this tool with caution and when you need to force the update.")?> </b></p></font>
						</small>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" data-dismiss="modal"><?=gettext("Cancel")?></button>
					<button class="btn btn-primary" type="click" id="reload_rules_acp" name="reload_rules_acp"><i class="fa fa-refresh"></i> <?=gettext("Reload Rules")?> </button>
					<button class="btn btn-primary" type="submit" id="force_update_rules" name="force_update_rules" value="on" onclick="showDisplayNoneUpdate11()";><i class="fa fa-refresh"></i> <?=gettext("Update Rules")?> </button>
				</div>
			</div>
		</div>
	</div>
	<!-- modal submask -->

</form>

<br>
<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
			<dl class="dl-horizontal responsive">
			<!-- Legend -->
				<dt><?=gettext('Legenda')?></dt>				<dd></dd>
				<dt><i class="fa fa-hand-stop-o text-danger"></i></dt>		<dd><?=gettext("RED = THREATS");?></dd>
				<dt><i class="fa fa-hand-stop-o text-danger"></i></dt>		<dd><?=gettext("PURPLE = MAXIMUM PRIORITY THREATS (RANSOMWARE,PHISHING,ETC)");?></dd>
				<dt><i class="fa fa-ban text-warning"></i></dt>		<dd><?=gettext("YELLOW = NAVIGATION SIGNAL OF HIGH CONSUMPTION OF TRAFFIC AND SITES - NOT RECOMMENDED");?></dd>
				<dt><i class="fa fa-cog"></i></dt>		<dd><?=gettext("BLUE = MISCELLANEOUS NAVIGATION");?></dd>
				<dt><i class="fa fa-cog text-success"></i></dt>		<dd><?=gettext("GREEN = SOCIAL NETWORK TRAFFIC");?></dd>
			</dl>
			<?php
				print(gettext("Active Protection is an active protection service, meaning everything that is a threat, ") .
					gettext("ransomware, vulnerabilities, blacklisted ips, malicious artifact haches... it's ") . 
					'<br />' .
					gettext("blocked in UTM automatically, that is, all subscriptions are autoblocks.  ") .
					gettext("We carry out the surveys, use the subscriptions, and send the service ") . 
					'<br />' .
					gettext("of Active Protection for blocking threats. "));
			?>
			<br>
			<br>
			<p><?=gettext("NOTE: The option to add exceptions is only functional if the file still exists within the UTM's temporary storage, this is necessary for the analysis of the same to add the exceptions, if it does not exist, the action will not occur;")?></p>
		</div>
	</div>
</div>

<!-- modal modal_inter_fapp -->
<div id="modal_enable_advanced_ACP" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title"><?=gettext("Habilitar Active Protection - Avançado")?></h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<form action="./ap_services.php" method="POST" style="border: 0px solid transparent;margin-top:0px;">
				<div class="modal-body">
					<?php
					$select_interfaces_acp = [];
					foreach (get_configured_interface_with_descr(true) as $key_interface => $interface_now) {
						$interface_now_temp = strtolower($key_interface);
						$if = get_real_interface($interface_now_temp);
						if (!in_array($if, $all_gtw, true) ||
						    in_array($if, $ignore_phisical_interfaces_vlan)) {
							continue;
						}
						$select_interfaces_acp[] = "<option value='{$interface_now_temp}||{$interface_now}'>{$interface_now}</option>";
					}
					if (!empty($select_interfaces_acp)) {
						$show_active_advanced = false;
						if (!limit_mult_interfaces_fapp_acp()) {
							$amount_interfaces_avaliable = explode("___", return_option_mult_interfaces_fapp_acp());
							$show_active_advanced = true;
							$show_heuristic_mode = true;
							if (!isset($config['system']['disablechecksumoffloading']) || !isset($config['system']['disablesegmentationoffloading']) || !isset($config['system']['disablelargereceiveoffloading'])) {
								?>
									<div class="alert alert-warning clearfix">
										<p>Heuristic mode requires that Hardware Checksum, Hardware TCP Segmentation and Hardware Large Receive Offloading all be disabled on the <b>System > Advanced > Networking</b> tab.</p>
									</div>
								<?php
								$show_heuristic_mode = false;
							}
							if (intval($amount_interfaces_avaliable[0]) != -1 || intval($amount_interfaces_avaliable[1]) != -1) {
							?>
								<p style="margin-top: 10px;margin-bottom: 10px;">Selecione uma interface:</p>
								<select name="interfaceACP" id="interfaceACP" class="form-control">
									<?php echo join("\n", $select_interfaces_acp); ?>
								</select>
								<p style="margin-top: 10px;margin-bottom: 10px;">Selecione o modo de operação:</p>
								<select name="ipsMode" id="ipsMode" class="form-control">
									<?php if (intval($amount_interfaces_avaliable[1]) != 0) { ?>
										<option value='ips_mode_legacy'><?=gettext('Performance')?></option>
									<?php } ?>
									<?php if ($show_heuristic_mode && intval($amount_interfaces_avaliable[0]) != 0) { ?>
										<option value='ips_mode_inline'><?=gettext('Heuristic')?></option>
									<?php } ?>
								</select>
								<p class="text-danger mt-2">Aviso: Selecione uma interface para ativar o serviço de Active Protection, lembrando que essa opção somente está disponível quando não existe nenhum serviço de Active Protection no equipamento.</p>
							<?php } else { ?>
								<p class="text-danger mt-2">Aviso: Não há mais interfaces disponíveis ao equipamento para iniciar uma nova instancia.</p>
							<?php } ?>
						<?php } else { ?>
							<p class="text-danger mt-2">Aviso: Não há mais interfaces disponíveis ao equipamento para iniciar uma nova instancia.</p>
						<?php } ?>
					<?php } else { ?>
						<p class="text-danger mt-2">Aviso: Não há mais interfaces disponíveis ao equipamento para iniciar uma nova instância.</p>
						<p class="text-danger mt-2">Todas as interfaces possíveis para ativar o Active Protection estão com uma VLAN ativada ou como exceção de interface, favor verificar estes pontos para continuar a ativação do serviço.</p>
					<?php } ?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
					<?php if ($show_active_advanced) { ?>
						<button class="btn btn-success" type="submit" onclick="habilitarInterfaceModalAtiva()"><i class="fa fa-check"></i> <?=gettext("Enable")?> </button>
					<?php } ?>
				</div>
			</form>
		</div>
	</div>
</div>
<!-- modal submask -->

<div class="p-0">
	<div class="col-12 cards-info">
		<div class="row pb-2 pr-xl-7">
			<div class="col-sm card-app p-3 p-2 mb-2" style="height: 100% !important;">
				<?php
				$show_msg = gettext("NO INTERFACE IS WITH THE SERVICE ENABLED");
				foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
					$if = get_real_interface($suricatacfg['interface']);
					if ($suricatacfg['enable'] != "on" || !in_array($if, $all_gtw, true)) {
						continue;
					}
					if (suricata_is_running($suricatacfg['uuid'], get_real_interface($suricatacfg['interface']))) {
						$show_msg = gettext("SELECT INTERFACE");
						break;
					}
					$show_msg = gettext("INTERFACES WITH THE SERVICE ARE DISABLED");
				}
				?>
				<h4 class="text-center margins-content-bottom"><?=$show_msg?></h4>
				<div class="col-12 text-center margins-content-bottom">
					<input type="hidden" value="" id="interface_acp_target" name="interface_acp_target">
					<?php
					$primeira_interface = true;
					$first_btn = true;
					$btn_interfaces_acp = [];
					foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
						$if = get_real_interface($suricatacfg['interface']);
						if ($suricatacfg['enable'] != "on" || !in_array($if, $all_gtw, true)) {
							continue;
						}
						$disabled = "";
						$btn_class = "primary";
						if (suricata_is_running($suricatacfg['uuid'], $if)) {
							if ($first_btn) {
								$btn_class = "success";
								$first_btn = false;
							}
						} else {
							$disabled = "disabled";
						}
						$btn_interfaces_acp[] = "<button type='click' class='btn weight-600 interface_target btn-{$btn_class} {$disabled}' style='margin: auto;margin-top: 10px;' id='interface-btn-{$if}{$suricatacfg['uuid']}' onclick=\"set_variable_interface_acp('{$if}{$suricatacfg['uuid']}')\" {$disabled}>{$suricatacfg['descr']}</button>";
					}
					echo join("\n", $btn_interfaces_acp);
					?>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="p-0">
	<div class="col-12 cards-info">
		<div class="row pb-2 pr-xl-7">
			<div class="col-sm card-app p-3 p-2 mb-2">
				<h6>Active Protection</h6>
				<hr>
				<div class="row">
					<div class="col-md-3">
						<div class="col-12" style="margin-top:5px;" >
							<div class="p-3" style="background-color:#fff;">
								<h4 class="text-center"><?=gettext("INVASION ATTEMPTS")?></h4>
								<div id="chart-map-threats" style="height:185px;width:100%;"></div>
							</div>
						</div>
					</div>
					<div class="col-md-9">
						<div class="col-12 text-center mt-20-mb-40">
							<h6><?=gettext("Monitoring Statistics")?></h6>
						</div>
						<div class="col-12 padding-top-15">
							<div class="row">
								<div class="col-md-3 text-center margin-bottom-5">
									<h1 class="text-color-orange" id="access_ameacas_geral"></h1>
									<h4><?=gettext("Threats (General)")?></h4>
									<p style="margin-bottom:1px;"><?=gettext("Number of threats found")?></p>
								</div>
								<div class="col-md-3 text-center margin-bottom-5">
									<h1 class="text-color-red" id="access_ram"></h1>
									<h4><?=gettext("Threats (Maximum Priority)")?></h4>
									<p style="margin-bottom:1px;"><?=gettext("Ransomware, Phishing, etc...")?></p>
								</div>
								<div class="col-md-3 text-center margin-bottom-5">
									<h1 class="text-color-yellow" id="access_nav"></h1>
									<h4><?=gettext("Navigation (High Consumption)")?></h4>
									<p style="margin-bottom:1px;"><?=gettext("Traffic and not recommended sites")?><br> <?=gettext("with high consumption")?></p>
								</div>
								<div class="col-md-3 text-center margin-bottom-5">
									<h1 class="text-color-green" id="access_soc"></h1>
									<h4><?=gettext("Social Network Traffic")?></h4>
									<p style="margin-bottom:1px;"><?=gettext("Network traffic consumption")?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="row pb-2 pr-xl-7 tables-inspect">
	<div id="page-active-protection" style="width: 100% !important;"> 
		<div class="row text-center">
			<div class="col-12 col-md-12 col-xl-7 pr-md-0 mb-3 origins-block">
				<div class="bg-dark-2 p-3 pb-3" style="height:610px!important;">
					<div class="col-12 text-center" id="title-firewall">
						<h6><?=gettext("ORIGINS OF GEOGRAPHICALLY BLOCKED THREATS")?></h6>
						<p>Firewall</p>
						<hr class="line-bottom-5 mt-2">
					</div>
					<div class="col-12 mt-5 mb-5">
						<div class="row d-flex justify-content-center">
							<div class="col-12 col-md-4 col-xl-4">
								<h3 class="color-orange"><img src="icon-threats.png"> <b id="qtd_rank1"></b></h3>
								<h6 id="country_rank1"></h6>
								<hr class="line-bottom-4 d-md-none">
							</div>
							<div class="ol-12 col-md-4 col-xl-4">
								<h3 class="color-orange"><img src="icon-threats.png"> <b id="rank2"></b></h3>
								<h6 id="country_rank2"></h6>
								<hr class="line-bottom-4 d-md-none">
							</div>
							<div class="col-12 col-md-4 col-xl-4">
								<h3 class="color-orange"><img src="icon-threats.png"> <b id="rank3"></b></h3>
								<h6 id="country_rank3"></h6>
							</div>
						</div>
					</div>
					<div class="container col-12 px-0">
						<table id="table-threats-geo" class="table table-bordered mt-lg-4" style="margin-top:0px!important;">
							<thead>
								<tr>
									<th><?=gettext("Date/Time")?></th>
									<th><?=gettext("Source IP and Port")?></th>
									<th><?=gettext("Destination IP and Port")?></th>
									<th><?=gettext("Status")?></th>
								</tr>
							</thead>
							<tbody id="update_table_strutuct_geo">
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>	
			<form action="./services_acp_rules.php" method="POST" style="border: 0px solid transparent;display: none;" id="submitSearchRule">
				<input type="hidden" id="searchSIDRules" name="searchSIDRules" value=""> 
			</form>			
			<form action="./services_acp_ameacas.php" method="POST" style="border: 0px solid transparent;display: none;" id="submitSearchThread">
				<input type="hidden" id="searchSIDThreads" name="searchSIDThreads" value=""> 
			</form>
			<div class="col-12 col-md-12 col-xl-5 text-center">
				<div class="bg-dark-3 p-3" id="startTransformation">
				</div>
			</div>
		</div>
	</div>
</div>

<div class="p-0" style="margin-top:10px!important;">
	<div class="col-12 cards-info">
		<div class="row pb-2 pr-xl-7">
			<div class="col-sm card-app p-3 p-2 mb-2">
				<div class="panel panel-default" style="background: transparent !important; margin-top:10px;">
					<div class="panel-heading" style="background: transparent !important; margin-top:10px;">
						<h2 class="panel-title" style="background: transparent !important; margin-top:unset; width:unset;"><?=gettext("Last Real-Time Inspection Records")?><br><?=gettext("(Apart from all those presented in the real-time inspection)")?></h2>
					</div>
					<button type="click" onclick="clearSearchDataBlock()" class="btn btn-danger form-control find-values" style="width: auto; float:right;"><i class="fa fa-times"></i> </button>
					<button type="click" onclick="EnterSearchDataBlock()" class="btn btn-primary form-control find-values" style="width: auto; float:right;"><i class="fa fa-search"></i> </button>
					<input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:400px;" id="search-rule-file-block" placeholder="<?=gettext("Search for...")?>">
					<select class="form-control" style="float:right;width:200px;" id="filterStatusClassification"></select>
					<div class="panel-body table-responsive" style="height: 260px !important;background: transparent !important;">
						<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" style="background: transparent !important;" data-sortable>
							<thead>
								<tr class="sortableHeaderRowIdentifier text-nowrap">
									<th data-sortable-type="date" data-sorted="true" data-sorted-direction="ascending" id="updateColumnDataAlert"><?=gettext("Date"); ?></th>
									<th><?=gettext("Source"); ?></th>
									<th><?=gettext("Destination"); ?></th>
									<th><?=gettext("Protocol"); ?></th>
									<th data-sortable-type="numeric"><?=gettext("GID:ID"); ?></th>
									<th><?=gettext("Classtype"); ?></th>
									<th><?=gettext("Action"); ?></th>
									<th data-sortable-type="alpha"><?=gettext("Description"); ?></th>
								</tr>
							</thead>
							<tbody id="table-file-rules-block">
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="p-0" style="margin-top:10px!important;">
	<div class="col-12 cards-info">
		<div class="row pb-2 pr-xl-7">
			<div class="col-sm card-app p-3 p-2 mb-2" style="height: 100% !important;">
				<h6><?=gettext("FILE INSPECTION")?></h6>
				<hr>
				<div class="col-12 text-center margins-content-bottom" style="display:flex;">
					<div class="col-12 col-sm">
						<img src="../images/trojan.png" style="width:64px;height:64px;">
						<h4 class="weight-700">Trojan</h4>
						<p class="weight-600 color-text-second" id="trojan_count">0</p>
						<hr>
					</div>
					<div class="col-12 col-sm">
						<img src="../images/ransonware.png" style="width:64px;height:64px;">
						<h4 class="weight-700">Ransomware</h4>
						<p class="weight-600 color-text-second" id="ransonware_count">0</p>
						<hr>
					</div>
					<div class="col-12 col-sm">
						<img src="../images/phishing.png" style="width:64px;height:64px;">
						<h4 class="weight-700">Phishing</h4>
						<p class="weight-600 color-text-second" id="phishing_count">0</p>
						<hr>
					</div>
					<div class="col-12 col-sm">
						<img src="../images/malware.png" style="width:64px;height:64px;">
						<h4 class="weight-700">Malware</h4>
						<p class="weight-600 color-text-second" id="malware_count">0</p>
						<hr>
					</div>  
					<div class="col-12 col-sm">
						<img src="../images/exploit.png" style="width:64px;height:64px;">
						<h4 class="weight-700">Exploit</h4>
						<p class="weight-600 color-text-second" id="exploit_count">0</p>
						<hr>
					</div>
					<div class="col-12 col-sm">
						<img src="../images/warning-icon.png" style="width:64px;height:64px;">
						<h4 class="weight-700"><?=gettext("Attention")?></h4>
						<p class="weight-600 color-text-second" id="warning_count">0</p>
						<hr>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="p-0" style="margin-top:10px!important;">
	<div class="col-12 cards-info">
		<div class="row pb-2 pr-xl-7">
			<div class="col-sm card-app p-3 p-2 mb-2">
				<div class="panel panel-default" style="background: transparent !important; margin-top:10px;">
					<div class="panel-heading" style="background: transparent !important; margin-top:10px;">
						<h2 class="panel-title" style="background: transparent !important; margin-top:unset;"><?=gettext("LAST FILTERED FILES")?></h2>
					</div>
					<button type="click" onclick="clearSearchDataFile()" class="btn btn-danger form-control find-values" style="width: auto; float:right;"><i class="fa fa-times"></i> </button>
					<button type="click" onclick="EnterSearchDataFile()" class="btn btn-primary form-control find-values" style="width: auto; float:right;"><i class="fa fa-search"></i> </button>
					<input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:400px;" id="search-rule-file" placeholder="<?=gettext("Search for...")?>">
					<select class="form-control" style="float:right;width:200px;" id="filterStatusFiles">
						<option value="" selected>Todos</option>
						<option value="noColor">Sem referencias</option>
						<option value="openColumns">Liberados</option>
						<option value="blockColumns">Bloqueados</option>
						<option value="exceptionColumns">Exceções</option>
					</select>
					<div class="panel-body table-responsive" style="height: 265px !important;background: transparent !important;">
						<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" style="background: transparent !important;" data-sortable>
							<thead>
							<tr class="sortableHeaderRowIdentifier text-nowrap">
								<th data-sortable-type="date" data-sorted-direction="ascending" id="updateColumnDataFiles"><?=gettext("Date"); ?></th>
								<th><?=gettext("Source"); ?></th>
								<th><?=gettext("Destination"); ?></th>
								<th><?=gettext("Request"); ?></th>
								<th><?=gettext("Host"); ?></th>
								<th><?=gettext("Hash's"); ?></th>
								<th><?=gettext("Size"); ?></th>
								<th data-sortable-type="alpha"><?=gettext("Filename"); ?></th>
							</tr>
							</thead>
							<tbody id="table-file-rules-geral">
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modal updatepack -->
<div class="modal fade" id="updatepack" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_updatepack" style="color:#007DC5"></h3>
				<br>
				<img id="loader_updatepack" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<!-- Modal Enable -->
<div class="modal fade" id="modal_enable2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_enable2" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_enable2" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<!-- Modal Disable -->
<div class="modal fade" id="modal_disable2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_disable2" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_disable2" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<!-- Modal Ativa -->
<div class="modal fade" id="modal_ativa3" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_ativa3" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_ativa3" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

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
<div class="modal fade" id="modal_enable3" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_enable3" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_enable3" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<!-- Modal Disable -->
<div class="modal fade" id="modal_disable3" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_disable3" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_disable3" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<!--modal info link-->
<div class="modal fade" id="modal_ativa_apresentation" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5" style="margin-top:unset!important;">
				<h3 class="titulo_modal_ativa_apresentation" style="color:#007DC5;text-align: initial;"></h3>
				<hr>
				<pre class="txt_modal_ativa_apresentation" style="color:#007DC5;text-align:initial;overflow-y:scroll;width:100%;height:300px;"></pre>
			</div>
		</div>
	</div>
</div>

<!--modal info sid-->
<div class="modal fade" id="modal_ativa_apresentation_sid" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5" style="margin-top:unset!important;">
				<h3 class="titulo_modal_ativa_apresentation_sid" style="color:#007DC5;text-align: initial;"></h3>
				<hr>
				<div class="txt_modal_ativa_apresentation_sid panel-body table-responsive" style="color:#007DC5;text-align:initial;width:100%;height:300px;"></div>
			</div>
		</div>
	</div>
</div>

<input type="hidden" id="modificationStatusOfRule" name="modificationStatusOfRule" value=""> 
<input type="hidden" id="search-rule-for-list" name="search-rule-for-list" value=""> 

<div id="modal_hash_white_black" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h6 id="changeTitleHash"><?=gettext("STATUS HASH")?></h6>
			</div>
			<br>
			<div class="modal-body">
				<div class="panel-body table-responsive" style="background: transparent !important;">
					<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" style="background: transparent !important;" data-sortable>
						<thead>
							<tr class="sortableHeaderRowIdentifier text-nowrap">
								<th><?=gettext("Filename"); ?></th>
								<th><?=gettext("Protocol"); ?></th>
								<th><?=gettext("Host"); ?></th>
								<th><?=gettext("Requisitante"); ?></th>
								<th><?=gettext("Hash's"); ?></th>
								<th><?=gettext("Actions"); ?></th>
							</tr>
						</thead>
						<tbody id="table-file-rules-lists">
							<tr>
								<td>---</td>
								<td>---</td>
								<td>---</td>
								<td>---</td>
								<td>---</td>
								<td>---</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include("foot.inc"); ?>

<script language="javascript" type="text/javascript">

function helpInformationNotEnableSimple() {
	setTimeout(() => {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Attention')?>");
		$('#modal_ativa .txt_modal_ativa').append("<p style='font-size: 18px;margin-top:10px;margin-bottom:10px;'><?=gettext("It is not possible to enable the Active Protection service in a performance way, the existence of VLAN's and/or interfaces with exceptions was identified in the UTM settings, please enable the advanced option on the side menu to proceed with the activation of the service.")?></p>");
		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/bp-logout.png');
		$('#modal_ativa #loader_modal_ativa').attr('style', 'width:32px; heigth:32px; margin:auto;');
		$('#modal_ativa').modal('show');
	}, 500);
}

function habilitarInterfaceModalAtiva() {
	$("#modal_enable_advanced_ACP").modal("hide");
	setTimeout(() => {
		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext('Actived Active Protection, please a moment')?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
		$('#modal_ativa3').modal('show');
	}, 500);
}

if ($(".btn.weight-600.interface_target.btn-success").length != 0) {
	$("#status-button-fapp2").attr('disabled', 'true');
	$("#status-button-fapp2").attr('title', 'Loading the page content, please wait a moment.');
	$("#status-button-fapp2").removeAttr('class').attr('class', 'btn btn-warning dropdown-toggle btn-sm');
	setTimeout(() => {
		$("#status-button-fapp2").removeAttr('class').attr('class', 'btn btn-success dropdown-toggle btn-sm');
		$("#status-button-fapp2").removeAttr('title');
		$("#status-button-fapp2").removeAttr('disabled');
	}, 10000);
}

let dropDownShow = true;

function returnDropMenuOpen() {
	if ($("div.col-12.col-md-3.mt-2.mt-md-0.d-md-flex.justify-content-end.open").length != 0) {
		dropDownShow = false;
	} else if ($(".nav-item.active").length != 0) {
		dropDownShow = false;
	} else if ($(".nav-item.dropdown.open").length != 0) {
		dropDownShow = false;
	} else if ($(".dropdown.open").length != 0) {
		dropDownShow = false;
	} else {
		dropDownShow = true;
	}
}

let openPageApServices = false;

//Set open page
setTimeout(() => {
	if ($(".btn.weight-600.interface_target.btn-success")[0]){
		$(".btn.weight-600.interface_target.btn-success")[0].click();
	} else {
		//If no button is clicked, at least run the minimum render
		if ($(".btn.weight-600.interface_target.btn-success").length != 0) {
			setTimeout(() => {
				update_count_malwares(); //15
				update_count_table(); //15
				update_count_threads_acp(); //15	
			}, 100);

			setTimeout(() => {
				update_table_strutuct_geo(); //30
				update_table_strutuct_files(); //30
			}, 200);

			setTimeout(() => {
				update_cards_strutuct(); //60
			}, 300);

			setTimeout(() => {
				update_table_strutuct_alertlog(); //300
			}, 400);

			setTimeout(() => {
				update_select_table_strutuct_alertlog(); //300
			}, 500);

			setTimeout(() => {
				update_map_invasion();
			}, 600);
			
			window.setInterval("update_count_table()", 20000);
			window.setInterval("update_count_threads_acp()", 22000);
			window.setInterval("update_table_strutuct_files()",24000);
			window.setInterval("update_table_strutuct_geo()",28000);
			window.setInterval("update_count_malwares()", 31000);
			window.setInterval("update_table_strutuct_alertlog()",299999);
			window.setInterval("update_select_table_strutuct_alertlog()",300100);
			window.setInterval("update_map_invasion()",300200);
		}
	}
}, 300);

//Use for change interface
function set_variable_interface_acp(set_hidden) {
	$(".btn.weight-600.interface_target.btn-success").attr('class', 'btn weight-600 interface_target btn-primary');
	document.getElementById("interface_acp_target").value=set_hidden;
	$("#interface-btn-" + set_hidden).attr('class', 'btn weight-600 interface_target btn-success');

	if (openPageApServices == true) {
		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext('Select interface, please a moment')?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
		$('#modal_ativa3').modal('show');
	}

	setTimeout(() => {
		update_count_malwares(); //15
		update_count_table(); //15
		update_count_threads_acp(); //15	
	}, 100);

	setTimeout(() => {
		update_table_strutuct_geo(); //30
		update_table_strutuct_files(); //30
	}, 200);

	setTimeout(() => {
		update_cards_strutuct(); //60
	}, 300);

	setTimeout(() => {
		update_table_strutuct_alertlog(); //300
	}, 400);
	setTimeout(() => {
		update_select_table_strutuct_alertlog(); //300
	}, 500);
	setTimeout(() => {
		update_map_invasion();
	}, 600);

	if (openPageApServices == true) {
		setTimeout(() => {
			$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/update_rules_ok.png');
			setTimeout(() => {
				$('#modal_ativa3').modal('hide');
			}, 1000);
		}, 2000);
	}

	openPageApServices = true;

	window.setInterval("update_count_table()", 20000);
	window.setInterval("update_count_threads_acp()", 22000);
	window.setInterval("update_table_strutuct_files()",24000);
	window.setInterval("update_table_strutuct_geo()",28000);
	window.setInterval("update_count_malwares()", 31000);
	window.setInterval("update_table_strutuct_alertlog()",299999);
	window.setInterval("update_select_table_strutuct_alertlog()",300100);
	window.setInterval("update_map_invasion()",300200);

}

function showDisplayNoneUpdate11() {
	document.getElementById('force_update_rules').style.display = 'none'
	setTimeout(() => {
		$('#modal_force_update11').modal('hide');	
	}, 100);
	setTimeout(() => {
		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext('Updating Active Protection rules, please wait until the end of the operation')?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
		$('#modal_ativa3').modal('show');
	}, 200);
}

$("#reload_rules_acp").click(function(event){
  	event.preventDefault();
	setTimeout(() => {
		$('#modal_force_update11').modal('hide');	
	}, 100);
	setTimeout(() => {
		$('#modal_ativa3 .txt_modal_ativa3').text($('#force_update_rules').text() + " <?=gettext("Reload rules is successfully")?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/update_rules_ok.png');	
		$('#modal_ativa3').modal('show');
	}, 150);
	setTimeout(() => {
		$.ajax({
			url: './update_interfaces_rules.php',
		});
	}, 200);
	setTimeout(function() {$('#modal_ativa3').modal('hide')}, 5000);
});

<?php if ($force_update_rules_action) { ?>
	$('#modal_ativa3 .txt_modal_ativa3').text($('#force_update_rules').text() + " <?=gettext("completed successfully")?>");
	$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
	$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/update_rules_ok.png');	
	$('#modal_ativa3').modal('show');
	setTimeout(() => {
		$.ajax({
			url: './update_interfaces_rules.php',
		});
	}, 2000);
	setTimeout(function() {$('#modal_ativa3').modal('hide')}, 5000);
<?php } ?>

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

$('#btn-save-ip').click(function() {
	$('#modal_avancado').modal('hide');

	var url_ajax = '';

	if ($('#has_group').val() == 'true') {
		url_ajax = "./ajax_save_group_rule.php";
	} else {
		url_ajax = "./ajax_save_rule.php";
	}

	$.ajax({
		data: {
			gid: $('#gid').val(),
			sid: $('#sid').val(),
			state: $('#state').val(),
			currentfile: $('#currentfile').val(),
			ip_source: $('#ip_source').val(),
			port_source: $('#port_source').val(),
			direction: $('#direction').val(),
			ip_destination: $('#ip_destination').val(),
			port_destination: $('#port_destination').val(),
		},
		method: "post",
		url: url_ajax,
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

		$.ajax({
			data: {
				gid: $('#gid').val(),
				sid: $('#sid').val(),
				action: $('#action').val(),
				state: $('#state').val(),
				currentfile: $('#currentfile').val(),
				ip_source: $('#ip_source').val(),
				port_source: $('#port_source').val(),
				direction: $('#direction').val(),
				ip_destination: $('#ip_destination').val(),
				port_destination: $('#port_destination').val(),
			},
			method: "post",
			url: "./ajax_save_rule.php",
		}).done(function() {
			alert('<?=gettext("Rule saved successfully!")?>');
		});
	}
}

function toggleStateGroup1(gid, btn, action_btn) {
	$('tbody').find("tr td input[type='radio']").each(function() {
		if ($(this).attr('data-gid') == gid && $(this).attr('data-action') == action_btn)
			$(this).prop( "checked", true );
	});

}

function toggleStateGroup(gid, btn) {
	$('#gid').val(gid);

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

		$.ajax({
			data: {
				gid: $('#gid').val(),
				action: $('#action').val(),
				state: $('#state').val(),
				currentfile: $('#currentfile').val(),
				ip_source: $('#ip_source').val(),
				port_source: $('#port_source').val(),
				direction: $('#direction').val(),
				ip_destination: $('#ip_destination').val(),
				port_destination: $('#port_destination').val(),
			},
			method: "post",
			url: "./ajax_save_group_rule.php",
		}).done(function() {
			alert('<?=gettext("Rule saved successfully!")?>');
		});
	}
}

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

function showStatus() {
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

	var status_iface = <?php echo getStatusNewAcp();?>;

	var status_iface2 = <?php echo getInterfaceNewAcp();?>;

	if ((status_iface == 0) && (status_iface2 > 0)) {
		$('#enable').removeClass('active');
		$('#disable').addClass('active');
		$('#status-info').text('<?=gettext("Stopped")?>');
		$('#status-info').show();
		$('#status-info').removeClass('status-stopped');
		$('#status-info').addClass('status-running');
		$('#title-active-protection').addClass('bg-disabled3');
		$('#status-button-fapp').addClass('bg-disabled3');
		$('#status-button-fapp2').addClass('bg-success3');
		$('#buton_status').addClass('fa fa-ban');
		$('#click_ativa3').hide();
	} else
	if ((status_iface == 0) && (status_iface2 == 0)) {
		$('#enable').removeClass('active');
		$('#disable').addClass('active');
		$('#status-info').text('<?=gettext("Disabled")?>');
		$('#status-info').show();
		$('#status-info').removeClass('status-running');
		$('#status-info').addClass('status-stopped');
		$('#title-active-protection').removeClass('bg-success3');
		$('#title-active-protection').addClass('btn-danger');
		$('#status-button-fapp').addClass('btn-danger');
		$('#status-button-fapp2').addClass('bg-success3');
		$('#buton_status').addClass('fa fa-ban');
	} else 
	if ((status_iface >= 1) && (status_iface2 >= 1)) {
		$('#disable').removeClass('active');
		$('#enable').addClass('active');
		$('#status-info').text('<?=gettext("Running")?>');
		$('#status-info').show();
		$('#status-info').addClass('status-running');
		$('#title-active-protection').removeClass('bg-disabled3');
		$('#status-button-fapp').removeClass('btn-disabled3');
		$('#status-button-fapp2').removeClass('btn-disabled3');
		$('#title-active-protection').addClass('bg-success3');
		$('#status-button-fapp').addClass('btn-success');
		$('#status-button-fapp2').addClass('btn-success');
		$('#buton_status').addClass('fa fa-check');
	}

}

// Set a timer to call the check_status()
// function in two seconds.
setTimeout(showStatus, 1000);

</script>
<script src="/js/echarts/dist/echarts.min.js?v=<?=filemtime("/usr/local/www/js/echarts/dist/echarts.min.js")?>"></script>
<script src="/js/echarts/map/js/world.js?v=<?=filemtime("/usr/local/www/js/echarts/map/js/world.js")?>"></script>
<script src="assets/vendors/highlight.js?v=<?=filemtime("/usr/local/www/active_protection/assets/vendors/highlight.js");?>"></script>
<script src="assets/js/app.js?v=<?=filemtime("/usr/local/www/active_protection/assets/js/app.js");?>"></script>
<script src="jquery.bxslider.min.js?v=<?=filemtime("/usr/local/www/active_protection/jquery.bxslider.min.js");?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.css" integrity="sha512-UTNP5BXLIptsaj5WdKFrkFov94lDx+eBvbKyoe1YAfjeRPC+gT5kyZ10kOHCfNZqEui1sxmqvodNUx3KbuYI/A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.css" integrity="sha512-OTcub78R3msOCtY3Tc6FzeDJ8N9qvQn1Ph49ou13xgA9VsH9+LRxoFU6EqLhW4+PKRfU+/HReXmSZXHEkpYoOA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<script type="text/javascript">

	function update_map_invasion() {
		returnDropMenuOpen();
		if (dropDownShow) {
			var data_response = "";
			var data = [];
			$.get("tentativas_invasao", function(response) {
				data_response = $.parseJSON(response);			
				for (item in data_response.data ) {
					data.push({name: data_response.data[item].name, value: data_response.data[item].value},);
				}
				var nameMap = {
					'Afghanistan':'Afghanistan',
					'Singapore':'Singapore',
					'Angola':'Angola',
					'Albania':'Albania',
					'United Arab Emirates':'United Arab Emirates',
					'Argentina':'Argentina',
					'Armenia':'Armenia',
					'French Southern and Antarctic Lands':'French Southern and Antarctic Lands',
					'Australia':'Australia',
					'Austria':'Austria',
					'Azerbaijan':'Azerbaijan',
					'Burundi':'Burundi',
					'Belgium':'Belgium',
					'Benin':'Benin',
					'Burkina Faso':'Burkina Faso',
					'Bangladesh':'Bangladesh',
					'Bulgaria':'Bulgaria',
					'The Bahamas':'The Bahamas',
					'Bosnia and Herzegovina':'Bosnia and Herzegovina',
					'Belarus':'Belarus',
					'Belize':'Belize',
					'Bermuda':'Bermuda',
					'Bolivia':'Bolivia',
					'Brazil':'Brazil',
					'Brunei':'Brunei',
					'Bhutan':'Bhutan',
					'Botswana':'Botswana',
					'Central African Republic':'Central African Republic',
					'Canada':'Canada',
					'Switzerland':'Switzerland',
					'Chile':'Chile',
					'China':'China',
					'Ivory Coast':'Ivory Coast',
					'Cameroon':'Cameroon',
					'Democratic Republic of the Congo':'Democratic Republic of the Congo',
					'Republic of the Congo':'Republic of the Congo',
					'Colombia':'Colombia',
					'Costa Rica':'Costa Rica',
					'Cuba':'Cuba',
					'Northern Cyprus':'Northern Cyprus',
					'Cyprus':'Cyprus',
					'Czech Republic':'Czech Republic',
					'Germany':'Germany',
					'Djibouti':'Djibouti',
					'Denmark':'Denmark',
					'Dominican Republic':'Dominican Republic',
					'Algeria':'Algeria',
					'Ecuador':'Ecuador',
					'Egypt':'Egypt',
					'Eritrea':'Eritrea',
					'Spain':'Spain',
					'Estonia':'Estonia',
					'Ethiopia':'Ethiopia',
					'Finland':'Finland',
					'Fiji':'Fiji',
					'Falkland Islands':'Falkland Islands',
					'France':'France',
					'Gabon':'Gabon',
					'United Kingdom':'United Kingdom',
					'Georgia':'Georgia',
					'Ghana':'Ghana',
					'Guinea':'Guinea',
					'Gambia':'Gambia',
					'Guinea Bissau':'Guinea Bissau',
					'Equatorial Guinea':'Equatorial Guinea',
					'Greece':'Greece',
					'Greenland':'Greenland',
					'Guatemala':'Guatemala',
					'French Guiana':'French Guiana',
					'Guyana':'Guyana',
					'Honduras':'Honduras',
					'Croatia':'Croatia',
					'Haiti':'Haiti',
					'Hungary':'Hungary',
					'Indonesia':'Indonesia',
					'India':'India',
					'Ireland':'Ireland',
					'Iran':'Iran',
					'Iraq':'Iraq',
					'Iceland':'Iceland',
					'Israel':'Israel',
					'Italy':'Italy',
					'Jamaica':'Jamaica',
					'Jordan':'Jordan',
					'Japan':'Japan',
					'Kazakhstan':'Kazakhstan',
					'Kenya':'Kenya',
					'Kyrgyzstan':'Kyrgyzstan',
					'Cambodia':'Cambodia',
					'South Korea':'South Korea',
					'Kosovo':'Kosovo',
					'Kuwait':'Kuwait',
					'Laos':'Laos',
					'Lebanon':'Lebanon',
					'Liberia':'Liberia',
					'Libya':'Libya',
					'Sri Lanka':'Sri Lanka',
					'Lesotho':'Lesotho',
					'Lithuania':'Lithuania',
					'Luxembourg':'Luxembourg',
					'Latvia':'Latvia',
					'Morocco':'Morocco',
					'Moldova':'Moldova',
					'Madagascar':'Madagascar',
					'Mexico':'Mexico',
					'Macedonia':'Macedonia',
					'Mali':'Mali',
					'Myanmar':'Myanmar',
					'Montenegro':'Montenegro',
					'Mongolia':'Mongolia',
					'Mozambique':'Mozambique',
					'Mauritania':'Mauritania',
					'Malawi':'Malawi',
					'Malaysia':'Malaysia',
					'Namibia':'Namibia',
					'New Caledonia':'New Caledonia',
					'Niger':'Niger',
					'Nigeria':'Nigeria',
					'Nicaragua':'Nicaragua',
					'Netherlands':'Netherlands',
					'Norway':'Norway',
					'Nepal':'Nepal',
					'New Zealand':'New Zealand',
					'Oman':'Oman',
					'Pakistan':'Pakistan',
					'Panama':'Panama',
					'Peru':'Peru',
					'Philippines':'Philippines',
					'Papua New Guinea':'Papua New Guinea',
					'Poland':'Poland',
					'Puerto Rico':'Puerto Rico',
					'North Korea':'North Korea',
					'Portugal':'Portugal',
					'Paraguay':'Paraguay',
					'Qatar':'Qatar',
					'Romania':'Romania',
					'Russia':'Russia',
					'Rwanda':'Rwanda',
					'Western Sahara':'Western Sahara',
					'Saudi Arabia':'Saudi Arabia',
					'Sudan':'Sudan',
					'South Sudan':'South Sudan',
					'Senegal':'Senegal',
					'Solomon Islands':'Solomon Islands',
					'Sierra Leone':'Sierra Leone',
					'El Salvador':'El Salvador',
					'Somaliland':'Somaliland',
					'Somalia':'Somalia',
					'Republic of Serbia':'Republic of Serbia',
					'Suriname':'Suriname',
					'Slovakia':'Slovakia',
					'Slovenia':'Slovenia',
					'Sweden':'Sweden',
					'Swaziland':'Swaziland',
					'Syria':'Syria',
					'Chad':'Chad',
					'Togo':'Togo',
					'Thailand':'Thailand',
					'Tajikistan':'Tajikistan',
					'Turkmenistan':'Turkmenistan',
					'East Timor':'East Timor',
					'Trinidad and Tobago':'Trinidad and Tobago',
					'Tunisia':'Tunisia',
					'Turkey':'Turkey',
					'United Republic of Tanzania':'United Republic of Tanzania',
					'Uganda':'Uganda',
					'Ukraine':'Ukraine',
					'Uruguay':'Uruguay',
					'United States of America':'United States of America',
					'Uzbekistan':'Uzbekistan',
					'Venezuela':'Venezuela',
					'Vietnam':'Vietnam',
					'Vanuatu':'Vanuatu',
					'West Bank':'West Bank',
					'Yemen':'Yemen',
					'South Africa':'South Africa',
					'Zambia':'Zambia',
					'Zimbabwe':'Zimbabwe'
				};

				var map_countries_chart_option = {
					timeline: {
						axisType: 'category',
						orient: 'vertical',
						autoPlay: true,
						inverse: true,
						playInterval: 5000,
						left: null,
						right: -105,
						top: 0,
						bottom: 0,
						width: 46,
						data: ['2019',]  
					},
					baseOption: {
						visualMap: {
							min: 50,
							max: 5000,
							text: ['Max', 'Min'],
							realtime: false,
							calculable: true,
							inRange: {
								color: ['#fddd57', '#F5B240', '#fdae61', '#f46d43', '#d73027', '#a50026']
							}
						},
						series: [{
							type: 'map',
							map: 'world',
							zoom: 1.20,
							roam: true,
							nameMap: nameMap,
							itemStyle: {
								normal: {
									borderColor: '#bebebe',
								}
							},
						}]
					},
					options: [{
						series: {
							data: data,
						} 
					},]
				};
				var ChartMapThreats = echarts.init(document.getElementById("chart-map-threats"));
				ChartMapThreats.setOption(map_countries_chart_option);
			});
		}
		returnDropMenuOpen();
	}

	function atualizar() {
		location.reload(true)
	}

	function getInfoDestino(enderecoAlvo) {

		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Buscando informações do endereço alvo")?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
		$('#modal_ativa3').modal('show');

		setTimeout(() => {
			$.ajax({
			data: {
				targetIp: enderecoAlvo
			},
			method: "post",
			dataType: "html",
			url: './ajax_retuns_inspect_real_time.php',
			}).done(function(data) {
				if (data.length > 0) {
					$('#modal_ativa3').modal('hide');
					$('#modal_ativa_apresentation .titulo_modal_ativa_apresentation').text("Informações do endereço: " + enderecoAlvo.split(":")[0]);
					$('#modal_ativa_apresentation .txt_modal_ativa_apresentation').text(data);
					$('#modal_ativa_apresentation').modal('show');
				} else {
					setTimeout(() => {
						$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Infelizmente nenhum informações foi encontrada")?>");
						$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
						$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/bp-logout.png');
					}, 3000);
					setTimeout(() => {
						$('#modal_ativa3').modal('hide');
					}, 6000);
				}
			});
		}, 1000);

	}

	function requestPathFile(flow_id, filename) {
		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Buscando informações do arquivo")?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
		$('#modal_ativa3').modal('show');

		setTimeout(() => {
			$.ajax({
			data: {
				service: "flow_id",
				interface: document.getElementById("interface_acp_target").value,
				flow_id_target: flow_id
			},
			method: "post",
			url: './ajax_dash_ap_services.php',
			}).done(function(data) {
				if (filename.length > 30) {
					filename = filename.substr(0, 29) + "...";
				}
				$('#modal_ativa_apresentation_sid .titulo_modal_ativa_apresentation_sid').html("File: " + filename);
				$('#modal_ativa_apresentation_sid .txt_modal_ativa_apresentation_sid').html(data);
			});
		}, 1000)

		setTimeout(() => {
			$('#modal_ativa3').modal('hide');
		}, 2000);
		setTimeout(() => {
			$('#modal_ativa_apresentation_sid').modal('show');
		}, 2100);

	}

	function submitSearchRulesThreads(sidRuleThread) {

		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Looking for card information")?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
		$('#modal_ativa3').modal('show');

		setTimeout(() => {
			$.ajax({
			data: {
				targetSID: sidRuleThread
			},
			method: "post",
			url: './ajax_retuns_inspect_real_time.php',
			}).done(function(data) {
				setTimeout(() => {
					if (data == "emerging") {
						document.getElementById("searchSIDRules").value=sidRuleThread;
						document.getElementById("submitSearchRule").submit();
					} else if (data == "ameacas") {
						document.getElementById("searchSIDThreads").value=sidRuleThread;
						document.getElementById("submitSearchThread").submit();
					}
				}, 4000);

			});
		}, 1000)

		setTimeout(function() {
			$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Selected card is not a rule/threat")?>")
			$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/bp-logout.png')
		}, 10000);

		setTimeout(function() {$('#modal_ativa3').modal('hide')}, 12000);
	}


	//-------------------------------------------------
	//Field request 
	function preencherSearchRequest(entrada) {
		$("#search-rule-request").val(entrada);
		searchDataRequest();
	}

	function clearSearchDataRequest() {
		$("#search-rule-request").val("");
		searchDataRequest();
	}

	function searchDataRequest() {
		var $rows = $('#table-data-request tr');
		var val = $.trim($('#search-rule-request').val()).replace(/ +/g, ' ').toLowerCase();
		$rows.show().filter(function() {
			var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
			return !~text.indexOf(val);
		}).hide();
	}
	//--------------------------------------------------


	//-------------------------------------------------
	//File request All
	function preencherSearchDataFile(entrada) {
		$("#search-rule-file").val(entrada);
		searchDataFile();
		showSelectColumnsFiles();
	}

	function EnterSearchDataFile() {
		searchDataFile();
		showSelectColumnsFiles();
	}

	function clearSearchDataFile() {
		$("#search-rule-file").val("");
		$("#filterStatusFiles").val("");
		searchDataFile();
	}

	function searchDataFile() {
		var $rows = $('#table-file-rules-geral tr');
		var val = $.trim($('#search-rule-file').val()).replace(/ +/g, ' ').toLowerCase();
		$rows.show().filter(function() {
			var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
			return !~text.indexOf(val);
		}).hide();
	}
	//--------------------------------------------------

	//-------------------------------------------------
	//File request Block
	function preencherSearchBlock(entrada) {
		$("#search-rule-file-block").val(entrada);
		searchDataBlock();
		showSelectColumnsAlerts();
	}

	function clearSearchDataBlock() {
		$("#search-rule-file-block").val("");
		$("#filterStatusClassification").val("");
		searchDataBlock();
		showSelectColumnsAlerts();
	}

	function EnterSearchDataBlock() {
		searchDataBlock();
		showSelectColumnsAlerts();
	}

	function searchDataBlock() {
		var $rows = $('#table-file-rules-block tr');
		var val = $.trim($('#search-rule-file-block').val()).replace(/ +/g, ' ').toLowerCase();
		$rows.show().filter(function() {
			var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
			return !~text.indexOf(val);
		}).hide();
	}
	//--------------------------------------------------


	function resolve_with_ajax(ip_to_resolve) {
		var url = "./ap_services.php";
		$.ajax(
			url,
			{
				type: 'post',
				dataType: 'json',
				data: {
					resolve: ip_to_resolve,
					},
				complete: resolve_ip_callback
			});
	}

	function resolve_ip_callback(transport) {
		var response = $.parseJSON(transport.responseText);
		var msg = 'IP address "' + response.resolve_ip + '" resolves to\n';
		alert(msg + 'host "' + htmlspecialchars(response.resolve_text) + '"');
	}

	function geoip_with_ajax(ip_to_check) {
		var url = "./ap_services.php";
		$.ajax(
			url,
			{
				type: 'post',
				dataType: 'json',
				data: {
					geoip: ip_to_check,
					},
				complete: geoip_callback
			});
	}

	function geoip_callback(transport) {
		var response = $.parseJSON(transport.responseText);
		alert(htmlspecialchars(response.geoip_text));
	}

	// From http://stackoverflow.com/questions/5499078/fastest-method-to-escape-html-tags-as-html-entities
	function htmlspecialchars(str) {
		return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
	}

	//Ajax fields and tables ACP Dashboard
	function update_count_malwares() {
		returnDropMenuOpen();
		if (dropDownShow) {
			$.ajax({
				data: {
					interface: document.getElementById("interface_acp_target").value,
					service: "update_count_malwares"
				},
				method: "POST",
				url: "./ajax_dash_ap_services.php",
				dataType: "json"
			}).done(function(data) {
				$("#trojan_count").html(data.trojan);
				$("#ransonware_count").html(data.ransonware);
				$("#phishing_count").html(data.phishing);
				$("#warning_count").html(data.warning);
				$("#exploit_count").html(data.exploit);
				$("#malware_count").html(data.malware);
			});
		}
		returnDropMenuOpen();
	}

	function update_count_table() {
		returnDropMenuOpen();
		if (dropDownShow) {
			$.ajax({
				data: {
					interface: document.getElementById("interface_acp_target").value,
					service: "update_count_table"
				},
				method: "POST",
				url: "./ajax_dash_ap_services.php",
				dataType: "json"
			}).done(function(data) {
				$("#qtd_rank1").html(data.qtd_rank1);
				$("#country_rank1").html(data.country_rank1);
				$("#rank2").html(data.rank2);
				$("#country_rank2").html(data.country_rank2);
				$("#rank3").html(data.rank3);
				$("#country_rank3").html(data.country_rank3);
			});
		}
		returnDropMenuOpen();
	}

	function update_count_threads_acp() {
		returnDropMenuOpen();
		if (dropDownShow) {
			$.ajax({
				data: {
					interface: $("#interface_acp_target").val(),
					service: "update_count_threads_acp"
				},
				method: "POST",
				url: "./ajax_dash_ap_services.php",
				dataType: "json"
			}).done(function(data) {
				$("#access_ameacas_geral").html(data.access_ameacas_geral);
				$("#access_ram").html(data.access_ram);
				$("#access_nav").html(data.access_nav);
				$("#access_soc").html(data.access_soc);
			});
		}
		returnDropMenuOpen();
	}

	function update_table_strutuct_geo() {
		returnDropMenuOpen();
		if (dropDownShow) {
			$.ajax({
				data: {
					interface: document.getElementById("interface_acp_target").value,
					service: "update_table_strutuct_geo"
				},
				method: "POST",
				url: "./ajax_dash_ap_services.php",
				dataType: "html"
			}).done(function(data) {
				$("#update_table_strutuct_geo").html(data);
			});
		}
	}

	function update_table_strutuct_files() {
		returnDropMenuOpen();
		if (dropDownShow) {
			$.ajax({
				data: {
					interface: document.getElementById("interface_acp_target").value,
					service: "update_table_strutuct_files"
				},
				method: "POST",
				url: "./ajax_dash_ap_services.php",
				dataType: "html"
			}).done(function(data) {
				$("#table-file-rules-geral").html(data);
				searchDataFile();
				showSelectColumnsFiles();
				document.getElementById("updateColumnDataFiles").click();	
			});
		}
		returnDropMenuOpen();
	}

	function update_table_strutuct_alertlog() {
		returnDropMenuOpen();
		if (dropDownShow) {
			$.ajax({
				data: {
					interface: document.getElementById("interface_acp_target").value,
					service: "update_table_strutuct_alertlog"
				},
				method: "POST",
				url: "./ajax_dash_ap_services.php",
				dataType: "html"
			}).done(function(data) {
				$("#table-file-rules-block").html(data);
				searchDataBlock();
				document.getElementById("updateColumnDataAlert").click();	
			});
		}
		returnDropMenuOpen();
	}

	function update_select_table_strutuct_alertlog() {
		returnDropMenuOpen();
		if (dropDownShow) {
			$.ajax({
				data: {
					interface: document.getElementById("interface_acp_target").value,
					service: "update_select_table_strutuct_alertlog"
				},
				method: "POST",
				url: "./ajax_dash_ap_services.php",
				dataType: "html"
			}).done(function(data) {
				$("#filterStatusClassification").html(data);
			});
		}
		returnDropMenuOpen();
	}

	function update_cards_strutuct() {
		returnDropMenuOpen();
		if (dropDownShow) {
			$.ajax({
				data: {
					interface: document.getElementById("interface_acp_target").value,
					service: "update_cards_strutuct"
				},
				method: "POST",
				url: "./ajax_dash_ap_services.php",
				dataType: "html"
			}).done(function(data) {
				$("#startTransformation").html(data);
				var runningBax = $('.bxslider').bxSlider({
					mode: 'vertical',
					moveSlides: 12, //7
					slideMargin: 10, //5
					infiniteLoop: true,
					minSlides: 8, //4
					maxSlides: 15, //10
					speed: 510,
					auto:true
				});
				$('#stopTransformation').mouseover(function() {
					runningBax.stopAuto();
				});
				$('#modal_ativa3').mouseover(function() {
					runningBax.stopAuto();
				});
				$('#startTransformation').mouseleave(function() {
					runningBax.startAuto();
				});
			});
		}
		returnDropMenuOpen();
	}

	function setHashList($inputValue) {
		$("#search-rule-for-list").val($inputValue);
		updateScreenListRulesClamad();
	}


	//--------------------------------------------------
	//Filter line for type classification
	
	function showSelectColumnsFiles() {
		if ($("#filterStatusFiles").val() != "") {
			for (var counterTypes=0;counterTypes <= $("#filterStatusFiles")[0].options.length-1;counterTypes++) {
				var nameClass = $("#filterStatusFiles")[0].options[counterTypes].value;
				if ($("#filterStatusFiles").val() != nameClass && nameClass.length > 0) {
					var target = "#table-file-rules-geral tr[name=" + nameClass + "]";
					$(target).css("display", "none");
				}
			}
		}
	}

	function showSelectColumnsAlerts() {
		if ($("#filterStatusClassification").val() != "") {
			for (var counterTypes=0;counterTypes <= $("#filterStatusClassification")[0].options.length-1;counterTypes++) {
				var nameClass = $("#filterStatusClassification")[0].options[counterTypes].value;
				if ($("#filterStatusClassification").val() != nameClass && nameClass.length > 0) {
					var target = "#table-file-rules-block tr[name=" + nameClass + "]";
					$(target).css("display", "none");
				}
			}
		}
	}


	//--------------------------------------------------
	// Ajax requests rules
	function updateScreenListRulesClamad() {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Search rule and your state... please a moment.')?>");
		$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/spinner.gif');
		setTimeout(() => {
			$('#modal_ativa').modal("show");		
		}, 100);
		setTimeout(() => {
			$.ajax(
				"./av/ajax_update_screen_clamav.php",
				{
					type: 'post',
					data: {
						find_values_files: $("#search-rule-for-list").val()
					},
				}).done(function(data) {
					$('#modal_ativa').modal("hide");
					$("#table-file-rules-lists").html(data);
					$("#changeTitleHash").html("<?=gettext("STATUS HASH")?>" + ": " + $("#search-rule-for-list").val());
					$('#modal_hash_white_black').modal("show");
				}
			);	
		}, 300);

	}
	//--------------------------------------------------

	function saveListsBlackWhite() {
		if ($("#modificationStatusOfRule").val() != "") {
			$("#modal_hash_white_black").modal("hide");
			$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext('Saving State Rule...')?>");
			$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../../images/spinner.gif');
			setTimeout(() => {
				$('#modal_ativa').modal("show");		
			}, 100);
			setTimeout(() => {
				$.ajax(
					"./ap_services.php",
					{
						type: 'post',
						data: {
							modification_status_of_rule: $("#modificationStatusOfRule").val()
						},
					}).done(function(data) {
						$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Saved State Rule...')?>");
						$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/update_rules_ok.png');
					}
				);
			}, 300);
		
			<?php if (intval(getStatusNewAcp()) >= 1) : ?>
				
				setTimeout(() => {
					$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Apply in Active Protection...')?>");
					$('#modal_ativa .txt_modal_ativa_msg').text("");
					$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/spinner.gif');
				}, 2000);

				setTimeout(() => {
					$.ajax({
						url: './update_interfaces_rules.php',
					});
				}, 2100);
		
				setTimeout(() => {
					$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Operation performed successfully!")?>");
					$('#modal_ativa #loader_modal_ativa').attr('src', '../images/update_rules_ok.png');	
				}, 10000);
				setTimeout(() => {
					$('#modal_ativa').modal('hide');
				}, 12000);
				setTimeout(() => {
					window.location.reload();
				}, 13100);

			<?php else: ?>

				setTimeout(() => {
					$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Operation performed successfully!")?>");
					$('#modal_ativa #loader_modal_ativa').attr('src', '../images/update_rules_ok.png');	
				}, 5000);
				setTimeout(() => {
					$('#modal_ativa').modal('hide');
				}, 6000);
				setTimeout(() => {
					window.location.reload();
				}, 7100);

			<?php endif; ?>


		}
	}

	function setValueOfListBlackWhite(value_sha_now) {
	$("#modificationStatusOfRule").val(value_sha_now);
	if ($("#modificationStatusOfRule").val().split("__")[0] == "black") {
		$(".btn.btn-danger.list.btn-disabled").removeAttr("class").addClass("btn btn-danger list");
		$(".btn.btn-primary.list").removeAttr("class").addClass("btn btn-primary list btn-disabled");
		$(".btn.btn-warning.list").removeAttr("class").addClass("btn btn-warning list btn-disabled");
	} else if ($("#modificationStatusOfRule").val().split("__")[0] == "white") {
		$(".btn.btn-primary.list.btn-disabled").removeAttr("class").addClass("btn btn-primary list");
		$(".btn.btn-danger.list").removeAttr("class").addClass("btn btn-danger list btn-disabled");
		$(".btn.btn-warning.list").removeAttr("class").addClass("btn btn-warning list btn-disabled");
	} else if ($("#modificationStatusOfRule").val().split("__")[0] == "exception") {
		$(".btn.btn-warning.list.btn-disabled").removeAttr("class").addClass("btn btn-warning list");
		$(".btn.btn-primary.list").removeAttr("class").addClass("btn btn-primary list btn-disabled");
		$(".btn.btn-danger.list").removeAttr("class").addClass("btn btn-danger list btn-disabled");
	} else {
		$(".btn.btn-danger.list.btn-disabled").removeAttr("class").addClass("btn btn-danger list");
		$(".btn.btn-primary.list").removeAttr("class").addClass("btn btn-primary list btn-disabled");
		$(".btn.btn-warning.list").removeAttr("class").addClass("btn btn-warning list btn-disabled");
	}
}
</script>
