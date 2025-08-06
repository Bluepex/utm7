<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Rewritten by Marcos Claudiano  <marcos.claudiano@bluepex.com>, 2024
 * Rewritten by Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2023
 *
 * ====================================================================
 *
 */

if (isset($argc)) {
	for ($i = 0; $i < $argc; $i++) {
		$arg_value = $argv[$i];
	}
}

require_once("config.inc");
require_once("interfaces.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

$vulnerability = [];

//Cpack
function checkCPacksInstalled($inputXML) {
	$cpackInstall = array();

	if (empty($inputXML)) {
		return $cpackInstall;
	}

	foreach ($inputXML as $allLines) {
		foreach ($allLines as $line_now) {
			if ($line_now['cpack_installed'] == "no") {
				$cpackInstall[] = array(
				    "type" => "vulnerability_cpack_not_installed",
				    "level" => 10,
				    "data" => array(
					"info" => "vulnerability_cpack_not_installed",
				    ),
				);
			}
		}
	}

	return $cpackInstall;
}

//Acp status
function checkACPEnable() {
	global $config;

	$acpEnable = [];
	$all_gtw = checkGTW();
	$disabled_interfaces = checkDisableInterface();

	if (!isset($config['installedpackages']['suricata']['rule']) ||
	    empty($config['installedpackages']['suricata']['rule'])) {
		$acpEnable[] = array(
		    "type" => "vulnerability_acp_dont_exists",
		    "level" => 10,
		    "data" => array(
			"info" => "vulnerability_acp_dont_exists_descr",
		    ),
		);
		return $acpEnable;
	}

	if (!isset($config['gateways']['defaultgw4']) ||
	    $config['gateways']['defaultgw4'] == "-" ||
	    empty($config['gateways']['defaultgw4'])) {
		$acpEnable[] = array(
		    "type" => "vulnerability_acp_define_none_gateway",
		    "level" => 10,
		    "data" => array(
			"info" => "vulnerability_acp_define_none_gateway_descr",
		    ),
		);
		return $acpEnable;
	}

	$no_exists_acp_in_gateway = true;

	if (isset($config['installedpackages']['suricata']['rule']) &&
	    !empty($config['installedpackages']['suricata']['rule'])) {
		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
			$uuid = $suricatacfg['uuid'];

			if (in_array($if, $disabled_interfaces)) {
				continue;
			}

			$return_array_key = false;
			if (is_array($all_gtw[$config['gateways']['defaultgw4']])) {
				$return_array_key = in_array($if, $all_gtw[$config['gateways']['defaultgw4']]);
			} else {
				$return_array_key = ($all_gtw[$config['gateways']['defaultgw4']] == $if);
			}

			if (in_array($if, $all_gtw) &&
			    suricata_is_running($uuid, $if) &&
			    $return_array_key) {
				$no_exists_acp_in_gateway = false;
				break;
			}
		}
	}

	if ($no_exists_acp_in_gateway) {
		$acpEnable[] = array(
		    "type" => "vulnerability_acp_service_not_define_in_default_gateway",
		    "level" => 10,
		    "data" => array(
			"info" => "vulnerability_acp_service_not_define_in_default_gateway_descr",
		    ),
		);
	}

	return $acpEnable;
}

//nat
function checkNATRules() {
	global $config;

	$natRules = [];

	if (empty($config['nat']) ||
	    empty($config['nat']['rule'])) {
		return $natRules;
	}

	$disabled_interfaces = checkDisableInterface();

	foreach ($config['nat']['rule'] as $rule_now) {
		if (isset($rule_now['disabled']) ||
		    in_array(get_real_interface($rule_now['interface']), $disabled_interfaces)) {
			continue;
		}

		if (isset($rule_now['source']['any']) &&
		    isset($rule_now['destination']['any'])) {
			$natRules[] = array(
			    "type" => "vulnerability_nat_source_destination_open_rules",
			    "level" => 10,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_nat_source_destination_open_rules_descr",
			    ),
			);
		} elseif (isset($rule_now['source']['any'])) {
			$natRules[] = array(
			    "type" => "vulnerability_nat_source_open_rules",
			    "level" => 10,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_nat_source_open_rules_descr",
			    ),
			);
		} elseif (isset($rule_now['destination']['any'])) {
			$natRules[] = array(
			    "type" => "vulnerability_nat_destination_open_rules",
			    "level" => 10,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_nat_destination_open_rules_descr",
			    ),
			);
		}
	}

	return $natRules;
}

/* Get disable interface */
function checkDisableInterface() {
	global $config;

	$disabled_interfaces = [];

	foreach ($config['interfaces'] as $interfaces_values) {
		if (isset($interfaces_values['enable'])) {
			continue;
		}

		$disabled_interfaces[] = $interfaces_values['if'];
	}

	return $disabled_interfaces;
}

/* Get gateways with functions to use ACP filters */
function checkGTW() {
	global $config;

	if (!function_exists('return_gateways_array')) {
		return [];
	}

	$values_return = array_column(return_gateways_array(true, false, true, true), 'interface', 'name');

	if (isset($config['gateways']) &&
	    isset($config['gateways']['gateway_group'])) {
		foreach ($config['gateways']['gateway_group'] as $groups_failover) {
			$array_itens = [];

			foreach ($groups_failover['item'] as $groups_failover_itens) {
				$name_item_failover = explode("|", $groups_failover_itens)[0];
				$array_itens[] = $values_return[$name_item_failover];
			}

			$values_return[$groups_failover['name']] = $array_itens;
		}
	}

	return $values_return;
}

// Get MFA Openvpn server
function checkMFAOpenvpn($gtw_array) {
	global $config;

	$iflist = get_configured_interface_with_descr(true);

	$list_general_interfaces = [];
	$openvpnServer = [];

	foreach ($iflist as $key => $value) {
		$list_general_interfaces[$value] = get_real_interface($key);
	}

	foreach ($gtw_array as $key => $value) {
		$list_general_interfaces[$key] = $value;
	}

	if (!empty($config['openvpn']['openvpn-server'])) {
		foreach ($config['openvpn']['openvpn-server'] as $openvpn_now) {
			if (!isset($openvpn_now['mult-factor']) ||
			    empty($openvpn_now['mult-factor'])) {
				continue;
			}

			if (array_key_exists($openvpn_now['interface'], $list_general_interfaces)) {
				$openvpnServer[] = [
				    'interfaces' => $list_general_interfaces[$openvpn_now['interface']],
				    'ports' => $openvpn_now['local_port'],
				    'mfa' => $openvpn_now['mult-factor']
				];
			} elseif (in_array(get_real_interface($openvpn_now['interface']), $list_general_interfaces)) {
				$openvpnServer[] = [
				    'interfaces' => [get_real_interface($openvpn_now['interface'])],
				    'ports' => $openvpn_now['local_port'],
				    'mfa' => $openvpn_now['mult-factor']
				];
			}
		}
	}

	return $openvpnServer;
}

// Check MFA to rule
function checkMFARule($rule_now, $openvpnServer, $value_default_return, $value_mfa_return, $all_alias_ports) {
	$type_return = "vulnerability_filter_rules_source_open_vpn";
	$descr_return = "vulnerability_filter_rules_source_open_vpn_descr";
	$values_level_vpn = $value_default_return;

	if (!empty($openvpnServer) &&
	    isset($rule_now['destination']['port']) &&
	    !empty($rule_now['destination']['port'])) {
		$destination_ports = [];

		if (strpos($rule_now['destination']['port'], '-') !== false) {
			$values_range = explode("-", $rule_now['destination']['port']);
			$destination_ports = array_merge($destination_ports, $values_range);

			$start_port = intval($values_range[0]);
			$end_port = intval($values_range[1]);

			for (;$start_port <= $end_port; $start_port++) {
				$destination_ports[] = "{$start_port}";
			}
		} else {
			$destination_ports[] = $rule_now['destination']['port'];

			if (count($destination_ports) == 1 &&
			    isset($all_alias_ports[$destination_ports[0]])) {
				$destination_ports = $all_alias_ports[$destination_ports[0]];
			}
		}

		$all_ports_servers_vpn_mfa = array_column($openvpnServer, 'ports');
		$compare_vpn_range_ports_1 = array_diff($all_ports_servers_vpn_mfa, $destination_ports);
		$compare_vpn_range_ports_2 = array_diff($destination_ports, $all_ports_servers_vpn_mfa);
		$compare_vpn_range_ports = array_filter(array_unique(array_merge($compare_vpn_range_ports_1, $compare_vpn_range_ports_2)));
		unset($compare_vpn_range_ports_1);
		unset($compare_vpn_range_ports_2);

		foreach ($openvpnServer as $openvpnServerNow) {
			if (!in_array(get_real_interface($rule_now['interface']), $openvpnServerNow['interfaces'])) {
				continue;
			}

			if (in_array($openvpnServerNow['ports'], $destination_ports)) {
				if (count($compare_vpn_range_ports) > 0) {
					$type_return = "vulnerability_filter_rules_source_open_vpn_ports";
					$descr_return = "vulnerability_filter_rules_source_open_vpn_ports_descr";
				} else {
					$values_level_vpn = $value_mfa_return;
				}
				break;
			}
		}
	}

	return [$type_return, $descr_return, $values_level_vpn];
}

// Ignore Rules Firewall
function checkIgnoreRulesFirewall() {
	global $config;

	$ignoreTracker = [
	    "0100000101",
	    "0100000102"
	];

	if (empty($config['filter']) ||
	    empty($config['filter']['rule'])) {
		return $ignoreTracker;
	}

	foreach (array_count_values(array_column($config['filter']['rule'], 'tracker')) as $tracker => $values) {
		if ($values <= 1) {
			continue;
		}

		$ignoreTracker[] = "{$tracker}";
	}

	return $ignoreTracker;
}

// Get all interfaces
function checkAllInterfaces() {
	global $config;

	if (empty($config['interfaces']) ||
	    !isset($config['interfaces'])) {
		return [];
	}

	return array_column($config['interfaces'], 'if');
}

// Check Alias Ports
function checkAliasPorts() {
	global $config;

	$return_alias = [];

	if (empty($config['aliases']) ||
	    !isset($config['aliases'])) {
		return $return_alias;
	}

	foreach ($config['aliases']['alias'] as $aliases) {
		if ($aliases['type'] != "port") {
			continue;
		}

		$return_alias[$aliases['name']] = array_filter(array_unique(explode(" ", $aliases['address'])));
	}

	return $return_alias;
}

// Filter
function checkFirewallRules() {
	global $config;

	$firewallRules = [];

	if (empty($config['filter']) ||
	    empty($config['filter']['rule'])) {
		return $firewallRules;
	}

	$gtw_array = checkGTW();
	$disabled_interfaces = checkDisableInterface();
	$openvpnServer = checkMFAOpenvpn($gtw_array);
	$ignore_rules = checkIgnoreRulesFirewall();
	$all_interfaces = checkAllInterfaces();
	$all_alias_ports = checkAliasPorts();

	foreach($config['filter']['rule'] as $rule_now) {
		$interface_real = get_real_interface($rule_now['interface']);

		if (isset($rule_now['associated-rule-id']) ||
		    isset($rule_now['disabled']) ||
		    in_array("{$rule_now['tracker']}", $ignore_rules) ||
		    in_array($rule_now['type'], ['block', 'reject']) ||
		    in_array($interface_real, $disabled_interfaces)) {
			continue;
		}

		// If the interface is openvpn or ipsec, it ignores this part
		if (!in_array($interface_real, ['openvpn', 'enc0']) &&
		    (!in_array($interface_real, $all_interfaces) ||
		    !in_array($interface_real, $gtw_array))) {
			continue;
		}

		if (isset($rule_now['suite_id']) &&
		    (!isset($rule_now['typerule']) ||
		    empty($rule_now['typerule']))) {
			$rule_now['typerule'] = 'internal';
		}

		if (isset($rule_now['source']['any']) &&
		    isset($rule_now['destination']['any']) &&
		    (!isset($rule_now['typerule']) ||
		    empty($rule_now['typerule']))) {
			$firewallRules[] = array(
			    "type" => "vulnerability_filter_rules_source_destination_open_typerule_empty",
			    "level" => 10,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_filter_rules_source_destination_open_typerule_empty_descr",
			    ),
			);
		} else if (isset($rule_now['source']['any']) &&
		    isset($rule_now['destination']['any']) &&
		    $rule_now['typerule'] == 'internal') {
			$firewallRules[] = array(
			    "type" => "vulnerability_filter_rules_source_destination_open_internal",
			    "level" => 10,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_filter_rules_source_destination_open_internal_descr",
			    ),
			);
		} else if (isset($rule_now['source']['any']) &&
		    isset($rule_now['destination']['any']) &&
		    $rule_now['typerule'] == 'external') {
			$firewallRules[] = array(
			    "type" => "vulnerability_filter_rules_source_destination_open_external",
			    "level" => 10,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_filter_rules_source_destination_open_external_descr",
			    ),
			);
		} else if (isset($rule_now['source']['any']) &&
		    isset($rule_now['destination']['any']) &&
		    $rule_now['typerule'] == 'vpn') {
			$firewallRules[] = array(
				"type" => "vulnerability_filter_rules_source_destination_open_vpn",
				"level" => 5,
				"data" => array(
				    "rule" => $rule_now,
				    "info" => "vulnerability_filter_rules_source_destination_open_vpn_descr",
				),
			);
		} else if (isset($rule_now['source']['any']) &&
		    !isset($rule_now['destination']['any']) &&
		    $rule_now['typerule'] == 'internal') {
			$firewallRules[] = array(
			    "type" => "vulnerability_filter_rules_source_open_internal",
			    "level" => 8,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_filter_rules_source_open_internal_descr",
			    ),
			);
		} else if (isset($rule_now['source']['any']) &&
		    !isset($rule_now['destination']['any']) &&
		    $rule_now['typerule'] == 'external') {
			$firewallRules[] = array(
			    "type" => "vulnerability_filter_rules_source_open_external",
			    "level" => 8,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_filter_rules_source_open_external_descr",
			    ),
			);
		} else if (isset($rule_now['source']['any']) &&
		    !isset($rule_now['destination']['any']) &&
		    $rule_now['typerule'] == 'vpn') {
			[$type_return, $descr_return, $values_level_vpn] = checkMFARule($rule_now, $openvpnServer, 3, 0, $all_alias_ports);

			if ($values_level_vpn > 0) {
				$firewallRules[] = array(
				    "type" => $type_return,
				    "level" => $values_level_vpn,
				    "data" => array(
					"rule" => $rule_now,
					"info" => $descr_return,
				    ),
				);
			}
		} else if (isset($rule_now['destination']['any']) &&
		    !isset($rule_now['source']['any']) &&
		    $rule_now['typerule'] == 'internal') {
			$firewallRules[] = array(
			    "type" => "vulnerability_filter_rules_destination_open_internal",
			    "level" => 8,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_filter_rules_destination_open_internal_descr",
			    ),
		    );
		} else if (isset($rule_now['destination']['any']) &&
		    !isset($rule_now['source']['any']) &&
		    $rule_now['typerule'] == 'external') {
			$firewallRules[] = array(
			    "type" => "vulnerability_filter_rules_destination_open_external",
			    "level" => 8,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_filter_rules_destination_open_external_descr",
			    ),
			);
		} else if (isset($rule_now['destination']['any']) &&
		    !isset($rule_now['source']['any']) &&
		    $rule_now['typerule'] == 'vpn') {
			$firewallRules[] = array(
			    "type" => "vulnerability_filter_rules_destination_open_vpn",
			    "level" => 5,
			    "data" => array(
				"rule" => $rule_now,
				"info" => "vulnerability_filter_rules_destination_open_vpn_descr",
			    ),
			);
		}
	}
	
	return $firewallRules;
}

//http
function checkWebGUIProtocol() {
	global $config;

	$protocol = [];

	if (!isset($config['system']['webgui']['protocol']) ||
	    empty($config['system']['webgui']['protocol'])) {
		return $protocol;
	}

	if ($config['system']['webgui']['protocol'] == "http") {
		$protocol[] = array(
		    "type" => "vulnerability_webgui",
		    "level" => 5,
		    "data" => array(
			"protocol" => $config['system']['webgui'],
			"info" => "vulnerability_webgui_descr",
		    ),
		);
	}

	return $protocol;
}

//openvpn
function checkOpenVpn() {
	global $config;

	$openVPN = [];

	if (empty($config['openvpn']) ||
	    empty($config['openvpn']['openvpn-server'])) {
		return $openVPN;
	}

	$disabled_interfaces = checkDisableInterface();

	foreach($config['openvpn']['openvpn-server'] as $openvpn_now) {
		if ($openvpn_now['interface'] != 'any' &&
		    in_array(get_real_interface($openvpn_now['interface']), $disabled_interfaces)) {
			continue;
		}

		if (isset($openvpn_now['crypto']) &&
		    ($openvpn_now['crypto'] == "BF-CBC")) {
			$openVPN[] = array(
			    "type" => "vulnerability_openvpn",
			    "level" => 10,
			    "data" => array(
				"vpn_id" => $openvpn_now['vpnid'],
				"description" => $openvpn_now['description'],
				"crypto" => $openvpn_now['crypto'],
				"info" => "vulnerability_openvpn_cryptography_descr"
			    ),
			);
		}

		if (isset($openvpn_now['ncp-ciphers']) &&
		    !empty($openvpn_now['ncp-ciphers'])) {
			foreach (explode(",", trim($openvpn_now['ncp-ciphers'])) as $chipers) {
				if (in_array($chipers, ['AES-128-GCM'])) {
					$openVPN[] = array(
					    "type" => "vulnerability_openvpn_chiphers",
					    "level" => 10,
					    "data" => array(
						"vpn_id" => $openvpn_now['vpnid'],
						"description" => $openvpn_now['description'],
						"crypto" => $openvpn_now['crypto'],
						"info" => "vulnerability_openvpn_chiphers_descr"
					    ),
					);
				}
			}
		}
	}

	return $openVPN;
}

//Password
function checkPassword() {
	global $config;

	$passwords_to_check = ['bluepex utm', 'b1uepex utm', 'bluepexutm', 'b1uepexutm', '123', '123456'];
	$passWords = [];

	if (empty($config['system']['user'])) {
		return $passWords;
	}

	foreach ($config['system']['user'] as $userNow) {
		if (!isset($userNow) ||
		    ($userNow['name'] != "admin") ||
		    ($userNow['scope'] != "system")) {
			continue;
		}

		foreach ($passwords_to_check as $check_now) {
			$problem = false;

			if (isset($userNow['bcrypt-hash']) &&
			    password_verify($check_now, $userNow['bcrypt-hash'])) {
				$problem = true;
			}

			if (isset($userNow['md5-hash']) &&
			    (md5($check_now) == $userNow['md5-hash'])) {
				$problem = true;
			}

			if ($problem) {
				$passWords[] = array(
				    "type" => "vulnerability_password",
				    "level" => 10,
				    "data" => array(
					"info" => "vulnerability_password_default_descr",
					"password" => $check_now
				    ),
				);
				break;
			}
		}
	}

	return $passWords;
}

//Mysql
function checkMysql() {
	$dbIPAddresss = "localhost";
	$dbUsr = "root";
	$dbPwd = "123";
	$dbName = "mysql";
	$mysqlVulnerability = [];
	$mySQLConnection = mysqli_connect($dbIPAddresss, $dbUsr, $dbPwd, $dbName);

	$usersDB = mysqli_query($mySQLConnection, "SELECT Host, User, Password FROM user");

	if (mysqli_num_rows($usersDB) > 0) {
		while($userNow=mysqli_fetch_array($usersDB)) {
			$user_data = array(
			    "host" => $userNow['Host'],
			    "user" => $userNow['User'],
			    "pass" => $userNow['Password']
			);

			if (($userNow['Host'] == "%") &&
			    ($userNow['Host'] == "*")) {
				$mysqlVulnerability[] = array(
				    "type" => "vulnerability_mysql",
				    "level" => 10,
				    "data" => array(
				    	"info" => "vulnerability_mysql_hosts_wildcard_descr",
				    	"user" => $user_data
				    ),
				);
			}

			if (($userNow['Password'] == "") ||
			    empty($userNow['Password'])) {
				$mysqlVulnerability[] = array(
				    "type" => "vulnerability_mysql",
				    "level" => 10,
				    "data" => array(
				    	"info" => "vulnerability_mysql_empty_password_descr",
				    	"user" => $user_data
				    ),
				);
			}
            
			if ($userNow['User'] == "webfilter") {
				if ($userNow['Password'] == "*F173D0793381C6DC13136F4FFB46FE933D81F464") {
					$mysqlVulnerability[] = array(
					    "type" => "vulnerability_mysql",
					    "level" => 10,
					    "data" => array(
					    "info" => "vulnerability_mysql_webfilter_default_password_descr",
					    "user" => $user_data
					),
				);
				}
			}
		}

		mysqli_close($mySQLConnection);
		return $mysqlVulnerability;
	} else {
		mysqli_close($mySQLConnection);
		return $mysqlVulnerability;
	}
	mysqli_close($mySQLConnection);
	return $mysqlVulnerability;    
}

$vulnerability['acp_enable'] = checkACPEnable();
$vulnerability['nat'] = checkNATRules();
$vulnerability['firewall'] = checkFirewallRules();
$vulnerability['openvpn'] = checkOpenVpn();
$vulnerability['webgui'] = checkWebGUIProtocol();
$vulnerability['password'] = checkPassword();

if ($arg_value == "debug") {
	$debug = "";
	foreach (array_keys($vulnerability) as $line) {
		foreach ($vulnerability[$line] as $lineNow) {
			$debug .= "{$line} -> {$lineNow['level']} -> {$lineNow['data']['info']}";
			if (isset($lineNow['data']['rule']['tracker'])) {
				$debug .= " | ID Tracker Rule: {$lineNow['data']['rule']['tracker']}";
			}
			$debug .= PHP_EOL;
		}
	}
	echo $debug;
	exit;
} elseif (in_array($arg_value, ["acp_enable", "firewall", "openvpn", "nat", "webgui", "password"])) {
	echo json_encode($vulnerability[$arg_value]);
	exit;
} else {
	$maxValueRegister = 0;
	foreach (array_keys($vulnerability) as $line) {
		foreach ($vulnerability[$line] as $lineNow) {
			if ($lineNow['level'] > $maxValueRegister) {
				$maxValueRegister = $lineNow['level'];
			}
		}
	}
	echo $maxValueRegister;
	exit;
}
