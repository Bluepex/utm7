<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Marcos Claudiano Moreira <marcos.claudiano@bluepex.com>, 2024
 * ====================================================================
 *
 */

require_once('config.inc');

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}

/* Get interface IP and netmask for Squid interfaces */
function squid_get_real_interface_address($iface) {
	if (!function_exists("get_interface_ip")) {
		require_once("interfaces.inc");
	}

	return array(get_interface_ip($iface), gen_subnet_mask(get_interface_subnet($iface)));
}

$wf_instances = &$config['system']['webfilter']['instance']['config'];

// Go through all instances to find the one with auth_method = "entraid"
foreach ($wf_instances as $index => $instance) {
	if (isset($instance['server']['authsettings']['auth_method']) &&
	    $instance['server']['authsettings']['auth_method'] === "entraid") {
		// Find the corresponding 'server' tag
		$settings = $instance['server'];
		$ssl_ifaces = explode(",", $settings['active_interface']);

		foreach ($ssl_ifaces as $s_iface) {
			$s_iface_ip = squid_get_real_interface_address($s_iface);
			// Process the IP address as needed (e.g. print or store)
			// echo "Interface: $s_iface, IP: $s_iface_ip\n";
		}

		// If you only want the first occurrence, add a 'break' to exit the loop
		break;
	}
}

/* if port is empty lets rely on the protocol selection */
$port = (isset($config['system']['webgui']['port']) &&
    !empty($config['system']['webgui']['port'])) ?
	$config['system']['webgui']['port'] :
	(($config['system']['webgui']['protocol'] == "http") ? 80 : 443);

$user = $_GET['u'];
$mail = $_GET['e'];
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'];
$url_utm = "https://{$s_iface_ip[0]}:{$port}";
$sleep_value = 2;

if (isset($user, $mail, $ip)) {
	$sql = shell_exec("/usr/local/bin/sqlite3 /var/db/entraid_access.db 'INSERT INTO entraid (username,email,ip) VALUES (\"{$user}\",\"{$mail}\",\"{$ip}\")'");
	$sleep_value = 10;
}

// Pause before redirection
sleep($sleep_value);

// Redirect to Google
header("Location: https://www.google.com");
exit();

?>
