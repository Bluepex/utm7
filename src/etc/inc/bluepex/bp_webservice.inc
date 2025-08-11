<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 * Written by  Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */
require_once("config.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

define("DEBUG", false);
define("WSUTM_CACHE_FILE", "{$g['tmp_path']}/wsutm.cache");
define("FILE_FORCE_STOP_INTERFACE_FAPP_ACP", "{$g['tmp_path']}/force_stop_interfaces_fapp_acp");
define("BP_FILE_STOP_SERVICES", "{$g['tmp_path']}/bp_stop_services_license");
define("BP_FILE_TO_SHOW_MSG", "{$g['tmp_path']}/bp_show_msg_to_license");
define("TIME_EXTRA_STOP_SERVICES", "+8 days");
define("TIME_EXTRA_TO_SHOW_MSG", "+1 days");

function do_webservice_request($service, $method, $params = array()) {
	// Set limit to 0 for CURLOPT_CONNECTTIMEOUT
	set_time_limit(0);

	$url = "http://wsutm.bluepex.com/api/{$service}/{$method}";

	$data = array(
		"serial" => file_exists('/etc/serial') ? trim(file_get_contents('/etc/serial')) : '',
		"product_key" => getProductKey(),
	);

	if (!empty($params)) {
		$data = array_merge($data, $params);
	}

	$ch = curl_init($url);
	if (is_resource($ch)) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		if (DEBUG)
			curl_setopt($ch, CURLOPT_VERBOSE, true);

		$result = curl_exec($ch);
		curl_close($ch);
		
		if (empty($result))
			return "error";

		$ret = json_decode($result);
		if(empty($ret))
			return "error";
		return $ret->response;
	}
}

function getProductKey() {

	$command = "/usr/local/sbin/dmidecode -t 4 | grep ID | sed 's/.*ID://;s/ //g'";

	if (file_exists("/etc/generatyNewKey")) {
		$command = "gpart list | grep rawuuid | head -n1 | awk -F\"-\" '{ print $4$5 }' | tr '[:lower:]' '[:upper:]'";
	}

	$gc = exec($command, $out, $err);
	if ($err == 0) {
		return $out[0];
	} else {
		return '';
	}
}

function send_serial_productkey() {
	$resp = do_webservice_request('util', 'insert-serial-productkey');

	if ( ($resp->status != 'ok') )
		log_error(dgettext("BPBackup", "Error to server validate serial and productkey."));
}

function create_wsutm_cache_file() {
	$data = array();
	$data['expire'] = time() + 24 * 60 * 60;

	$resp = do_webservice_request("serial", "info");
	if (isset($resp->data) && !empty($resp->data)) {
		$data['serial'] = $resp->data;
	}
	if (file_put_contents(WSUTM_CACHE_FILE, serialize($data))) {
		return true;
	}
	return false;
}

function read_wsutm_cache_file() {
	if (!file_exists(WSUTM_CACHE_FILE)) {
		create_wsutm_cache_file();
	}
	if (file_exists(WSUTM_CACHE_FILE)) {
		$content = unserialize(file_get_contents(WSUTM_CACHE_FILE));
		if (isset($content['expire']) && $content['expire'] <= time()) {
			unlink(WSUTM_CACHE_FILE);
			$content = read_wsutm_cache_file();
		}
		return $content;
	}
}

function get_serial_status() {
	$data = read_wsutm_cache_file();
	$serial_status = "ok";
	if (!isset($data['serial']) || (isset($data['serial']->overdue) && $data['serial']->overdue == "1")) {
		$serial_status = "irregular";
	} elseif (isset($data['serial']->cancelled) && $data['serial']->cancelled == "1") {
		$serial_status = "cancelled";
	}

	if ($serial_status != "ok" &&
	    !file_exists(BP_FILE_STOP_SERVICES)) {
		file_put_contents(BP_FILE_STOP_SERVICES, strtotime(TIME_EXTRA_STOP_SERVICES));
		file_put_contents(BP_FILE_TO_SHOW_MSG, strtotime(TIME_EXTRA_TO_SHOW_MSG));
	} elseif ($serial_status == "ok" &&
	     file_exists(BP_FILE_STOP_SERVICES)) {
		unlink_if_exists(BP_FILE_STOP_SERVICES);
		unlink_if_exists(BP_FILE_TO_SHOW_MSG);
	}

	return $serial_status;
}

function check_serial_status() {
	$status = get_serial_status();
	if ($status !== "ok") {
		set_flash_message("warning", "<i class='fa fa-warning'></i> " . sprintf(gettext("The Serial Number assigned to this UTM is '%s'! To avoid the stopping of the services, please contact the BluePex Support Team for further information."), strtoupper($status)));
	}
}

function redirect_licensed_area($area_id = "") {

	$status = get_serial_status();
	
	if ($status === "ok") {
		return;
	}

	if (file_exists(BP_FILE_STOP_SERVICES) &&
	    !empty(trim(file_get_contents(BP_FILE_STOP_SERVICES))) &&
	    strtotime('now') <= trim(file_get_contents(BP_FILE_STOP_SERVICES))) {
		return;
	}

	if (!empty($area_id)) {
		$exec_function_cancelled_serial = "{$area_id}_cancelled_serial";
		$exec_function_irregular_serial = "{$area_id}_irregular_serial";

		if ($status === "cancelled" && function_exists($exec_function_cancelled_serial)) {
			$exec_function_cancelled_serial();
		}
		if ($status === "irregular" && function_exists($exec_function_irregular_serial)) {
			$exec_function_irregular_serial();
		}
	}

	require_once('util.inc');
	//killbyname("suricata");
	$pages_necessary_license = ["oc_services.php", "ap_services.php",
		"wf_dataclick_settings.php", "services.php",
		"report_settings.php", "consumo_de_aplicacoes.php",
		"relatorios.php", "services_acp_rules.php", 
		"services_acp_ameacas.php", "request_api_equipment.php",
		"request_api_equipment_table.php"];
	$page_now = end(explode("/", $_SERVER["REQUEST_URI"]));
	if (array_search($page_now, $pages_necessary_license)) {
		header("Location: /bluepex_license_control.php?serial_status={$status}");
		exit;
	}

	return;
}

function bp_restrict_acesses_page() {
	$pages_restrict = [
		"acp_global_config.php",
		"services_acb.php",
		"services_acb_backup.php",
		"services_acb_settings.php",
		"system_register.php"
	];
	foreach(['DOCUMENT_URI', 'SCRIPT_FILENAME', 'PHP_SELF'] as $server_session) {
		if (in_array(end(explode("/", $_SERVER[$server_session])), $pages_restrict)) {
			header("Location: /bluepex_access_control.php?restrict=block");
			exit;
		}
	}
}

function checkFirewallAppService() {
	$status = get_serial_status();

	if (file_exists(BP_FILE_STOP_SERVICES) &&
	    !empty(trim(file_get_contents(BP_FILE_STOP_SERVICES))) &&
	    strtotime('now') <= trim(file_get_contents(BP_FILE_STOP_SERVICES))) {
		return;
	}

	if ($status != "ok") {
		stopFirewallAppService();
	}
}

function startFirewallAppService() {
	require_once("config.inc");

	global $g, $rebuild_rules;

	$suricata_rules_dir = SURICATA_RULES_DIR;
	$suricatalogdir = SURICATALOGDIR;

	if (!is_array($config['installedpackages']['suricata'])) {
		$config['installedpackages']['suricata'] = array();
	}

	$suricataglob = $config['installedpackages']['suricata'];

	if (!is_array($config['installedpackages']['suricata']['rule'])) {
		$config['installedpackages']['suricata']['rule'] = array();
	}

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$id = 0;

	$suricatacfg = $config['installedpackages']['suricata']['rule'][0];
	$if_real = get_real_interface($suricatacfg['interface']);
	$if_friendly = convert_friendly_interface_to_friendly_descr($suricatacfg['interface']);

	$start_lck_file = "{$g['varrun_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_starting.lck";
	file_put_contents("/var/run/suricata_{$if_real}{$suricatacfg['uuid']}_starting", '');

	$suricata_start_cmd = <<<EOD

	<?php
	require_once('/usr/local/pkg/suricata/suricata.inc');
	require_once('service-utils.inc');
	global \$g, \$rebuild_rules, \$config;
	\$suricatacfg = \$config['installedpackages']['suricata']['rule'][{$id}];
	\$rebuild_rules = true;
	touch('{$start_lck_file}');
	sync_suricata_package_config();
	\$rebuild_rules = false;
	suricata_start(\$suricatacfg, '{$if_real}');
	unlink_if_exists('{$start_lck_file}');
	unlink(__FILE__);
	?>
EOD;

	file_put_contents("{$g['tmp_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_startcmd.php", $suricata_start_cmd);

	if (!suricata_is_running($suricatacfg['uuid'], $if_real)) {
		log_error("Starting Suricata on {$if_friendly}({$if_real}) per user request...");
		mwexec_bg("/usr/local/bin/php -f {$g['tmp_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_startcmd.php");
	}
	unset($suricata_start_cmd);
}

function stopFirewallAppService() {
	require("config.inc");

	global $g, $rebuild_rules, $config;

	$suricata_rules_dir = SURICATA_RULES_DIR;
	$suricatalogdir = SURICATALOGDIR;

	if (!is_array($config['installedpackages']['suricata'])) {
		$config['installedpackages']['suricata'] = array();
	}

	$suricataglob = $config['installedpackages']['suricata'];

	if (!is_array($config['installedpackages']['suricata']['rule'])){
		$config['installedpackages']['suricata']['rule'] = array();
	}

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	foreach ($config['installedpackages']['suricata']['rule'] as $id => $suricatacfg) {
		$if_real = get_real_interface($suricatacfg['interface']);
		$if_friendly = convert_friendly_interface_to_friendly_descr($suricatacfg['interface']);

		if (suricata_is_running($suricatacfg['uuid'], $if_real)) {
			log_error("Stopping Suricata on {$if_friendly}({$if_real}) per user request...");
			suricata_stop($suricatacfg, $if_real);
			file_put_contents(FILE_FORCE_STOP_INTERFACE_FAPP_ACP, "{$if_real}{$suricatacfg['uuid']}\n", FILE_APPEND);
		}

		unset($suri_starting[$id]);
		unset($by2_starting[$id]);
	}

	unlink_if_exists($start_lck_file);

	unset($suricata_start_cmd);

}

function bp_remove_lck_to_interfaces_if_serial_is_validate() {
	global $g;

	$status = get_serial_status();

	if ($status != "ok" ||
	    !file_exists(FILE_FORCE_STOP_INTERFACE_FAPP_ACP) ||
	    (file_exists(FILE_FORCE_STOP_INTERFACE_FAPP_ACP) &&
	    empty(trim(file_get_contents(FILE_FORCE_STOP_INTERFACE_FAPP_ACP))))) {
		return;
	}

	$remove_lck_interfaces = array_unique(array_filter(explode("\n", trim(file_get_contents(FILE_FORCE_STOP_INTERFACE_FAPP_ACP)))));

	foreach ($remove_lck_interfaces as $values) {
		unlink_if_exists("{$g['etc_path']}/suricata_{$values}_stop.lck");
		unlink_if_exists("{$g['varrun_path']}/suricata_{$values}_stop.lck");
	}

	unlink_if_exists(FILE_FORCE_STOP_INTERFACE_FAPP_ACP);
}

function returnIfExistsValuesInDB() {
	if (intval(trim(shell_exec("ps ax | grep mysqld | grep -v grep -c"))) > 0) {
		$httpCount = count(array_unique(array_filter(explode("\n", trim(shell_exec("mysql -uwebfilter -pwebfilter webfilter -e 'select id from http limit 11;'"))))));
		$httpsCount = count(array_unique(array_filter(explode("\n", trim(shell_exec("mysql -uwebfilter -pwebfilter webfilter -e 'select id from https limit 11;'"))))));
		$accessesCount = count(array_unique(array_filter(explode("\n", trim(shell_exec("mysql -uwebfilter -pwebfilter webfilter -e 'select id from accesses limit 11;'"))))));
		$alertsCount = count(array_unique(array_filter(explode("\n", trim(shell_exec("mysql -uwebfilter -pwebfilter webfilter -e 'select id from alerts limit 11;'"))))));
		return ($httpCount + $httpsCount + $accessesCount + $alertsCount) >= 11;
	} else {
		return false;
	}
}

function returnIfExistsValuesInDBSqlite() {
	if (file_exists('/var/db/openvpn_control.db')) {
		if (intval(trim(shell_exec("/usr/local/bin/sqlite3 /var/db/openvpn_control.db '.tables' | awk -F\" \" '{print $2}' | grep openvpn_control -c"))) > 0) {
			$openVpnAccess = count(array_unique(array_filter(explode("\n", trim(shell_exec("/usr/local/bin/sqlite3 /var/db/openvpn_control.db 'select id from openvpn_control limit 11'"))))));
			return $openVpnAccess >= 1;
		} else {
			return false;
		}
	} else {
		return false;
	}
}
