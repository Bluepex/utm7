<?php
/*
 * firewallapp_interfaces_edit.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2006-2019 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2003-2004 Manuel Kasper
 * Copyright (c) 2005 Bill Marquette
 * Copyright (c) 2009 Robert Zelaya Sr. Developer
 * Copyright (c) 2019 Bill Meeks
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("guiconfig.inc");
require_once("firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("bp_auditing.inc");

global $g, $rebuild_rules;

$suricatadir = SURICATADIR;
$suricatalogdir = SURICATALOGDIR;

init_config_arr(array('installedpackages', 'suricata', 'rule'));
$suricataglob = $config['installedpackages']['suricata'];
$a_rule = &$config['installedpackages']['suricata']['rule'];
$a_vlan = isset($config['vlans']['vlan']);
init_config_arr(array('gateways', 'gateway_item'));
$a_gtw = &$config['gateways']['gateway_item'];
$a_gateways = return_gateways_array(true, false, true, true);

$all_gtw = getInterfacesInGatewaysWithNoExceptions();
//print_r($all_gtw);die;

//print_r("heuristica: ".mult_interfaces_fapp_acp()[0]." | ");
//print_r("simples: ".mult_interfaces_fapp_acp()[1]);

if (isset($_POST['id']) && is_numericint($_POST['id']))
	$id = $_POST['id'];
elseif (isset($_GET['id']) && is_numericint($_GET['id']));
	$id = htmlspecialchars($_GET['id'], ENT_QUOTES | ENT_HTML401);

if (is_null($id)) {
		header("Location: /firewallapp/firewallapp_interfaces.php");
		exit;
}

if (isset($_POST['action']))
	$action = htmlspecialchars($_POST['action'], ENT_QUOTES | ENT_HTML401);
elseif (isset($_GET['action']))
	$action = htmlspecialchars($_GET['action'], ENT_QUOTES | ENT_HTML401);
else
	$action = "";

$pconfig = array();
if (empty($suricataglob['rule'][$id]['uuid'])) {
	/* Adding new interface, so generate a new UUID and flag rules to build. */
	$pconfig['uuid'] = suricata_generate_id();
	$rebuild_rules = true;
}
else {
	$pconfig['uuid'] = $a_rule[$id]['uuid'];
	$pconfig['descr'] = $a_rule[$id]['descr'];
	$rebuild_rules = false;
}
$if_real_interface = get_real_interface($pconfig['interface']);
$suricata_uuid = $pconfig['uuid'];

// Get the physical configured interfaces on the firewall
$interfaces = get_configured_interface_with_descr();

/*
if (isset($_GET["action"])) {
	$interface_in_use = [];
	foreach($a_rule as $rule_now) {
		if (strlen($rule_now['interface']) > 0) {
			$interface_in_use[] = strtoupper($rule_now['interface']);
		}
	}
	
	$array_clean = [];
	foreach($interfaces as $interface_now) {
		if (strlen(array_search($interface_now, $interface_in_use)) == 0) {
			$array_clean[strtolower($interface_now)] = strtoupper($interface_now); 
		}
	}
	$interfaces = $array_clean;
}

if (isset($_GET['interface'])) {
	$array_clean[strtolower($_GET['interface'])] = strtoupper($_GET['interface']);
	$interfaces = $array_clean;
}
*/

/*start commentary*/
/*Clean wan line of the options tag*/ 
/*
$ifrules = array();
$if_new = array();
foreach($a_rule as $r)
	$ifrules[] = $r['interface'];
foreach ($interfaces as $i) {
	if ($i != 'WAN') {
		$if_new[$i] = $i;
	}
}
*/

//$interfaces = $if_new;
/*end commentary*/

// See if interface is already configured, and use its values
if (isset($id) && isset($a_rule[$id])) {
	/* old options */
	$pconfig = $a_rule[$id];
	if (!empty($pconfig['configpassthru']))
		$pconfig['configpassthru'] = base64_decode($pconfig['configpassthru']);
	if (empty($pconfig['uuid']))
		$pconfig['uuid'] = $suricata_uuid;
}

// Must be a new interface, so try to pick next available physical interface to use
elseif (isset($id) && !isset($a_rule[$id])) {
	$ifaces = get_configured_interface_list();
	$ifrules = array();
	foreach($a_rule as $r)
		$ifrules[] = $r['interface'];
	foreach ($ifaces as $i) {
		if (!in_array($i, $ifrules)) {
			$pconfig['interface'] = $i;
			$pconfig['enable'] = 'on';
			$pconfig['descr'] = strtoupper($i);
			$pconfig['inspect_recursion_limit'] = '3000';
			break;
		}
	}
	if (count($ifrules) == count($ifaces)) {
		$input_errors[] = gettext("No more available interfaces to configure for Firewallapp!");
		$interfaces = array();
		$pconfig = array();
	}
}

// Set defaults for any empty key parameters
if (empty($pconfig['blockoffendersip']))
	$pconfig['blockoffendersip'] = "both";
if (empty($pconfig['blockoffenderskill']))
	$pconfig['blockoffenderskill'] = "on";
if (empty($pconfig['ips_mode']))
	$pconfig['ips_mode'] = 'ips_mode_legacy';
if (empty($pconfig['block_drops_only']))
	$pconfig['block_drops_only'] = "off";
if (empty($pconfig['runmode']))
	$pconfig['runmode'] = "autofp";
if (empty($pconfig['max_pending_packets']))
	$pconfig['max_pending_packets'] = "1024";
if (empty($pconfig['detect_eng_profile']))
	$pconfig['detect_eng_profile'] = "medium";
if (empty($pconfig['mpm_algo']))
	$pconfig['mpm_algo'] = "auto";
if (empty($pconfig['sgh_mpm_context']))
	$pconfig['sgh_mpm_context'] = "auto";
if (empty($pconfig['enable_http_log']))
	$pconfig['enable_http_log'] = "on";
if (empty($pconfig['append_http_log']))
	$pconfig['append_http_log'] = "on";
if (empty($pconfig['http_log_extended']))
	$pconfig['http_log_extended'] = "on";
if (empty($pconfig['tls_log_extended']))
	$pconfig['tls_log_extended'] = "on";
if (empty($pconfig['stats_upd_interval']))
	$pconfig['stats_upd_interval'] = "10";
if (empty($pconfig['append_json_file_log']))
	$pconfig['append_json_file_log'] = "on";
if (empty($pconfig['max_pcap_log_size']))
	$pconfig['max_pcap_log_size'] = "32";
if (empty($pconfig['max_pcap_log_files']))
	$pconfig['max_pcap_log_files'] = "1000";
if (empty($pconfig['alertsystemlog_facility']))
	$pconfig['alertsystemlog_facility'] = "local1";
if (empty($pconfig['alertsystemlog_priority']))
	$pconfig['alertsystemlog_priority'] = "notice";
if (empty($pconfig['eve_output_type']))
	$pconfig['eve_output_type'] = "regular";
if (empty($pconfig['eve_systemlog_facility']))
	$pconfig['eve_systemlog_facility'] = "local1";
if (empty($pconfig['eve_systemlog_priority']))
	$pconfig['eve_systemlog_priority'] = "notice";
if (empty($pconfig['eve_log_alerts']))
	$pconfig['eve_log_alerts'] = "on";
if (empty($pconfig['eve_log_alerts_payload']))
	$pconfig['eve_log_alerts_payload'] = "on";
if (empty($pconfig['eve_log_alerts_packet']))
	$pconfig['eve_log_alerts_packet'] = "on";
if (empty($pconfig['eve_log_alerts_http']))
	$pconfig['eve_log_alerts_http'] = "on";
if (empty($pconfig['eve_log_alerts_xff']))
	$pconfig['eve_log_alerts_xff'] = "off";
if (empty($pconfig['eve_log_alerts_xff_mode']))
	$pconfig['eve_log_alerts_xff_mode'] = "extra-data";
if (empty($pconfig['eve_log_alerts_xff_deployment']))
	$pconfig['eve_log_alerts_xff_deployment'] = "reverse";
if (empty($pconfig['eve_log_alerts_xff_header']))
	$pconfig['eve_log_alerts_xff_header'] = "X-Forwarded-For";
if (empty($pconfig['eve_log_http']))
	$pconfig['eve_log_http'] = "on";
if (empty($pconfig['eve_log_dns']))
	$pconfig['eve_log_dns'] = "on";
if (empty($pconfig['eve_log_tls']))
	$pconfig['eve_log_tls'] = "on";
if (empty($pconfig['eve_log_dhcp']))
	$pconfig['eve_log_dhcp'] = "on";
if (empty($pconfig['eve_log_nfs']))
	$pconfig['eve_log_nfs'] = "on";
if (empty($pconfig['eve_log_smb']))
	$pconfig['eve_log_smb'] = "on";
if (empty($pconfig['eve_log_krb5']))
	$pconfig['eve_log_krb5'] = "on";
if (empty($pconfig['eve_log_ikev2']))
	$pconfig['eve_log_ikev2'] = "on";
if (empty($pconfig['eve_log_tftp']))
	$pconfig['eve_log_tftp'] = "on";
if (empty($pconfig['eve_log_files']))
	$pconfig['eve_log_files'] = "on";
if (empty($pconfig['eve_log_ssh']))
	$pconfig['eve_log_ssh'] = "on";
if (empty($pconfig['eve_log_smtp']))
	$pconfig['eve_log_smtp'] = "on";
if (empty($pconfig['eve_log_flow']))
	$pconfig['eve_log_flow'] = "off";
if (empty($pconfig['eve_log_netflow']))
	$pconfig['eve_log_netflow'] = "off";
if (empty($pconfig['eve_log_stats']))
	$pconfig['eve_log_stats'] = "off";
if (empty($pconfig['eve_log_stats_totals']))
	$pconfig['eve_log_stats_totals'] = "on";
if (empty($pconfig['eve_log_stats_deltas']))
	$pconfig['eve_log_stats_deltas'] = "off";
if (empty($pconfig['eve_log_stats_threads']))
	$pconfig['eve_log_stats_threads'] = "off";
if (empty($pconfig['eve_log_drop'])) {
	$pconfig['eve_log_drop'] = "on";
}

if (empty($pconfig['eve_log_http_extended']))
	$pconfig['eve_log_http_extended'] = $pconfig['http_log_extended'];
if (empty($pconfig['eve_log_tls_extended']))
	$pconfig['eve_log_tls_extended'] = $pconfig['tls_log_extended'];
if (empty($pconfig['eve_log_dhcp_extended']))
	$pconfig['eve_log_dhcp_extended'] = "off";
if (empty($pconfig['eve_log_smtp_extended']))
	$pconfig['eve_log_smtp_extended'] = $pconfig['smtp_log_extended'];

if (empty($pconfig['eve_log_http_extended_headers']))
	$pconfig['eve_log_http_extended_headers'] = "accept, accept-charset, accept-datetime, accept-encoding, accept-language, accept-range, age, allow, authorization, cache-control, connection, content-encoding, content-language, content-length, content-location, content-md5, content-range, content-type, cookie, date, dnt, etags, from, last-modified, link, location, max-forwards, origin, pragma, proxy-authenticate, proxy-authorization, range, referrer, refresh, retry-after, server, set-cookie, te, trailer, transfer-encoding, upgrade, vary, via, warning, www-authenticate, x-authenticated-user, x-flash-version, x-forwarded-proto, x-requested-with";

if (empty($pconfig['eve_log_smtp_extended_fields']))
	$pconfig['eve_log_smtp_extended_fields'] = "received, x-mailer, x-originating-ip, relays, reply-to, bcc";

if (empty($pconfig['eve_log_files_magic']))
	$pconfig['eve_log_files_magic'] = "off";
if (empty($pconfig['eve_log_files_hash']))
	$pconfig['eve_log_files_hash'] = "none";

if (empty($pconfig['eve_redis_server']))
	$pconfig['eve_redis_server'] = "127.0.0.1";
if (empty($pconfig['eve_redis_port']))
	$pconfig['eve_redis_port'] = "6379";
if (empty($pconfig['eve_redis_mode']))
	$pconfig['eve_redis_mode'] = "list";
if (empty($pconfig['eve_redis_key']))
	$pconfig['eve_redis_key'] = "suricata{$if_real_interface}{$suricata_uuid}";

if (empty($pconfig['intf_promisc_mode']))
	$pconfig['intf_promisc_mode'] = "on";
if (empty($pconfig['intf_snaplen']))
	$pconfig['intf_snaplen'] = "1518";

// See if creating a new interface by duplicating an existing one
if (strcasecmp($action, 'dup') == 0) {

	// Try to pick the next available physical interface to use
	$ifaces = get_configured_interface_list();
	$ifrules = array();
	foreach($a_rule as $r)
		$ifrules[] = $r['interface'];
	foreach ($ifaces as $i) {
		if (!in_array($i, $ifrules)) {
			$pconfig['interface'] = $i;
			$pconfig['enable'] = 'on';
			$pconfig['descr'] = strtoupper($i);
			$pconfig['inspect_recursion_limit'] = '3000';
			break;
		}
	}

	if (count($ifrules) == count($ifaces)) {
		$input_errors[] = gettext("No more available interfaces to configure for Firewallapp!");
		$interfaces = array();
		$pconfig = array();
	}

	// Set Home Net, External Net, Suppress List and Pass List to defaults
	unset($pconfig['suppresslistname']);
	unset($pconfig['passlistname']);
	unset($pconfig['homelistname']);
	unset($pconfig['externallistname']);
}

if ($_REQUEST['ajax'] == 'ajax') {
	print(gettext("At least we got that straight!"));
	exit;
}

if (isset($_POST["save"]) && !$input_errors) {
	if (!isset($_POST['interface']))
		$input_errors[] = gettext("Choosing an Interface is mandatory!");

	/* See if assigned interface is already in use */
	if (isset($_POST['interface'])) {
		foreach ($a_rule as $k => $v) {
			if (($v['interface'] == $_POST['interface']) && ($_POST["action"] == "dup")) {
				$input_errors[] = gettext("The '{$_POST['interface']}' interface is already assigned to another firewallapp instance.");
				break;
			}
		}
		//if ((strtolower($_POST['interface']) == 'wan') && ($_POST["action"] == "dup")) {
		//	$show_interface = strtoupper($_POST['interface']);
		//	$input_errors[] = gettext("Don't choose interface $show_interface to use FirewallAPP!");
		//}

		$IfsH = 0;
		$IfsS = 0;
		$IfsP = 0;
		if (isset($_GET['action'])) {
			$IfsH = mult_interfaces_fapp_acp($_POST['interface'], $_POST['ips_mode'], 'dup')[0];
			$IfsS = mult_interfaces_fapp_acp($_POST['interface'], $_POST['ips_mode'], 'dup')[1];
			$IfsP = mult_interfaces_fapp_acp($_POST['interface'], $_POST['ips_mode'], 'dup')[2];
		} else {
			$IfsH = mult_interfaces_fapp_acp($_POST['interface'], $_POST['ips_mode'], '')[0];
			$IfsS = mult_interfaces_fapp_acp($_POST['interface'], $_POST['ips_mode'], '')[1];
			$IfsP = mult_interfaces_fapp_acp($_POST['interface'], $_POST['ips_mode'], '')[2];
		}

		//Add if duplicate
		if ($_POST["action"] == "dup") {
			if ($_POST['ips_mode'] == 'ips_mode_inline') {
				$IfsH--;
			} elseif ($_POST['ips_mode'] == 'ips_mode_legacy') {
				$IfsS--;
			}
		}

		if (($a_rule[$id]['ips_mode'] != $_POST['ips_mode']) && ($_POST["action"] != "dup")) {
			if ($_POST['ips_mode'] == 'ips_mode_inline') {
				$IfsH--;
			} elseif ($_POST['ips_mode'] == 'ips_mode_legacy') {
				$IfsS--;
			}
		}

		$if_w = get_real_interface($_POST['interface']);
		$show_interface = strtoupper($_POST['interface']);

		if (!file_exists('/etc/lock_fapp_acp_limit')) {
			file_put_contents('/etc/lock_fapp_acp_limit', 'false');
			sleep(1);
		}

		if (file_exists('/etc/lock_fapp_acp_limit') && trim(file_get_contents('/etc/lock_fapp_acp_limit')) == 'false') {

			//if ($_POST["action"] == "dup") {
			if (in_array($if_w, $all_gtw,true)) {
				$input_errors[] = gettext("Don't choose interface $show_interface to use FirewallAPP!");
			} else {
				if ((intval($IfsH) <= -1) || (intval($IfsS) <= -1)) {
					$input_errors[] = gettext("Don't choose interface $show_interface why exceeded the instance limit for this model!");
				} 
				if (((intval($IfsP) > 2) && $_POST['ips_mode'] == 'ips_mode_inline')) {
					$input_errors[] = gettext("Don't choose interface $show_interface why identified webfilter instance!");
				}
			}
			//}
		}
	}

	// If Suricata is disabled on this interface, stop any running instance,
	// save, lkc and exit.
	$write_status_log = "";

	if ($_POST['enable'] != 'on') {
		if ($a_rule[$id]['enable'] == 'on') { $write_status_log = "report_0008_acp_fapp_disabled"; }
		$a_rule[$id]['enable'] = 'off';
		suricata_stop($a_rule[$id], get_real_interface($a_rule[$id]['interface']));
		$if_real = get_real_interface($a_rule[$id]['interface']);
		$uuid = $a_rule[$id]['uuid'];
		file_put_contents("/etc/suricata_{$if_real}{$uuid}_stop.lck", "");
		file_put_contents("{$g['varrun_path']}/suricata_{$if_real}{$uuid}_stop.lck", "");
		write_config("firewallapp pkg: disabled firewallapp on " . convert_friendly_interface_to_friendly_descr($a_rule[$id]['interface']));
		$rebuild_rules = false;
		sync_suricata_package_config();
		/*
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
		header("Location: /firewallapp/firewallapp_interfaces.php");
		exit;
		*/
	} elseif ($_POST['enable'] == 'on' &&
	    $a_rule[$id]['enable'] == 'off') {
		$write_status_log = "report_0008_acp_fapp_enabled";
	}

	if (!empty($write_status_log)) {
		$if_friendly = convert_friendly_interface_to_friendly_descr($a_rule[$id]['interface']);
		$if_real = get_real_interface($a_rule[$id]['interface']);
		$uuid = $a_rule[$id]['uuid'];
		bp_write_report_db($write_status_log, "FirewallApp|{$if_friendly}|{$if_real}{$uuid}");
	}
	//else {
	//	header("Location: /firewallapp/firewallapp_interfaces.php");
	//}

	// Validate inputs
	if (isset($_POST['stats_upd_interval']) && !is_numericint($_POST['stats_upd_interval']))
		$input_errors[] = gettext("The value for Stats Update Interval must contain only digits and evaluate to an integer.");

	if ($_POST['max_pending_packets'] < 1 || $_POST['max_pending_packets'] > 65000)
		$_POST['max_pending_packets'] == 1024;

	if (isset($_POST['max_pcap_log_size']) && !is_numeric($_POST['max_pcap_log_size']))
		$_POST['max_pcap_log_size'] == 1024;

	if (isset($_POST['max_pcap_log_files']) && !is_numeric($_POST['max_pcap_log_files']))
		$input_errors[] = gettext("The value for 'Max Packet Log Files' must be numbers only.");

	if (!empty($_POST['inspect_recursion_limit']) && !is_numeric($_POST['inspect_recursion_limit']))
		$input_errors[] = gettext("The value for Inspect Recursion Limit can either be blank or contain only digits evaluating to an integer greater than or equal to 0.");

	if ($_POST['intf_snaplen'] < 1 || !is_numeric($_POST['intf_snaplen']))
		$_POST['intf_snaplen'] == 3000;

	if (!empty($_POST['eve_redis_server']) && !is_ipaddr($_POST['eve_redis_server']))
		$input_errors[] = gettext("The value for 'EVE REDIS Server' must be an IP address.");

	if (!empty($_POST['eve_redis_port']) && !is_port($_POST['eve_redis_port']))
		$input_errors[] = gettext("The value for 'EVE REDIS Server' must have a valid TCP port.");

	if (!empty($_POST['eve_redis_key']) && !preg_match('/^[A-Za-z0-9\.]+$/',$_POST['eve_redis_key']))
		$input_errors[] = gettext("The value for 'EVE REDIS Key' must be alphanumeric.");


	$all_vlans = [];
	if (!empty($a_vlan)) {
		foreach($a_vlan as $vlan_rules) {
			$all_vlans[] = $vlan_rules['if'];
		}
	}

	$interface_real = get_real_interface($_POST['interface']);
	foreach($all_vlans as $vlan_now) {
		if ($vlan_now == $interface_real && $_POST['ips_mode'] == "ips_mode_inline") {
			$show_interface = strtoupper($_POST['interface']);
			$input_errors[] = gettext("It is not possible to enable FirewallApp on the physical interface of $show_interface, but it is possible on the VLAN interface of the same.");
			break;
		}
	}


	// if no errors write to suricata.yaml
	if (!$input_errors) {
		$natent = $a_rule[$id];
		$natent['interface'] = strtolower($_POST['interface']);
		$natent['enable'] = $_POST['enable'] ? 'on' : 'off';
		$natent['uuid'] = $pconfig['uuid'];

		if ($_POST['descr']) $natent['descr'] =  htmlspecialchars($_POST['descr']); else $natent['descr'] = strtoupper($natent['interface']);
		if ($_POST['max_pcap_log_size']) $natent['max_pcap_log_size'] = $_POST['max_pcap_log_size']; else unset($natent['max_pcap_log_size']);
		if ($_POST['max_pcap_log_files']) $natent['max_pcap_log_files'] = $_POST['max_pcap_log_files']; else unset($natent['max_pcap_log_files']);
		if ($_POST['enable_stats_log'] == "on") { $natent['enable_stats_log'] = 'on'; }else{ $natent['enable_stats_log'] = 'off'; }
		if ($_POST['append_stats_log'] == "on") { $natent['append_stats_log'] = 'on'; }else{ $natent['append_stats_log'] = 'off'; }
		if ($_POST['stats_upd_interval'] >= 1) $natent['stats_upd_interval'] = $_POST['stats_upd_interval']; else $natent['stats_upd_interval'] = "10";
		if ($_POST['enable_http_log'] == "on") { $natent['enable_http_log'] = 'on'; }else{ $natent['enable_http_log'] = 'on'; }
		if ($_POST['append_http_log'] == "on") { $natent['append_http_log'] = 'on'; }else{ $natent['append_http_log'] = 'on'; }
		if ($_POST['enable_tls_log'] == "on") { $natent['enable_tls_log'] = 'on'; }else{ $natent['enable_tls_log'] = 'on'; }
		if ($_POST['enable_tls_store'] == "on") { $natent['enable_tls_store'] = 'on'; }else{ $natent['enable_tls_store'] = 'off'; }
		if ($_POST['http_log_extended'] == "on") { $natent['http_log_extended'] = 'on'; }else{ $natent['http_log_extended'] = 'on'; }
		if ($_POST['tls_log_extended'] == "on") { $natent['tls_log_extended'] = 'on'; }else{ $natent['tls_log_extended'] = 'on'; }
		if ($_POST['enable_pcap_log'] == "on") { $natent['enable_pcap_log'] = 'on'; }else{ $natent['enable_pcap_log'] = 'off'; }
		if ($_POST['enable_json_file_log'] == "on") { $natent['enable_json_file_log'] = 'on'; }else{ $natent['enable_json_file_log'] = 'off'; }
		if ($_POST['append_json_file_log'] == "on") { $natent['append_json_file_log'] = 'on'; }else{ $natent['append_json_file_log'] = 'on'; }
		if ($_POST['enable_tracked_files_magic'] == "on") { $natent['enable_tracked_files_magic'] = 'on'; }else{ $natent['enable_tracked_files_magic'] = 'off'; }
		if ($_POST['tracked_files_hash']) $natent['tracked_files_hash'] = $_POST['tracked_files_hash'];
		if ($_POST['enable_file_store'] == "on") { $natent['enable_file_store'] = 'on'; }else{ $natent['enable_file_store'] = 'off'; }
		if ($_POST['enable_eve_log'] == "on") { $natent['enable_eve_log'] = 'on'; }else{ $natent['enable_eve_log'] = 'off'; }
		if ($_POST['runmode']) $natent['runmode'] = $_POST['runmode']; else unset($natent['runmode']);
		if ($_POST['max_pending_packets']) $natent['max_pending_packets'] = $_POST['max_pending_packets']; else unset($natent['max_pending_packets']);
		if ($_POST['inspect_recursion_limit'] >= '0') $natent['inspect_recursion_limit'] = $_POST['inspect_recursion_limit']; else unset($natent['inspect_recursion_limit']);
		if ($_POST['intf_snaplen'] > '0') $natent['intf_snaplen'] = $_POST['intf_snaplen']; else $natent['inspect_recursion_limit'] = "1518";
		if ($_POST['detect_eng_profile']) $natent['detect_eng_profile'] = $_POST['detect_eng_profile']; else unset($natent['detect_eng_profile']);
		if ($_POST['mpm_algo']) $natent['mpm_algo'] = $_POST['mpm_algo']; else unset($natent['mpm_algo']);
		if ($_POST['sgh_mpm_context']) $natent['sgh_mpm_context'] = $_POST['sgh_mpm_context']; else unset($natent['sgh_mpm_context']);
		if ($_POST['blockoffenders'] == "on") $natent['blockoffenders'] = 'on'; else $natent['blockoffenders'] = 'on';

		#Add mix mode with chechbox
		if (isset($_POST['mixed_mode'])) {
			$natent['ips_mode'] = 'ips_mode_mix';
			suricata_merge_mixed_and_commented_fapp($natent);
			file_put_contents("/etc/performance_extends", "false");	
		} else {
			if ($_POST['ips_mode']) $natent['ips_mode'] = $_POST['ips_mode']; else unset($natent['ips_mode']);
			if (($_POST['performance_extends'] == "on") && ($_POST['ips_mode'] == 'ips_mode_legacy')) {
				file_put_contents("/etc/performance_extends", "true");
			} else {
				file_put_contents("/etc/performance_extends", "false");	
			}
		}

		if ($_POST['blockoffenderskill'] == "on") $natent['blockoffenderskill'] = 'on'; else $natent['blockoffenderskill'] = 'off';
		if ($_POST['block_drops_only'] == "on") $natent['block_drops_only'] = 'on'; else $natent['block_drops_only'] = 'on';
		if ($_POST['blockoffendersip']) $natent['blockoffendersip'] = $_POST['blockoffendersip']; else unset($natent['blockoffendersip']);
		if ($_POST['passlistname']) $natent['passlistname'] =  $_POST['passlistname']; else unset($natent['passlistname']);
		if ($_POST['homelistname']) $natent['homelistname'] =  $_POST['homelistname']; else unset($natent['homelistname']);
		if ($_POST['externallistname']) $natent['externallistname'] =  $_POST['externallistname']; else unset($natent['externallistname']);
		if ($_POST['suppresslistname']) $natent['suppresslistname'] =  $_POST['suppresslistname']; else unset($natent['suppresslistname']);
		if ($_POST['alertsystemlog'] == "on") { $natent['alertsystemlog'] = 'on'; }else{ $natent['alertsystemlog'] = 'off'; }
		if ($_POST['alertsystemlog_facility']) $natent['alertsystemlog_facility'] = $_POST['alertsystemlog_facility'];
		if ($_POST['alertsystemlog_priority']) $natent['alertsystemlog_priority'] = $_POST['alertsystemlog_priority'];
		if ($_POST['enable_eve_log'] == "on") { $natent['enable_eve_log'] = 'on'; }else{ $natent['enable_eve_log'] = 'off'; }
		if ($_POST['eve_output_type']) $natent['eve_output_type'] = $_POST['eve_output_type'];
		if ($_POST['eve_systemlog_facility']) $natent['eve_systemlog_facility'] = $_POST['eve_systemlog_facility'];
		if ($_POST['eve_systemlog_priority']) $natent['eve_systemlog_priority'] = $_POST['eve_systemlog_priority'];
		if ($_POST['eve_log_alerts'] == "on") { $natent['eve_log_alerts'] = 'on'; }else{ $natent['eve_log_alerts'] = 'on'; }
		if ($_POST['eve_log_alerts_payload']) { $natent['eve_log_alerts_payload'] = $_POST['eve_log_alerts_payload']; }else{ $natent['eve_log_alerts_payload'] = 'on'; }
		if ($_POST['eve_log_alerts_packet'] == "on") { $natent['eve_log_alerts_packet'] = 'on'; }else{ $natent['eve_log_alerts_packet'] = 'on'; }
		if ($_POST['eve_log_alerts_http'] == "on") { $natent['eve_log_alerts_http'] = 'on'; }else{ $natent['eve_log_alerts_http'] = 'on'; }
		if ($_POST['eve_log_alerts_xff'] == "on") { $natent['eve_log_alerts_xff'] = 'on'; }else{ $natent['eve_log_alerts_xff'] = 'off'; }
		if ($_POST['eve_log_alerts_xff_mode']) { $natent['eve_log_alerts_xff_mode'] = $_POST['eve_log_alerts_xff_mode']; }else{ $natent['eve_log_alert_xff_mode'] = 'extra-data'; }
		if ($_POST['eve_log_alerts_xff_deployment']) { $natent['eve_log_alerts_xff_deployment'] = $_POST['eve_log_alerts_xff_deployment']; }else{ $natent['eve_log_alert_xff_deployment'] = 'reverse'; }
		if ($_POST['eve_log_alerts_xff_header']) { $natent['eve_log_alerts_xff_header'] = $_POST['eve_log_alerts_xff_header']; }else{ $natent['eve_log_alert_xff_mode'] = 'X-Forwarded-For'; }
		if ($_POST['eve_log_http'] == "on") { $natent['eve_log_http'] = 'on'; }else{ $natent['eve_log_http'] = 'on'; }
		if ($_POST['eve_log_dns'] == "on") { $natent['eve_log_dns'] = 'on'; }else{ $natent['eve_log_dns'] = 'on'; }
		if ($_POST['eve_log_tls'] == "on") { $natent['eve_log_tls'] = 'on'; }else{ $natent['eve_log_tls'] = 'on'; }
		if ($_POST['eve_log_dhcp'] == "on") { $natent['eve_log_dhcp'] = 'on'; }else{ $natent['eve_log_dhcp'] = 'off'; }
		if ($_POST['eve_log_nfs'] == "on") { $natent['eve_log_nfs'] = 'on'; }else{ $natent['eve_log_nfs'] = 'off'; }
		if ($_POST['eve_log_smb'] == "on") { $natent['eve_log_smb'] = 'on'; }else{ $natent['eve_log_smb'] = 'off'; }
		if ($_POST['eve_log_krb5'] == "on") { $natent['eve_log_krb5'] = 'on'; }else{ $natent['eve_log_krb5'] = 'off'; }
		if ($_POST['eve_log_ikev2'] == "on") { $natent['eve_log_ikev2'] = 'on'; }else{ $natent['eve_log_ikev2'] = 'off'; }
		if ($_POST['eve_log_tftp'] == "on") { $natent['eve_log_tftp'] = 'on'; }else{ $natent['eve_log_tftp'] = 'off'; }
		if ($_POST['eve_log_files'] == "on") { $natent['eve_log_files'] = 'on'; }else{ $natent['eve_log_files'] = 'on'; }
		if ($_POST['eve_log_ssh'] == "on") { $natent['eve_log_ssh'] = 'on'; }else{ $natent['eve_log_ssh'] = 'on'; }
		if ($_POST['eve_log_smtp'] == "on") { $natent['eve_log_smtp'] = 'on'; }else{ $natent['eve_log_smtp'] = 'on'; }
		if ($_POST['eve_log_stats'] == "on") { $natent['eve_log_stats'] = 'on'; }else{ $natent['eve_log_stats'] = 'off'; }
		if ($_POST['eve_log_flow'] == "on") { $natent['eve_log_flow'] = 'on'; }else{ $natent['eve_log_flow'] = 'on'; }
		if ($_POST['eve_log_netflow'] == "on") { $natent['eve_log_netflow'] = 'on'; }else{ $natent['eve_log_netflow'] = 'off'; }
		if ($_POST['eve_log_stats_totals'] == "on") { $natent['eve_log_stats_totals'] = 'on'; }else{ $natent['eve_log_stats_totals'] = 'on'; }
		if ($_POST['eve_log_stats_deltas'] == "on") { $natent['eve_log_stats_deltas'] = 'on'; }else{ $natent['eve_log_stats_deltas'] = 'off'; }
		if ($_POST['eve_log_stats_threads'] == "on") { $natent['eve_log_stats_threads'] = 'on'; }else{ $natent['eve_log_stats_threads'] = 'off'; }
		if ($_POST['eve_log_http_extended'] == "on") { $natent['eve_log_http_extended'] = 'on'; }else{ $natent['eve_log_http_extended'] = 'off'; }
		if ($_POST['eve_log_tls_extended'] == "on") { $natent['eve_log_tls_extended'] = 'on'; }else{ $natent['eve_log_tls_extended'] = 'off'; }
		if ($_POST['eve_log_dhcp_extended'] == "on") { $natent['eve_log_dhcp_extended'] = 'on'; }else{ $natent['eve_log_dhcp_extended'] = 'off'; }
		if ($_POST['eve_log_smtp_extended'] == "on") { $natent['eve_log_smtp_extended'] = 'on'; }else{ $natent['eve_log_smtp_extended'] = 'off'; }
		if ($_POST['eve_log_http_extended_headers']) { $natent['eve_log_http_extended_headers'] = implode(", ",$_POST['eve_log_http_extended_headers']); }else{ $natent['eve_log_http_extended_headers'] = ""; }
		if ($_POST['eve_log_smtp_extended_fields']) { $natent['eve_log_smtp_extended_fields'] = implode(", ",$_POST['eve_log_smtp_extended_fields']); }else{ $natent['eve_log_smtp_extended_fields'] = ""; }

		if ($_POST['eve_log_files_magic'] == "on") { $natent['eve_log_files_magic'] = 'on'; }else{ $natent['eve_log_files_magic'] = 'off'; }
		if ($_POST['eve_log_files_hash']) { $natent['eve_log_files_hash'] = $_POST['eve_log_files_hash']; }else{ $natent['eve_log_files_hash'] = 'none'; }
		if ($_POST['eve_log_drop'] == "on") { $natent['eve_log_drop'] = 'on'; }else{ $natent['eve_log_drop'] = 'on'; }
		if ($_POST['delayed_detect'] == "on") { $natent['delayed_detect'] = 'on'; }else{ $natent['delayed_detect'] = 'off'; }
		if ($_POST['intf_promisc_mode'] == "on") { $natent['intf_promisc_mode'] = 'on'; }else{ $natent['intf_promisc_mode'] = 'off'; }
		if ($_POST['configpassthru']) $natent['configpassthru'] = base64_encode(str_replace("\r\n", "\n", $_POST['configpassthru'])); else $natent['configpassthru'];

		if ($_POST['eve_redis_server']) $natent['eve_redis_server'] = $_POST['eve_redis_server'];
		if ($_POST['eve_redis_port']) $natent['eve_redis_port'] = $_POST['eve_redis_port'];
		if ($_POST['eve_redis_mode']) $natent['eve_redis_mode'] = $_POST['eve_redis_mode'];
		if ($_POST['eve_redis_key']) {
			$out_if_real = get_real_interface($natent['interface']);
			$natent['eve_redis_key'] = "suricata{$out_if_real}{$_POST['uuid']}";
		}


		// Check if EVE OUTPUT TYPE is 'syslog' and auto-enable Suricata syslog output if true.
		if ($natent['eve_output_type'] == "syslog" && $natent['alertsystemlog'] == "off") {
			$natent['alertsystemlog'] = "on";
			$savemsg1 = gettext("EVE Output to syslog requires Suricata alerts to be copied to the system log, so 'Send Alerts to System Log' has been auto-enabled.");
		}

		if ($_POST['performance_extends'] == "on") {
			file_put_contents("/etc/performance_extends", "true");
		} else {
			file_put_contents("/etc/performance_extends", "false");	
		}

		// Check if Inline IPS mode is enabled and display a message about potential
		// incompatibilities with Netmap and some NIC hardware drivers.

		//if ($natent['ips_mode'] == "ips_mode_inline") {
		//	$savemsg2 = gettext("Inline IPS Mode is selected.  Not all hardware NIC drivers support Netmap operation which is required for Inline IPS Mode.  If problems are experienced, switch to Legacy Mode instead.");
		//}
		$reloadInterface = false;

		$if_real = get_real_interface($natent['interface']);
		if (isset($id) && $a_rule[$id] && $action == '') {
			// See if moving an existing Suricata instance to another physical interface
			if ($natent['interface'] != $a_rule[$id]['interface']) {
				$oif_real = get_real_interface($a_rule[$id]['interface']);
				if (suricata_is_running($a_rule[$id]['uuid'], $oif_real)) {
					suricata_stop($a_rule[$id], $oif_real);
					$suricata_start = true;
				}
				else
					$suricata_start = false;
				@rename("{$suricatalogdir}suricata_{$oif_real}{$a_rule[$id]['uuid']}", "{$suricatalogdir}suricata_{$if_real}{$a_rule[$id]['uuid']}");
				@rename("{$suricatadir}suricata_{$a_rule[$id]['uuid']}_{$oif_real}", "{$suricatadir}suricata_{$a_rule[$id]['uuid']}_{$if_real}");
			}
			$a_rule[$id] = $natent;
			$reloadInterface = true;
	
		} elseif (strcasecmp($action, 'dup') == 0) {
			// Duplicating an existing interface to a new interface, so set flag to build new rules
			$rebuild_rules = true;

			// Duplicating an interface, so need to generate a new UUID for the cloned interface
			$natent['uuid'] = suricata_generate_id();

			// Add the new duplicated interface configuration to the [rule] array in config
			$a_rule[] = $natent;

			//Create lck
			$if_real = get_real_interface(strtolower($natent['interface']));
			file_put_contents("/etc/suricata_{$if_real}{$natent['uuid']}_stop.lck", "");
			file_put_contents("{$g['varrun_path']}/suricata_{$if_real}{$natent['uuid']}_stop.lck", "");

		} else {
			// Adding new interface, so set interface configuration parameter defaults
			$natent['ip_max_frags'] = "65535";
			$natent['ip_frag_timeout'] = "60";
			$natent['frag_memcap'] = '33554432';
			$natent['ip_max_trackers'] = '65535';
			$natent['frag_hash_size'] = '65536';

			$natent['flow_memcap'] = '33554432';
			$natent['flow_prealloc'] = '10000';
			$natent['flow_hash_size'] = '65536';
			$natent['flow_emerg_recovery'] = '30';
			$natent['flow_prune'] = '5';

			$natent['flow_tcp_new_timeout'] = '60';
			$natent['flow_tcp_established_timeout'] = '3600';
			$natent['flow_tcp_closed_timeout'] = '120';
			$natent['flow_tcp_emerg_new_timeout'] = '10';
			$natent['flow_tcp_emerg_established_timeout'] = '300';
			$natent['flow_tcp_emerg_closed_timeout'] = '20';

			$natent['flow_udp_new_timeout'] = '30';
			$natent['flow_udp_established_timeout'] = '300';
			$natent['flow_udp_emerg_new_timeout'] = '10';
			$natent['flow_udp_emerg_established_timeout'] = '100';

			$natent['flow_icmp_new_timeout'] = '30';
			$natent['flow_icmp_established_timeout'] = '300';
			$natent['flow_icmp_emerg_new_timeout'] = '10';
			$natent['flow_icmp_emerg_established_timeout'] = '100';

			$natent['stream_memcap'] = '67108864';
			$natent['stream_prealloc_sessions'] = '32768';
			$natent['reassembly_memcap'] = '67108864';
			$natent['reassembly_depth'] = '1048576';
			$natent['reassembly_to_server_chunk'] = '2560';
			$natent['reassembly_to_client_chunk'] = '2560';
			$natent['max_synack_queued'] = '5';
			$natent['enable_midstream_sessions'] = 'off';
			$natent['enable_async_sessions'] = 'off';
			$natent['delayed_detect'] = 'off';
			$natent['intf_promisc_mode'] = 'on';
			$natent['intf_snaplen'] = '1518';

			$natent['asn1_max_frames'] = '256';
			$natent['dns_global_memcap'] = "16777216";
			$natent['dns_state_memcap'] = "524288";
			$natent['dns_request_flood_limit'] = "500";
			$natent['http_parser_memcap'] = "67108864";
			$natent['dns_parser_udp'] = "yes";
			$natent['dns_parser_tcp'] = "yes";
			$natent['dns_parser_udp_ports'] = "53";
			$natent['dns_parser_tcp_ports'] = "53";
			$natent['http_parser'] = "yes";
			$natent['tls_parser'] = "yes";
			$natent['tls_detect_ports'] = "443";
			$natent['tls_encrypt_handling'] = "default";
			$natent['tls_ja3_fingerprint'] = "off";
			$natent['smtp_parser'] = "yes";
			$natent['smtp_parser_decode_mime'] = "off";
			$natent['smtp_parser_decode_base64'] = "on";
			$natent['smtp_parser_decode_quoted_printable'] = "on";
			$natent['smtp_parser_extract_urls'] = "on";
			$natent['smtp_parser_compute_body_md5'] = "off";
			$natent['imap_parser'] = "detection-only";
			$natent['ssh_parser'] = "yes";
			$natent['ftp_parser'] = "yes";
			$natent['dcerpc_parser'] = "yes";
			$natent['smb_parser'] = "yes";
			$natent['msn_parser'] = "detection-only";
			$natent['krb5_parser'] = "yes";
			$natent['ikev2_parser'] = "yes";
			$natent['nfs_parser'] = "yes";
			$natent['tftp_parser'] = "yes";
			$natent['ntp_parser'] = "yes";
			$natent['dhcp_parser'] = "yes";

			$natent['enable_iprep'] = "off";
			$natent['host_memcap'] = "33554432";
			$natent['host_hash_size'] = "4096";
			$natent['host_prealloc'] = "1000";

			$default = array( "name" => "default", "bind_to" => "all", "policy" => "bsd" );
			if (!is_array($natent['host_os_policy']))
				$natent['host_os_policy'] = array();
			if (!is_array($natent['host_os_policy']['item']))
				$natent['host_os_policy']['item'] = array();
			$natent['host_os_policy']['item'][] = $default;

			$default = array( "name" => "default", "bind_to" => "all", "personality" => "IDS",
					  "request-body-limit" => 4096, "response-body-limit" => 4096,
					  "double-decode-path" => "no", "double-decode-query" => "no",
					  "uri-include-all" => "no" );
			if (!is_array($natent['libhtp_policy']))
				$natent['libhtp_policy'] = array();
			if (!is_array($natent['libhtp_policy']['item']))
				$natent['libhtp_policy']['item'] = array();
			$natent['libhtp_policy']['item'][] = $default;

			// Enable the basic default rules for the interface
			$natent['rulesets'] = "app-layer-events.rules||decoder-events.rules||dnp3-events.rules||dns-events.rules||files.rules||http-events.rules||ipsec-events.rules||kerberos-events.rules||" .
					      "modbus-events.rules||nfs-events.rules||ntp-events.rules||smb-events.rules||smtp-events.rules||stream-events.rules||tls-events.rules";

			// Adding a new interface, so set flag to build new rules
			$rebuild_rules = true;

			// Add the new interface configuration to the [rule] array in config
			$a_rule[] = $natent;

			//Create lck
			$if_real = get_real_interface(strtolower($natent['interface']));
			file_put_contents("/etc/suricata_{$if_real}{$natent['uuid']}_stop.lck", "");
			file_put_contents("{$g['varrun_path']}/suricata_{$if_real}{$natent['uuid']}_stop.lck", "");
		}

		// If Suricata is disabled on this interface, stop any running instance
		if ($natent['enable'] != 'on') {
			suricata_stop($natent, $if_real);
		}

		$if_friendly = convert_friendly_interface_to_friendly_descr($natent['interface']);
		$if_real = get_real_interface($natent['interface']);
		$uuid = $natent['uuid'];

		if (isset($_REQUEST['id']) &&
		    is_numericint($_REQUEST['id']) &&
		    $_REQUEST['action'] != 'dup') {
			bp_write_report_db("report_0008_acp_fapp_edit", "FirewallApp|{$if_friendly}|{$if_real}{$uuid}");
		}

		if (isset($_REQUEST['action']) &&
		    $_REQUEST['action'] == 'dup') {
			$base_interface_values = $a_rule[$_REQUEST['id']];
			$dup_if_friendly = convert_friendly_interface_to_friendly_descr($base_interface_values['interface']);
			$dup_if_real = get_real_interface($base_interface_values['interface']);
			$dup_uuid = $base_interface_values['uuid'];
			bp_write_report_db("report_0008_acp_fapp_dup", "FirewallApp|{$dup_if_friendly}|{$dup_if_real}{$dup_uuid}|{$if_friendly}|{$if_real}{$uuid}");
		}

		// Save configuration changes
		write_config("firewallapp pkg: modified interface configuration for " . convert_friendly_interface_to_friendly_descr($natent['interface']));

		//Reload inteface if is running

		if (isset($id) && $action == '' && $reloadInterface) {

			exec("rm /usr/local/share/suricata/rules_fapp/_emerging.rules && rm /usr/local/share/suricata/rules_fapp/_ameacas_ext.rules && rm /usr/local/share/suricata/rules_fapp/_ameacas.rules");
			exec("cd /usr/local/share/suricata/rules/ && rm * && cd /usr/local/share/suricata/ && cp rules_fapp/* rules && rm -f /usr/local/share/suricata/rules/_ameacas.rules && rm -f /usr/local/share/suricata/rules/_ameacas_ext.rules && rm -f /usr/local/share/suricata/rules/_emerging.rules");			
			exec("cp -f /usr/local/pkg/suricata/yalm/fapp/suricata_yaml_template.inc /usr/local/pkg/suricata/");

			foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {

				if ($id == $key) {

					$if = get_real_interface($suricatacfg['interface']);
					$uuid = $suricatacfg['uuid'];

					if (suricata_is_running($uuid, $if)) {

						if (!in_array($if, $all_gtw,true)) {
							if ($suricatacfg['enable'] != 'on' || get_real_interface($suricatacfg['interface']) == "") {
								continue;
							}

							//print_r($suricatacfg);die;
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
					break;
				}
			}
		}

		// Update suricata.conf and suricata.sh files for this interface
		sync_suricata_package_config();

		// Refresh page fields with just-saved values
		$pconfig = $natent;
	} else
		$pconfig = $_POST;

	if ($_POST['ips_mode'] == 'ips_mode_legacy') {
		file_put_contents("/var/run/faap_mode.txt","1");
	} else {
		file_put_contents("/var/run/faap_mode.txt","0");
	}
	
	if (!$input_errors) {
		file_put_contents("/etc/suricata_{$if_real}{$natent['uuid']}_stop.lck", "");
		file_put_contents("{$g['varrun_path']}/suricata_{$if_real}{$natent['uuid']}_stop.lck", "");
		$interface_target = strtoupper($_POST['interface']);
		$savemsg3 = gettext("Changes saved successfully to the interface: ") . $interface_target;
	}
	
}

function suricata_get_config_lists($lists) {
	global $suricataglob;

	$list = array();

	if (is_array($suricataglob[$lists]['item'])) {
		$slist_select = $suricataglob[$lists]['item'];
		foreach ($slist_select as $value) {
			$ilistname = $value['name'];
			$list[$ilistname] = htmlspecialchars($ilistname);
		}
	}

	return(['default' => 'default'] + $list);
}

$if_friendly = convert_friendly_interface_to_friendly_descr($pconfig['interface']);

$pgtitle = array(gettext("Services"), gettext("FirewallApp"), gettext("FirewallApp - Interfaces"), gettext("Edit Interface Settings - ") . $if_friendly);
$pglinks = array("", "/firewallapp/services.php", "/firewallapp/firewallapp_interfaces.php", "@self");

include_once("head.inc");

//echo get_real_interface('interface_vlan_opt3') . "<br>";


/* Display Alert message */
if ($input_errors) {
	print_input_errors($input_errors);
}
if ($savemsg1) {
	print_info_box($savemsg1);
}
if ($savemsg2) {
	print_info_box($savemsg2);
}
if ($savemsg3) {
	print_info_box($savemsg3, 'success');
}

if ($pconfig['enable'] == 'on' && $pconfig['ips_mode'] == 'ips_mode_inline' && (!isset($config['system']['disablechecksumoffloading']) || !isset($config['system']['disablesegmentationoffloading']) || !isset($config['system']['disablelargereceiveoffloading']))) {
	print_info_box(gettext('Heuristic mode requires that Hardware Checksum, Hardware TCP Segmentation and Hardware Large Receive Offloading ' .
				'all be disabled on the ') . '<b>' . gettext('System > Advanced > Networking') . ' </b>' . gettext('tab.'));
}

$tab_array = array();
$tab_array[] = array(gettext("Interfaces"), true, "/firewallapp/firewallapp_interfaces.php");
display_top_tabs($tab_array, true);

$tab_array = array();
$menu_iface=($if_friendly?substr($if_friendly,0,5)." ":"Iface ");
$tab_array[] = array($menu_iface . gettext("Settings"), true, "/firewallapp/firewallapp_interfaces_edit.php?id={$id}");
display_top_tabs($tab_array, true);

$form = new Form;

$section = new Form_Section(gettext("Settings"));
$section->addInput(new Form_Checkbox(
	'enable',
	gettext('Enable'),
	'',
	$pconfig['enable'] == 'on' ? true:false,
	'on'
))->addClass('fapp_enable_bt_switch');

$section->addInput(new Form_Select(
	'interface',
	gettext('Interface'),
	$pconfig['interface'],
	$interfaces
))->setHelp('');

$section->addInput(new Form_Input(
	'descr',
	gettext('Name or nickname'),
	'text',
	$pconfig['descr']
))->setHelp('');

$form->add($section);

$section = new Form_Section(gettext('Logging Settings'),'',0,true);

$section->addInput(new Form_Checkbox(
	'alertsystemlog',
	gettext('Send Alerts to System Log'),
	gettext('Firewallapp will send Alerts from this interface to the firewall\'s system log.'),
	$pconfig['alertsystemlog'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Select(
	'alertsystemlog_facility',
	gettext('Log Facility'),
	$pconfig['alertsystemlog_facility'],
	array(  "auth" => "AUTH", "authpriv" => "AUTHPRIV", "daemon" => "DAEMON", "kern" => "KERN", "security" => "SECURITY",
		"syslog" => "SYSLOG", "user" => "USER", "local0" => "LOCAL0", "local1" => "LOCAL1", "local2" => "LOCAL2",
		"local3" => "LOCAL3", "local4" => "LOCAL4", "local5" => "LOCAL5", "local6" => "LOCAL6", "local7" => "LOCAL7" )
))->setHelp(gettext('Select system log Facility to use for reporting. Default is LOCAL1.'));

$section->addInput(new Form_Select(
	'alertsystemlog_priority',
	gettext('Log Priority'),
	$pconfig['alertsystemlog_priority'],
	array( "emerg" => "EMERG", "crit" => "CRIT", "alert" => "ALERT", "err" => "ERR", "warning" => "WARNING", "notice" => "NOTICE", "info" => "INFO" )
))->setHelp(gettext('Select system log Priority (Level) to use for reporting. Default is NOTICE.'));

$section->addInput(new Form_Checkbox(
	'enable_stats_log',
	gettext('Enable Stats Log'),
	gettext('Firewallapp will periodically log statistics for the interface. Default is Not Checked.'),
	$pconfig['enable_stats_log'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Input(
	'stats_upd_interval',
	gettext('Stats Update Interval'),
	'text',
	$pconfig['stats_upd_interval']
))->setHelp(gettext('Enter the update interval in seconds for collection and logging of statistics. Default is 10.'));

$section->addInput(new Form_Checkbox(
	'append_stats_log',
	gettext('Append Stats Log'),
	gettext('Firewallapp will append-to instead of clearing statistics log file when restarting. Default is Not Checked.'),
	$pconfig['append_stats_log'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Checkbox(
	'enable_http_log',
	gettext('Enable HTTP Log'),
	gettext('Firewallapp will log decoded HTTP traffic for the interface. Default is Checked.'),
	$pconfig['enable_http_log'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Checkbox(
	'append_http_log',
	gettext('Append HTTP Log'),
	gettext('Firewallapp will append-to instead of clearing HTTP log file when restarting. Default is Checked.'),
	$pconfig['append_http_log'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Checkbox(
	'http_log_extended',
	gettext('Log Extended HTTP Info'),
	gettext('Firewallapp will log extended HTTP information. Default is Checked.'),
	$pconfig['http_log_extended'] == 'on' ? true:false,
	'on'
));
$section->addInput(new Form_Checkbox(
	'enable_tls_log',
	gettext('Enable TLS Log'),
	gettext('Firewallapp will log TLS handshake traffic for the interface. Default is Not Checked.'),
	$pconfig['enable_tls_log'] == 'on' ? true:false,
	'on'
));
$section->addInput(new Form_Checkbox(
	'enable_tls_store',
	gettext('Enable TLS Store'),
	gettext('Firewallapp will log and store TLS certificates for the interface. Default is Not Checked.'),
	$pconfig['enable_tls_store'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Checkbox(
	'tls_log_extended',
	gettext('Log Extended TLS Info'),
	gettext('Firewallapp will log extended TLS info such as fingerprint. Default is Checked.'),
	$pconfig['tls_log_extended'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Checkbox(
	'enable_json_file_log',
	gettext('Enable Tracked-Files Log'),
	gettext('Firewallapp will log tracked files in JavaScript Object Notation (JSON) format. Default is Not Checked.'),
	$pconfig['enable_json_file_log'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Checkbox(
	'append_json_file_log',
	gettext('Append Tracked-Files Log'),
	gettext('Firewallapp will append-to instead of clearing Tracked Files log file when restarting. Default is Checked.'),
	$pconfig['append_json_file_log'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Checkbox(
	'enable_tracked_files_magic',
	gettext('Enable Logging Magic for Tracked-Files'),
	gettext('Firewallapp will force logging magic on all logged Tracked Files. Default is Not Checked.'),
	$pconfig['enable_tracked_files_magic'] == 'on' ? true:false,
	'on'
));
$section->addInput(new Form_Select(
	'tracked_files_hash',
	gettext('Tracked-Files Checksum'),
	$pconfig['tracked_files_hash'],
	array("none" => "None", "md5" => "MD5", "sha1" => "SHA1", "sha256" => "SHA256")
))->setHelp('firewallapp will generate checksums for all logged Tracked Files using the chosen algorithm. Default is None.');
$section->addInput(new Form_Checkbox(
	'enable_file_store',
	gettext('Enable File-Store'),
	gettext('Firewallapp will extract and store files from application layer streams. Default is Not Checked. Warning: This will consume a significant amount of disk space on a busy network when enabled.'),
	$pconfig['enable_file_store'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Checkbox(
	'enable_pcap_log',
	gettext('Enable Packet Log'),
	'Firewallapp will log decoded packets for the interface in pcap-format. Default is Not Checked. This can consume a significant amount of disk space when enabled.',
	$pconfig['enable_pcap_log'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Input(
	'max_pcap_log_size',
	gettext('Max Packet Log File Size'),
	'text',
	$pconfig['max_pcap_log_size']
))->setHelp(gettext('Enter maximum size in MB for a packet log file. Default is 32. When the packet log file size reaches the set limit, it will be rotated and a new one created.'));

$section->addInput(new Form_Input(
	'max_pcap_log_files',
	gettext('Max Packet Log Files'),
	'text',
	$pconfig['max_pcap_log_files']
))->setHelp(gettext('Enter maximum number of packet log files to maintain. Default is 1000. When the number of packet log files reaches the set limit, the oldest file will be overwritten.'));

$form->add($section);

$section = new Form_Section('EVE Output Settings','',0,true);

$section->addInput(new Form_Checkbox(
	'enable_eve_log',
	gettext('EVE JSON Log'),
	gettext('Firewallapp will output selected info in JSON format to a single file or to syslog. Default is Not Checked.'),
	$pconfig['enable_eve_log'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Select(
	'eve_output_type',
	gettext('EVE Output Type'),
	$pconfig['eve_output_type'],
	array("regular" => "FILE", "syslog" => "SYSLOG", "redis"=>"REDIS")
))->setHelp(gettext('Select EVE log output destination. Choosing FILE is suggested, and is the default value.'));

$section->addInput(new Form_Select(
	'eve_systemlog_facility',
	gettext('EVE Syslog Output Facility'),
	$pconfig['eve_systemlog_facility'],
	array(  "auth" => "AUTH", "authpriv" => "AUTHPRIV", "daemon" => "DAEMON", "kern" => "KERN", "security" => "SECURITY",
		"syslog" => "SYSLOG", "user" => "USER", "local0" => "LOCAL0", "local1" => "LOCAL1", "local2" => "LOCAL2",
		"local3" => "LOCAL3", "local4" => "LOCAL4", "local5" => "LOCAL5", "local6" => "LOCAL6", "local7" => "LOCAL7" )
))->setHelp(gettext('Select EVE syslog output facility.'));

$section->addInput(new Form_Select(
	'eve_systemlog_priority',
	gettext('EVE Syslog Output Priority'),
	$pconfig['eve_systemlog_priority'],
	array( "emerg" => "EMERG", "crit" => "CRIT", "alert" => "ALERT", "err" => "ERR", "warning" => "WARNING", "notice" => "NOTICE", "info" => "INFO" )
))->setHelp(gettext('Select EVE syslog output priority.'));

$group = new Form_Group(gettext('EVE REDIS Server'));

$group->add(new Form_Input(
	'eve_redis_server',
	gettext('Redis Server'),
	'text',
	$pconfig['eve_redis_server']
))->setHelp(gettext('Enter the Redis server IP'));

$group->add(new Form_Input(
	'eve_redis_port',
	gettext('Port'),
	'text',
	$pconfig['eve_redis_port']
))->setHelp(gettext('Enter the Redis server port'));

$section->add($group)->addClass('eve_redis_connection');

$section->addInput(new Form_Select(
	'eve_redis_mode',
	gettext('EVE REDIS Mode'),
	$pconfig['eve_redis_mode'],
	array("list"=>"List (LPUSH)","rpush"=>"List (RPUSH)","channel"=>"Channel(PUBLISH)")
))->setHelp(gettext('Select the REDIS output mode'));

$section->addInput(new Form_Input(
	'eve_redis_key',
	gettext('EVE REDIS Key'),
	'text',
	$pconfig['eve_redis_key']
))->setHelp(gettext('Enter the REDIS Key'));

$section->addInput(new Form_Checkbox(
	'eve_log_alerts_xff',
	gettext('EVE HTTP XFF Support'),
	gettext('Log X-Forwarded-For IP addresses.  Default is Not Checked.'),
	$pconfig['eve_log_alerts_xff'] == 'on' ? true:false,
	'on'
));
$section->addInput(new Form_Select(
	'eve_log_alerts_xff_mode',
	gettext('EVE X-Forwarded-For Operational Mode'),
	$pconfig['eve_log_alerts_xff_mode'],
	array( "extra-data" => "extra-data", "overwrite" => "overwrite" )
))->setHelp(gettext('Select HTTP X-Forwarded-For Operation Mode. Extra-Data adds an extra field while Overwrite overwrites the existing source or destination IP. Default is extra-data.'));

$section->addInput(new Form_Select(
	'eve_log_alerts_xff_deployment',
	gettext('EVE X-Forwarded-For Deployment'),
	$pconfig['eve_log_alerts_xff_deployment'],
	array( "reverse" => "reverse", "forward" => "forward" )
))->setHelp(gettext('Select HTTP X-Forwarded-For Deployment.  Reverse deployment uses the last IP address while Forward uses the first one. Default is reverse.'));

$section->addInput(new Form_Input(
	'eve_log_alerts_xff_header',
	gettext('EVE Log Alert X-Forwarded-For Header'),
	'text',
	$pconfig['eve_log_alerts_xff_header']
))->setHelp(gettext('Enter header where actual IP address is reported. Default is X-Forwarded-For. If more than one IP address is present, the last one will be used.'));

$section->addInput(new Form_Checkbox(
	'eve_log_alerts',
	gettext('EVE Log Alerts'),
	gettext('firewallapp will output Alerts via EVE'),
	$pconfig['eve_log_alerts'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Select(
	'eve_log_alerts_payload',
	gettext('EVE Log Alert Payload Data Formats'),
	$pconfig['eve_log_alerts_payload'],
	array("off"=>"NO","only-base64"=>"BASE64","only-printable"=>"PRINTABLE","on"=>"BOTH")
))->setHelp(gettext('Log the payload data with alerts.  Options are No (disable payload logging), Only Printable (lossy) format, Only Base64 encoded or Both. See Suricata documentation.'));

$group = new Form_Group(gettext('EVE Log Alert details'));

$group->add(new Form_Checkbox(
	'eve_log_alerts_packet',
	gettext('Alert Payloads'),
	gettext('Log a packet dump with alerts.'),
	$pconfig['eve_log_alerts_packet'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_alerts_http',
	gettext('Alert Payloads'),
	gettext('Log additional HTTP data.'),
	$pconfig['eve_log_alerts_http'] == 'on' ? true:false,
	'on'
));

$group->setHelp(gettext('Select which details firewallapp will use to enrich alerts.'));

$section->add($group)->addClass('eve_log_alerts_details');

$group = new Form_Group(gettext('EVE Logged Traffic'));

$group->add(new Form_Checkbox(
	'eve_log_http',
	gettext('HTTP Traffic'),
	gettext('HTTP Traffic'),
	$pconfig['eve_log_http'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_dns',
	gettext('DNS Traffic'),
	gettext('DNS Traffic'),
	$pconfig['eve_log_dns'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_smtp',
	gettext('SMTP Traffic'),
	gettext('SMTP Traffic'),
	$pconfig['eve_log_smtp'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_nfs',
	gettext('NFS Traffic'),
	gettext('NFS Traffic'),
	$pconfig['eve_log_nfs'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_smb',
	gettext('SMB Traffic'),
	gettext('SMB Traffic'),
	$pconfig['eve_log_smb'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_krb5',
	gettext('Kerberos Traffic'),
	gettext('Kerberos Traffic'),
	$pconfig['eve_log_krb5'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_ikev2',
	gettext('IKEv2 Traffic'),
	gettext('IKEv2 Traffic'),
	$pconfig['eve_log_ikev2'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_tftp',
	gettext('TFTP Traffic'),
	gettext('TFTP Traffic'),
	$pconfig['eve_log_tftp'] == 'on' ? true:false,
	'on'
));

$group->setHelp(gettext('Choose the traffic types to log via EVE JSON output.'));
$section->add($group)->addClass('eve_log_info');

$group = new Form_Group(gettext('EVE Logged Info'));
$group->add(new Form_Checkbox(
	'eve_log_tls',
	gettext('TLS Handshakes'),
	gettext('TLS Handshakes'),
	$pconfig['eve_log_tls'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_ssh',
	gettext('SSH Handshakes'),
	gettext('SSH Handshakes'),
	$pconfig['eve_log_ssh'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_dhcp',
	gettext('DHCP Messages'),
	gettext('DHCP Messages'),
	$pconfig['eve_log_dhcp'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_files',
	gettext('Tracked Files'),
	gettext('Tracked Files'),
	$pconfig['eve_log_files'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_stats',
	gettext('firewallapp Stats'),
	gettext('firewallapp Stats'),
	$pconfig['eve_log_stats'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_flow',
	gettext('Traffic Flows'),
	gettext('Traffic Flows'),
	$pconfig['eve_log_flow'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_netflow',
	gettext('Net Flow'),
	gettext('Net Flow'),
	$pconfig['eve_log_netflow'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_drop',
	gettext('Dropped Traffic'),
	gettext('Dropped Traffic'),
	$pconfig['eve_log_drop'] == 'on' ? true:false,
	'on'
));

$group->setHelp(gettext('Choose the information to log via EVE JSON output.'));
$section->add($group)->addClass('eve_log_info');

$group = new Form_Group(gettext('EVE Logged Extended'));

$group->add(new Form_Checkbox(
	'eve_log_http_extended',
	gettext('Extended HTTP Info'),
	gettext('Extended HTTP Info'),
	$pconfig['eve_log_http_extended'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_tls_extended',
	gettext('Extended TLS Info'),
	gettext('Extended TLS Info'),
	$pconfig['eve_log_tls_extended'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_dhcp_extended',
	gettext('Extended DHCP Info'),
	gettext('Extended DHCP Info'),
	$pconfig['eve_log_dhcp_extended'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_smtp_extended',
	gettext('Extended SMTP Info'),
	gettext('Extended SMTP Info'),
	$pconfig['eve_log_tls_extended'] == 'on' ? true:false,
	'on'
));

$group->setHelp(gettext('Select which EVE logs are supplemented with extended information.'));
$section->add($group)->addClass('eve_log_info');

$section->addInput(new Form_Select(
	'eve_log_http_extended_headers',
	gettext('Extended HTTP Headers'),
	is_array($pconfig['eve_log_http_extended_headers']) 
	? $pconfig['eve_log_http_extended_headers'] 
	: explode(", ", $pconfig['eve_log_http_extended_headers']),
	array("accept"=>"accept","accept-charset"=>"accept-charset","accept-datetime"=>"accept-datetime","accept-encoding"=>"accept-encoding","accept-language"=>"accept-language","accept-range"=>"accept-range","age"=>"age","allow"=>"allow","authorization"=>"authorization","cache-control"=>"cache-control","connection"=>"connection","content-encoding"=>"content-encoding","content-language"=>"content-language","content-length"=>"content-length","content-location"=>"content-location","content-md5"=>"content-md5","content-range"=>"content-range","content-type"=>"content-type","cookie"=>"cookie","date"=>"date","dnt"=>"dnt","etags"=>"etags","from"=>"from","last-modified"=>"last-modified","link"=>"link","location"=>"location","max-forwards"=>"max-forwards","origin"=>"origin","pragma"=>"pragma","proxy-authenticate"=>"proxy-authenticate","proxy-authorization"=>"proxy-authorization","range"=>"range","referrer"=>"referrer","refresh"=>"refresh","retry-after"=>"retry-after","server"=>"server","set-cookie"=>"set-cookie","te"=>"te","trailer"=>"trailer","transfer-encoding"=>"transfer-encoding","upgrade"=>"upgrade","vary"=>"vary","via"=>"via","warning"=>"warning","www-authenticate"=>"www-authenticate","x-authenticated-user"=>"x-authenticated-user","x-flash-version"=>"x-flash-version","x-forwarded-proto"=>"x-forwarded-proto","x-requested-with"=>"x-requested-with"),
	true
))->setHelp(gettext('Select HTTP headers for logging.  Use CTRL + click for multiple selections.'));


$section->addInput(new Form_Select(
	'eve_log_smtp_extended_fields',
	gettext('Extended SMTP Fields'),
	is_array($pconfig['eve_log_smtp_extended_fields']) 
    ? $pconfig['eve_log_smtp_extended_fields'] 
    : explode(", ", $pconfig['eve_log_smtp_extended_fields']),
	array("bcc"=>"bcc","content-md5"=>"content-md5","date"=>"date","importance"=>"importance","in-reply-to"=>"in-reply-to","message-id"=>"message-id","organization"=>"organization","priority"=>"priority","received"=>"received","references"=>"references","reply-to"=>"reply-to","sensitivity"=>"sensitivity","subject"=>"subject","user-agent"=>"user-agent","x-mailer"=>"x-mailer","x-originating-ip"=>"x-originating-ip"),
	true
))->setHelp(gettext('Select SMTP fields for logging.  Use CTRL + click for multiple selections.'));

$section->addInput(new Form_Checkbox(
	'eve_log_files_magic',
	gettext('Enable Logging Magic for Tracked-Files'),
	gettext('firewallapp will force logging magic on all logged Tracked Files. Default is Not Checked.'),
	$pconfig['eve_log_files_magic'] == 'on' ? true:false,
	'on'
));
$section->addInput(new Form_Select(
	'eve_log_files_hash',
	gettext('Tracked-Files Checksum'),
	$pconfig['eve_log_files_hash'],
	array("none" => "None", "md5" => "MD5", "sha1" => "SHA1", "sha256" => "SHA256")
))->setHelp(gettext('firewallapp will generate checksums for all logged Tracked Files using the chosen algorithm. Default is None.'));

$group = new Form_Group('EVE Logged Stats');

$group->add(new Form_Checkbox(
	'eve_log_stats_totals',
	gettext('Stats total'),
	gettext('Log Totals'),
	$pconfig['eve_log_stats_totals'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_stats_deltas',
	gettext('Stats deltas'),
	gettext('Log deltas'),
	$pconfig['eve_log_stats_deltas'] == 'on' ? true:false,
	'on'
));

$group->add(new Form_Checkbox(
	'eve_log_stats_threads',
	gettext('Stats per thread'),
	gettext('Log per thread'),
	$pconfig['eve_log_stats_threads'] == 'on' ? true:false,
	'on'
));

$section->add($group)->addClass('eve_log_stats_details');

$form->add($section);

$section = new Form_Section(gettext('Firewall Execution and Performance'));

$section->addInput(new Form_Checkbox(
	'blockoffenders',
	gettext('Activate'),
	gettext('Choose the option as needed and the Hardware resources available.'),
	$pconfig['blockoffenders'] == 'on' ? true:false,
	'on'
));

$group = new Form_Group(gettext('FApp execution'));
$group->add(new Form_Select(
	'ips_mode',
	gettext('IPS Mode'),
	$pconfig['ips_mode'],
	#return_array_compatiby_interfaces()
	array( "ips_mode_legacy" => gettext('Performance'), "ips_mode_inline" => gettext('Heuristic') )
))->setHelp(gettext('Select Run Mode. Performance Mode inspects copies of packets while Heuristic Mode introduces the firewallapp inspection mechanism') .
gettext('in the network stack between the NIC and the operating system. The default is Performance mode.'));
$group->setHelp("<p style='color: red;'>" . gettext('ATTENTION: Consider that in Performance mode the exceptions in LOCKS will be DISABLED. And the Heuristic mode needs to spare Hardware resources on the equipment and that it can generate small variations in the network when it is turned ON or Enabled, it is also worth commenting that when this field is changed to any desired mode, it will be necessary to restart the FirewallApp service (Disable/Enable in the side menu);')  . "</p>" );
$section->add($group);

$section->addInput(new Form_Checkbox(
	'mixed_mode',
	gettext('Performance (Mixed mode)'),
	gettext('Enable mixed operation of FirewallApp interface.'),
	'',
	''
))->setHelp("<p style='color:red;'>" . gettext('Note: This operation will activate the mixed mode on the interface, enabling performace analysis operations with heuristics, be aware that saving the rules can take longer than normal to be carried out with this mode activated on the interfaces.') . "</p>");

$section->addInput(new Form_Checkbox(
	'performance_extends',
	gettext('Extended performance mode'),
	gettext('Enable exceptions in performance mode.'),
	enable_performance_extends(),
	'on'
))->setHelp("<p style='color:red;'>" . gettext('Note: This option has global value and affects all interfaces configured with performance mode.') . "</p>");

$section->addInput(new Form_Checkbox(
	'blockoffenderskill',
	gettext('Kill States'),
	gettext('Checking this option will kill firewall states for the blocked IP.  Default is Checked.'),
	$pconfig['blockoffenderskill'] == 'on' ? true:false,
	'on'
),true);

$section->addInput(new Form_Select(
	'blockoffendersip',
	gettext('Which Blocking Origin'),
	$pconfig['blockoffendersip'],
	array( 'src' => 'SRC', 'dst' => 'DST', 'both' => 'BOTH' )
))->setHelp(gettext("Select which IP extracted from the packet you want to block."));

$section->addInput(new Form_Checkbox(
	'block_drops_only',
	gettext('Block On DROP Only'),
	gettext('Checking this option will insert blocks only when rule signatures having the DROP action are triggered.  When not checked, any rule action (ALERT or DROP) will generate a block of the offending host.  Default is Not Checked.'),
	$pconfig['block_drops_only'] == 'on' ? true:false,
	'on'
),true);

$form->add($section);

// Add Inline IPS rule edit warning modal pop-up
/*$modal = new Modal('Important Information About IPS Inline Mode Blocking', 'ips_warn_dlg', 'large', 'Close');

$modal->addInput(new Form_StaticText (
	null,
	'<span class="help-block">' . 
	gettext('When using Inline IPS Mode blocking, you must manually change the rule action ') . 
	gettext('from ALERT to DROP for every rule which you wish to block traffic when triggered.') . 
	'<br/><br/>' . 
	gettext('The default action for rules is ALERT.  This will produce alerts but will not ') . 
	gettext('block traffic when using Inline IPS Mode for blocking. ') . 
	'<br/><br/>' . 
	gettext('Use the "dropsid.conf" feature on the SID MGMT tab to select rules whose action ') . 
	gettext('should be changed from ALERT to DROP.  If you run the Snort rules and have ') . 
	gettext('an IPS policy selected on the CATEGORIES tab, then rules defined as DROP by the ') . 
	gettext('selected IPS policy will have their action automatically changed to DROP when the ') . 
	gettext('"IPS Policy Mode" selector is configured for "Policy".') . 
	'</span>'
));

$form->add($modal);*/

$section = new Form_Section(gettext('Performance'));
$section->addInput(new Form_Select(
	'runmode',
	'Perfil de Processamento',
	$pconfig['runmode'],
	array('autofp' => gettext('Low performance'), 'workers' => gettext('Recommended'))
))->setHelp('');
$section->addInput(new Form_Input(
	'max_pending_packets',
	gettext('Max Pending Packets'),
	'text',
	$pconfig['max_pending_packets']
),true)->setHelp(gettext('Enter number of simultaneous packets to process. Default is 1024.<br/>This controls the number simultaneous packets the engine can handle. ') .
			gettext('Setting this higher generally keeps the threads more busy. The minimum value is 1 and the maximum value is 65,000.<br />') .
			gettext('Warning: Setting this too high can lead to degradation and a possible system crash by exhausting available memory.'));

$section->addInput(new Form_Select(
	'detect_eng_profile',
	gettext('Processor & Memory Balancing'),
	$pconfig['detect_eng_profile'],
	array('low' => gettext('More memory, less CPU'), 'medium' => gettext('Resource Balance'), 'high' => gettext('More CPU, Less Memory'))
))->setHelp('');

$section->addInput(new Form_Select(
	'mpm_algo',
	gettext('Pattern Matcher Algorithm'),
	$pconfig['mpm_algo'],
	array('auto' => 'Auto', 'ac' => 'AC', 'ac-bs' => 'AC-BS', 'ac-ks' => 'AC-KS', 'hs' => 'Hyperscan')
),true)->setHelp(gettext('Choose a multi-pattern matcher (MPM) algorithm. Auto is the default, and is the best choice for almost all systems.  Auto will use hyperscan if available.'));

$section->addInput(new Form_Select(
	'sgh_mpm_context',
	gettext('Signature Group Header MPM Context'),
	$pconfig['sgh_mpm_context'],
	array('auto' => 'Auto', 'full' => 'Full', 'single' => 'Single')
),true)->setHelp(gettext('Choose a Signature Group Header multi-pattern matcher context. Default is Auto.<br />AUTO means firewallapp selects between Full and Single based on the MPM algorithm chosen. ') .
			gettext('FULL means every Signature Group has its own MPM context. SINGLE means all Signature Groups share a single MPM context. Using FULL can improve performance at the expense of significant memory consumption.'));

$section->addInput(new Form_Input(
	'inspect_recursion_limit',
	gettext('Inspection Recursion Limit'),
	'text',
	$pconfig['inspect_recursion_limit']
),true)->setHelp(gettext('Enter limit for recursive calls in content inspection code. Default is 3000.<br />When set to 0 an internal default is used. When left blank there is no recursion limit.'));

$section->addInput(new Form_Checkbox(
	'delayed_detect',
	gettext('Performance'),
	gettext("Enable for High Latency networks."),
	$pconfig['delayed_detect'] == 'on' ? true:false,
	'on'
));

$section->addInput(new Form_Checkbox(
	'intf_promisc_mode',
	gettext('Promiscuous Mode'),
	gettext('FirewallApp will place the monitored interface in promiscuous mode when checked. Default is Checked.'),
	$pconfig['intf_promisc_mode'] == 'on' ? true:false,
	'on'
),true);

$section->addInput(new Form_Input(
	'intf_snaplen',
	gettext('Interface PCAP Snaplen'),
	'text',
	$pconfig['intf_snaplen']
),true)->setHelp(gettext('Enter value in bytes for the interface PCAP snaplen. Default is 1518.  This parameter is only valid when IDS or Performance Mode IPS is enabled.<br />This value may need to be increased if the physical interface is passing VLAN traffic and expected alerts are not being received.'));

$form->add($section);

$section = new Form_Section(gettext('Networks firewallapp Should Inspect and Protect'),'',0,true);

$group = new Form_Group(gettext('Home Net'));

$group->add(new Form_Select(
	'homelistname',
	gettext('Home Net'),
	$pconfig['homelistname'],
	suricata_get_config_lists('passlist')
))->setHelp(gettext('Choose the Home Net you want this interface to use.'));

$group->add(new Form_Button(
	'btnHomeNet',
	' ' . gettext('View List'),
	'#',
	'fa-file-text-o'
))->removeClass('btn-primary')->addClass('btn-info')->addClass('btn-sm')->setAttribute('data-toggle', 'modal')->setAttribute('data-target', '#homenet');

$group->setHelp(gettext('Default Home Net adds only local networks, WAN IPs, Gateways, VPNs and VIPs.') . '<br />' .
		gettext('Create an Alias to hold a list of friendly IPs that the firewall cannot see or to customize the default Home Net.'));

$section->add($group);

$group = new Form_Group(gettext('External Net'));

$group->add(new Form_Select(
	'externallistname',
	gettext('External Net'),
	$pconfig['externallistname'],
	suricata_get_config_lists('passlist')
))->setHelp(gettext('Choose the External Net you want this interface to use.'));

$group->add(new Form_Button(
	'btnExternalNet',
	' ' . gettext('View List'),
	'#',
	'fa-file-text-o'
))->removeClass('btn-primary')->addClass('btn-info')->addClass('btn-sm')->setAttribute('data-target', '#externalnet')->setAttribute('data-toggle', 'modal');

$group->setHelp(gettext('External Net is networks that are not Home Net.  Most users should leave this setting at default.') . '<br />' .
		gettext('Create a Pass List and add an Alias to it, and then assign the Pass List here for custom External Net settings.'));

$section->add($group);

$group = new Form_Group('Pass List');

$group->addClass('passlist');
$list = suricata_get_config_lists('passlist');
$list['none'] = 'none';
$group->add(new Form_Select(
	'passlistname',
	gettext('Pass List'),
	$pconfig['passlistname'],
	$list
))->setHelp(gettext('Choose the Pass List you want this interface to use. Addresses in a Pass List are never blocked. Select "none" to prevent use of a Pass List.'));

$group->add(new Form_Button(
	'btnPasslist',
	' ' . gettext('View List'),
	'#',
	'fa-file-text-o'
))->removeClass('btn-primary')->addClass('btn-info')->addClass('btn-sm')->setAttribute('data-target', '#passlist')->setAttribute('data-toggle', 'modal');

$group->setHelp(gettext('The default Pass List adds Gateways, DNS servers, locally-attached networks, the WAN IP, VPNs and VIPs.  Create a Pass List with an alias to customize whitelisted IP addresses.  ' . 
		'This option will only be used when block offenders is on.  Choosing "none" will disable Pass List generation.'));

$section->add($group);

$form->add($section);

// Add view HOME_NET modal pop-up
$modal = new Modal(gettext('View HOME_NET'), 'homenet', 'large', 'Close');

$modal->addInput(new Form_Textarea (
	'homenet_text',
	'',
	gettext('...Loading...')
))->removeClass('form-control')->addClass('row-fluid col-sm-10')->setAttribute('rows', '10')->setAttribute('wrap', 'off');
$form->add($modal);

// Add view EXTERNAL_NET modal pop-up
$modal = new Modal(gettext('View EXTERNAL_NET'), 'externalnet', 'large', 'Close');

$modal->addInput(new Form_Textarea (
	'externalnet_text',
	'',
	gettext('...Loading...')
))->removeClass('form-control')
  ->addClass('row-fluid col-sm-10')
  ->setAttribute('rows', '10')
  ->setAttribute('wrap', 'off');

$form->add($modal);

// Add view PASS_LIST modal pop-up
$modal = new Modal(gettext('View PASS LIST'), 'passlist', 'large', 'Close');

$modal->addInput(new Form_Textarea (
	'passlist_text',
	'',
	gettext('...Loading...')
))->removeClass('form-control')
  ->addClass('row-fluid col-sm-10')
  ->setAttribute('rows', '10')
  ->setAttribute('wrap', 'off');

$form->add($modal);

$section = new Form_Section(gettext('Alert Suppression and Filtering'),'',0,true);
$group = new Form_Group(gettext('Alert Suppression and Filtering'));
$group->add(new Form_Select(
	'suppresslistname',
	gettext('Alert Suppression and Filtering'),
	$pconfig['suppresslistname'],
	suricata_get_config_lists('suppress')
))->setHelp(gettext('Choose the suppression or filtering file you want this interface to use. Default option disables suppression and filtering.'));

$group->add(new Form_Button(
	'btnSuppressList',
	' ' . 'View List',
	'#',
	'fa-file-text-o'
))->removeClass('btn-primary')
  ->addClass('btn-info btn-sm')
  ->setAttribute('data-target', '#suppresslist')
  ->setAttribute('data-toggle', 'modal');

$section->add($group);

$form->add($section);

// Add view SUPPRESS_LIST modal pop-up
$modal = new Modal(gettext('View Suppress List'), 'suppresslist', 'large', 'Close');

$modal->addInput(new Form_Textarea (
	'suppresslist_text',
	'',
	gettext('...Loading...')
))->removeClass('form-control')->addClass('row-fluid col-sm-10')->setAttribute('rows', '10')->setAttribute('wrap', 'off');

$form->add($modal);

$section = new Form_Section(gettext('Arguments here will be automatically inserted into the firewallapp configuration'),'', 0, true);
$section->addInput(new Form_Textarea (
	'configpassthru',
	gettext('Advanced Configuration Pass-Through'),
	($_POST['configpassthru']?$_POST['configpassthru']:$pconfig['configpassthru'])
))->setHelp(gettext('Enter any additional configuration parameters to add to the firewallapp configuration here, separated by a newline'));

$form->add($section);


if (isset($id)) {
	$form->addGlobal(new Form_Input(
		'id',
		'id',
		'hidden',
		$id
	));
}
if (isset($action)) {
	$form->addGlobal(new Form_Input(
		'action',
		'action',
		'hidden',
		$action
	));
}
print($form);
?>

<div class="infoblock">
	<?=print_info_box('<div class="row">
		<div class="col-md-12">
			<p><strong>' . gettext("Please save your settings before you attempt to start firewallapp.") . '</strong></p>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<p>' . gettext("***********************************") . '</p>
			<p><strong>' . gettext("UTM Models Vs Mult-Interfaces.") . '</strong></p>
			<p>' . gettext("UTMs 1000,1500,2000 - 4 simply or 1 simply and 1 heuristic") . '</p>
			<p>' . gettext("UTMs 2500,3000 - 6 simply or 3 simply and 1 heuristic") . '</p>
			<p>' . gettext("UTMs 3500,4000,4500 - 10 simply or 5 simply and 2 heuristic") . '</p>
			<p>' . gettext("UTM 5000 - 14 simply or 7 simply and 3 heuristic") . '</p>
		</div>
	</div>', 'info')?>
</div>

<script type="text/javascript">
//<![CDATA[
events.push(function(){

	function enable_blockoffenders() {
		var hide = ! $('#blockoffenders').prop('checked');
		hideCheckbox('blockoffenderskill', hide);
		hideCheckbox('block_drops_only', hide);
		hideSelect('blockoffendersip', hide);
		hideSelect('ips_mode', hide);
		hideClass('passlist', hide);
		if ($('#ips_mode').val() == 'ips_mode_inline') {
			hideCheckbox('blockoffenderskill', true);
			hideCheckbox('block_drops_only', true);
			hideSelect('blockoffendersip', true);
			hideClass('passlist', true);
			hideInput('intf_snaplen', true);
			if (hide) {
				$('#eve_log_drop').parent().hide();
			}
			else {
				$('#eve_log_drop').parent().show();
			}
		}
		else {
			$('#eve_log_drop').parent().hide();
			hideInput('intf_snaplen', false);
		}
	}

	function toggle_system_log() {
		var hide = ! $('#alertsystemlog').prop('checked');
		hideSelect('alertsystemlog_facility', hide);
		hideSelect('alertsystemlog_priority', hide);
	}

	function toggle_stats_log() {
		var hide = ! $('#enable_stats_log').prop('checked');
		hideInput('stats_upd_interval', hide);
		hideCheckbox('append_stats_log', hide);
		disableInput('eve_log_stats',hide);
		var hide_stats_eve = ! ($('#enable_stats_log').prop('checked') && $('#eve_log_stats').prop('checked'));
		hideClass('eve_log_stats_details',hide_stats_eve);
	}

	function toggle_http_log() {
		var hide = ! $('#enable_http_log').prop('checked');
		hideCheckbox('append_http_log', hide);
		hideCheckbox('http_log_extended', hide);
	}

	function toggle_tls_log() {
		var hide = ! $('#enable_tls_log').prop('checked');
		hideCheckbox('enable_tls_store', hide);
		hideCheckbox('tls_log_extended', hide);
	}

	function toggle_json_file_log() {
		var hide = ! $('#enable_json_file_log').prop('checked');
		hideCheckbox('append_json_file_log', hide);
		hideCheckbox('enable_tracked_files_magic', hide);
		hideSelect('tracked_files_hash', hide);
	}

	function toggle_pcap_log() {
		var hide = ! $('#enable_pcap_log').prop('checked');
		hideInput('max_pcap_log_size', hide);
		hideInput('max_pcap_log_files', hide);
	}

	function toggle_eve_log() {
		var hide = ! $('#enable_eve_log').prop('checked');
		hideSelect('eve_output_type', hide);
		hideCheckbox('eve_log_alerts',hide);
		hideCheckbox('eve_log_alerts_xff',hide);
		hideClass('eve_log_info', hide);
		toggle_eve_log_files();
	}
	function toggle_eve_syslog() {
		var hide = ! ($('#enable_eve_log').prop('checked') && $('#eve_output_type').val() == "syslog");
		hideSelect('eve_systemlog_facility',hide);
		hideSelect('eve_systemlog_priority',hide);
	}
	function toggle_eve_redis() {
		var hide = ! ($('#enable_eve_log').prop('checked') && $('#eve_output_type').val() == "redis");
		hideClass('eve_redis_connection',hide);
		hideSelect('eve_redis_mode',hide);
		hideInput('eve_redis_key',hide);
	}
	function toggle_eve_log_alerts() {
		var hide = ! ($('#eve_log_alerts').prop('checked') && $('#enable_eve_log').prop('checked'));
		hideSelect('eve_log_alerts_payload',hide);
		hideClass('eve_log_alerts_details',hide);
	}

	function toggle_eve_log_alerts_xff() {
		var hide = ! ($('#eve_log_alerts_xff').prop('checked') && $('#eve_log_alerts').prop('checked') && $('#enable_eve_log').prop('checked'));
		hideSelect('eve_log_alerts_xff_mode',hide);
		hideSelect('eve_log_alerts_xff_deployment',hide);
		hideInput('eve_log_alerts_xff_header', hide);
	}

	function toggle_eve_log_stats() {
		var hide = ! ($('#eve_log_stats').prop('checked') && $('#enable_eve_log').prop('checked') && $('#enable_stats_log').prop('checked'));
		hideClass('eve_log_stats_details',hide);
	}

	function toggle_eve_log_http() {
		var disable = ! $('#eve_log_http').prop('checked');
		disableInput('eve_log_http_extended',disable);
		toggle_eve_log_http_extended();
	}

	function toggle_eve_log_tls() {
		var disable = ! $('#eve_log_tls').prop('checked');
		disableInput('eve_log_tls_extended',disable);
	}

	function toggle_eve_log_dhcp() {
		var disable = ! $('#eve_log_dhcp').prop('checked');
		disableInput('eve_log_dhcp_extended',disable);
	}

	function toggle_eve_log_smtp() {
		var disable = ! $('#eve_log_smtp').prop('checked');
		disableInput('eve_log_smtp_extended',disable);
		toggle_eve_log_smtp_extended();
	}

	function toggle_eve_log_files() {
		var hide = ! ($('#eve_log_files').prop('checked') && $('#enable_eve_log').prop('checked'));
		hideCheckbox('eve_log_files_magic',hide);
		hideSelect('eve_log_files_hash',hide);
	}

	function toggle_eve_log_http_extended() {
		var hide = ! ($('#eve_log_http_extended').prop('checked') && $('#enable_eve_log').prop('checked') && $('#eve_log_http').prop('checked'));
		hideSelect('eve_log_http_extended_headers\\[\\]',hide);
	}

	function toggle_eve_log_smtp_extended() {
		var hide = ! ($('#eve_log_smtp_extended').prop('checked') && $('#enable_eve_log').prop('checked') && $('#eve_log_smtp').prop('checked'));
		hideSelect('eve_log_smtp_extended_fields\\[\\]',hide);
	}

	function enable_change() {
		var disable = ! $('#enable').prop('checked');

		disableInput('alertsystemlog', disable);
		disableInput('alertsystemlog_facility', disable);
		disableInput('alertsystemlog_priority', disable);
		disableInput('blockoffenders', disable);
		//disableInput('ips_mode', disable);
		disableInput('blockoffenderskill', disable);
		disableInput('block_drops_only', disable);
		//disableInput('blockoffendersip', disable);
		disableInput('performance', disable);
		//disableInput('max_pending_packets', disable);
		//disableInput('detect_eng_profile', disable);
		//disableInput('inspect_recursion_limit', disable);
		//disableInput('mpm_algo', disable);
		//disableInput('sgh_mpm_context', disable);
		disableInput('delayed_detect', disable);
		disableInput('intf_promisc_mode', disable);
		disableInput('intf_snaplen', disable);
		disableInput('fpm_split_any_any', disable);
		disableInput('fpm_search_optimize', disable);
		disableInput('fpm_no_stream_inserts', disable);
		disableInput('cksumcheck', disable);
		//disableInput('externallistname', disable);
		//disableInput('homelistname', disable);
		//disableInput('suppresslistname', disable);
		disableInput('btnHomeNet', disable);
		disableInput('btnExternalNet', disable);
		disableInput('btnSuppressList', disable);
		//disableInput('passlistname', disable);
		disableInput('btnPasslist', disable);
		//disableInput('configpassthru', disable);
		disableInput('enable_stats_log', disable);
		disableInput('stats_upd_interval', disable);
		disableInput('append_stats_log', disable);
		disableInput('enable_http_log', disable);
		disableInput('append_http_log', disable);
		disableInput('http_log_extended', disable);
		disableInput('enable_tls_log', disable);
		disableInput('enable_tls_store', disable);
		disableInput('tls_log_extended', disable);
		disableInput('enable_json_file_log', disable);
		disableInput('append_json_file_log', disable);
		disableInput('enable_tracked_files_magic', disable);
		disableInput('tracked_files_hash', disable);
		disableInput('enable_file_store', disable);
		disableInput('enable_pcap_log', disable);
		//disableInput('max_pcap_log_size', disable);
		//disableInput('max_pcap_log_files', disable);
		disableInput('enable_eve_log', disable);
		disableInput('eve_output_type', disable);
		disableInput('eve_systemlog_facility', disable);
		disableInput('eve_systemlog_priority', disable);
		disableInput('eve_redis_mode', disable);
		disableInput('eve_redis_key', disable);
		disableInput('eve_redis_server', disable);
		disableInput('eve_redis_port', disable);
		disableInput('eve_log_info', disable);
		disableInput('eve_log_alerts', disable);
		disableInput('eve_log_alerts_payload', disable);
		disableInput('eve_log_http', disable);
		disableInput('eve_log_dns', disable);
		disableInput('eve_log_nfs', disable);
		disableInput('eve_log_smb', disable);
		disableInput('eve_log_krb5', disable);
		disableInput('eve_log_ikev2', disable);
		disableInput('eve_log_tftp', disable);
		disableInput('eve_log_tls', disable);
		disableInput('eve_log_files', disable);
		disableInput('eve_log_dhcp', disable);
		disableInput('eve_log_ssh', disable);
		disableInput('eve_log_smtp', disable);
		disableInput('eve_log_flow', disable);
		disableInput('eve_log_netflow', disable);
		disableInput('eve_log_drop', disable);
		disableInput('eve_log_alerts_packet',disable)
		disableInput('eve_log_alerts_payload',disable);
		disableInput('eve_log_alerts_http',disable);
		disableInput('eve_log_alerts_xff',disable);
		disableInput('eve_log_alerts_xff_mode',disable);
		disableInput('eve_log_alerts_xff_deployment',disable);
		disableInput('eve_log_alerts_xff_header',disable);
		disableInput('eve_log_files_magic',disable);
		disableInput('eve_log_files_hash',disable);

		var disable_http = ! $('#eve_log_http').prop('checked');
		disableInput('eve_log_http_extended',disable||disable_http);

		var disable_tls = ! $('#eve_log_tls').prop('checked');
		disableInput('eve_log_tls_extended',disable||disable_tls);

		var disable_dhcp = ! $('#eve_log_dhcp').prop('checked');
		disableInput('eve_log_dhcp_extended',disable||disable_dhcp);

		var disable_smtp = ! $('#eve_log_smtp').prop('checked');
		disableInput('eve_log_smtp_extended',disable||disable_smtp);

		var disable_stats = ! $('#enable_stats_log').prop('checked');
		disableInput('eve_log_stats',disable||disable_stats);

		disableInput('eve_log_stats_totals',disable);
		disableInput('eve_log_stats_deltas',disable);
		disableInput('eve_log_stats_threads',disable);

		//disableInput('eve_log_http_extended_headers\\[\\]',disable);
		//disableInput('eve_log_smtp_extended_fields\\[\\]',disable);

	}

	// Call the list viewing page and write what it returns to the modal text area
	function getListContents(listName, listType, ctrlID) {
		var ajaxRequest;

		ajaxRequest = $.ajax({
			url: "/firewallapp/firewallapp_list_view.php",
			type: "post",
			data: { ajax: 	"ajax",
			        wlist: 	listName,
					type: 	listType,
					id: 	"<?=$id?>"
			}
		});

		// Display the results of the above ajax call
		ajaxRequest.done(function (response, textStatus, jqXHR) {
			// Write the list contents to the text control
			$('#' + ctrlID).text(response);
			$('#' + ctrlID).attr('readonly', true);
		});
	}

	// ---------- Event triggers fired after the VIEW LIST modals are shown -----------------------
	$('#homenet').on('shown.bs.modal', function() {
		getListContents($('#homelistname option:selected' ).text(), 'homenet', 'homenet_text');
	});

	$('#externalnet').on('shown.bs.modal', function() {
		getListContents($('#externallistname option:selected' ).text(), 'externalnet', 'externalnet_text');
	});

	$('#passlist').on('shown.bs.modal', function() {
		getListContents($('#passlistname option:selected' ).text(), 'passlist', 'passlist_text');
	});

	$('#suppresslist').on('shown.bs.modal', function() {
		getListContents($('#suppresslistname option:selected').text(), 'suppress', 'suppresslist_text');
	});

	// ---------- Click checkbox handlers ---------------------------------------------------------

	/* When form control id is clicked, disable/enable it's associated form controls */

	$('#enable').click(function() {
		enable_change();
	});

	$('#alertsystemlog').click(function() {
		toggle_system_log();
	});

	$('#enable_stats_log').click(function() {
		toggle_stats_log();
	});

	$('#enable_http_log').click(function() {
		toggle_http_log();
	});

	$('#enable_tls_log').click(function() {
		toggle_tls_log();
	});

	$('#enable_json_file_log').click(function() {
		toggle_json_file_log();
	});

	$('#enable_pcap_log').click(function() {
		toggle_pcap_log();
	});

	$('#enable_eve_log').click(function() {
		toggle_eve_log();
		toggle_eve_redis();
		toggle_eve_syslog();
		toggle_eve_log_alerts();
		toggle_eve_log_alerts_xff();
		toggle_eve_log_stats();
		toggle_eve_log_http_extended();
		toggle_eve_log_smtp_extended();
	});

	$('#eve_output_type').change(function() {
		toggle_eve_redis();
		toggle_eve_syslog();
	});

	$('#eve_log_alerts').click(function() {
		toggle_eve_log_alerts();
	});

	$('#eve_log_alerts_xff').click(function() {
		toggle_eve_log_alerts_xff();
	});

	$('#eve_log_stats').click(function() {
		toggle_eve_log_stats();
	});

	$('#eve_log_http').click(function() {
		toggle_eve_log_http();
	});

	$('#eve_log_tls').click(function() {
		toggle_eve_log_tls();
	});

	$('#eve_log_dhcp').click(function() {
		toggle_eve_log_dhcp();
	});

	$('#eve_log_smtp').click(function() {
		toggle_eve_log_smtp();
	});

	$('#eve_log_files').click(function() {
		toggle_eve_log_files();
	});

	$('#blockoffenders').click(function() {
		enable_blockoffenders();
	});

	$('#eve_log_http_extended').click(function(){
		toggle_eve_log_http_extended();
	});

	$('#eve_log_smtp_extended').click(function(){
		toggle_eve_log_smtp_extended();
	});

	$('#ips_mode').on('change', function() {
		if ($('#ips_mode').val() == 'ips_mode_inline') {
			hideCheckbox('blockoffenderskill', true);
			hideCheckbox('block_drops_only', true);
			hideSelect('blockoffendersip', true);
			hideCheckbox('performance_extends', true);
			hideClass('passlist', true);
			hideInput('intf_snaplen', true);
			$('#eve_log_drop').parent().show();
			$('#ips_warn_dlg').modal('show');
			if ($("#mixed_mode").is(':checked')) {
				$("#mixed_mode").click();
			}
			hideCheckbox('mixed_mode', true);
		}
		else {
			hideCheckbox('blockoffenderskill', false);
			hideCheckbox('block_drops_only', false);
			hideSelect('blockoffendersip', false);
			hideInput('intf_snaplen', false);
			hideClass('passlist', false);
			hideCheckbox('performance_extends', false);
			$('#eve_log_drop').parent().hide();
			$('#ips_warn_dlg').modal('hide');
			hideCheckbox('mixed_mode', false);
		}
	});

	// ---------- On initial page load ------------------------------------------------------------
	enable_change();
	enable_blockoffenders();
	toggle_system_log();
	toggle_stats_log();
	toggle_http_log();
	toggle_tls_log();
	toggle_json_file_log();
	toggle_pcap_log();
	toggle_eve_log();
	toggle_eve_redis();
	toggle_eve_syslog();
	toggle_eve_log_alerts();
	toggle_eve_log_alerts_xff();
	toggle_eve_log_stats();
	toggle_eve_log_http();
	toggle_eve_log_smtp();
	toggle_eve_log_tls();
	toggle_eve_log_dhcp();
	toggle_eve_log_files();
	<?php if ($pconfig['ips_mode'] != 'ips_mode_inline'): ?>
		hideCheckbox('performance_extends', false);
	<?php else: ?>
		hideCheckbox('performance_extends', true);
		hideCheckbox('mixed_mode', true);
	<?php endif ?>
	<?php if ($pconfig['ips_mode'] == 'ips_mode_mix'): ?>
		$("#ips_mode").val("ips_mode_legacy");
		if (!$("#mixed_mode").is(':checked')) {
			$("#mixed_mode").click();
		}
	<?php endif ?>

	<?php if ($pconfig['ips_mode'] == 'ips_mode_inline'): ?>
		hideCheckbox('mixed_mode', true);
	<?php endif ?>

});
//]]>
</script>

<?php include("foot.inc"); ?>

<script>
$(document).ready(function() {
        $(".fapp_enable_bt_switch").bootstrapSwitch('size', 'mini');
        $(".fapp_enable_bt_switch").bootstrapSwitch('state', <?=$pconfig['enable'] == 'on' ? 'true' : 'false';?>);
});
</script>
