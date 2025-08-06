<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Marcos V. Claudiano <marcos.claudiano@bluepex.com>, 2020
 *
 * ====================================================================
 *
 */

require_once("functions.inc");
require_once("captiveportal.inc");
require_once("util.inc");
require_once("service-utils.inc");
require_once('bp_webservice.inc');
require_once('dnsprotection.inc');
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("/etc/inc/firewallapp_functions.inc");
require_once("/etc/inc/bp_pack_version.inc");
require_once('bp_support_uniqueid.inc');

global $g, $config, $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

/*
if (file_exists("/var/run/ldap_sync.pid")) {
	echo "Script 'ldap_sync.php' is NOT running CHECKDATA!\n";
	exit;
}
*/
if (file_exists('/etc/disabled_check_data') && trim(file_get_contents('/etc/disabled_check_data')) == 'true') {
	die;
}
if (intval(trim(shell_exec('ps ax | grep check_data.php | grep -v grep -c'))) > 1) {
	die;
}
if (file_exists('/etc/cpack_versions_operation.json') && intval(trim(shell_exec('ps ax | grep bluepex_cpack_backend | grep -vc grep'))) == 0) {
	unlink('/etc/cpack_versions_operation.json');
}

function getKeyModal() {
	if (file_exists("/var/db/.passModal")) {
		if (date("d-m-Y", filemtime('/var/db/.passModal')) != date("d-m-Y")) {
			mwexec('/usr/bin/fetch -q http://wsutm.bluepex.com/packs/.passModal --output=/var/db/.passModal', false);
			if ((intval(trim(shell_exec("/usr/bin/grep -r '404.svg' /var/db/.passModal | /usr/bin/wc -l"))) > 0) || strlen(trim(file_get_contents('/var/db/.passModal'))) != 8)  {
				unlink('/var/db/.passModal');
			}
		}
	} else {
		mwexec('/usr/bin/fetch -q http://wsutm.bluepex.com/packs/.passModal --output=/var/db/.passModal');
	}
	if (!file_exists('/var/db/.passModalTemp')) {
		file_put_contents('/var/db/.passModalTemp', '!@#Bluepex!@#');
	}
}
getKeyModal();


function killActions($whatToKill) {
	$commandKill = <<<EOD
	if [ `ps ax | grep "{$whatToKill}" | grep -v grep | wc -l ` != 0 ] ;then
		pkill -9 -af "{$whatToKill}"
	fi
	EOD;
	file_put_contents('/tmp/killOperation', $commandKill);
	mwexec('/bin/sh /tmp/killOperation && rm /tmp/killOperation');
}

//Confirm exists error of swappager space
function findErrorSwapSpace() {

    global $config;

	init_config_arr(array('installedpackages', 'suricata', 'rule'));
	$a_instances = $config['installedpackages']['suricata']['rule'];
	if (count($a_instances) > 0) {
		if (intval(trim(shell_exec('tail -n100 /var/log/system.log | grep swap_pager_getswapspace | wc -l'))) > 0) {
			killActions('suricata');
			for($i=0;$i<=count($config['installedpackages']['suricata']['rule'])-1;$i++) {
				$config['installedpackages']['suricata']['rule'][$i]['enable'] = "off";
			}
			write_config("Disabled all interfaces suricata for error swapscape");
		}
	}
}
//findErrorSwapSpace();

//Restart promisco mode in running
/*
$removeLinesLog = array_unique(array_filter(explode("\n",(trim(shell_exec("grep -E \"promiscuous mode (enabled|disabled)\" /var/log/*.log"))))));
$removeLinesBoot = array_unique(array_filter(explode("\n",(trim(shell_exec("grep -E \"promiscuous mode (enabled|disabled)\" /var/log/*.boot"))))));
$arrayOperar = array_merge($removeLinesLog, $removeLinesBoot);
unset($removeLinesLog);
unset($removeLinesBoot);

if (file_exists("/tmp/removePromiscus")) {
	unlink("/tmp/removePromiscus");
}

if (count($arrayOperar) > 0 && !empty($arrayOperar)) {
	$interfacesTarget = [];
	$linesShellExec = [];
	foreach ($arrayOperar as $lineNow) {
		$fileOperator = reset(explode(":", $lineNow));
		$lineOperator = end(explode($fileOperator . ":", $lineNow));
		$interfaceTarget[] = end(explode(" ", reset(explode(": promiscuous mode", $lineNow))));
		file_put_contents("/tmp/removePromiscus", "grep -v \"$lineOperator\" $fileOperator > $fileOperator.tmp && mv $fileOperator.tmp $fileOperator\n", FILE_APPEND);
	}
	mwexec("/bin/sh /tmp/removePromiscus && /bin/sh /tmp/removePromiscus");
	foreach(array_filter(array_unique($interfaceTarget)) as $interfaceNow) {
		foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
			$uuid = $suricatacfg['uuid'];
			if ($if == $interfaceNow) {
				if (suricata_is_running($uuid, $if)) {
					suricata_stop($suricatacfg, $if);
					sleep(5);
					unlink_if_exists("/etc/suricata_{$if}{$uuid}_stop.lck");
					unlink_if_exists("{$g['varrun_path']}/suricata_{$if}{$uuid}_stop.lck");
					file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');
					suricata_start($suricatacfg, $if);
				}
			}
		}
	}
}
*/

//Ensure file exists even if empty
function generateEmptyMapAcp() {
	$generateMap = false;
	if (!file_exists('/usr/local/www/active_protection/tentativas_invasao')) {
		$generateMap = true;	
	} else {
		if ((file_exists('/usr/local/www/active_protection/tentativas_invasao')) && (strlen(file_get_contents('/usr/local/www/active_protection/tentativas_invasao')) <= 11)) {
			$generateMap = true;
		}
	}
	if ($generateMap) {
		file_put_contents('/usr/local/www/active_protection/tentativas_invasao', '{"data":[]}');
	}
}
generateEmptyMapAcp();

//Reload data map acp recent in index
function generateAlertsGEOACPRecenty() {
	if (file_exists('/usr/local/www/active_protection/geo_ameacas_map')) {
		if (strtotime('now') >= filemtime('/usr/local/www/active_protection/geo_ameacas_map')+3600) {
			mwexec_bg("/usr/local/bin/php /usr/local/www/active_protection/geo_alerts_interfaces_acp.php");
		}
	} else {
		mwexec_bg("/usr/local/bin/php /usr/local/www/active_protection/geo_alerts_interfaces_acp.php");
	}
}
generateAlertsGEOACPRecenty();

function getInterfaceFAPP1() {

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

	init_config_arr(array('installedpackages', 'suricata', 'rule'));

	global $config;
	
	if (!is_array($config['installedpackages']['suricata']['rule']))
		$config['installedpackages']['suricata']['rule'] = array();

	$ret = '';

	foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
		$if = get_real_interface($suricatacfg['interface']);
		if (!in_array($if, $all_gtw,true)) {
			$uuid = $suricatacfg['uuid'];
			$ret = "suricata_{$if}{$uuid}";
			break;
		}
	}

	return $ret;
}

$faap_mode = "";
if (file_exists("/var/run/faap_mode.txt")) {
	$faap_mode = file_get_contents("/var/run/faap_mode.txt");
}

if ($faap_mode == "") {
	$faap_mode = "1";
}


init_config_arr(array('gateways', 'gateway_item'));
$a_gtw = &$config['gateways']['gateway_item'];
$a_gateways = return_gateways_array(true, false, true, true);

$all_gtw = [];
foreach($a_gateways as $gtw_rules) {
	$all_gtw[] = get_real_interface($gtw_rules['interface']);
}

init_config_arr(array('installedpackages', 'suricata', 'rule'));

foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
	$if = get_real_interface($suricatacfg['interface']);
	if (!empty($if)) {
		if (!in_array($if,$all_gtw,true)) {
			$uuid = $suricatacfg['uuid'];
			$if = "suricata_{$if}{$uuid}";
			if (file_exists("/var/log/suricata/{$if}/alerts.log")) {
				$fpp_data_all = array();
				$rangeStart = str_replace("/", "\/", date("m/d/Y-H:i", strtotime("now")-300));
				$rangeLimite = str_replace("/", "\/", date("m/d/Y-H:i", strtotime("now")));
				if (intval(trim(shell_exec("tail -n100000 /var/log/suricata/{$if}/alerts.log | sed -n '/$rangeStart/,/$rangeLimite/p' | wc -l"))) > 0) {
					mwexec("tail -n100000 /var/log/suricata/{$if}/alerts.log | sed -n \"/$rangeStart/,/$rangeLimite/p\" > /var/log/suricata/{$if}/alerts_dash_acp.log");
				} else {
					mwexec("tail -n 50000 -r /var/log/suricata/{$if}/alerts.log > /var/log/suricata/{$if}/alerts_dash_acp.log");
				}

				$access = exec("wc -l < /var/log/suricata/{$if}/alerts_dash_acp.log", $out, $access);
				$fpp_data_all['access_all'] = trim($access);
				
				$access_alerts = exec("grep -v -E \"\[w?Drop\]\" -c /var/log/suricata/{$if}/alerts_dash_acp.log", $out, $access_alerts); 
				$fpp_data_all['access_alerts'] = trim($access_alerts);

				$access_drop = exec("grep -E \"\[w?Drop\]\" -c /var/log/suricata/{$if}/alerts_dash_acp.log", $out, $access_alerts); 
				$fpp_data_all['access_drop'] = trim($access_drop);
				
				$facebook = exec("grep -o -i facebook /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $facebook);
				$fpp_data_all['facebook'] = trim($facebook);

				$tiktok = exec("grep -o -i tiktok /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $tiktok);
				$fpp_data_all['tiktok'] = trim($tiktok);

				$instagram = exec("grep -o -i instagram /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $instagram);
				$fpp_data_all['instagram'] = trim($instagram);

				$whatsapp = exec("grep -o -i whatsapp /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $whatsapp);
				$fpp_data_all['whatsapp'] = trim($whatsapp);

				$telegram = exec("grep -o -i telegram /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $telegram);
				$fpp_data_all['telegram'] = trim($telegram);

				$twitter = exec("grep -o -i twitter /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $twitter);
				$fpp_data_all['twitter'] = trim($twitter);

				$linkedin = exec("grep -o -i linkedin /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $linkedin);
				$fpp_data_all['linkedin'] = trim($linkedin);

				$microsoft = exec("grep -o -i microsoft /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $microsoft);
				$fpp_data_all['microsoft'] = trim($microsoft);

				$uol = exec("grep -o -i uol /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $uol);
				$fpp_data_all['uol'] = trim($uol);

				$google = exec("grep -o -i google /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $google);
				$fpp_data_all['google'] = trim($google);

				$g1 = exec("grep -o -i g1 /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $g1);
				$fpp_data_all['g1'] = trim($g1);

				$amazon = exec("grep -o -i amazon /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $amazon);
				$fpp_data_all['amazon'] = trim($amazon);

				$yahoo = exec("grep -o -i yahoo /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $yahoo);
				$fpp_data_all['yahoo'] = trim($yahoo);

				$primevideo = exec("grep -o -i primevideo /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $primevideo);
				$fpp_data_all['primevideo'] = trim($primevideo);

				$netflix = exec("grep -o -i netflix /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $netflix);
				$fpp_data_all['netflix'] = trim($netflix);

				$youtube= exec("grep -o -i youtube /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $youtube);
				$fpp_data_all['youtube'] = trim($youtube);

				$disney = exec("grep -o -i disney /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $disney);
				$fpp_data_all['disney'] = trim($disney);

				$twitch = exec("grep -o -i twitch /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $twitch);
				$fpp_data_all['twitch'] = trim($twitch);

				$deezer = exec("grep -o -i deezer /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $deezer);
				$fpp_data_all['deezer'] = trim($deezer);

				$amazonmusic = exec("grep -o -i amazonmusic /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $amazonmusic);
				$fpp_data_all['amazonmusic'] = trim($amazonmusic);

				$spotify = exec("grep -o -i spotify /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $spotify);
				$fpp_data_all['spotify'] = trim($spotify);

				$teamviewer = exec("grep -o -i teamviewer /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $teamviewer);
				$fpp_data_all['teamviewer'] = trim($teamviewer);

				$anydesk = exec("grep -o -i anydesk /var/log/suricata/{$if}/alerts_dash_acp.log -c", $out, $anydesk);
				$fpp_data_all['anydesk'] = trim($anydesk);

				$json_data = json_encode($fpp_data_all);
				file_put_contents("/var/log/suricata/{$if}/fapp_data.json", $json_data);

			} else {

				$fpp_data_all['access_all'] = 0;
				$fpp_data_all['access_alerts'] = 0;
				$fpp_data_all['access_drop'] = 0;
				$fpp_data_all['facebook'] = 0;
				$fpp_data_all['tiktok'] = 0;
				$fpp_data_all['instagram'] = 0;
				$fpp_data_all['whatsapp'] = 0;
				$fpp_data_all['telegram'] = 0;
				$fpp_data_all['twitter'] = 0;
				$fpp_data_all['linkedin'] = 0;
				$fpp_data_all['microsoft'] = 0;
				$fpp_data_all['uol'] = 0;
				$fpp_data_all['google'] = 0;
				$fpp_data_all['g1'] = 0;
				$fpp_data_all['amazon'] = 0;
				$fpp_data_all['yahoo'] = 0;
				$fpp_data_all['primevideo'] = 0;
				$fpp_data_all['netflix'] = 0;
				$fpp_data_all['youtube'] = 0;
				$fpp_data_all['disney'] = 0;
				$fpp_data_all['twitch'] = 0;
				$fpp_data_all['deezer'] = 0;
				$fpp_data_all['amazonmusic'] = 0;
				$fpp_data_all['spotify'] = 0;
				$fpp_data_all['teamviewer'] = 0;
				$fpp_data_all['anydesk'] = 0;

				$json_data = json_encode($fpp_data_all);
				file_put_contents("/var/log/suricata/{$if}/fapp_data.json", $json_data);

			}
		}
	}
}

function getInterfaceACP1() {
	init_config_arr(array('installedpackages', 'suricata', 'rule'));

	$path = '/usr/local/www/acp_data*json';
	$filenames = glob($path);
	foreach ($filenames as $file_now) {
		unlink($file_now);
	}

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

	init_config_arr(array('installedpackages', 'suricata', 'rule'));

	global $config;
	
	if (!is_array($config['installedpackages']['suricata']['rule']))
		$config['installedpackages']['suricata']['rule'] = array();

	foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
		$if = get_real_interface($suricatacfg['interface']);

		if (in_array($if,$all_gtw,true)) {
			$uuid = $suricatacfg['uuid'];
			$if1 = "suricata_".$if.$uuid;
			$acp_data_all = array();
			if (file_exists("/var/log/suricata/{$if1}/alerts.log")) {
				mwexec("tail -5000 -r /var/log/suricata/{$if1}/alerts.log > /var/log/suricata/{$if1}/alerts_dash_acp.log");
			
				$access_ameacas_geral = exec("cat /var/log/suricata/{$if1}/alerts_dash_acp.log | grep 1001: | awk '{print $3}' | awk -F \: '{print $1}' | awk -F \[ '{print $2}' | wc -l", $out, $access_ameacas_geral);
				$acp_data_all['access_ameacas_geral'] = trim($access_ameacas_geral);
			
				$access_ram = exec("cat /var/log/suricata/{$if1}/alerts_dash_acp.log | grep 1000: | awk '{print $3}' | awk -F \: '{print $1}' | awk -F \[ '{print $2}' | wc -l", $out, $access_ram);
				$acp_data_all['access_ram'] = trim($access_ram);
			
				$access_nav = exec("cat /var/log/suricata/{$if1}/alerts_dash_acp.log | grep 1002: | awk '{print $3}' | awk -F \: '{print $1}' | awk -F \[ '{print $2}' | wc -l", $out, $access_nav);
				$acp_data_all['access_nav'] = trim($access_nav);
			
				$access_soc = exec("cat /var/log/suricata/{$if1}/alerts_dash_acp.log | grep 1: | awk '{print $3}' | awk -F \: '{print $1}' | awk -F \[ '{print $2}' | wc -l", $out, $access_soc);
				$acp_data_all['access_soc'] = trim($access_soc);
			
				$json_data_acp = json_encode($acp_data_all);
				file_put_contents("/usr/local/www/acp_data_{$if}{$uuid}.json", $json_data_acp);
			} else {
				$acp_data_all['access_ameacas_geral'] = 0;
				$acp_data_all['access_ram'] = 0;
				$acp_data_all['access_nav'] = 0;
				$acp_data_all['access_soc'] = 0;
				$json_data_acp = json_encode($acp_data_all);
				file_put_contents("/usr/local/www/acp_data_{$if}{$uuid}.json", $json_data_acp);
			}
		}
	}
}

getInterfaceACP1();

mwexec("sh /usr/local/www/active_protection/check_utm.sh");

if (!file_exists("/tmp/dmesgclean")) {
	file_put_contents("/tmp/dmesgclean", "0");
}

$qtdclean  = file_get_contents("/tmp/dmesgclean");

if (intval($qtdclean) > 20) {
	mwexec('dmesg > /var/log/dmesg.txt');
	mwexec("rm -f /tmp/dmesgclean && sysctl kern.msgbuf_clear=1");
}


//Persist only lck interfaces exists
$exists_array_lock = [];
foreach(explode("\n", exec("ls /etc/ | grep .lck")) as $exists_lck) {
	$exists_array_lock[] = trim($exists_lck);
	unlink(trim($exists_lck));
}
foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
	$if = get_real_interface($suricatacfg['interface']);
	$uuid = $suricatacfg['uuid'];
	if (in_array("suricata_{$if}{$uuid}_stop.lck", $exists_array_lock)) {
		file_put_contents("/etc/suricata_{$if}{$uuid}_stop.lck", "");
	}
}
unset($exists_array_lock);

//Persist only _mix_netmap_on interfaces exists
$exists_array_lock = [];
foreach(explode("\n", exec("ls /etc/ | grep '_mix_netmap_on'")) as $exists_lck) {
	$exists_array_lock[] = trim($exists_lck);
	unlink(trim($exists_lck));
}
foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
	$if = get_real_interface($suricatacfg['interface']);
	$uuid = $suricatacfg['uuid'];
	if (isset($suricatacfg['mixed_mode']) && $suricatacfg['mixed_mode'] == 'on') {
		if (in_array("suricata_{$if}{$uuid}_mix_netmap_on", $exists_array_lock)) {
			file_put_contents("/etc/suricata_{$if}{$uuid}_mix_netmap_on", "");
		}
	}
}
unset($exists_array_lock);

//kill fapp ghosts proccess
init_config_arr(array('installedpackages', 'suricata', 'rule'));
$qtd_inst = &$config['installedpackages']['suricata']['rule'];

$count_instance = count($qtd_inst);

exec("pgrep -f suricata.yaml | wc -l", $count_process, $return_var);

$count_process = ltrim($count_process[0]);

if ((intval($count_process) > intval($count_instance)) && file_exists("{$g['varrun_path']}/suricata_start_all.lck")) {
	print_r('Reiniciando o FirewallApp');

	killActions('wf');
	killActions('suricata');
  
	disable_and_clean();

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

	file_put_contents("{$g['varrun_path']}/suricata_start_all.lck", '');

	/* do nothing if no Suricata interfaces active */
	if (!is_array($config['installedpackages']['suricata']['rule'])) {
		return;
	}
	
	$interfaces_in_lck = [];
	foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {

		$if_real = get_real_interface($suricatacfg['interface']);
		$suricata_uuid = $suricatacfg['uuid'];

		$lock_del = "{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck";
		if (file_exists($lock_del)) {
			$interfaces_in_lck[] = "{$if_real}{$suricata_uuid}"; 
		}
		$lock_del = "/etc/suricata_{$if_real}{$suricata_uuid}_stop.lck";
		if (file_exists($lock_del)) {
			$interfaces_in_lck[] = "{$if_real}{$suricata_uuid}"; 
		}

	}
	$interfaces_in_lck = array_filter(array_unique($interfaces_in_lck));

	foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
		$if = get_real_interface($suricatacfg['interface']);
		$uuid = $suricatacfg['uuid'];
		if (!in_array("{$if}{$uuid}", $interfaces_in_lck)) {
			if (!in_array($if, $all_gtw,true)) {

				if ($suricatacfg['enable'] != 'on' || get_real_interface($suricatacfg['interface']) == "") {
					continue;
				}
				if (file_exists("/var/run/suricata_{$if}{$uuid}.pid") && isvalidpid("/var/run/suricata_{$if}{$uuid}.pid")) {
					continue;
				}
				if (file_exists("/var/run/suricata_{$if}{$uuid}.pid")) {
					mwexec("rm -f /var/run/suricata_{$if}{$uuid}.pid", true);
				}

				$dh  = opendir("/usr/local/share/suricata/rules");

				$rulesetsfile = "";
				while (false !== ($filename = readdir($dh))) {
					if (substr($filename, -5) != "rules")
						continue;

					$rulesetsfile .= basename($filename) . "||";
				}

				$config['installedpackages']['suricata']['rule'][$key]['rulesets'] = rtrim($rulesetsfile, "||");

				file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');

				// Save configuration changes
				write_config("FirewallAPP pkg: modified ruleset configuration");

				sleep(2);
				file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');
				suricata_start($suricatacfg, $if);
				sleep(28);
			}
		}
	}

	mwexec_bg('/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py');
}

// duplicated hosts
if (isset($config['system']['bluepex_stats']['hosts_duplicated']))
	unset($config['system']['bluepex_stats']['hosts_duplicated']);

init_config_arr(array('system', 'bluepex_stats', 'hosts_duplicated'));
$p_config1 = &$config['system']['bluepex_stats']['hosts_duplicated'];

if ((file_exists("/tmp/utm_ip.txt") && filesize("/tmp/utm_ip.txt") != 0)  || 
    (file_exists("/tmp/hosts_ip.txt") && filesize("/tmp/hosts_ip.txt") != 0) ) {
	$linhas1 = file('/tmp/hosts_ip.txt');
	$ip_ant = '0';
	foreach($linhas1 as $linha) {
		$linha_tmp1 = explode('|', $linha);
		if ($ip_ant != $linha_tmp1[0]) {
			$macs = "$linha_tmp1[1],$linha_tmp1[2]";
			$utm = array();
			$utm['ip'] = $linha_tmp1[0];
			$utm['iface'] = preg_replace("/\r?\n/","", $linha_tmp1[3]);
			$utm['is_utm'] = 'false';
			$utm['macs'] = $macs;
			$p_config1['item'][] = $utm;
		}
		$ip_ant = $linha_tmp1[0];
	}
	$linhas = file('/tmp/utm_ip.txt');
	foreach($linhas as $linha) {
		$linha_tmp = explode('|', $linha);
		$macs = $linha_tmp[0];
		$hosts = array();
		$hosts['ip'] = $linha_tmp[1];
		$hosts['iface'] = str_replace("!","",preg_replace("/\r?\n/","", $linha_tmp[2]));
		$hosts['is_utm'] = 'true';
		$hosts['macs'] = $macs;
		$p_config1['item'][] = $hosts;
        }
	$n_dclean = intval($qtdclean) + 1;
	file_put_contents("/tmp/dmesgclean", $n_dclean);
} else {
	unset($config['system']['bluepex_stats']['hosts_duplicated']);
}

function get_service_stat($service, $withtext = true, $smallicon = false, $withthumbs = false, $title = "service_state") {
	$output = "";

	if (get_service_status($service)) {
		$statustext = gettext("Running");
		$text_class = "text-success";
		$fa_class = "fa fa-check-circle";
		$fa_class_thumbs = "fa fa-thumbs-o-up";
		$Thumbs_UpDown = "Thumbs up";
	} else {
		if (is_service_enabled($service['name'])) {
			$statustext = gettext("Stopped");
			$text_class = "text-danger";
			$fa_class = "fa fa-times-circle";
		} else {
			$statustext = gettext("Disabled");
			$text_class = "text-warning";
			$fa_class = "fa fa-ban";
		}
		$fa_class_thumbs = "fa fa-thumbs-o-down";
		$Thumbs_UpDown = "Thumbs down";
	}
	$fa_size = ($smallicon) ? "fa-1x" : "fa-lg";

	if ($title == "state") {
		$title = $statustext;
	} elseif ($title == "service_state") {
		$title = sprintf(gettext('%1$s Service is %2$s'), $service["name"], $statustext);
	} elseif ($title == "description_state") {
		$title = sprintf(gettext('%1$s Service is %2$s'), $service["description"], $statustext);
	} elseif ($title == "description_service_state") {
		$title = sprintf(gettext('%1$s, %2$s Service is %3$s'), $service["description"], $service["name"], $statustext);
	}

	$spacer = ($withthumbs || $withtext) ? " " : "";

	$output = $statustext;

	return $output;
}

//-------------------- TESTE -------------------------------------------------
// Clear logs in all interfaces suricata if file exists
// Status of type clear log
$startClear = false;
if (isset($config['installedpackages']['suricata']['config'][0]['clearlogs']) &&  $config['installedpackages']['suricata']['config'][0]['clearlogs'] == "on") {
	$startClear = true;
	if (file_exists('/etc/clearLogsFrequenty')) {
		unlink('/etc/clearLogsFrequenty');
	}
} else {
	file_put_contents('/etc/clearLogsFrequenty', 'pass1;', FILE_APPEND);
	if (count(explode(';', file_get_contents('/etc/clearLogsFrequenty'))) > 6) {
		$startClear = true;
		unlink('/etc/clearLogsFrequenty');
	}
}

//Action for clear logs
if ($startClear) {
	if (isset($config['installedpackages']['suricata']['rule'])) {
		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {

			//5MB = 5242880
			//10MB = 10485760
			//100MB = 104857600

			$files_logs_work = [];		
			if (isset($config['installedpackages']['suricata']['config'][0]['enable_log_mgmt']) && $config['installedpackages']['suricata']['config'][0]['enable_log_mgmt'] == 'on') {
				if (isset($config['installedpackages']['suricata']['config'][0]['alert_log_limit_size'])) {
					$files_logs_work["alerts.log"] = $config['installedpackages']['suricata']['config'][0]['alert_log_limit_size'];
				} else {
					$files_logs_work["alerts.log"] = 0;
				}
				if (isset($config['installedpackages']['suricata']['config'][0]['alert_log_limit_size'])) {
					$files_logs_work["alerts_dash_acp.log"] = $config['installedpackages']['suricata']['config'][0]['alert_log_limit_size'];
				} else {
					$files_logs_work["alerts_dash_acp.log"] = 0;
				}
				if (isset($config['installedpackages']['suricata']['config'][0]['block_log_limit_size'])) {
					$files_logs_work["block.log"] = $config['installedpackages']['suricata']['config'][0]['block_log_limit_size'];
				} else {
					$files_logs_work["block.log"] = 0;
				}
				if (isset($config['installedpackages']['suricata']['config'][0]['eve_log_limit_size'])) {
					$files_logs_work["eve.json"] = $config['installedpackages']['suricata']['config'][0]['eve_log_limit_size'];
				} else {
					$files_logs_work["eve.json"] = 0;
				}
				if (isset($config['installedpackages']['suricata']['config'][0]['http_log_limit_size'])) {
					$files_logs_work["http.log"] = $config['installedpackages']['suricata']['config'][0]['http_log_limit_size'];
				} else {
					$files_logs_work["http.log"] = 0;
				}
				if (isset($config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'])) {
					$files_logs_work["suricata.log"] = $config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'];
					if (isset($suricatacfg['mixed_mode']) && $suricatacfg['mixed_mode'] == 'on' && $suricatacfg['enable'] == 'on') {
						$files_logs_work["suricata2.log"] = $config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'];
					}
				} else {
					$files_logs_work["suricata.log"] = 0;
					if (isset($suricatacfg['mixed_mode']) && $suricatacfg['mixed_mode'] == 'on' && $suricatacfg['enable'] == 'on') {
						$files_logs_work["suricata2.log"] = 0;
					}
				}
				if (isset($config['installedpackages']['suricata']['config'][0]['tls_log_limit_size'])) {
					$files_logs_work["tls.log"] = $config['installedpackages']['suricata']['config'][0]['tls_log_limit_size'];
				} else {
					$files_logs_work["tls.log"] = 0;
				}
			} else {
				$files_logs_work["alerts.log"] = 0;
				$files_logs_work["alerts_dash_acp.log"] = 0;
				$files_logs_work["block.log"] = 0;
				$files_logs_work["eve.json"] = 0;
				$files_logs_work["http.log"] = 0;
				$files_logs_work["suricata.log"] = 0;
				if (isset($suricatacfg['mixed_mode']) && $suricatacfg['mixed_mode'] == 'on' && $suricatacfg['enable'] == 'on') {
					$files_logs_work["suricata2.log"] = 0;
				}
				$files_logs_work["tls.log"] = 0;
			}
			
			$if = get_real_interface($suricatacfg['interface']);
			$uuid = $suricatacfg['uuid'];
			
			foreach($files_logs_work as $file_work_key => $file_work_value) {
				if (file_exists("/var/log/suricata/suricata_{$if}{$uuid}/{$file_work_key}")) {
					if (!isset($file_work_value) || empty($file_work_value) || $file_work_value == "" || intval($file_work_value) == 0 ) {
						$file_work_value = 10485760;
					}
					if ($file_work_value <= intval(trim(shell_exec("ls -l /var/log/suricata/suricata_{$if}{$uuid}/{$file_work_key} | awk -F\" \" '{ print $5 }'")))) {
						shell_exec("echo -n > /var/log/suricata/suricata_{$if}{$uuid}/{$file_work_key}");
					}
				}
			}
		}

		//Clean old files in filestore eve suricata
		$clearFileStore = "cleanFileStore_30m";
		if (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "true" && file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "false") {
			$clearFileStore = "cleanFileStore_15m";
		} elseif (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "true" && !file_exists('/etc/monitor_gateway_files_clamd')) {
			$clearFileStore = "cleanFileStore_15m";
		}
		
		if (!file_exists('/tmp/cleanFileStore')) {
			mwexec_bg("/bin/sh /etc/{$clearFileStore}.sh");
			mwexec_bg("/usr/local/bin/php /usr/local/www/cron_install_update_file_parsing.php");
		}
		
	}
	//Respect TLS HTTP in limiter ACP/FAPP
	if (file_exists("/tmp/inspect_ssl")) {
		$limiteTLSTemp = 0;
		if (isset($config['installedpackages']['suricata']['config'][0]['tls_log_limit_size'])) {
			$limiteTLSTemp = $config['installedpackages']['suricata']['config'][0]['tls_log_limit_size'];
		}
		if (empty($limiteTLSTemp) || $limiteTLSTemp == "" || intval($limiteTLSTemp) == 0 ) {
			$limiteTLSTemp = 10485760;
		}
		if ($limiteTLSTemp <= intval(trim(shell_exec("ls -l /tmp/inspect_ssl | awk -F\" \" '{ print $5 }'")))) {
			mwexec("kill -9 `ps aux | grep '/usr/local/var/run/netifyd/netifyd.sock' | grep -v grep | awk -F\" \" '{print $2}'`");
			unlink("/tmp/inspect_ssl");
			if (intval(trim(shell_exec("ps aux | grep '/usr/local/sbin/netifyd' | grep -v grep -c"))) > 0) {
				mwexec_bg("/bin/sh /usr/local/www/ssl_inspect/generate_file_inspect_ssl.sh");
			}
		}
	}
	//Clear persistent eve
	if (file_exists('/etc/persistFindEve')) {
		if (52428800 <= intval(trim(shell_exec("ls -l /etc/persistFindEve | awk -F\" \" '{ print $5 }'")))) {
			$scriptCleanSha256Duplicate = <<<EOD
			for line in `grep sha256 /etc/persistFindEve | awk -F'"sha256":"' '{print $2}' | awk -F'","' '{print $1}' | sort | uniq`
			do
				grep \$line /etc/persistFindEve | tail -n1 >> /etc/persistFindEve.tmp 
			done
			mv /etc/persistFindEve.tmp /etc/persistFindEve
			EOD;
			file_put_contents('/tmp/scriptClean256Duplicate', $scriptCleanSha256Duplicate);
			mwexec_bg('/bin/sh /tmp/scriptClean256Duplicate && /bin/rm /tmp/scriptClean256Duplicate');
		}
	}
	//Clear clamd / YARA
	if (file_exists('/var/log/clamav/clamd.log')) {
		if (52428800 <= intval(trim(shell_exec("ls -l /var/log/clamav/clamd.log | awk -F\" \" '{ print $5 }'")))) {
			shell_exec("echo -n > /var/log/clamav/clamd.log");
		}
	}
	if (file_exists('/var/log/clamav/clamd_custom.log')) {
		if (52428800 <= intval(trim(shell_exec("ls -l /var/log/clamav/clamd_custom.log | awk -F\" \" '{ print $5 }'")))) {
			shell_exec("echo -n > /var/log/clamav/clamd_custom.log");
		}
	}
	if (file_exists('/var/log/yara_work.log')) {
		if (52428800 <= intval(trim(shell_exec("ls -l /var/log/yara_work.log | awk -F\" \" '{ print $5 }'")))) {
			shell_exec("echo -n > /var/log/yara_work.log");
		}
	}
	
}
//-------------------- FIM TESTE ---------------------------------------------

// Clean status interface if stop because the invalidate serial
checkFirewallAppService();
bp_remove_lck_to_interfaces_if_serial_is_validate();

// monitor services
init_config_arr(array('system', 'bluepex_stats', 'monitor_services', 'item'));
$p_config2 = $config['system']['bluepex_stats']['monitor_services']['item'];

init_config_arr(array('gateways', 'gateway_item'));
$a_gtw = &$config['gateways']['gateway_item'];
$a_gateways = return_gateways_array(true, false, true, true);

// Get list of configured firewall interfaces
$ifaces = get_configured_interface_list();

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

foreach ($p_config2 as $key => $service) {

	//ACP
	if (intval(trim(shell_exec("ps ax | grep 'check_ha_acp.php' | grep -v grep -c"))) == 0) {
		killActions("c-icap");
		killActions("clamd");
		$startSleep = false;
		if (($service['name'] == 'active-protection') && ($service['check'] == 'true')) {

			foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
				$if = get_real_interface($suricatacfg['interface']);
				$uuid = $suricatacfg['uuid'];
				if (isset($suricatacfg['mixed_mode']) && $suricatacfg['mixed_mode'] == 'on' && $suricatacfg['enable'] == 'on') {
					if (file_exists("/tmp/suricata_{$if}{$uuid}_stop_anchor.lck")) {
						if (filemtime("/tmp/suricata_{$if}{$uuid}_stop_anchor.lck")+300 <= strtotime("now")) {
							if (file_exists("/var/run/suricata_{$if}{$uuid}_stop.lck")){
								unlink("/var/run/suricata_{$if}{$uuid}_stop.lck");
							}
							if (file_exists("/etc/suricata_{$if}{$uuid}_stop.lck")){
								unlink("/etc/suricata_{$if}{$uuid}_stop.lck");
							}
							if (file_exists("/tmp/suricata_{$if}{$uuid}_stop_anchor.lck")){
								unlink("/tmp/suricata_{$if}{$uuid}_stop_anchor.lck");
							}
						}
					}
					if (!file_exists("/var/run/suricata_{$if}{$uuid}_stop.lck") || !file_exists("/etc/suricata_{$if}{$uuid}_stop.lck")) {
						if (intval(trim(shell_exec("ps ax | grep 'suricata_{$uuid}_{$if}' -c"))) != 0) {
							file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');
							if (intval(trim(shell_exec("ps ax | grep 'suricata_{$uuid}_{$if}' | grep 'suricata2.yaml' | grep -v grep -c"))) != 1) {
								if (file_exists("/var/run/suricata_{$if}{$uuid}_2.pid")) {
									mwexec("rm -rf /var/run/suricata_{$if}{$uuid}_2.pid");
								}
								if (file_exists("/etc/suricata_{$if}{$uuid}_mix_netmap_on")) {
									suricata_start_mixed_mode($suricatacfg, $if, $uuid);
									//mwexec("/usr/local/bin/suricata --netmap -D -c /usr/local/etc/suricata/suricata_{$uuid}_{$if}/suricata2.yaml --pidfile /var/run/suricata_{$if}{$uuid}_2.pid");
								}
							}
							if (intval(trim(shell_exec("ps ax | grep 'suricata_{$uuid}_{$if}' | grep 'suricata.yaml' | grep -v grep -c"))) != 1) {
								if (file_exists("/var/run/suricata_{$if}{$uuid}.pid")) {
									mwexec("rm -rf /var/run/suricata_{$if}{$uuid}.pid");
								}
								mwexec("/usr/local/bin/suricata -i {$if} -D -c /usr/local/etc/suricata/suricata_{$uuid}_{$if}/suricata.yaml --pidfile /var/run/suricata_{$if}{$uuid}.pid");
							}
						} else {
							killActions("suricata_{$if}{$uuid}");
							exec("rm -rf /var/run/suricata_{$if}{$uuid}*");
							$startSleep = true;
						}
					}
				}
			}


			if($startSleep) {
				sleep(5);
			}

			$status = get_service_stat($service, false, true, false, "state");

			if ($status == "Stopped") {
				clean();

				file_put_contents("{$g['varrun_path']}/suricata_start_acp.lck", '');
				if (!is_array($config['installedpackages']['suricata']['rule']))
					$config['installedpackages']['suricata']['rule'] = array();
				
				/* do nothing if no Suricata interfaces active */
				if (!is_array($config['installedpackages']['suricata']['rule'])) {
					return;
				}

				$interfaces_in_lck = [];
				foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {

					$if_real = get_real_interface($suricatacfg['interface']);
					$suricata_uuid = $suricatacfg['uuid'];
			
					$lock_del = "{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck";
					if (file_exists($lock_del)) {
						$interfaces_in_lck[] = "{$if_real}{$suricata_uuid}"; 
					}
					$lock_del = "/etc/suricata_{$if_real}{$suricata_uuid}_stop.lck";
					if (file_exists($lock_del)) {
						$interfaces_in_lck[] = "{$if_real}{$suricata_uuid}"; 
					}
			
				}
				$interfaces_in_lck = array_filter(array_unique($interfaces_in_lck));
						
				foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
					$if = get_real_interface($suricatacfg['interface']);
					$uuid = $suricatacfg['uuid'];
					if (!in_array("{$if}{$uuid}", $interfaces_in_lck)) {
						if (in_array($if, $all_gtw,true)) {
							
							if ($suricatacfg['enable'] != 'on' || get_real_interface($suricatacfg['interface']) == "") {
								continue;
							}
							if (file_exists("/var/run/suricata_{$if}{$uuid}.pid") && isvalidpid("/var/run/suricata_{$if}{$uuid}.pid")) {
								continue;
							}
							if (file_exists("/var/run/suricata_{$if}{$uuid}.pid")) {
								mwexec("rm -f /var/run/suricata_{$if}{$uuid}.pid", true);
							}

							$lock_del = "/var/run/suricata_{$if}{$uuid}_stop.lck";
							if (file_exists($lock_del)) {
								unlink($lock_del);
							}
							$lock_del = "/etc/suricata_{$if}{$uuid}_stop.lck";
							if (file_exists($lock_del)) {
								unlink($lock_del);
							}

							if ($suricatacfg['mixed_mode'] == 'on') {

								exec("cd /usr/local/share/suricata/rules/ && rm *");
								exec("cp /usr/local/share/suricata/rules_acp/_emerging.rules /usr/local/share/suricata/rules/");
				
							}

							$dh  = opendir("/usr/local/share/suricata/rules_acp");

							$rulesetsfile = "";
							while (false !== ($filename = readdir($dh))) {
								if (substr($filename, -5) != "rules")
									continue;

								$rulesetsfile .= basename($filename) . "||";
							}

							$config['installedpackages']['suricata']['rule'][$key]['rulesets'] = rtrim($rulesetsfile, "||");

							exec("cp /usr/local/share/suricata/otx/ransomd5/* /usr/local/etc/suricata/suricata_{$uuid}_{$if}/rules/");

							file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');

							// Save configuration changes
							write_config("Active Protection pkg: modified ruleset configuration");

							sleep(2);
							file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');
							suricata_start($suricatacfg, $if);
							sleep(28);
						}
					}
				}
				mwexec_bg('/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py');
			}  
		}
	}

	//FAPP
	if (($service['name'] == 'firewallapp') && ($service['check'] == 'true')) {
		$status = get_service_stat($service, false, true, false, "state");
		if ($status == "Stopped") {

			clean();

			file_put_contents("{$g['varrun_path']}/suricata_start_all.lck", '');
			if (!is_array($config['installedpackages']['suricata']['rule'])) {
				$config['installedpackages']['suricata']['rule'] = array();
			}
			$a_rule = &$config['installedpackages']['suricata']['rule'];

			$interfaces_in_lck = [];
			foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {

				$if_real = get_real_interface($suricatacfg['interface']);
				$suricata_uuid = $suricatacfg['uuid'];
		
				$lock_del = "{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck";
				if (file_exists($lock_del)) {
					$interfaces_in_lck[] = "{$if_real}{$suricata_uuid}"; 
				}
				$lock_del = "/etc/suricata_{$if_real}{$suricata_uuid}_stop.lck";
				if (file_exists($lock_del)) {
					$interfaces_in_lck[] = "{$if_real}{$suricata_uuid}"; 
				}
		
			}
			$interfaces_in_lck = array_filter(array_unique($interfaces_in_lck));

			foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {

				$if = get_real_interface($suricatacfg['interface']);
				$uuid = $suricatacfg['uuid'];
				if (!in_array("{$if}{$uuid}", $interfaces_in_lck)) {
					if (!in_array($if, $all_gtw, true)) {
						if ($suricatacfg['enable'] != 'on' || get_real_interface($suricatacfg['interface']) == "") {
							continue;
						}
						if (file_exists("/var/run/suricata_{$if}{$uuid}.pid") && isvalidpid("/var/run/suricata_{$if}{$uuid}.pid")) {
							continue;
						}
						if (file_exists("/var/run/suricata_{$if}{$uuid}.pid")) {
							mwexec("rm -f /var/run/suricata_{$if}{$uuid}.pid", true);
						}

						$lock_del = "/var/run/suricata_{$if}{$uuid}_stop.lck";
						if (file_exists($lock_del)) {
							unlink($lock_del);
						}
						$lock_del = "/etc/suricata_{$if}{$uuid}_stop.lck";
						if (file_exists($lock_del)) {
							unlink($lock_del);
						}

						$dh  = opendir("/usr/local/share/suricata/rules");

						$rulesetsfile = "";
						while (false !== ($filename = readdir($dh))) {
							if (substr($filename, -5) != "rules")
								continue;

							$rulesetsfile .= basename($filename) . "||";
						}

						$config['installedpackages']['suricata']['rule'][$key]['rulesets'] = rtrim($rulesetsfile, "||");

						file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');

						// Save configuration changes
						write_config("FirewallApp pkg: modified ruleset configuration");

						sleep(2);
						file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');
						suricata_start($suricatacfg, $if);
						sleep(28);
					}
				}
			}
			mwexec_bg('/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py');
		}
	}

	//Renice interfaces
	mwexec("/usr/bin/top -abC 10000 > /tmp/topSuricata");
	if (($service['name'] == 'active-protection') && (isset($service['priority']))) {
		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
			$uuid = $suricatacfg['uuid'];
			if (suricata_is_running($uuid, $if)) {
				if (in_array($if, $all_gtw, true)) {
					if (trim(shell_exec("grep suricata_{$uuid}_{$if} /tmp/topSuricata | grep 'suricata.yaml' | grep -v grep | awk -F\" \" '{print $5}'")) != $service['priority']) {
						mwexec("renice {$service['priority']} -p `ps aux | grep suricata_{$uuid}_{$if} | grep 'suricata.yaml' | grep -v grep | awk -F\" \" '{print $2}'`");
					}
					if (isset($suricatacfg["mixed_mode"]) && $suricatacfg["mixed_mode"] == "on") {
						if (trim(shell_exec("grep suricata_{$uuid}_{$if} /tmp/topSuricata | grep 'suricata2.yaml' | grep -v grep | awk -F\" \" '{print $5}'")) != 5) {
							mwexec("renice 5 -p `ps aux | grep suricata_{$uuid}_{$if} | grep 'suricata2.yaml' | grep -v grep | awk -F\" \" '{print $2}'`");
						}
					}
				}
			}
		}
	}

	mwexec("/usr/bin/top -abC 10000 > /tmp/topSuricata");
	if (($service['name'] == 'firewallapp') &&  (isset($service['priority']))) {
		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
			$uuid = $suricatacfg['uuid'];
			if (suricata_is_running($uuid, $if)) {
				if (!in_array($if, $all_gtw, true)) {
					if (trim(shell_exec("grep suricata_{$uuid}_{$if} /tmp/topSuricata | grep -v grep | awk -F\" \" '{print $5}'")) != $service['priority']) {
						mwexec("renice {$service['priority']} -p `ps aux | grep suricata_{$uuid}_{$if} | grep -v grep | awk -F\" \" '{print $2}'`");
					}
				}
			}
		}
	}
	
	if (($service['name'] == 'syslogd') && ($service['check'] == 'true')) {
		$status = get_service_stat($service, false, true, false, "state");
		if ($status == "Stopped") {
			killActions('wf');
			mwexec('/etc/rc.d/syslogd restart');
			print_r('Reiniciando o syslogd');
		}
	}

	if (($service['name'] == 'wfrotated' || $service['name'] == 'wf_monitor') && ($service['check'] == 'true')) {
		$status = get_service_stat($service, false, true, false, "state");
		if ($status == "Stopped") {
			killActions('wf');
			mwexec('/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py');
			killActions('wfrotated restart');
			print_r('Reiniciando o wfrotated');
		}
	}

	if (($service['name'] == 'redis-server') && ($service['check'] == 'true')) {
		$status = get_service_stat($service, false, true, false, "state");
		if ($status == "Stopped") {
			mwexec("/usr/sbin/service redis start");
			print_r('Reiniciando o redis-server');
		}
	}

	if (($service['name'] == 'swapfile_upgrade') && ($service['check'] == 'true')) {
		if (!file_exists("/tmp/swapfile_upgraded")) {
			file_put_contents("/tmp/swapfile_upgraded",0);
		}
		if (intval(trim(file_get_contents("/tmp/swapfile_upgraded"))) == 0) {
			file_put_contents("/tmp/swapfile_upgraded",1);
			mwexec('/bin/sh /etc/swapfile.sh');
			print_r('Configurando Upgrade Swap');
		}
		if ((intval(trim(shell_exec("swapinfo | grep -E \"swapfile|md10\" -c"))) == 0) && (intval(trim(file_get_contents("/tmp/swapfile_upgraded"))) == 1)) {
			file_put_contents("/tmp/swapfile_upgraded",1);
			mwexec('/bin/sh /etc/swapfile.sh');
			print_r('Configurando Upgrade Swap');	
		}
	}

	if (($service['name'] == 'swapfile_upgrade') && ($service['check'] == 'false')) {
		if (!file_exists("/tmp/swapfile_upgraded")) {
			file_put_contents("/tmp/swapfile_upgraded",1);
		}
		if (intval(trim(file_get_contents("/tmp/swapfile_upgraded"))) == 1) {
			file_put_contents("/tmp/swapfile_upgraded",0);
			mwexec('/bin/sh /etc/swap_off.sh');
			print_r('Umount Swapfile');
		}

		if ((intval(trim(shell_exec("swapinfo | grep -E \"swapfile|md10\" -c"))) >= 1) && (intval(trim(file_get_contents("/tmp/swapfile_upgraded"))) == 0)) {
			file_put_contents("/tmp/swapfile_upgraded",0);
			mwexec('/bin/sh /etc/swap_off.sh');
			print_r('Umount Swapfile');
		}
	}

	if (($service['name'] == 'bpmonitor') && ($service['check'] == 'true')) {    
		$status = get_service_stat($service, false, true, false, "state");
		if ($status == "Stopped") {
			killActions('bp_monitor_agent');
			mwexec('/usr/local/bin/python3.8 /usr/local/bin/bp_monitor_agent');
			print_r('Reiniciando o agent');
		} 
	}

	if (($service['name'] == 'smbd') && ($service['check'] == 'true')) {
		$status = get_service_stat($service, false, true, false, "state");
		if ($status == "Stopped") {
			killActions('smb');
			if (file_exists('/usr/local/etc/rc.d/samba_server')) {
				mwexec('/usr/local/etc/rc.d/samba_server onestart');
				print_r('Reiniciando o samba');
			}
		} 
	}

	if (($service['name'] == 'nginx') && ($service['check'] == 'true')) {    
		$status = get_service_stat($service, false, true, false, "state");
		if ($status == "Stopped") {
			mwexec('/etc/rc.restart_webgui');
			mwexec('/etc/rc.php-fpm_restart');
			print_r('Reiniciando o Nginx');
		}
	}

	/*
	if ($service['name'] == 'dnsprotection') {
		[$processCounter, $serviceProcess, $pidProcess, $haveRunning] = returnStatusProcessDNSProtection();
		if (empty($serviceProcess)) {
			if (($service['check'] == 'true') && !empty($pidProcess)) {
				enableServiceDNSProtection();
				print_r('Iniciando DNSProtection');
			}
		} elseif ($serviceProcess != "luna-dns") {
			disabledServiceDNSprotection();
			print_r('Desativando DNSProtection');
		}
	}
	*/
	if (($service['name'] == 'dnsprotection') && ($service['check'] == 'true')) {    
		$status = get_service_stat($service, false, true, false, "state");
		$ymlPathTmp = "/usr/local/pkg/dnsprotect/config-max-now.yml";
		$dnsProtection = "/usr/local/pkg/dnsprotect/dnsprotect-max";
		if (file_exists($ymlPathTmp)) {
			file_put_contents("/tmp/dnsProtectionExecutation", "{$dnsProtection} --config {$ymlPathTmp} >&1 &");
			shell_exec("chmod 755 /tmp/dnsProtectionExecutation");
			mwexec_bg("/bin/sh /tmp/dnsProtectionExecutation");
			sleep(5);
		}
	}

}
// autoload bluepex center
if (intval(trim(shell_exec("/bin/pgrep -l -f client.php | wc -l"))) == 0) {
	mwexec("nohup /usr/local/bin/php /usr/local/bin/bp-manager/client.php > /var/log/bpmonitor_client.log 2 1 &");
}

// autoload services
init_config_arr(array('system', 'bluepex_stats', 'monitor_services'));
$p_config3 = $config['system']['bluepex_stats']['monitor_services'];

$statusSwap = "";
foreach ($config['system']['bluepex_stats']['monitor_services']['item'] as $line_swap) {
	if ($line_swap['name'] == "swapfile_upgrade") {
		$statusSwap = $line_swap['check'];
		break;
	}
}

$arrayReload = [];
$findTagXML = ['reloadservice1','reloadservice2','reloadservice3','reloadservice4'];
foreach($findTagXML as $findTagXMLNow) {
	if(isset($config['system']['bluepex_stats']['monitor_services']["{$findTagXMLNow}"]) && 
	!empty($config['system']['bluepex_stats']['monitor_services']["{$findTagXMLNow}"]) && 
	strlen($config['system']['bluepex_stats']['monitor_services']["{$findTagXMLNow}"]) == 17
	) {
		$arrayReload[] = $config['system']['bluepex_stats']['monitor_services']["{$findTagXMLNow}"];
	}
}
	
$arrayReload = array_unique(array_filter($arrayReload));

if ($p_config3['autoload'] == 'on') {

	$now = time();

	if (count($arrayReload) == 0) {
		$arrayReload = ['06:00:00_06:06:00', '12:00:00_12:06:00', '19:00:00_19:06:00', '23:40:00_23:46:00'];	
	}

	foreach ($arrayReload as $reloadHorarios) {
		
		$hoursReload = explode("_",$reloadHorarios);
		$start = strtotime( date('Y-m-d' . $hoursReload[0]) );
		$end = strtotime( date('Y-m-d' . $hoursReload[1]) );

		if ( $start <= $now && $now <= $end ) {
			$arrayStopInterfaces=[];
			foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
				$if = get_real_interface($suricatacfg['interface']);
				$uuid = $suricatacfg['uuid'];
				if (suricata_is_running($uuid, $if)) {
					suricata_stop($suricatacfg, $if);
					$arrayStopInterfaces[]="{$uuid}{$if}";
					if (isset($suricatacfg["mixed_mode"]) && $suricatacfg["mixed_mode"] == "on") {
						if (intval(trim(shell_exec("ps ax | grep suricata_{$uuid}_{$if} | grep 'suricata2.yaml' | grep -v grep -c"))) > 0) {
							mwexec("kill -9 `ps ax | grep suricata_{$uuid}_{$if} | grep 'suricata2.yaml' | grep -v grep | awk -F\" \" '{print $1}'`");
						}
					}
				}
			}
			killActions('wf');
			killActions('http|tls|alerts');
			killActions("suricata");
			killActions("fetch");
			killActions("clamd");
			if ($statusSwap == "true") {
				$restartSwap = <<<EOD
				echo 1 > /tmp/swapfile_upgraded
				/bin/sh /etc/swap_remount.sh
				sleep 2
				echo 0 > /tmp/swapfile_upgraded
				/bin/sh /etc/swap_off.sh
				sleep 2
				echo 1 > /tmp/swapfile_upgraded
				/bin/sh /etc/swapfile.sh
				EOD;
				file_put_contents('/tmp/restartSwap', $restartSwap);
				mwexec('/bin/sh /tmp/restartSwap && /bin/rm /tmp/restartSwap');
			}
			foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
				$if = get_real_interface($suricatacfg['interface']);
				$uuid = $suricatacfg['uuid'];
				if (in_array("{$uuid}{$if}", $arrayStopInterfaces)) {
					if (!suricata_is_running($uuid, $if)) {
						$lock_del = "/var/run/suricata_{$if}{$uuid}_stop.lck";
						if (file_exists($lock_del)) {
							unlink($lock_del);
						}
						$lock_del = "/etc/suricata_{$if}{$uuid}_stop.lck";
						if (file_exists($lock_del)) {
							unlink($lock_del);
						}
						file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');
						suricata_start($suricatacfg, $if);
					}
				}
			}
			syslog(LOG_NOTICE, "UTM6 Control Services: Restarting general services: {$hoursReload[0]}");
		}
	}
}

// data hardware
if (isset($config['system']['bluepex_stats']['vendor']))
	unset($config['system']['bluepex_stats']['vendor']);

init_config_arr(array('system', 'bluepex_stats', 'vendor'));
$p_config4 = &$config['system']['bluepex_stats']['vendor'];

if (file_exists("/tmp/vendor.txt") && filesize("/tmp/vendor.txt") != 0) {
	$linhas1 = file('/tmp/vendor.txt');
	$vendor = array();
	$count = 0;
	for ($i = 0; $i < count($linhas1); $i++) {
		if (substr($linhas1[$i],0,1) == ' ') {
			if (substr($linhas1[$i],0,10) == '    vendor')
            			$vend = str_replace('vendor', ' ', $linhas1[$i]);

            		$vend = str_replace("'", ' ', $vend);
            		$vend = str_replace("=", ' ', $vend);
            		$vendor['vendor'] = trim(str_replace('  ', ' ', $vend));

			if (substr($linhas1[$i],0,10) == '    device')
				$dev = str_replace('device', ' ', $linhas1[$i]);

			$dev = str_replace("'", ' ', $dev);
			$dev = str_replace("=", ' ', $dev);
			$vendor['device'] = trim(str_replace('  ', ' ', $dev));

			if (substr($linhas1[$i],0,9) == '    class')
				$cla = str_replace('class', ' ', $linhas1[$i]);

			$cla = str_replace("'", ' ', $cla);
			$cla = str_replace("=", ' ', $cla);
			$vendor['class'] = trim(str_replace('  ', ' ', $cla));

			if (substr($linhas1[$i],0,12) == '    subclass')
				$subcla = str_replace('subclass', ' ', $linhas1[$i]);

			$subcla = str_replace("'", ' ', $subcla);
			$subcla= str_replace("=", ' ', $subcla);
			$vendor['subclass'] = trim(str_replace('  ', ' ', $subcla));
			$count = $count +1;

			if ($count == 4) {
				$p_config4['item'][] = $vendor;
				$vendor = array();
				$count = 0;
			}
		}
	}   
}

//-----------------------------------------------------------------------------------------------------------------------
// Packfix list and latest
//-----------------------------------------------------------------------------------------------------------------------
if (isset($config['system']['bluepex_stats']['cpack_list'])) {
	unset($config['system']['bluepex_stats']['cpack_list']);
}

if (isset($config['system']['bluepex_stats']['cpack_latest'])) {
	unset($config['system']['bluepex_stats']['cpack_latest']);
}

if (isset($config['system']['bluepex_packfix']['packfix_list'])) {
	unset($config['system']['bluepex_packfix']['packfix_list']);
}

if (isset($config['system']['bluepex_packfix']['packfix_latest'])) {
	unset($config['system']['bluepex_packfix']['packfix_latest']);
}

init_config_arr(array('system', 'bluepex_packfix', 'packfix_list'));
init_config_arr(array('system', 'bluepex_packfix', 'packfix_latest'));
$p_config5 = &$config['system']['bluepex_packfix']['packfix_list'];
$p_config7 = &$config['system']['bluepex_packfix']['packfix_latest'];

$installed_packfix = bp_list_files_pack_fix('sort', true);

foreach ($installed_packfix as $packfix) {
	$p_config5[$packfix] = '';
}

$p_config7 = end($installed_packfix);

//-----------------------------------------------------------------------------------------------------------------------
//Test - ACP
//-----------------------------------------------------------------------------------------------------------------------

init_config_arr(array('system', 'bluepex_stats', 'active_protection_status'));
$p_config6 = &$config['system']['bluepex_stats']['active_protection_status'];
$p_config6 = "";
if (getStatusNewAcp() == 0 && getInterfaceNewAcp() == 0) {
	$p_config6 = "off";
} else {
	$p_config6 = "on";
}

//-----------------------------------------------------------------------------------------------------------------------
//Test - FAPP
//-----------------------------------------------------------------------------------------------------------------------

init_config_arr(array('system', 'bluepex_stats', 'firewallapp_status'));
$p_config7 = &$config['system']['bluepex_stats']['firewallapp_status'];
$p_config7 = "";
if (getInterfaceNewFapp() == 0 && getStatusNewFapp() == 0) {
	$p_config7 = "off";
} else {
	$p_config7 = "on";
}

// Status interfaces FAPP/ACP
if (isset($config['system']['bluepex_stats']['acp_interfaces'])) {
	unset($config['system']['bluepex_stats']['acp_interfaces']);
}

if (isset($config['system']['bluepex_stats']['fapp_interfaces'])) {
	unset($config['system']['bluepex_stats']['fapp_interfaces']);
}

if (isset($config['installedpackages']['suricata']['rule']) &&
    !empty($config['installedpackages']['suricata']['rule'])) {
	foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
		$if = get_real_interface($suricatacfg['interface']);

		$type_interface = in_array($if, $all_gtw, true) ? "acp_interfaces" : "fapp_interfaces";

		$status_interface = ($suricatacfg['enable'] == "on") ?
		    (suricata_is_running($suricatacfg['uuid'], $if) ? 'running' : 'not_running') :
		    "disabled";

		$config['system']['bluepex_stats'][$type_interface]['item'][] = [
			'identification' => $if . $suricatacfg['uuid'],
			'interface' => $suricatacfg['interface'],
			'descr' => $suricatacfg['descr'],
			'status' => $status_interface,
		];
	}
}

//Uniqueid
bp_generate_uniqueid(true);
$config['system']['bluepex_stats']['uniqueid'] = bp_get_uniqueid();

//UTM Temperature
if (file_exists("/tmp/coretemp.txt") && filesize("/tmp/coretemp.txt") != 0) {
	$linhas1 = file('/tmp/coretemp.txt');
	$coretemp = array();
	$temp = 0;

	for ($i = 0; $i < count($linhas1); $i++) {
		$t = substr($linhas1[$i],-6);
		$t2 = trim(str_replace('C', '', $t));
		$temp = $temp + intval($t2);
	}

	$temp = round($temp / count($linhas1), 2);
	file_put_contents("/tmp/tempmed", $temp);  
}

//Rules DEFAULT for BluePex Endpoint

$alias_check = 0;
$alias_check1 = 0;
$alias_check2 = 0;
$passlist_check = false;

/* check for name conflicts */
init_config_arr(array('aliases', 'alias'));
$a_aliases = &$config['aliases']['alias'];
foreach ($a_aliases as $key => $alias) {
	if (($alias['name'] == 'endpoint_ips')) {
		$alias_check = 1;
		break;
	}
}
$a_aliases1 = &$config['aliases']['alias'];
foreach ($a_aliases1 as $key => $alias1) {
	if (($alias1['name'] == 'endpoint_urls')) {
		$alias_check1 = 1;
		break;
	}
}
$a_aliases2 = &$config['aliases']['alias'];
foreach ($a_aliases2 as $key => $alias2) {
	if (($alias2['name'] == 'bpcentermanager_urls')) {
		$alias_check2 = 1;
		break;
	}
}

if ($alias_check == 0){
	$alias['name'] = 'endpoint_ips';
	$alias['type'] = 'network';
	$alias['address'] = '52.42.202.0/24 139.178.82.0/24 52.179.129.0/24 201.0.221.0/24 52.40.182.0/24 91.199.212.0/24 38.109.53.0/24 149.5.95.0/24 38.113.0.0/16 52.41.0.0/16 92.123.0.0/16 34.208.0.0/16';
	$alias['descr'] = 'ips default for endpoint';
	$alias['detail'] = 'endpoint_ips';

	pfSense_handle_custom_code("/usr/local/pkg/firewall_aliases_edit/pre_write_config");

	if (isset($id) && $a_aliases[$id]) {
		if ($a_aliases[$id]['name'] <> $alias['name']) {
			foreach ($a_aliases as $aliasid => $aliasd) {
				if ($aliasd['address'] <> "") {
					$tmpdirty = false;
					$tmpaddr = explode(" ", $aliasd['address']);
					foreach ($tmpaddr as $tmpidx => $tmpalias) {
						if ($tmpalias == $a_aliases[$id]['name']) {
							$tmpaddr[$tmpidx] = $alias['name'];
							$tmpdirty = true;
							break;
						}
					}
					if ($tmpdirty == true) {
						$a_aliases[$aliasid]['address'] = implode(" ", $tmpaddr);
					}
				}
			}
		}
		$a_aliases[$id] = $alias;
	} else {
		$a_aliases[] = $alias;
	}

	// rules for endpoint ips
	init_config_arr(array('filter', 'rule'));
	filter_rules_sort();
	$a_filter = &$config['filter']['rule'];

	$filterent = array();
	$filterent['id'] = '';
	$filterent['tracker'] = (int)microtime(true);
	$filterent['type'] = 'pass'; 
	$filterent['interface'] = 'lan';
	$filterent['tag'] = '';
	$filterent['tagged'] = '';
	$filterent['max'] = '';
	$filterent['max-src-nodes'] = '';
	$filterent['max-src-conn'] = '';
	$filterent['max-src-state'] = '';
	$filterent['statetimeout'] = '';
	$filterent['statetype'] = 'keep state';
	$filterent['os'] = '';
	//$filterent['source'] = '';
	$filterent['source']['network'] = 'lan';
	//$filterent['destination'] = '';
	$filterent['destination']['address'] = 'endpoint_ips';
	$filterent['descr'] = 'Default allow LAN to endpoint rules ips';
	//$filterent['created'] = '';
	$filterent['created']['time'] = (int)microtime(true);
	$filterent['created']['username'] = 'admin@default (Local Database)';

	// Allow extending of the firewall edit page and include custom input validation
	pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_write_config");

	$a_filter[] = $filterent;
}

if ($alias_check1 == 0){
	$alias1['name'] = 'endpoint_urls';
	$alias1['type'] = 'host';
	$alias1['address'] = 'mq.bluepex.com.br suiteapi.bluepex.com.br emsisoft.com arctic.emsisoft.com cdn.bluepex.com.br dl.emsisoft.com';
	$alias1['descr'] = 'urls default for endpoint';
	$alias1['detail'] = 'endpoint_urls';

	pfSense_handle_custom_code("/usr/local/pkg/firewall_aliases_edit/pre_write_config");

	if (isset($id) && $a_aliases1[$id]) {
		if ($a_aliases1[$id]['name'] <> $alias1['name']) {
			foreach ($a_aliases1 as $aliasid => $aliasd) {
				if ($aliasd['address'] <> "") {
					$tmpdirty = false;
					$tmpaddr = explode(" ", $aliasd['address']);
					foreach ($tmpaddr as $tmpidx => $tmpalias) {
						if ($tmpalias == $a_aliases[$id]['name']) {
							$tmpaddr[$tmpidx] = $alias1['name'];
							$tmpdirty = true;
							break;
						}
					}
					if ($tmpdirty == true) {
						$a_aliases1[$aliasid]['address'] = implode(" ", $tmpaddr);
					}
			}
		}
	}
	$a_aliases1[$id] = $alias1;
	} else {
		$a_aliases1[] = $alias1;
	}

	// rules for endpoint urls
	init_config_arr(array('filter', 'rule'));
	filter_rules_sort();
	$a_filter1 = &$config['filter']['rule'];

	$filterent1 = array();
	$filterent1['id'] = '';
	$filterent1['tracker'] = (int)microtime(true);
	$filterent1['type'] = 'pass'; 
	$filterent1['interface'] = 'lan';
	$filterent1['tag'] = '';
	$filterent1['tagged'] = '';
	$filterent1['max'] = '';
	$filterent1['max-src-nodes'] = '';
	$filterent1['max-src-conn'] = '';
	$filterent1['max-src-state'] = '';
	$filterent1['statetimeout'] = '';
	$filterent1['statetype'] = 'keep state';
	$filterent1['os'] = '';
	//$filterent['source'] = '';
	$filterent1['source']['network'] = 'lan';
	//$filterent['destination'] = '';
	$filterent1['destination']['address'] = 'endpoint_urls';
	$filterent1['descr'] = 'Default allow LAN to endpoint rules urls';
	//$filterent['created'] = '';
	$filterent1['created']['time'] = (int)microtime(true);
	$filterent1['created']['username'] = 'admin@default (Local Database)';

	// Allow extending of the firewall edit page and include custom input validation
	pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_write_config");

	$a_filter1[] = $filterent1;
}

if ($alias_check2 == 0){
	$alias2['name'] = 'bpcentermanager_urls';
	$alias2['type'] = 'host';
	$alias2['address'] = 'chatendpoint.bluepex.com.br chatendpoint.bluepex.com.br apichatendpoint.bluepex.com.br';
	$alias2['descr'] = 'urls default for bp_center_manager';
	$alias2['detail'] = 'bp_center_manager';

	pfSense_handle_custom_code("/usr/local/pkg/firewall_aliases_edit/pre_write_config");

	if (isset($id) && $a_aliases2[$id]) {
		if ($a_aliases2[$id]['name'] <> $alias2['name']) {
			foreach ($a_aliases2 as $aliasid => $aliasd) {
				if ($aliasd['address'] <> "") {
					$tmpdirty = false;
					$tmpaddr = explode(" ", $aliasd['address']);
					foreach ($tmpaddr as $tmpidx => $tmpalias) {
						if ($tmpalias == $a_aliases[$id]['name']) {
							$tmpaddr[$tmpidx] = $alias2['name'];
							$tmpdirty = true;
							break;
						}
					}
					if ($tmpdirty == true) {
						$a_aliases2[$aliasid]['address'] = implode(" ", $tmpaddr);
					}
				}
			}
		}
		$a_aliases2[$id] = $alias2;
	} else {
		$a_aliases2[] = $alias2;
	}

	// rules for bp_center_manager
	init_config_arr(array('filter', 'rule'));
	filter_rules_sort();
	$a_filter2 = &$config['filter']['rule'];

	$filterent2 = array();
	$filterent2['id'] = '';
	$filterent2['tracker'] = (int)microtime(true);
	$filterent2['type'] = 'pass'; 
	$filterent2['interface'] = 'lan';
	$filterent2['tag'] = '';
	$filterent2['tagged'] = '';
	$filterent2['max'] = '';
	$filterent2['max-src-nodes'] = '';
	$filterent2['max-src-conn'] = '';
	$filterent2['max-src-state'] = '';
	$filterent2['statetimeout'] = '';
	$filterent2['statetype'] = 'keep state';
	$filterent2['os'] = '';
	//$filterent['source'] = '';
	$filterent2['source']['network'] = 'lan';
	//$filterent['destination'] = '';
	$filterent2['destination']['address'] = 'bpcentermanager_urls';
	$filterent2['descr'] = 'Default allow LAN to bp_center_manager urls';
	//$filterent['created'] = '';
	$filterent2['created']['time'] = (int)microtime(true);
	$filterent2['created']['username'] = 'admin@default (Local Database)';

	// Allow extending of the firewall edit page and include custom input validation
	pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_write_config");

	$a_filter2[] = $filterent2;
}

// check packages wflogs exists
init_config_arr(array('installedpackages', 'package'));
$p_packages = &$config['installedpackages']['package'];

$wf_sq = false;
$wf_cr = false;
$wf_cr1 = false;

foreach ($p_packages as $pack) {
	if (($pack['logging']['facilityname'] == 'local2.info') && ($pack['logging']['logfilename'] == 'squid.log')) {
		$wr_sq = true;
	}
	if (($pack['logging']['facilityname'] == 'local2.info') && ($pack['logging']['logfilename'] == '|exec /usr/local/bin/wft_log.sh')) {
		$wr_cr = true;
	}
	if ($pack['logging']['facilityname'] == 'local6.info') {
		$wr_cr1 = true;
	}
}

if ($wr_sq == false) {
	$item = array();
	$item_pack = array(); 
	$item['name'] = '![CDATA[squid]]';
	$item_pack = $item;

	$item_pack['logging']['appname'] = '*squid*,redirector';
	$item_pack['logging']['facilityname'] = 'local2.info';
	$item_pack['logging']['logfilename'] = 'squid.log';

	$p_packages['logging'] = $item_pack;
}

if ($wr_cr == false) {
	$item = array();
	$item_pack = array(); 
	$item['name'] = '![CDATA[wft_log]]';
	$item_pack = $item;

	$item_pack['logging']['appname'] = '*squid*,redirector';
	$item_pack['logging']['facilityname'] = 'local2.info';
	$item_pack['logging']['logfilename'] = '|exec /usr/local/bin/wft_log.sh';

	$p_packages['logging'] = $item_pack;
}

if ($wr_cr1 == false) {
	$item = array();
	$item_pack = array();
	$item['name'] = '![CDATA[wflogs]]';
	$item_pack = $item;
    
	$item_pack['logging']['appname'] = 'wfrotated';
	$item_pack['logging']['facilityname'] = 'local6.info';
	$item_pack['logging']['logfilename'] = 'wflogs.log';
    
	$p_packages['logging'] = $item_pack;
}

//Check Version UTM
$mode = "";
if (file_exists('/etc/mode')) {
	$mode = file_get_contents("/etc/mode");
}

if ((empty($mode)) || ($mode == "stable")) {
	mwexec("/usr/local/bin/curl http://wsutm.bluepex.com/packs/{$versionutm}/version_server -o /tmp/tmp_file_test");
} else {
	mwexec("/usr/local/bin/curl http://wsutm.bluepex.com/packs/{$versionutm}/version_server_h -o /tmp/tmp_file_test");
}

if (file_exists('/tmp/tmp_file_test')) {
	if (intval(trim(shell_exec("/usr/bin/grep -r '404.svg' /tmp/tmp_file_test | /usr/bin/wc -l"))) == 0) {

		if ((empty($mode)) || ($mode == "stable")) {
			mwexec("cd /etc && rm -f /etc/version_server && fetch http://wsutm.bluepex.com/packs/{$versionutm}/version_server");
		} else {
			mwexec("cd /etc && rm -f /etc/version_server && fetch http://wsutm.bluepex.com/packs/{$versionutm}/version_server_h");
			mwexec('mv /etc/version_server_h  /etc/version_server');
		}

		$versionserver = file_get_contents("/etc/version_server");
		$versionutmfull = file_get_contents("/etc/version"); 

		if ($versionserver != $versionutmfull) {
			mwexec("cp /etc/version_server /etc/update_pack && rm -f /etc/version_server");
			init_config_arr(array('system', 'firmware'));

			if (isset($config['system']['firmware']['disablecheck'])) {
				if (file_exists("/etc/update_pack")) {
					$p_version = trim(substr(file_get_contents("/etc/update_pack"),strpos(file_get_contents("/etc/version"),"P")+1 ,3));
					$pack_name = "pack_{$p_version}.sh";
					$pack_sha = "pack{$p_version}.sha256";
					$pk_version_server = trim($versionserver).'.zip';

					mwexec("cd /etc && rm -f /etc/{$versionutm}_{$pack_sha} && fetch http://pkg.bluepex.com/sha256/{$versionutm}/{$versionutm}_{$pack_sha}");
					mwexec("cd /etc && rm -f /etc/update_{$pk_version_server} && fetch http://wsutm.bluepex.com/packs/{$versionutm}/update_{$pk_version_server}");

					$sha_test = trim(file_get_contents("/etc/{$versionutm}_{$pack_sha}"));
					$sha_file = shell_exec("cd /etc/ && sha256 -q update_{$pk_version_server}");

					if (trim($sha_test) == trim($sha_file)) {
						mwexec("rm -f /usr/local/share/BluePexUTM/{$pack_name} && cd /usr/local/share/BluePexUTM && fetch http://wsutm.bluepex.com/packs/{$versionutm}/{$pack_name}");
						mwexec("cd /usr/local/share/BluePexUTM && sh {$pack_name} && rm -f /etc/update_pack");
					}
				}
			}
		} else {
			mwexec("rm -f /etc/update_pack");
		}
	}
}
unlink_if_exists('/tmp/tmp_file_test');

//Apply configs in XML
print_r("\nConfigura\xc3\xa7\xc3\xb5es do check_data salvas com sucesso!!!\n");
write_config("Configura\xc3\xa7\xc3\xb5es do check_data salvas com sucesso!!!");

//Ensuring cleanliness of the rules on a constant basis
clean();

