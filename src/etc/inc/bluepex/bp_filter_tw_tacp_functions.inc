<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2024
 * ====================================================================
 *
 */

require_once("config.inc");
require_once("interfaces.inc");
require_once("util.inc");
require_once("firewallapp_functions.inc");
require_once("firewallapp_webservice.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

// Define variables
define("URL_WSUTM", "http://wsutm.bluepex.com:33777/api/");

$token_api = "";

// Confirm serial
$serial = file_exists('/etc/serial') ? trim(file_get_contents('/etc/serial')) : '';

if (empty($serial)) {
	log_error("Bluepex: Access Denials Filter Logs: Equipment without serial, it is not possible to continue the actions");
	exit;
}

// Get the token
function get_token($type) {
	global $token_api;

	if (empty($type)) {
		return;
	}

	try {
		$curl = curl_init();
		$url = URL_WSUTM."login";

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => "user=devutm&pwd=bddda08abb3cfbc5f04ad561d880cead",
			CURLOPT_HTTPHEADER => array("Content-Type: application/x-www-form-urlencoded"),
		));

		$response = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);

		if ($info["http_code"] == 200 ||
		    $info["http_code"] == 201) {
			$data = json_decode($response, true);

			if (isset($data["token"])) {
				$token_api = $data["token"];
			}
		} else {
			log_error("Bluepex: Access Denials Filter Logs: {$type}: Access error 200/201 to get Token API");
		}
	} catch (Exception $e){
		log_error("Bluepex: Access Denials Filter Logs: {$type}: Error getting token: " . $e->getMessage());
	}
}

// Send values
function set_values($values, $type) {
	global $token_api;

	if (empty($values) ||
	    empty($type)) {
		return;
	}

	try {
		$curl = curl_init();
		$url = URL_WSUTM."threats_new/{$values}";

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_HTTPHEADER => array("x-access-token:{$token_api}"),
		));

		curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);

		if ($info["http_code"] != 200 &&
		    $info["http_code"] != 201) {
			log_error("Bluepex: Access Denials Filter Logs: {$type}: Access error 200/201 to send values API");
		}
	} catch (Exception $e) {
		log_error("Bluepex: Access Denials Filter Logs: {$type}: Error sending filtered log:" . $e->getMessage());
	}
}

// Filter zero to day for logs
function bp_return_date_filter_log($filter_date = "") {
	if (empty($filter_date)) {
		$filter_date = strtotime("now");
	}

	$date_grep = date("M d H:", $filter_date);

	for ($counter=1; $counter <= 9; $counter++) {
		$date_grep = str_replace(" 0{$counter} ", "  $counter ", $date_grep);
	}

	return $date_grep;
}

// Generec action to send values
function bp_send_array_values($type_action, $values_write = []) {
	global $token_api;

	if (empty($type_action) ||
	    empty($values_write)) {
		return;
	}

	// Pause before execute action
	sleep(rand(1, 1800));

	// Renew Token
	$date_loop = 0;

	// Send values
	foreach ($values_write as $lines_write) {
		if (strtotime("now") >= $date_loop) {
			sleep(rand(1,5));
			get_token($type_action);
			$date_loop = strtotime("now") + 50;
		}

		ob_start();
		set_values($lines_write, $type_action);
		ob_end_clean();
	}
}

// Generate support filter file and clear old supports
function bp_support_files_to_send($file_filter_support_all, $file_filter_support_day) {
	if (empty($file_filter_support_all) ||
	    empty($file_filter_support_day)) {
		return false;
	}

	if (!file_exists($file_filter_support_day)) { file_put_contents($file_filter_support_day, ""); }

	foreach (glob($file_filter_support_all) as $filter_filter) {
		if ($filter_filter == $file_filter_support_day) {
			continue;
		}

		unlink_if_exists($filter_filter);
	}

	return true;
}

// Generate file to filter external address
function bp_generate_filter_external_address($file_target) {
	file_put_contents($file_target, <<<EOD
<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2024
 * ====================================================================
 *
 */

require_once('config.inc');
require_once('interfaces.inc');
require_once('pfsense-utils.inc');
require_once('util.inc');

if (!isset(\$argv[1]) ||
    empty(\$argv[1])) {
	exit;
}

\$values = \$argv[1];

[\$real_interface, \$address] = explode("|", \$values);

\$real_interface = str_replace(",", "", \$real_interface);
\$address = str_replace(",", "", \$address);

if (empty(\$real_interface) ||
    empty(\$address)) {
	echo "false";
	exit;
}

\$intip = find_interface_ip(\$real_interface);
\$netmask = find_interface_subnet(\$real_interface);

if (!ip_in_subnet(\$address, gen_subnet(\$intip, \$netmask) . "/\$netmask")) {
	echo "true";
	exit;
}

echo "false";
exit;
EOD);
}
