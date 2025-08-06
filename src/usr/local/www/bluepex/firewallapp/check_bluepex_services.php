<?php

require_once("/usr/local/pkg/suricata/suricata.inc");

global $g, $rebuild_rules;

define('DEBUG', false);
define('WSUTM_CACHE_FILE', "{$g['tmp_path']}/wsutm.cache");

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
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
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
	$gc = exec($command, $out, $err);
	if ($err == 0) {
		return $out[0];
	} else {
		return '';
	}
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
	return $serial_status;
}

function checkFirewallAppService() {

	$status = get_serial_status();

	if ($status == "ok") {
		startFirewallAppService();
	} else {
		stopFirewallAppService();
	}

}

function startFirewallAppService() {

	require("config.inc");

	global $g, $rebuild_rules;

	$suricata_rules_dir = SURICATA_RULES_DIR;
	$suricatalogdir = SURICATALOGDIR;

	if (!is_array($config['installedpackages']['suricata']))
		$config['installedpackages']['suricata'] = array();
	$suricataglob = $config['installedpackages']['suricata'];

	if (!is_array($config['installedpackages']['suricata']['rule']))
		$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$id = 0;

	// INICIALIZA O SERVICO DO SURICATA
	$suricatacfg = $config['installedpackages']['suricata']['rule'][0];
	$if_real = get_real_interface($suricatacfg['interface']);
	$if_friendly = convert_friendly_interface_to_friendly_descr($suricatacfg['interface']);

	$start_lck_file = "{$g['varrun_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_starting.lck";
	$suricata_start_cmd = <<<EOD
			<?php
			require_once('/usr/local/pkg/suricata/suricata.inc');
			require_once('service-utils.inc');
			global \$g, \$rebuild_rules, \$config;
			\$suricatacfg = \$config['installedpackages']['suricata']['rule'][{$id}];
			\$rebuild_rules = true;
			touch('{$start_lck_file}');
			conf_mount_rw();
			sync_suricata_package_config();
			conf_mount_ro();
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

}

function stopFirewallAppService() {

	require("config.inc");

	global $g, $rebuild_rules;

	$suricata_rules_dir = SURICATA_RULES_DIR;
	$suricatalogdir = SURICATALOGDIR;

	if (!is_array($config['installedpackages']['suricata']))
		$config['installedpackages']['suricata'] = array();
	$suricataglob = $config['installedpackages']['suricata'];

	if (!is_array($config['installedpackages']['suricata']['rule']))
		$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$id = 0;

	$suricatacfg = $config['installedpackages']['suricata']['rule'][0];
	$if_real = get_real_interface($suricatacfg['interface']);

	// Clear block table
	$suri_pf_table = SURICATA_PF_TABLE;
	exec("/sbin/pfctl -t {$suri_pf_table} -T flush");

	if (suricata_is_running($suricatacfg['uuid'], $if_real)) {
		log_error("Stopping Suricata on {$if_friendly}({$if_real}) per user request...");
		suricata_stop($suricatacfg, $if_real);
	}

	unset($suri_starting[$id]);
	unset($by2_starting[$id]);

	unlink_if_exists($start_lck_file);

}

checkFirewallAppService();

?>
