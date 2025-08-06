<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Guilherme R.Brechot<guilherme.brechot@bluepex.com>, 2022
 *
 * ====================================================================
 *
 */

require_once('guiconfig.inc');
require_once('functions.inc');
require_once('notices.inc');
require_once("pkg-utils.inc");
require_once("config.inc");
require_once("bp_webservice.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("util.inc");
require_once("firewallapp_functions.inc");

require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("/usr/local/pkg/suricata/suricata_acp.inc");

require_once("services.inc");

if(!file_exists('/etc/model')) {
	header("Location: ../ap_services.php");
} else {
	$modelFile = file_get_contents('/etc/model');
	if (strlen($modelFile) >= 5) {
		$modelFile = explode(" ", $modelFile)[1];
		if (intval($modelFile) < 3000) {
			header("Location: ../ap_services.php");
		}
	} else {
		header("Location: ../ap_services.php");
	}
}

if (!file_exists('/etc/monitor_gateway_files_clamd')) {
	file_put_contents('/etc/monitor_gateway_files_clamd', 'false');
}

if (!file_exists('/etc/monitor_gateway_files_yara')) {
	file_put_contents('/etc/monitor_gateway_files_yara', 'false');
}

if (!is_array($config['installedpackages']['suricata']['rule'])) {
	$config['installedpackages']['suricata']['rule'] = array();
}

$a_nat = $config['installedpackages']['suricata']['rule'];
$id_gen = count($config['installedpackages']['suricata']['rule']);

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

if (file_exists("/usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules")) {
	mwexec("/bin/sh /etc/mergeListsOfACP.sh"); 	
}

if (!file_exists("/usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules")) {
	mwexec("cp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules");
}

if (file_exists('/tmp/sedChangeLine')) {
	unlink('/tmp/sedChangeLine');
}

if (isset($_POST['startStopHourService']) && strlen($_POST['startStopHourService']) > 0 && isset($_POST['endStopHourService']) && strlen($_POST['endStopHourService']) > 0) {
	file_put_contents("/etc/stopFileGateway", $_POST['startStopHourService'] . "___" . $_POST['endStopHourService']);
	$serviceSimpleOperationStop = ($_POST['serviceSimpleOperationStop'] == 'true') ? 'simple' : '';
	$serviceAdvancedOperationStop = ($_POST['serviceAdvancedOperationStop'] == 'true') ? 'advanced' : '';
	file_put_contents("/etc/servicesStopFileGateway",  $serviceSimpleOperationStop . "___" . $serviceAdvancedOperationStop);	
}

if ($_POST['cleanServicesOperationStop']) {
	if (file_exists('/etc/stopFileGateway')) {
		unlink('/etc/stopFileGateway');
	}
	if (file_exists('/etc/servicesStopFileGateway')) {
		unlink('/etc/servicesStopFileGateway');
	}
} 

$reloadRules = false;

if ($_POST['start_clamd']) {

	$reloadRules = true;

	//Zero state 
	$valuesZeroState = explode("\n", shell_exec("grep -rhn 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep filesha256 | grep -v '#drop'"));
	$protocolsOperation = ['http'];#, 'smb'];
	foreach($valuesZeroState as $zeroStateNow) {
		if (!empty($zeroStateNow)) {
			foreach ($protocolsOperation as $protocolLine) {
				$zeroStateNow = explode(":", $zeroStateNow)[0];
				file_put_contents("/tmp/sedChangeLine", "/usr/bin/sed -E '{$zeroStateNow}s/drop {$protocolLine} /#drop {$protocolLine} /' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules > /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp\n");
				file_put_contents("/tmp/sedChangeLine", "mv /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules\n", FILE_APPEND);
			}
		}
	}
	mwexec("/bin/sh /tmp/sedChangeLine && /bin/rm /tmp/sedChangeLine");
	mwexec("cp /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules /usr/local/share/suricata/rules_acp/_ameacas_ext.rules");		

	//Apply only rules if not exists hashtag
	if (file_exists('/tmp/sedChangeLine')) {
		unlink('/tmp/sedChangeLine');
	}
	if (!empty($_POST['select_mode'])) {
		foreach (array_filter(array_unique(explode("___", $_POST['select_mode']))) as $operationMode) {
			if (!empty($operationMode)) {
				foreach ($protocolsOperation as $protocolLine) {
					$valuesReturn = explode("\n", shell_exec("grep -rhn 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep filesha256 | grep '#drop {$protocolLine}' | grep 'fileext:\"{$operationMode}\"'"));
					foreach ($valuesReturn as $valuesReturnNow) {
						$lineChange = explode(":#", $valuesReturnNow)[0];
						if (!empty($lineChange)) {
							file_put_contents("/tmp/sedChangeLine", "/usr/bin/sed -E '{$lineChange}s/#drop {$protocolLine} /drop {$protocolLine} /' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules > /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp\n", FILE_APPEND);
							file_put_contents("/tmp/sedChangeLine", "mv /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules\n", FILE_APPEND);
						}
					}
				}
			}
		}
	}
	mwexec("/bin/sh /tmp/sedChangeLine && /bin/rm /tmp/sedChangeLine");
	mwexec("cp /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules /usr/local/share/suricata/rules_acp/_ameacas_ext.rules");

	//Start clamscan and change rules suricata
	if (file_exists('/tmp/sedChangeLine')) {
		unlink('/tmp/sedChangeLine');
	}

	#$commentedRulesOperation = [4100010, 4100009, 4100008, 4100007, 4100006, 4100005, 4100004, 4100003];
	$commentedRulesOperation = [4100003, 4100005, 4100007, 4100009];
	if ($_POST['start_clamd'] == "true") {
		mwexec("cp /usr/local/www/active_protection/av/clamd.conf /usr/local/etc/");
		mwexec("cp /usr/local/www/active_protection/av/freshclam.conf /usr/local/etc/");
		foreach ($commentedRulesOperation as $commentedRulesOperationLine) {
			if (intval(trim(shell_exec("grep 'sid:{$commentedRulesOperationLine}' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules -c"))) > 0) {
				foreach ($protocolsOperation as $protocolLine) {
					$valuesReturn = explode(":", shell_exec("grep -n 'sid:{$commentedRulesOperationLine}' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules"))[0];
					file_put_contents("/tmp/sedChangeLine", "/usr/bin/sed -E '{$valuesReturn}s/#?drop {$protocolLine} /drop {$protocolLine} /' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules > /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp\n", FILE_APPEND);
					file_put_contents("/tmp/sedChangeLine", "mv /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules\n", FILE_APPEND);
				}
			}
		}
	} else {
		$valuesReturn = explode(":", shell_exec("grep -n 'sid:{$commentedRulesOperationLine}' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules"))[0];
		foreach ($commentedRulesOperation as $commentedRulesOperationLine) {
			if (intval(trim(shell_exec("grep 'sid:{$commentedRulesOperationLine}' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules -c"))) > 0) {
				foreach ($protocolsOperation as $protocolLine) {
					$valuesReturn = explode(":", shell_exec("grep -n 'sid:{$commentedRulesOperationLine}' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules"))[0];			
					file_put_contents("/tmp/sedChangeLine", "/usr/bin/sed -E '{$valuesReturn}s/#?drop {$protocolLine} /#drop {$protocolLine} /' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules > /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp\n", FILE_APPEND);
					file_put_contents("/tmp/sedChangeLine", "mv /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules\n", FILE_APPEND);
				}
			}
		}
	}

	mwexec("/bin/sh /tmp/sedChangeLine && /bin/rm /tmp/sedChangeLine");
	mwexec("cp /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules /usr/local/share/suricata/rules_acp/_ameacas_ext.rules");

	//Start clamscan and change rules suricata
	if (file_exists('/tmp/sedChangeLine')) {
		unlink('/tmp/sedChangeLine');
	}

	if (getInterfaceNewAcp() >= 1) {
		file_put_contents("/etc/monitor_gateway_files_clamd", $_POST['start_clamd']);
	}
}

if ($_POST['start_yara']) {
	if (!file_exists('/usr/local/www/active_protection/av/YARA/enable_yara_filters')) {
		if (intval(trim(shell_exec("/bin/ls /usr/local/www/active_protection/av/YARA/ | grep -E 'index' -c"))) > 0) {
			$enableFilter = array_filter(explode("\n", trim(shell_exec("/bin/ls /usr/local/www/active_protection/av/YARA/ | grep -E 'index'"))));
			file_put_contents('/usr/local/www/active_protection/av/YARA/enable_yara_filters', implode(";", $enableFilter));
		}
	}
	if (getInterfaceNewAcp() >= 1) {
		file_put_contents("/etc/monitor_gateway_files_yara", $_POST['start_yara']);
	} else {
		file_put_contents("/etc/monitor_gateway_files_yara", "false");
	}
}

if ($_POST['start_yara_simple']) {
	if (!file_exists('/usr/local/www/active_protection/av/YARA/enable_yara_filters')) {
		if (intval(trim(shell_exec("/bin/ls /usr/local/www/active_protection/av/YARA/ | grep -E 'index' -c"))) > 0) {
			$enableFilter = array_filter(explode("\n", trim(shell_exec("/bin/ls /usr/local/www/active_protection/av/YARA/ | grep -E 'index'"))));
			file_put_contents('/usr/local/www/active_protection/av/YARA/enable_yara_filters', implode(";", $enableFilter));
		}
	}
	if (!empty($_POST['select_mode_simple'])) {
		$filters = [];
		foreach(explode("___", $_POST['select_mode_simple']) as $filterLine) {
			$filters[] = str_replace("index_yar", "index.yar", $filterLine);
		}
		file_put_contents('/usr/local/www/active_protection/av/YARA/enable_yara_filters', implode(";",array_filter(array_unique($filters))));
	} else {
		file_put_contents('/usr/local/www/active_protection/av/YARA/enable_yara_filters', '');
	}
 	if (getInterfaceNewAcp() >= 1) {
		file_put_contents("/etc/monitor_gateway_files_yara", $_POST['start_yara_simple']);
	} else {
		file_put_contents("/etc/monitor_gateway_files_yara", "false");
	}
}

if ($_POST['modification_status_of_rule']) {	
	
	$values_operation =  explode("__", $_POST['modification_status_of_rule']);
	$operation = $values_operation[0];
	$value = $values_operation[1];
	
	if (file_exists('/tmp/sedChangeLine')) {
		unlink('/tmp/sedChangeLine');
	}
	
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
			mwexec("sort /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt | uniq | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt.tmp"); 
			mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt");
			$values_blacklist_file_custom = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt'))));
		}
		$values_whitelist_file_custom = [];
		if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt')) {
			mwexec("sort /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt | uniq | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt.tmp");
			mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt");
			$values_whitelist_file_custom = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt'))));
		}
		$values_blacklist_file = [];
		if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt')) {
			mwexec("sort /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt | uniq | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp");
			mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt");
			$values_blacklist_file = array_filter(array_unique(explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt'))));
		}
		$values_whitelist_file = [];
		if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt')) {
			mwexec("sort /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt | uniq | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp");
			mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt");
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

		mwexec("sort /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt | uniq | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp");
		mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt");
		
		mwexec("sort /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt | uniq | grep -v '^$' > /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp");
		mwexec("mv /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt");

		mwexec("cp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt");
		mwexec("cp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt");

	} else {
		if (!file_exists("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt")) {
			mwexec("cp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256_custom.txt");
		}
		if (!file_exists("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt")) {
			mwexec("cp /usr/local/share/suricata/otx/ransomd5/clamav_whilelist_256.txt /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256_custom.txt");
		}
	}

	die;
}

$pgtitle = array(gettext("Active Protection"), gettext("File Parsing Gateway"), gettext("Status Files Lists"));
$pglinks = array("./active_protection/ap_services.php", "./active_protection/av/services_files_gateway.php", "@self");
include_once("head.inc");

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

	.bg-success {
		background-color: #54ca8b !important;
	}

	@media only screen and (max-width: 1200px) {
		.origins-block  {
			padding-right: 15px !important;
			margin-bottom: 10px !important;
		}
		.ml-md-5, .mx-md-5 {
			margin-left: 0px !important; */
		}
	}
	@media (min-width: 992px) {
		#selectModeOperationAdvanced > .modal-dialog {
			width: 95% !important;
		    margin: 1.75rem auto;
			max-width: unset;
		}
		.ml-md-5, .mx-md-5 {
			margin-left: 0px !important; */
		}
	}
	@media (min-width: 768px) {
		.ml-md-5, .mx-md-5 {
			margin-left: 0px !important; */
		}
	}
	@media (min-width: 576px) {
		#selectModeOperationAdvanced > .modal-dialog {
			width: 95% !important;
		    margin: 1.75rem auto;
			max-width: unset;
		}
		.ml-md-5, .mx-md-5 {
			margin-left: 0px !important; */
		}
	}
	@media (max-width: 575px) {
		#selectModeOperationAdvanced > .modal-dialog {
			width: 95% !important;
		    margin: 1.75rem auto;
			max-width: unset;
		}
		.ml-md-5, .mx-md-5 {
			margin-left: 0px !important; */
		}
	}
	select.form-control:not([size]):not([multiple]) {
		height: unset;
		text-align: center;
	}
</style>
<?php
if ($savemsg)
	print_info_box(gettext($savemsg), 'success');

if ($errormsg)
	print_info_box(gettext($errormsg), 'danger');

?>
<hr style="border: 1px solid #c5c5c5;padding-right: 10px;padding-left: 10px;margin-bottom: 15px;">
<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
        <div class="pull-left">
			<p>File Parsing Gateway is an application for analyzing files traveling within the network.</p>
			<br>
			<p>Application notes:</p>
			<ul>
				<li>It only works if there is an Active Protection interface in operation;</li>
				<li>All hash's presented are based on user traffic or external rules already added;</li>
				<li>The hash's can be used to block, release or ignore the traffic of a certain file;</li>
				<li>The only limited state of use is the exception mode, for its use it is necessary to temporarily store the file, this occurs temporarily within the time of analysis of the file with an average lifetime of 30 minutes;</li>
				<li>The execution of this application is stopped automatically if there is no Active Protection interface in operation;</li>
				<li>If you select a hash to perform a state change and it does not show a change combo, it means that this hash is not present within your system, being automatically loaded by the equipment rules;</li>
			</ul>
			<hr>
			<p>This icon <i class="fa fa-search"> </i> serves both to autocomplete the search field and to load the state change combo;</p>
			<p>This icon <i class="fa fa-info"> </i> only shows the complete information of the field in question;</p>
			<hr>
			<p style='color:red;'>OBS: The state of Sime-ScanV is that there are extensions selected by the advanced mode in active, however, the ScanV and/or Simple services are disabled, if you want to avoid the appearance of this mode, disable the selected extensions.</p>	
		</div>
	</div>
</div>

<form action="./listAllfiles.php" method="POST" style="display:none;" id="startScanValues">
	<input type="hidden" value="" name="start_clamd" id="start_clamd">
	<input type="hidden" value="" name="start_yara" id="start_yara">
	<input type="hidden" value="" name="select_mode" id="select_mode">
</form>

<form action="./listAllfiles.php" method="POST" style="display:none;" id="startScanValuesSimple">
	<input type="hidden" value="" name="start_yara_simple" id="start_yara_simple">
	<input type="hidden" value="" name="select_mode_simple" id="select_mode_simple">
</form>

<!--<form action="./services_files_gateway.php" method="POST" style="margin: unset;border: unset;padding: unset;" id="barStatusFilesGateway">-->
<div style="margin: unset;border: unset;padding: unset;" id="barStatusFilesGateway">
	<div class="col-12 bg-danger py-3 color-white" id="status-class-bar">
		<div class="row">
			<div class="col-12 col-md-9">
				<div class="row" id="status-gateway-files">
					<h6 for="service_status" class="mb-3 mb-sm-0 mb-md-0 mt-0 pt-2"><i id="buton_status" class="" aria-hidden="true"></i> <span class="mx-2"><?=gettext("Status")?>: </span> <span id="status-info" style="color:white"> </span></span></h6>
					<div id="id_status_click_files"></div>
				</div>
			</div>
			<div class="col-12 col-md-3 mt-2 mt-md-0 d-md-flex justify-content-end">
				<button id="status-button-fapp2" type="button" class="btn btn-success dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <?=gettext("Settings")?>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                	<a href="services_files_gateway.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("File Parsing Gateway")?></a>
					<a href="listAllhashs.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("Listas de hash")?></a>
					<a href="listAllfiles.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("List of files")?></a>
					<a href="analyze_artifact.php" class="dropdown-item"><i class="fa fa-pencil"></i> <?=gettext("Analyze Artifacts")?></a>
					<a onclick="exceptionExecuteFateway()" class="dropdown-item" id="exceptionExecuteFateway" ><i class="fa fa-pencil"></i> <?=gettext("Exception Execute File Gateway")?></a>
					<a onclick="simpledEnableOptationScan()" class="dropdown-item" id="showExtensionNowSimple" ><i class="fa fa-pencil"></i> <?=gettext("Change filter Simple")?></a>
					<a onclick="advancedEnableOptationScan()" class="dropdown-item" id="showExtensionNow" style="display:none;"><i class="fa fa-pencil"></i> <?=gettext("Change filter Advanced")?></a>
					<!--<a onclick="startScanNow()" class="dropdown-item" id="showExecuteNow" style="display:none;"><i class="fa fa-check"></i> <?=gettext("Exec Scan now")?></a>-->
                </div>
			</div>
		</div>
	</div>
</div>
<!--</form>-->

<div class="p-0" style="margin-top:10px!important;">
	<div class="col-12 cards-info">
		<div class="row pb-2 pr-xl-7">
			<div class="col-sm card-app p-3 p-2 mb-2" style="height: 100% !important;">
				<h6 id="changeTitleHash"><?=gettext("FIND LIST HASH")?></h6>
				<hr>
				<div class="col-12 text-center margins-content-bottom">
					<div class="panel panel-default" style="background: transparent !important; margin-top:10px;">
                	    <!--
						<button type="click" onclick="clearSearchListRulesClamad()" class="btn btn-danger form-control find-values" style="width: auto; float:right;"><i class="fa fa-times"></i> </button>
                	    <button type="click" onclick="updateScreenListRulesClamad()" class="btn btn-primary form-control find-values" style="width: auto; float:right;"><i class="fa fa-search"></i> </button>
						-->
						<input type="hidden" class="form-control" style="background-color: #FFF!important; float:right; width:400px;" id="search-rule-for-list" placeholder="<?=gettext("Search for...")?>">	
                	    <div class="panel-body table-responsive" style="background: transparent !important;">
                	        <table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" style="background: transparent !important;" data-sortable>
                	            <thead>
                	            <tr class="sortableHeaderRowIdentifier text-nowrap">
									<th><?=gettext("Filename"); ?></th>
									<th><?=gettext("Protocol"); ?></th>
									<th><?=gettext("Host"); ?></th>
									<th><?=gettext("Requsitante"); ?></th>
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
	</div>
</div>

<input type="hidden" id="modificationStatusOfRule" name="modificationStatusOfRule" value=""> 

<div class="p-0" style="margin-top:10px!important;">
	<div class="col-12 cards-info">
		<div class="row pb-2 pr-xl-7">
			<div class="col-sm card-app p-3 p-2 mb-2" style="height: auto !important;">
				<h6><?=gettext("Lists FIND or LAST FILES")?></h6>
				<hr>
				<div style="display: flex; margin-bottom:10px;">
					<input type="text" class="form-control" style="background-color: #FFF!important; width:400px; margin-left:auto;" id="search_file_value" placeholder="<?=gettext("Search for...")?>">	
					<select class='form-control' style="background-color: #FFF!important; width:100px;" id="selectAmountShowEve">
						<option value="10">10</option>
						<option value="25">25</option>
						<option value="50">50</option>
						<option value="100">100</option>
					</select>
					<button type="click" onclick="clearSearchDataFile()" class="btn btn-danger form-control find-values" style="width: auto;"><i class="fa fa-times"></i> </button>							
				</div>				
				<div style="display: flex">
				    <div class="panel-body table-responsive" style="background: transparent !important;margin:auto;margin-top:0px;height:300px;">
						<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" style="background: transparent !important;" data-sortable>
							<thead>
                	            <tr class="sortableHeaderRowIdentifier text-nowrap">
									<th><?=gettext("Filename"); ?></th>
									<th><?=gettext("Protocol"); ?></th>
									<th><?=gettext("Host"); ?></th>
									<th><?=gettext("Requsitante"); ?></th>
									<th><?=gettext("Hash's"); ?></th>
                	            </tr>
                	        </thead>
                	        <tbody id="table-file-eve-lists">
								<tr>
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
</div>


<!-- Modal Ativa -->
<div class="modal fade" id="modal_ativa" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel2" aria-hidden="true">
	<div class="modal-dialog" role="document" style="margin:auto!important;margin-top:20px!important;">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_ativa" style="color:#007DC5"></h3>
				<p class="txt_modal_ativa_msg" style="color:#007DC5"></p>
				<br>
				<img id="loader_modal_ativa" src="../../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<form action="../services_acp_rules.php" method="POST" style="border: 0px solid transparent;display: none;" id="submitSearchRule">
	<input type="hidden" id="searchSIDRules" name="searchSIDRules" value=""> 
</form>			
<form action="../services_acp_ameacas.php" method="POST" style="border: 0px solid transparent;display: none;" id="submitSearchThread">
	<input type="hidden" id="searchSIDThreads" name="searchSIDThreads" value=""> 
</form>

<div id="selectModeOperation" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title"><?=gettext("Selecione o modo de operação")?></h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<p>Selecione o modo de operação que deseja aplicar sobre o filtros dos arquivos de Download:</p>
				<ul>
					<li>Modo Simples - Este método realizada a análise dos arquivos sobre demanda, com periodicidade curta e análises mais simples;</li>
					<li>Modo Simples (Com filtros) - Este método funciona exatamente como o modelo acima, porém você pode selecionar os filtros a se operar na ação;</li>
					<li>Modo ScanV - Este método realizada a análise dos arquivos sobre demanda, porém, diferente do primeiro, a periodicidade é maior, por aplicar uma base diferente de regras de análise;</li>
					<li>Modo Scan Total - Este método aplica os dois últimos listados em conjunto, melhorando a filtragem e segurança da rede;</li>
					<li>Modo Avançado - Este método tem o mesmo funcionamento do Scan Total, com a adição de restrição sobre o download de determinadas extensões selecionadas préviavelmente, necessitando liberar o acesso aos arquivos de extensões do tipo selecionado caso deseje baixar o mesmo;</li>
				</ul>
				<p style="color:red;">OBS:</p>
				<p style="color:red;"> * As extensões ativadas no modo avançado não iram afetar interfaces ativada com o modo de "proteção extra";</p>
				<p style="color:red;"> * O filtro do modo simples se utiliza de todas as opções disponíveis para operar de forma padrão e somente necessita de 1 filtro paa operar, caso não exista nenhum filtro marcado, a ação de análise simples não será realizada;</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button class="btn btn-primary" type="click" onclick="simpleEnableOptationScanYara()"><i class="fa fa-check"></i> <?=gettext("Simples")?> </button>
				<button class="btn btn-primary" type="click" onclick="simpledEnableOptationScan()"><i class="fa fa-check"></i> <?=gettext("Simples")?> (Com filtros)</button>
				<button class="btn btn-primary" type="click" onclick="simpleEnableOptationScanClamd()"><i class="fa fa-check"></i> <?=gettext("ScanV")?> </button>
				<button class="btn btn-primary" type="click" onclick="simpleEnableOptationScanDetails()"><i class="fa fa-check"></i> <?=gettext("Scan Total")?> </button>
				<button class="btn btn-warning" type="click" onclick="advancedEnableOptationScan()"><i class="fa fa-check"></i> <?=gettext("Advanced")?> </button>
			</div>
		</div>
	</div>
</div>


<?php
$hourReload = array(
	'00:00:00' => '00:00:00 (00:00 AM)',
	'01:00:00' => '01:00:00 (01:00 AM)',
	'02:00:00' => '02:00:00 (02:00 AM)',
	'03:00:00' => '03:00:00 (03:00 AM)',
	'04:00:00' => '04:00:00 (04:00 AM)',
	'05:00:00' => '05:00:00 (05:00 AM)',
	'06:00:00' => '06:00:00 (06:00 AM)',
	'07:00:00' => '07:00:00 (07:00 AM)',
	'08:00:00' => '08:00:00 (08:00 AM)',
	'09:00:00' => '09:00:00 (09:00 AM)',
	'10:00:00' => '10:00:00 (10:00 AM)',
	'11:00:00' => '11:00:00 (11:00 AM)',
	'12:00:00' => '12:00:00 (12:00 AM)',
	'13:00:00' => '13:00:00 (01:00 PM)',
	'14:00:00' => '14:00:00 (02:00 PM)',
	'15:00:00' => '15:00:00 (03:00 PM)',
	'16:00:00' => '16:00:00 (04:00 PM)',
	'17:00:00' => '17:00:00 (05:00 PM)',
	'18:00:00' => '18:00:00 (06:00 PM)',
	'19:00:00' => '19:00:00 (07:00 PM)',
	'20:00:00' => '20:00:00 (08:00 PM)',
	'21:00:00' => '21:00:00 (09:00 PM)',
	'22:00:00' => '22:00:00 (10:00 PM)',
	'23:00:00' => '23:00:00 (11:00 PM)'
);
?>

<div id="exceptionExecuteFatewayModal" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title"><?=gettext("Selecione o modo de operação")?></h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">

				<p>Selecione o periodo a qual o serviço de proteção não será executado:</p>

				<label for="startStopService">Serviço não será executado a partir desta hora:</label>
				<select class="form-control" id="startStopService">
					<?php foreach($hourReload as $key => $hourNow) { ?>
						<option value="<?=$key?>"><?=$hourNow?></option>
					<?php } ?>
				</select>

				<label for="endStopService">Serviço voltará a ser executado a partir desta hora:</label>
				<select class="form-control" id="endStopService">
					<?php foreach($hourReload as $key => $hourNow) { ?>
						<option value="<?=$key?>"><?=$hourNow?></option>
					<?php } ?>
				</select>

				<hr>
				<p>Selecione o serviços a qual deseja parar durante este periodo selecionado:</p>
				<button class='btn btn-primary' style='margin: 5px; border-radius:5px; text-transform: uppercase;' id='simpleStopTemp' onclick="stopActionFileGateway('simpleStopTemp', 'simple')">Simples</button>
				<button class='btn btn-primary' style='margin: 5px; border-radius:5px; text-transform: uppercase;' id='advancedStopTemp' onclick="stopActionFileGateway('advancedStopTemp', 'advanced')">Avançado</button>
				<hr>

				<p style="color:red;">OBS: 
				<ul style="color:red;">
					<li>Esta ação irá parar a ação dos serviços selecionados pela quantidade de tempo definido acima, o serviço em si ainda permanece ativado.</li>
					<li>O botão de 'Limpar estado' irá limpar qualquer configuração do tipo já setada, os campos serão retornados ao estado padrão.</li>
				</ul>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button class="btn btn-primary" type="click" onclick="saveCleanStopService()"><i class="fa fa-check"></i> <?=gettext("Limpar ação")?> </button>
				<button class="btn btn-success" type="click" onclick="saveTimeStopServices()"><i class="fa fa-check"></i> <?=gettext("Save")?> </button>
			</div>
		</div>
	</div>
</div>


<form action="./listAllfiles.php" method="POST" style="display:none;" id="saveTimeStopServices">
	<input type="hidden" name="startStopHourService" id="startStopHourService" value="">
	<input type="hidden" name="endStopHourService" id="endStopHourService" value="">
	<input type="hidden" name="serviceSimpleOperationStop" id="serviceSimpleOperationStop" value="">
	<input type="hidden" name="serviceAdvancedOperationStop" id="serviceAdvancedOperationStop" value="">
</form>

<form action="./listAllfiles.php" method="POST" style="display:none;" id="saveCleanStopService">
	<input type="hidden" name="cleanServicesOperationStop" id="cleanServicesOperationStop">
</form>

<?php
#$extensionsScanRules = array_filter(array_unique(explode("\n", shell_exec("grep -rh \"ACP-BLOCK-EXTENSION-HEURISTIC-\" /usr/local/share/suricata/rules_acp/_ameacas_ext.rules | grep filestore"))));
$extensionsScanRules = array_filter(array_unique(explode("\n", shell_exec("grep -rh \"ACP-BLOCK-EXTENSION-HEURISTIC-\" /usr/local/share/suricata/rules_acp/_ameacas_ext.rules | grep filestore | grep filesha256"))));
$setAlreadyValues = "";
$allExtensionsFind = [];
?>
<div id="selectModeOperationAdvanced" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title">Filtro avançado</h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>

			<div class="modal-body" style="text-align:center">
				<p style="margin-bottom:10px;">Selecione as extensões que deseja bloquear automaticamente:</p>
				<div>
				<?php
				foreach ($extensionsScanRules as $extensionsScanRule) {
					$extensionsNow = explode("\"; ", explode("fileext:\"", $extensionsScanRule)[1])[0];
					if (!in_array($extensionsNow, $allExtensionsFind)) {
						$allExtensionsFind[] = $extensionsNow;
						if (strpos($extensionsScanRule, "#drop") === false) {
							?>
								<button class='btn btn-success scanSelect' style='margin: 5px; border-radius:5px; text-transform: uppercase;' id='<?=$extensionsNow?>' name='<?=$extensionsNow?>' onclick="disabledSelectValueOperationScan('<?=$extensionsNow?>')">.<?=$extensionsNow?></button>
							<?php
							$setAlreadyValues .= "___" . $extensionsNow;
						} else {
							?>
								<button class='btn btn-primary scanSelect' style='margin: 5px; border-radius:5px; text-transform: uppercase;' id='<?=$extensionsNow?>' name='<?=$extensionsNow?>' onclick="selectValueOperationScan('<?=$extensionsNow?>')">.<?=$extensionsNow?></button>
							<?php
						}
					}
				}
				?>
				</div>
				<p style="margin-top:10px;color:red;">OBS:</p>
				<p style="margin-top:10px;color:red;">Re-lembrando que o Download de arquivos dessas extensões serão bloqueados automáticamente, necessitando a liberação pela WhiteList para realizar o Download.</p>
				<p style="margin-top:10px;color:red;">Esteja ciente que extensões selecionas não iram afetar interfaces com a "proteção extra" ativada.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button type="button" class="btn btn-warning" onclick="resetSelectOptionScan()"><i class="fa fa-ban"></i> Clear select</button>
				<?php if (count($extensionsScanRules) > 0): ?>
					<button type="button" class="btn btn-secondary" onclick="restoreStateOfRules()"><i class="fa fa-ban"></i> Restore old state</button>
				<?php endif; ?>
				<button type="button" class="btn btn-success" onclick="selectAllSelectOptionScan()"><i class="fa fa-ban"></i> Select All</button>
				<button class="btn btn-primary" type="submit" onclick="advancedEnableOptationScanSetValues()"><i class="fa fa-check"></i> <?=gettext("Save/Apply")?> </button>
			</div>
		</div>
	</div>
</div>

<?php
$enableFilter = [];
if (file_exists('/usr/local/www/active_protection/av/YARA/enable_yara_filters')) {
	$enableFilter = array_filter(array_unique(explode(";", trim(file_get_contents("/usr/local/www/active_protection/av/YARA/enable_yara_filters")))));
} else {
	if (intval(trim(shell_exec("/bin/ls /usr/local/www/active_protection/av/YARA/ | grep -E 'index' -c"))) > 0) {
		$enableFilter = array_filter(explode("\n", trim(shell_exec("/bin/ls /usr/local/www/active_protection/av/YARA/ | grep -E 'index'"))));
		file_put_contents('/usr/local/www/active_protection/av/YARA/enable_yara_filters', implode(";", $enableFilter));
	}
}
$setAlreadyValuesSimple = "";
?>


<div id="selectModeOperationSimple" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title">Filtro Simples</h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>

			<div class="modal-body" style="text-align:center">
				<p style="margin-bottom:10px;">Selecione os filtros a serem aplicados pela análise simples:</p>
				<div>
				<?php
				foreach (array_filter(explode("\n", trim(shell_exec("/bin/ls /usr/local/www/active_protection/av/YARA/ | grep -E 'index'")))) as $optionsFilter) {
					if (in_array($optionsFilter,$enableFilter)) {
						$optionsFilter = str_replace(".","_", $optionsFilter);
				?>
						<button class='btn btn-success scanSelectSimple' style='margin: 5px; border-radius:5px; text-transform: uppercase;' id='<?=$optionsFilter?>' name='<?=$optionsFilter?>' onclick="disabledSelectValueOperationScanSimple('<?=$optionsFilter?>')"><?=$optionsFilter?></button>
				<?php
						$setAlreadyValuesSimple .= "___" . $optionsFilter;
					} else {
						$optionsFilter = str_replace(".","_", $optionsFilter);
				?>								
						<button class='btn btn-primary scanSelectSimple' style='margin: 5px; border-radius:5px; text-transform: uppercase;' id='<?=$optionsFilter?>' name='<?=$optionsFilter?>' onclick="selectValueOperationScanSimple('<?=$optionsFilter?>')"><?=$optionsFilter?></button>
				<?php 
					}
				}
				?>
				</div>
				<p style="margin-top:10px;color:red;">OBS:</p>
				<p style="margin-top:10px;color:red;">Caso nenhum filtro esteja aplicado, não irá ocorrer a ação de análise;</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button type="button" class="btn btn-warning" onclick="resetSelectOptionScanSimple()"><i class="fa fa-ban"></i> Clear select</button>
				<?php if (count($extensionsScanRules) > 0): ?>
					<button type="button" class="btn btn-secondary" onclick="restoreStateOfRulesSimple()"><i class="fa fa-ban"></i> Restore old state</button>
				<?php endif; ?>
				<button type="button" class="btn btn-success" onclick="selectAllSelectOptionScanSimple()"><i class="fa fa-ban"></i> Select All</button>
				<button class="btn btn-primary" type="submit" onclick="advancedEnableOptationScanSetValuesSimple()"><i class="fa fa-check"></i> <?=gettext("Save/Apply")?> </button>
			</div>
		</div>
	</div>
</div>

<?php include("foot.inc"); ?>

<script type="text/javascript">


<?php 
if (file_exists('/etc/stopFileGateway')) {
	$times = array_filter(explode("___", file_get_contents("/etc/stopFileGateway")));
	if (strlen($times[0]) > 0) {
?>
		$("#startStopService").val('<?=$times[0]?>');
		$("#startStopHourService").val('<?=$times[0]?>');
<?php
	}
	if (strlen($times[1]) > 0) {
?>
		$("#endStopService").val('<?=$times[1]?>');
		$("#endStopHourService").val('<?=$times[1]?>');
<?php
	}
}
if (file_exists('/etc/servicesStopFileGateway')) {
	$services = array_filter(explode("___", file_get_contents("/etc/servicesStopFileGateway")));
	if ($services[0] == 'simple') {
?>
		$('#simpleStopTemp').removeAttr('class').attr('class', 'btn btn-success');
		$('#simpleStopTemp').removeAttr('onclick').attr('onclick', "removeStopActionFileGateway('simpleStopTemp', 'simple')");
		$('#serviceSimpleOperationStop').val("true");

<?php
	}
	if ($services[1] == 'advanced') {
?>
		$('#advancedStopTemp').removeAttr('class').attr('class', 'btn btn-success');
		$('#advancedStopTemp').removeAttr('onclick').attr('onclick', "removeStopActionFileGateway('advancedStopTemp', 'advanced')");
		$('#serviceAdvancedOperationStop').val("true");
<?php
	}
}
?>

function exceptionExecuteFateway() {
	$('#exceptionExecuteFatewayModal').modal('show');
}

function stopActionFileGateway(idTarget, model) {
	$('#' + idTarget).removeAttr('class').attr('class', 'btn btn-success');
	$('#' + idTarget).removeAttr('onclick').attr('onclick', "removeStopActionFileGateway('" + idTarget + "', 'simple')");
	if (model == 'simple') {
		$('#serviceSimpleOperationStop').val('true');
	}
	if (model == 'advanced') {
		$('#serviceAdvancedOperationStop').val('true');
	}

}

function removeStopActionFileGateway(idTarget, model) {
	$('#' + idTarget).removeAttr('class').attr('class', 'btn btn-primary');
	$('#' + idTarget).removeAttr('onclick').attr('onclick', "stopActionFileGateway('" + idTarget + "', '" + model + "')");
	if (model == 'simple') {
		$('#serviceSimpleOperationStop').val('false');
	}
	if (model == 'advanced') {
		$('#serviceAdvancedOperationStop').val('false');
	}

}

function saveTimeStopServices() {
	$("#startStopHourService").val($("#startStopService").val());
	$("#endStopHourService").val($("#endStopService").val());
	if (parseInt($("#startStopHourService").val().split(":")[0]) != parseInt($("#endStopHourService").val().split(":")[0])) {
		$("#saveTimeStopServices").submit();
	} else {
		alert("Horários registrados não podem ser salvos, entre com um novo range de horários de ausência do serviço.")
	}

}

function saveCleanStopService() {
	$("#cleanServicesOperationStop").val("true");
	$("#saveCleanStopService").submit();
}

//-------------------------------------------------
//File request All
function preencherSearchDataFile(entrada) {
	$("#search_file_value").val(entrada);
	insertValueFindRule(entrada);
	updateScreenListRulesClamad();
}

function clearSearchDataFile() {
	$("#search_file_value").val("");
}
//--------------------------------------------------



//-------------------------------------------------
//Ajax request status
function barStatusFilesGateway() {
	$.ajax(
		"./ajax_update_screen_clamav.php",
		{
			type: 'post',
			data: {
				barStatusFilesGateway: true,
				colorBar: true
			},
		}).done(function(data) {
            $("#status-class-bar").removeAttr('class').addClass(data);
        }
	);
	$.ajax(
		"./ajax_update_screen_clamav.php",
		{
			type: 'post',
			data: {
				barStatusFilesGateway: true,
				buton_status: true
			},
		}).done(function(data) {
            $("#buton_status").removeAttr('class').addClass(data);
        }
	);
	$.ajax(
		"./ajax_update_screen_clamav.php",
		{
			type: 'post',
			data: {
				barStatusFilesGateway: true,
				id_status_click_files: true
			},
		}).done(function(data) {
            $("#id_status_click_files").html(data);
        }
	);
	/*
	$.ajax(
		"./ajax_update_screen_clamav.php",
		{
			type: 'post',
			data: {
				barStatusFilesGateway: true,
				showExecuteNow: true
			},
		}).done(function(data) {
            $("#showExecuteNow").css("display", data);
        }
	);
	*/
	$.ajax(
		"./ajax_update_screen_clamav.php",
		{
			type: 'post',
			data: {
				barStatusFilesGateway: true,
				showExtensionNow: true
			},
		}).done(function(data) {
            $("#showExtensionNow").css("display", data);
        }
	);
}


//

barStatusFilesGateway();
window.setInterval("barStatusFilesGateway()",5000);
//--------------------------------------------------




//-------------------------------------------------
//Ajax request status
function get_list_persistent() {
	$.ajax(
		"./ajax_update_screen_clamav.php",
		{
			type: 'post',
			data: {
				get_list_persistent: true,
				search_file_value: $("#search_file_value").val(),
				selectAmountShowEve: $("#selectAmountShowEve").val(),
			},
		}).done(function(data) {
            $("#table-file-eve-lists").html(data);
        }
	);
}
get_list_persistent();
window.setInterval("get_list_persistent()",5000);
//--------------------------------------------------



//--------------------------------------------------
function insertValueFindRule(value_now) {
	$("#search-rule-for-list").val(value_now);
	setTimeout(() => {
		updateScreenListRulesClamad();
	}, 100);
}

function clearSearchListRulesClamad() {
	$("#search-rule-for-list").val("");
	$("#changeTitleHash").html("<?=gettext("FIND LIST HASH")?>");
	$("#table-file-rules-lists").html("<tr><td>---</td><td>---</td><td>---</td><td>---</td><td>---</td><td>---</td><td>---</td></tr>");
}

//--------------------------------------------------
// Ajax requests rules
function updateScreenListRulesClamad() {
	$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Search rule and your state... please a moment.')?>");
	$('#modal_ativa .txt_modal_ativa_msg').text("");
	$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/spinner.gif');
	setTimeout(() => {
		$('#modal_ativa').modal("show");		
	}, 100);
	setTimeout(() => {
		$.ajax(
			"./ajax_update_screen_clamav.php",
			{
				type: 'post',
				data: {
					find_values_files: $("#search-rule-for-list").val()
				},
			}).done(function(data) {
				$('#modal_ativa').modal("hide");
        	    $("#table-file-rules-lists").html(data);
        	    $("#changeTitleHash").html("<?=gettext("FIND LIST HASH")?>" + ": " + $("#search-rule-for-list").val());
        	}
		);	
	}, 300);

}
//--------------------------------------------------



// From http://stackoverflow.com/questions/5499078/fastest-method-to-escape-html-tags-as-html-entities
function htmlspecialchars(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
}

function submitSearchRulesThreads(sidRuleThread) {

	$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Looking for card information")?>");
	$('#modal_ativa .txt_modal_ativa_msg').text("");
	$('#modal_ativa #loader_modal_ativa').attr('style', 'width:100px;height:auto');
	$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/spinner.gif');
	$('#modal_ativa').modal('show');

	//console.log(sidRuleThread);

	setTimeout(() => {
		$.ajax({
		data: {
			targetSID: sidRuleThread
		},
		method: "post",
		url: '../ajax_retuns_inspect_real_time.php',
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
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Selected card is not a rule/threat")?>")
		$('#modal_ativa .txt_modal_ativa_msg').text("");
		$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/bp-logout.png')
	}, 10000);

	setTimeout(function() {$('#modal_ativa').modal('hide')}, 12000);
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

function saveListsBlackWhite() {
	if ($("#modificationStatusOfRule").val() != "") {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Saving State Rule...')?>");
		$('#modal_ativa .txt_modal_ativa_msg').text("");
		$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/spinner.gif');
		setTimeout(() => {
			$('#modal_ativa').modal("show");		
		}, 100);
		setTimeout(() => {
			$.ajax(
				"./listAllfiles.php",
				{
					type: 'post',
					data: {
						modification_status_of_rule: $("#modificationStatusOfRule").val()
					},
				}).done(function(data) {
					$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Saved State Rule...')?>");
					$('#modal_ativa .txt_modal_ativa_msg').text("");
					$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/update_rules_ok.png');
				}
			);
		}, 1000);
	
		<?php if (intval(getStatusNewAcp()) >= 1) : ?>

			setTimeout(() => {
				$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Apply in Active Protection...')?>");
				$('#modal_ativa .txt_modal_ativa_msg').text("");
				$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/spinner.gif');
			}, 3000);

			setTimeout(() => {
				$.ajax({
					url: '../update_interfaces_rules.php',
				});
				$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/update_rules_ok.png');
			}, 3100);

			setTimeout(() => {
				$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Operation performed successfully!")?>");
				$('#modal_ativa .txt_modal_ativa_msg').text("");
				$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/update_rules_ok.png');	
			}, 10000);
			setTimeout(() => {
				$('#modal_ativa').modal('hide');
			}, 12000);
			setTimeout(() => {
				insertValueFindRule($("#modificationStatusOfRule").val().split("__")[1]);
			}, 13000);

		<?php else: ?>
		
			setTimeout(() => {
				$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Operation performed successfully!")?>");
				$('#modal_ativa .txt_modal_ativa_msg').text("");
				$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/update_rules_ok.png');	
			}, 5000);
			setTimeout(() => {
				$('#modal_ativa').modal('hide');
			}, 7000);
			setTimeout(() => {
				insertValueFindRule($("#modificationStatusOfRule").val().split("__")[1]);
			}, 8000);

		<?php endif; ?>

	}
}

//------------------------------------------------
//Start scan in BK
function startScanNow() {
	$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Operation scan now!")?>");
	$('#modal_ativa .txt_modal_ativa_msg').text("<?=gettext("This operation can take up to 5 minutes and it occurs internally, closing this modal does not affect the operation;")?>");	
	$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/spinner.gif');
	$('#modal_ativa').modal('show');
	setTimeout(() => {
		$.ajax(
			"./fetch_different_files_clamav.php",
		);
	}, 100);
	setTimeout(() => {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Finish scan !")?>");
		$('#modal_ativa .txt_modal_ativa_msg').text("");
		$('#modal_ativa #loader_modal_ativa').attr('src', '../../images/update_rules_ok.png');
	}, 10000);
	setTimeout(() => {
		$('#modal_ativa').modal('hide');
	}, 12000);
}
//-------------------------------------------------


function disabledOptationScan() {
	$("#start_clamd").val("false");
	$("#start_yara").val("false");
	$("#select_mode").val("");
	$("#startScanValues").submit();
}

function selectionOperationScan() {
	$('#selectModeOperation').modal('show');
}

function simpleEnableOptationScanClamd() {
	$("#start_clamd").val("true");
	$("#start_yara").val("false");
	$("#select_mode").val("");
	$("#startScanValues").submit();
}

function simpleEnableOptationScanYara() {
	$("#start_clamd").val("false");
	$("#start_yara").val("true");
	$("#select_mode").val("");
	$("#startScanValues").submit();
}

function simpleEnableOptationScanDetails() {
	$("#start_clamd").val("true");
	$("#start_yara").val("true");
	$("#select_mode").val("");
	$("#startScanValues").submit();
}

function selectValueOperationScan(valueNow) {
	$("#" + valueNow).removeAttr('onclick').attr('onclick','disabledSelectValueOperationScan("' + valueNow + '")');
	$("#" + valueNow).removeAttr('class').addClass("btn btn-success scanSelect");
	$("#select_mode").val("");
	var valuesMerge  = "";
	for(var count=0; count <= $(".btn.btn-success.scanSelect").length-1; count++) {
		valuesMerge += $("#select_mode").val() + "___" + $(".btn.btn-success.scanSelect")[count].name;
	}
	$("#select_mode").val(valuesMerge);
}

function disabledSelectValueOperationScan(valueNow) {
	$("#" + valueNow).removeAttr('onclick').attr('onclick','selectValueOperationScan("' + valueNow + '")');
	$("#" + valueNow).removeAttr('class').addClass("btn btn-primary scanSelect");
	$("#select_mode").val("");
	var valuesMerge  = "";
	for(var count=0; count <= $(".btn.btn-success.scanSelect").length-1; count++) {
		valuesMerge += $("#select_mode").val() + "___" + $(".btn.btn-success.scanSelect")[count].name;
	}
	$("#select_mode").val(valuesMerge);
}	

function resetSelectOptionScan() {
	var allExtensions = [];
	for(var count=0; count <= $(".btn.btn-success.scanSelect").length-1; count++) {
		allExtensions.push($(".btn.btn-success.scanSelect")[count].id);
	}
	for(var count=0; count <= allExtensions.length-1; count++) {
		$("#" + allExtensions[count]).removeAttr('onclick').attr('onclick','selectValueOperationScan("' + allExtensions[count] + '")');
		$("#" + allExtensions[count]).removeAttr('class').addClass("btn btn-primary scanSelect");
	}
	$("#select_mode").val("");
}

function selectAllSelectOptionScan() {
	var allExtensions = [];
	for(var count=0; count <= $(".btn.btn-primary.scanSelect").length-1; count++) {
		allExtensions.push($(".btn.btn-primary.scanSelect")[count].id);
	}
	valuesMerge = "";
	for(var count=0; count <= allExtensions.length-1; count++) {
		$("#" + allExtensions[count]).removeAttr('onclick').attr('onclick','disabledSelectValueOperationScan("' + allExtensions[count] + '")');
		$("#" + allExtensions[count]).removeAttr('class').addClass("btn btn-primary scanSelect");
		$("#" + allExtensions[count]).removeAttr('onclick').attr('onclick','disabledSelectValueOperationScan("' + allExtensions[count] + '")');
		$("#" + allExtensions[count]).removeAttr('class').addClass("btn btn-success scanSelect");
		valuesMerge += $("#select_mode").val() + "___" + allExtensions[count];
	}
	$("#select_mode").val(valuesMerge);
}

<?php if (strlen($setAlreadyValues) > 0): ?>
	$("#select_mode").val("<?=$setAlreadyValues?>");
<?php endif; ?>

<?php if (strlen($setAlreadyValuesSimple) > 0): ?>
	$("#select_mode_simple").val("<?=$setAlreadyValuesSimple?>");
<?php endif; ?>

function advancedEnableOptationScanSetValues() {
	$("#start_clamd").val("true");
	$("#start_yara").val("true");
	var valuesMerge = "";
	if ($(".btn.btn-success.scanSelect").length != 0) {
		for(var count=0; count <= $(".btn.btn-success.scanSelect").length-1; count++) {
			valuesMerge += $("#select_mode").val() + "___" + $(".btn.btn-success.scanSelect")[count].name;
		}
	}
	$("#select_mode").val(valuesMerge);
	$("#startScanValues").submit();
}

function simpleEnableOptationScanSetValues() {
	$("#start_yara_simple").val("true");
	var valuesMerge = "";
	if ($(".btn.btn-success.scanSelectSimple").length != 0) {
		for(var count=0; count <= $(".btn.btn-success.scanSelectSimple").length-1; count++) {
			valuesMerge += $("#select_mode_simple").val() + "___" + $(".btn.btn-success.scanSelectSimple")[count].name;
		}
	}
	$("#select_mode_simple").val(valuesMerge);
	$("#startScanValuesSimple").submit();
}

function simpledEnableOptationScan() {
	$('#selectModeOperation').modal('hide');
	setTimeout(() => {
		$("#selectModeOperationSimple").modal("show");
	}, 100);
}

function disabledSelectValueOperationScanSimple(valueNow) {
	$("#" + valueNow).removeAttr('onclick').attr('onclick','selectValueOperationScanSimple("' + valueNow + '")');
	$("#" + valueNow).removeAttr('class').addClass("btn btn-primary scanSelectSimple");
	$("#select_mode_simple").val("");
	var valuesMerge  = "";
	for(var count=0; count <= $(".btn.btn-success.scanSelectSimple").length-1; count++) {
		valuesMerge += $("#select_mode_simple").val() + "___" + $(".btn.btn-success.scanSelectSimple")[count].name;
	}
	$("#select_mode_simple").val(valuesMerge);
}

function selectValueOperationScanSimple(valueNow) {
	$("#" + valueNow).removeAttr('onclick').attr('onclick','disabledSelectValueOperationScanSimple("' + valueNow + '")');
	$("#" + valueNow).removeAttr('class').addClass("btn btn-success scanSelectSimple");
	$("#select_mode_simple").val("");
	var valuesMerge  = "";
	for(var count=0; count <= $(".btn.btn-success.scanSelectSimple").length-1; count++) {
		valuesMerge += $("#select_mode_simple").val() + "___" + $(".btn.btn-success.scanSelectSimple")[count].name;
	}
	$("#select_mode_simple").val(valuesMerge);
}

function resetSelectOptionScanSimple() {
	var allFilters = [];
	for(var count=0; count <= $(".btn.btn-success.scanSelectSimple").length-1; count++) {
		allFilters.push($(".btn.btn-success.scanSelectSimple")[count].id);
	}
	for(var count=0; count <= allFilters.length-1; count++) {
		$("#" + allFilters[count]).removeAttr('onclick').attr('onclick','selectValueOperationScanSimple("' + allFilters[count] + '")');
		$("#" + allFilters[count]).removeAttr('class').addClass("btn btn-primary scanSelectSimple");
	}
	$("#select_mode_simple").val("");
}

function selectAllSelectOptionScanSimple() {
	var allFilters = [];
	for(var count=0; count <= $(".btn.btn-primary.scanSelectSimple").length-1; count++) {
		allFilters.push($(".btn.btn-primary.scanSelectSimple")[count].id);
	}
	valuesMerge = "";
	for(var count=0; count <= allFilters.length-1; count++) {
		$("#" + allFilters[count]).removeAttr('onclick').attr('onclick','disabledSelectValueOperationScanSimple("' + allFilters[count] + '")');
		$("#" + allFilters[count]).removeAttr('class').addClass("btn btn-primary scanSelectSimple");
		$("#" + allFilters[count]).removeAttr('onclick').attr('onclick','disabledSelectValueOperationScanSimple("' + allFilters[count] + '")');
		$("#" + allFilters[count]).removeAttr('class').addClass("btn btn-success scanSelectSimple");
		valuesMerge += $("#select_mode_simple").val() + "___" + allFilters[count];
	}
	$("#select_mode_simple").val(valuesMerge);
}
function advancedEnableOptationScanSetValuesSimple() {
	$("#start_yara_simple").val("true");
	var valuesMerge = "";
	if ($(".btn.btn-success.scanSelectSimple").length != 0) {
		for(var count=0; count <= $(".btn.btn-success.scanSelectSimple").length-1; count++) {
			valuesMerge += $("#select_mode_simple").val() + "___" + $(".btn.btn-success.scanSelectSimple")[count].name;
		}
	}
	$("#select_mode_simple").val(valuesMerge);
	$("#startScanValuesSimple").submit();

}

function advancedEnableOptationScan() {
	$('#selectModeOperation').modal('hide');
	setTimeout(() => {
		$("#selectModeOperationAdvanced").modal("show");
	}, 100);
}

<?php if (strlen($setAlreadyValues) > 0) { ?>
	var restoreExtensions = "<?=$setAlreadyValues?>".split("___");
	function restoreStateOfRules() {
		resetSelectOptionScan();
		setTimeout(() => {
			for(var i=0; i <= restoreExtensions.length-1; i++) {
				selectValueOperationScan(restoreExtensions[i]);
			}			
		}, 100);
	}
<?php } ?>

<?php if ($reloadRules): ?>
	setTimeout(() => {
		$.ajax({
			url: '../update_interfaces_rules.php',
		});
	}, 2000);
<?php endif; ?>

</script>