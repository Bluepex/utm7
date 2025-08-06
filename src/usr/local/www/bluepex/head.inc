<?php
/*
 * head.inc
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2019 Rubicon Communications, LLC (Netgate)
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

require_once("globals.inc");
require_once("functions.inc");
require_once("shortcuts.inc");
require_once("service-utils.inc");
require_once('notices.inc');
require_once('pkg-utils.inc');
require_once("bluepex/bp_webservice.inc");

if ($_SESSION['implementation_status'] == "new" || $_SESSION['implementation_status'] == "renovation") {
	unset($_SESSION['implementation_status']);
	include_once("auth.inc");
	include_once("priv.inc");
	if (!function_exists('platform_booting')) {
		require_once('globals.inc');
	}
	require_once("guiconfig.inc");
	require_once('pfsense-utils.inc');
	display_login_form($statusAlterarSenha=true);
	exit;		
}

if ($_SESSION['implementation_status'] == "new_pass_confirmation") {
	unset($_SESSION['implementation_status']);
	include_once("auth.inc");
	include_once("priv.inc");
	if (!function_exists('platform_booting')) {
		require_once('globals.inc');
	}
	require_once("guiconfig.inc");
	require_once('pfsense-utils.inc');
	display_login_form();
	exit;
}

redirect_licensed_area();

checkFirewallAppService();

bp_restrict_acesses_page();

header('Content-Type: text/html; charset=utf-8');

$pagetitle = gentitle($pgtitle);
$system_url = $config['system']['hostname'] . "." . $config['system']['domain'];

if ($user_settings['webgui']['pagenamefirst']) {
	$tabtitle = $pagetitle . " - " . htmlspecialchars($system_url);
} else {
	$tabtitle = htmlspecialchars($system_url) . " - " . $pagetitle;
}

$cssfile = "/css/BluePexUTM.css";

if (isset($user_settings['webgui']['webguicss'])) {
	if (file_exists("/usr/local/www/css/" . $user_settings['webgui']['webguicss'])) {
		$cssfile = "/css/" . $user_settings['webgui']['webguicss'];
	}
}

// set default columns to two if unset
if (!isset($config['system']['webgui']['dashboardcolumns'])) {
	$config['system']['webgui']['dashboardcolumns'] = 2;
}

$pages_for_print = ['/status_monitoring.php', '/active_protection/request_api_equipment.php'];
$enable_print_page = false;

foreach (['DOCUMENT_URI','REQUEST_URI','SCRIPT_NAME','SCRIPT_FILENAME','PHP_SELF'] as $tags_target) {
	$enable_print_page = in_array($_SERVER[$tags_target], $pages_for_print);
}

if ($_GET['ajax_requests_icons_menubar']) {
	$returnTable = "";
	if (!$hide_service_status && !empty($shortcuts[$shortcut_section]['service']) && isAllowedPage('status_services.php')) {
		$ssvc = array();
		switch ($shortcut_section) {
			case "openvpn":
				$ssvc = find_service_by_openvpn_vpnid($vpnid);
				break;
			case "captiveportal":
				$ssvc = find_service_by_cp_zone($cpzone);
				break;
			default:
				$ssvc = find_service_by_name($shortcuts[$shortcut_section]['service']);
		}
		if (!empty($ssvc)) {
			$returnTable .= '<li>'. get_service_control_links($ssvc, false). '</li>';
		}
	}
	if (('' != ($link = get_shortcut_main_link($shortcut_section, false))) && (isAllowedPage($shortcuts[$shortcut_section]['main']))) {
		$returnTable .= '<li>' . $link . '</li>';
	}
	if (('' != ($link = get_shortcut_status_link($shortcut_section, false))) && (isAllowedPage($shortcuts[$shortcut_section]['status']))) {
		$returnTable .= '<li>' . $link . '</li>';
	}
	if (('' != ($link = get_shortcut_log_link($shortcut_section, false))) && (isAllowedPage($shortcuts[$shortcut_section]['log']))) {
		$returnTable .= '<li>' . $link . '</li>';
	}

	if ($enable_print_page) {
		$returnTable .= "<li><a onclick='window.print();' title='" . gettext('Print the page in report format') . "'><i class='fa fa-print'></i></a></li>";
	}

	echo $returnTable;
	unset($_GET);
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="/vendor/bootstrap4/css/bootstrap.min.css?v=<?=filemtime('/usr/local/www/vendor/bootstrap4/css/bootstrap.min.css')?>">
	<link rel="stylesheet" href="/vendor/font-awesome/css/font-awesome.min.css?v=<?=filemtime('/usr/local/www/vendor/font-awesome/css/font-awesome.min.css')?>">
	  <link href="/vendor/font-awesome/css/all.min.css" rel="stylesheet">
	<link rel="stylesheet" href="/css/BluePexUTM.css">
	<link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap-switch.min.css">
	<style>
	/*CSS tweaks on NAV and Side Menu when minified*/

	@media screen and (max-width: 1099px) {
		ul#menu-vert2 {
			word-break: unset;
		}
	}
	@media screen and (min-width: 993px) {
		.container-fluid {
			margin-top: unset;
		}
	}
	@media screen and (max-width: 992px) {
		.container-fluid {
			margin-top: 100px;
		}
		.navbar-expand-lg {
			height: unset;
		}
		ul#menu-vert2 {
			word-break: break-word;
		}
	}
	@media screen and (max-width: 961px) {
		.container-fluid {
			margin-top: unset;
		}
		body {
			margin-top: unset;
		}
		#wrapper {
			padding-left: unset;
		}
	}
	@media screen and (max-width: 991px) {
		.float-right.col-xl-7.col-sm-12 {
			margin-top: unset;
		}
		#menu-vert2 .nav-item.active > ul {
			display: contents;
		}
		#menu-vert2 .nav-item.active > ul > li {
			background-color: #06365d;
		}
		#menu-vert2 .nav-item > ul li a {
			padding: unset;
			margin-left: 2%;
		}
		#menu-vert2 .nav-item.active > ul > li:hover {
			background-color: #007bff;
		}
	}
	@media(min-width:993px) {
		body { 
			margin-top: 65px !important; 
		}
	}
	@media (min-width: 992px) {
		.navbar.navbar-fixed-top.navbar-inverse { 
			display: block !important;
		}
		.float-right.col-xl-7.col-sm-12 {
			width: 50% !important;
		}
		.navbar-header.col-xl-5.col-sm-12 {
			width: 50% !important;
			height: 100% !important;
			display: grid;
		}
	}
	@media(max-width:992px) {
		body { 
			margin-top: 100px !important; 
		}
	}
	@media(max-width:961px) {
		#wrapper {
    		padding-left: unset !important;
		}
		body { 
			margin-top: 0px !important; 
		}
	}
	@media (max-width: 767px) {
		.breadcrumb {
			font-size: 14px;
			padding: 15px;
			width: 100%;
		}
	}
	@media (min-width: 576px) {
		.col-sm-2 {
			-webkit-box-flex: 0;
			-ms-flex: 0 0 16.666667%;
			flex: 0 0 16.666667%;
			max-width: 100%;
		}
	}
	
	.popover-content > table > thead {
		border-bottom-width: 2px !important;
		background: #108ad0 !important;
		color: #fff !important;
	}

	.popover-content > table > thead > tr > th {
		padding: 5px !important;
		text-align: center !important;
	}

	.popover-content > table > tbody > tr > td {
		padding: 5px !important;
		text-align: center !important;
	}

	select.form-control:not([size]):not([multiple]) {
		height:28px!important;
	}

	ul.context-links li a {
		color: #007dc5 !important;
	}

	</style>
	<?php if ($enable_print_page): ?>
	<link rel="stylesheet" href="/css/BluePexUTM-printer.css?v=<?=filemtime('/usr/local/www/css/BluePexUTM-printer.css')?>" media="print">
	<?php endif; ?>

	<title><?=$tabtitle?></title>
	<script type="text/javascript">
	//<![CDATA[
	var events = events || [];
	var newSeperator = false;
	//]]>
	</script>
</head>

<?php

/* Determine automated help URL. Should output the page name and parameters
   separately */
$uri_split = "";
preg_match("/\/(.*)\?(.*)/", $_SERVER["REQUEST_URI"], $uri_split);

/* If there was no match, there were no parameters, just grab the filename
   Otherwise, use the matched filename from above. */
if (empty($uri_split[0])) {
	$pagename = ltrim($_SERVER["REQUEST_URI"], '/');
} else {
	$pagename = $uri_split[1];
}

/* If the page name is still empty, the user must have requested / (index.php) */
if (empty($pagename)) {
	$pagename = "index.php";
}

/* If the filename is pkg_edit.php or wizard.php, reparse looking
	for the .xml filename */
if (($pagename == "pkg.php") || ($pagename == "pkg_edit.php") || ($pagename == "wizard.php")) {
	$param_split = explode('&', $uri_split[2]);
	foreach ($param_split as $param) {
		if (substr($param, 0, 4) == "xml=") {
			$xmlfile = explode('=', $param);
			$pagename = $xmlfile[1];
		}
	}
} else if ($pagename == "status_logs.php") {
	$param_split = explode('&', $uri_split[2]);
	foreach ($param_split as $param) {
		if (substr($param, 0, 8) == "logfile=") {
			$logtype = explode('=', $param);
			$pagename .= '-' . $logtype[1];
		}
	}
}

// Build the full help URL.
$helpurl .= "{$g['help_base_url']}?page={$pagename}";

/*
 * Read files from $g['ext_menu_path']/*.xml and fill an array with menu info
 */
function read_ext_menu_path_data() {
	global $g;

	$result = array();

	if (!is_dir($g['ext_menu_path'])) {
		return $result;
	}

	foreach (glob("{$g['ext_menu_path']}/*.xml") as $menu_xml) {
		$xml_data = parse_xml_config_pkg($menu_xml, "packagegui");
		if (empty($xml_data['menu'])) {
			continue;
		}
		foreach ($xml_data['menu'] as $menu) {
			$result[] = $menu;
		}
	}

	return $result;
}

// Create a menu entry of any installed packages in the specified category
// (Now reads the menu information from $config['installedpackages']['menu'] only)
function return_ext_menu($section) {
	global $config, $ext_menu_path_data;

	$htmltext = "";
	$extarray = array();
	$ext_menu_entries = array();

	if ((!empty($config['installedpackages']['package'])) && (!empty($config['installedpackages']['menu']))) {
		foreach ($config['installedpackages']['menu'] as $menu) {
			if ($menu['name'] != "AutoConfigBackup") { // AutoConfigBackup was moved to a built-in function
	//			print('Name: ' . $menu['name'] . ', Pkg category: ' . $menu['category'] . ', Section: ' . $section . '<br />');
				if ($menu['section'] == $section) {
					$ext_menu_entries[] = $menu;
				}
			}

			//print('Name: ' . $menu['name'] . ', Pkg category: ' . $menu['category'] . ', Section: ' . $section . '<br />');
		}
	}

	foreach ($ext_menu_path_data as $menu) {
		if ($menu['section'] == $section) {
			$ext_menu_entries[] = $menu;
		}
	}

	foreach ($ext_menu_entries as $menu) {
		if ((!empty($menu['url'])) && ($menu['url'] != "")) {
			$test_url = $menu['url'];
			$addresswithport = getenv("HTTP_HOST");
			$colonpos = strpos($addresswithport, ":");

			if ($colonpos !== false) {
				//my url is actually just the IP address of the pfsense box
				$myurl = substr($addresswithport, 0, $colonpos);
			} else {
				$myurl = $addresswithport;
			}
			$description = str_replace($myurl, $myurl, $menu['url']);
			if(strpos($menu['url'], "pkg_edit.php?xml") !== false){
				$description = str_replace($myurl, $myurl, $menu['url']);
			}
		} else {
			$description = '/pkg.php?xml=' . $menu['configfile'];
			$test_url=$description;
		}

		if (isAllowedPage($test_url)) {
			if (!empty($menu['pkg_intl_translate'])) {
				$extarray[] = array(dgettext($menu['pkg_intl_translate'], $menu['name']), $description);
			} else {
				$extarray[] = array($menu['name'], $description);
			}
		}
	}

	return $extarray;
}

function output_menu($arrayitem, $target = null, $section = "") {
	$output = "";

	foreach ($arrayitem as $item) {

		/* If the user has access to help pages, also show the full help menu. See #5909 */
		if (isAllowedPage($item[1]) || $item[1] == "/index.php?logout" ||
		    (($section == "Help") && isAllowedPage("help.php")) ||
		    (substr($item[1], 0, 8) == "https://")) {
			$attr = sprintf("href=\"%s\"", htmlentities($item[1]));

			if ($target) {
				$attr .= sprintf(" target=\"%s\"", htmlentities($target));
			}

			$class = "navlnk";

			if ($item['class']) {
				$class .= " {$item['class']}";
			}

			$attr .= sprintf(" class=\"%s\"", htmlentities($class));

			if ($item['style']) {
				$attr .= sprintf(" style=\"%s\"", htmlentities($item['style']));
			}


			if ($item[0] == '-DIVIDER-') {
				$output .= ' <li class="divider"></li>';
			} else {
				$output .= "<li>". sprintf("<a %s %s usepost>%s</a>", $attr, ($item[1] == "/index.php?logout") ? "usepost":"",$item[0]) . "</li>\n";
			}
		}
	}

	return $output;
}

$ext_menu_path_data = read_ext_menu_path_data();

// System
$system_menu = array();
$system_menu[] = array(gettext("Logout") . " (" . $_SESSION['Username'] . ")", "/index.php?logout");
$system_menu[] = array(gettext("Advanced"), "/system_advanced_admin.php");
$system_menu[] = array(gettext("Update"), "/pkg_mgr_install.php?id=firmware");
$system_menu[] = array(gettext("General Setup"), "/system.php");
$system_menu[] = array(gettext("High Avail. Sync"), "/system_hasync.php");
#$system_menu[] = array(gettext("Package Manager"), "/pkg_mgr_installed.php");
$system_menu[] = array(gettext("Setup Wizard"), "/wizard.php?xml=setup_wizard.xml");
$system_menu[] = array(gettext("Routing"), "/system_gateways.php");
$system_menu[] = array(gettext("Cert. Manager"), "/system_camanager.php");
if (!isAllowedPage("system_usermanager.php")) {
	$system_menu[] = array(gettext("User Manager"), "/system_usermanager_passwordmg.php");
} else {
	$system_menu[] = array(gettext("User Manager"), "/system_usermanager.php");
}

if ($user_settings['customsettings'] && isAllowedPage("system_user_settings.php")) {
	$system_menu[] = array(gettext("User Settings"), "/system_user_settings.php");
}

$system_menu = msort(array_merge($system_menu, return_ext_menu("System")), 0);

// Interfaces
// NOTE:
// Now that menus are sorted, adding a DIVIDER must be done after the sorting so an array is formed of the
// items above the divider and another for below it. These are then sorted and combined with the divider
$interfaces_menu = array();
$interfaces_top = array();
$interfaces_bottom = array();

if (!isset($config['system']['webgui']['noassigninterfaces'])) {
	$interfaces_top[] = array(gettext("Assignments"), "/interfaces_assign.php");
	$div = true;
}

$platform = system_identify_specific_platform();

if ($platform['name'] == "uFW") {
	$interfaces_top[] = array(gettext("Switches"), "/switch_system.php");
}

$opts = get_configured_interface_with_descr(true);

foreach ($opts as $oif => $odescr) {
	if (!isset($config['interfaces'][$oif]['ovpn'])) {
		$interfaces_bottom[] = array(htmlspecialchars($odescr), "/interfaces.php?if={$oif}");
	}
}

$interfaces_bottom = array_merge($interfaces_bottom, return_ext_menu("Interfaces"));

if ($user_settings['webgui']['interfacessort']) {
	$interfaces_bottom = msort($interfaces_bottom, 0);
}

// Combine the top section, the divider and the bottom section of this menu
$interfaces_menu = array_merge($interfaces_top, [array(0 => "-DIVIDER-")], $interfaces_bottom);

// Firewall
$firewall_menu = array();
$firewall_menu[] = array(gettext("Objects"), "/firewall_aliases.php");
$firewall_menu[] = array(gettext("NAT"), "/firewall_nat.php");
$firewall_menu[] = array(gettext("Rules"), "/firewall_rules.php");
$firewall_menu[] = array(gettext("Schedules"), "/firewall_schedule.php");
$firewall_menu[] = array(gettext("Traffic Shaper"), "/firewall_shaper.php");
$firewall_menu[] = array(gettext("Virtual IPs"), "/firewall_virtual_ip.php");
$firewall_menu = msort(array_merge($firewall_menu, return_ext_menu("Firewall")), 0);

// Services
$services_menu = array();
$services_menu[] = array(gettext("Captive Portal"), "/services_captiveportal.php");
$services_menu[] = array(gettext("DNS Forwarder"), "/services_dnsmasq.php");
$services_menu[] = array(gettext("DNS Resolver"), "/services_unbound.php");
$services_menu[] = array(gettext("DHCP Relay"), "/services_dhcp_relay.php");
$services_menu[] = array(gettext("DHCPv6 Relay"), "/services_dhcpv6_relay.php");
$services_menu[] = array(gettext("SSL Inspect"), "/ssl_inspect/ssl_inspect.php");
$services_menu[] = array(gettext("DNS Protection"), "/active_protection/acp_dnsprotection.php");

if ($g['services_dhcp_server_enable']) {
	$services_menu[] = array(gettext("DHCP Server"), "/services_dhcp.php");
	$services_menu[] = array(htmlspecialchars(gettext("DHCPv6 Server & RA")), "/services_dhcpv6.php");
}

$services_menu[] = array(gettext("Dynamic DNS"), "/services_dyndns.php");
$services_menu[] = array(gettext("IGMP Proxy"), "/services_igmpproxy.php");
$services_menu[] = array(gettext("Load Balancer"), "/load_balancer_pool.php");
$services_menu[] = array(gettext("NTP"), "/services_ntpd.php");
#$services_menu[] = array(gettext("PPPoE Server"), "/services_pppoe.php");
$services_menu[] = array(gettext("SNMP"), "/services_snmp.php");

//if (count($config['interfaces']) > 1) {
	/* no use for UPnP in single-interface deployments
	remove to reduce user confusion
	*/
	//$services_menu[] = array(gettext("UPnP &amp; NAT-PMP"), "/pkg_edit.php?xml=miniupnpd.xml");
//}

$services_menu[] = array(gettext("Wake-on-LAN"), "/services_wol.php");
$services_menu = msort(array_merge($services_menu, return_ext_menu("Services")), 0);

// VPN
$vpn_menu = array();
$vpn_menu[] = array(gettext("IPsec"), "/vpn_ipsec.php");
$vpn_menu[] = array(gettext("OpenVPN"), "/vpn_openvpn_server.php");
//$vpn_menu[] = array(gettext("PPTP"), "/vpn_pptp.php");
$vpn_menu[] = array(gettext("L2TP"), "/vpn_l2tp.php");
$vpn_menu = msort(array_merge($vpn_menu, return_ext_menu("VPN")), 0);

// Status
$status_menu = array();
$status_menu[] = array(gettext("Captive Portal"), "/status_captiveportal.php");
$status_menu[] = array(gettext("CARP (failover)"), "/status_carp.php");
$status_menu[] = array(gettext("Dashboard"), "/index.php");
$status_menu[] = array(gettext("Gateways"), "/status_gateways.php");
$status_menu[] = array(gettext("DHCP Leases"), "/status_dhcp_leases.php");
$status_menu[] = array(gettext("DHCPv6 Leases"), "/status_dhcpv6_leases.php");
$status_menu[] = array(gettext("DNS Resolver"), "/status_unbound.php");
$status_menu[] = array(gettext("Filter Reload"), "/status_filter_reload.php?user=true");
$status_menu[] = array(gettext("Interfaces"), "/status_interfaces.php");
$status_menu[] = array(gettext("IPsec"), "/status_ipsec.php");
$status_menu[] = array(gettext("Load Balancer"), "/status_lb_pool.php");
$status_menu[] = array(gettext("NTP"), "/status_ntpd.php");
$status_menu[] = array(gettext("OpenVPN"), "/status_openvpn.php");
$status_menu[] = array(gettext("Package Logs"), "/status_pkglogs.php");
$status_menu[] = array(gettext("Queues"), "/status_queues.php");
$status_menu[] = array(gettext("Services"), "/status_services.php");
$status_menu[] = array(gettext("System Logs"), "/status_logs.php");
$status_menu[] = array(gettext("Traffic Graph"), "/status_graph.php");

//if (count($config['interfaces']) > 1) {
	//$status_menu[] = array(gettext("UPnP &amp; NAT-PMP"), "/status_upnp.php");
//}

$wifdescrs = array();
$ifentries = get_configured_interface_with_descr();
foreach ($ifentries as $ent => $entdesc) {
	if (is_array($config['interfaces'][$ent]['wireless']) &&
	    preg_match($g['wireless_regex'], $config['interfaces'][$ent]['if'])) {
		$wifdescrs[$ent] = $entdesc;
	}
}

if (count($wifdescrs) > 0) {
	$status_menu[] = array(gettext("Wireless"), "/status_wireless.php");
}

$status_menu = msort(array_merge($status_menu, return_ext_menu("Status")), 0);

// Diagnostics
$diagnostics_menu = array();
$diagnostics_menu[] = array(gettext("ARP Table"), "/diag_arp.php");
$diagnostics_menu[] = array(gettext("Authentication"), "/diag_authentication.php");
$diagnostics_menu[] = array(htmlspecialchars(gettext("Backup & Restore")), "/diag_backup.php");
// $diagnostics_menu[] = array(gettext("Command Prompt"), "/diag_command.php");
$diagnostics_menu[] = array(gettext("DNS Lookup"), "/diag_dns.php");
// $diagnostics_menu[] = array(gettext("Edit File"), "/diag_edit.php");
$diagnostics_menu[] = array(gettext("Factory Defaults"), "/diag_defaults.php");

if (file_exists("/var/run/gmirror_active")) {
	$diagnostics_menu[] = array(gettext("GEOM Mirrors"), "/diag_gmirror.php");
}

$diagnostics_menu[] = array(gettext("Halt System"), "/diag_halt.php");
$diagnostics_menu[] = array(gettext("Limiter Info"), "/diag_limiter_info.php");
$diagnostics_menu[] = array(gettext("NDP Table"), "/diag_ndp.php");
$diagnostics_menu[] = array(gettext("Tables"), "/diag_tables.php");
$diagnostics_menu[] = array(gettext("Ping"), "/diag_ping.php");
$diagnostics_menu[] = array(gettext("Test Port"), "/diag_testport.php");
$diagnostics_menu[] = array(gettext("Firewall Info"), "/diag_pf_info.php");
$diagnostics_menu[] = array(gettext("Firewall Top"), "/diag_pftop.php");
$diagnostics_menu[] = array(gettext("Reboot"), "/diag_reboot.php");
$diagnostics_menu[] = array(gettext("Routes"), "/diag_routes.php");
$diagnostics_menu[] = array(gettext("S.M.A.R.T. Status"), "/diag_smart.php");
$diagnostics_menu[] = array(gettext("Sockets"), "/diag_sockets.php");
$diagnostics_menu[] = array(gettext("States"), "/diag_dump_states.php");
$diagnostics_menu[] = array(gettext("States Summary"), "/diag_states_summary.php");
$diagnostics_menu[] = array(gettext("System Activity"), "/diag_system_activity.php");
$diagnostics_menu[] = array(gettext("Traceroute"), "/diag_traceroute.php");
$diagnostics_menu[] = array(gettext("Packet Capture"), "/diag_packet_capture.php");
$diagnostics_menu[] = array(gettext("BluePex LAB"), "/bluepex_lab.php");
$diagnostics_menu[] = array(gettext("Monitor Services"), "/status_bp_services.php");

$diagnostics_menu = msort(array_merge($diagnostics_menu, return_ext_menu("Diagnostics")), 0);

$gold_menu = array();
$gold_menu[] = array(gettext("pfSense Gold"), "https://www.pfsense.org/gold");
$gold_menu = msort(array_merge($gold_menu, return_ext_menu("Gold")), 0);

if (!$g['disablehelpmenu']) {
	$help_menu = array();
	$help_menu[] = array(gettext("About this Page"), $helpurl);
	if ($g['product_name'] == "pfSense") {
		$help_menu[] = array(gettext("Bug Database"), "https://www.pfsense.org/j.php?jumpto=redmine");
	}

	$help_menu[] = array(gettext("User Forum"), "https://www.pfsense.org/j.php?jumpto=forum");
	$help_menu[] = array(gettext("Documentation"), "https://www.pfsense.org/j.php?jumpto=doc");
	$help_menu[] = array(gettext("Paid Support"), "https://www.netgate.com/support");
	$help_menu[] = array(gettext("pfSense Book"), "https://www.pfsense.org/j.php?jumpto=book");
	$help_menu[] = array(gettext("FreeBSD Handbook"), "https://www.pfsense.org/j.php?jumpto=fbsdhandbook");
	$help_menu = msort(array_merge($help_menu, return_ext_menu("Help")), 0);
}

$menuclass = "static";

if ($user_settings['webgui']['webguifixedmenu'] == "fixed") {
	$menuclass = "fixed";
}

$numColumns = (int) $user_settings['webgui']['dashboardcolumns'];

if (($pagename === "index.php") && ($numColumns > 2)) {
	$columnsContainer = 'style="max-width: ' . 585*$numColumns . 'px;width: 100%"';
}

$display_notices = false;
$allow_clear_notices = false;

if (are_notices_pending()) {
	// Evaluate user privs to determine if notices should be displayed, and if the user can clear them.
	$user_entry = getUserEntry($_SESSION['Username']);
	if (isAdminUID($_SESSION['Username']) || userHasPrivilege($user_entry, "user-view-clear-notices") || userHasPrivilege($user_entry, "page-all")) {
		$display_notices = true;
		$allow_clear_notices = true;
	} elseif (userHasPrivilege($user_entry, "user-view-notices")) {
		$display_notices = true;
	}
}
?>
<body id="<?=$numColumns?>">
	<div id="wrapper">
		<nav class="navbar navbar-fixed-top navbar-inverse" role="navigation">
			<div class="navbar-header col-xl-5 col-sm-12">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#menu-vert2">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a id="logo" href="/index.php" style="width:0px!important;"><img src="/images/logo.png" alt="BluePexUTM" title="BluePexUTM.localdomain"/></a>
			</div>
			<div class="float-right col-xl-7 col-sm-12">
				<ul class="nav navbar-right top-nav">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-question-circle"></i> <?=gettext('Help');?> <b class="caret"></b></a>
						<ul class="dropdown-menu" style="margin-right: 80px;">
							<li><a target="_blank" href="http://atendimento.bluepex.com">Chat</a></li>
							<li><a target="_blank" href="https://www.bluepex.com.br/contato/">SAC</a></li>
							<li><a target="_blank" href="http://blog.bluepex.com"><?=gettext('News');?></a></li>
						</ul>
					</li>
					<li class="dropdown" id="menu_set_get_language">
						<?php

						function resolverNameLanguage($lang) {
							if ($lang == "pt_BR") {
								return "Português";
							} elseif ($lang == "en_US") {
								return "English";
							} else {
								return "Português";
							}
						}

						$allLanguagens = get_locale_list();

						$returnOptions .= "<a href='#' class='dropdown-toggle' data-toggle='dropdown'>";
						if (isset($_SESSION['lang']) && !empty($_SESSION['lang'])) {
							foreach($allLanguagens as $keyLanguage => $valueLanguage) {
								if ($_SESSION['lang'] == $keyLanguage) {
									$returnOptions .= "<img src='../images/{$keyLanguage}.png' style='width:18px;'/>";
								}
							}

						} elseif (isset($config['system']['language']) && !empty($config['system']['language'])) {
							foreach($allLanguagens as $keyLanguage => $valueLanguage) {
								if ($config['system']['language'] == $keyLanguage) {
									$returnOptions .= "<img src='../images/{$keyLanguage}.png' style='width:18px;'/>";
								}
							}
						} else {
							$returnOptions .= "<img src='../images/pt_BR.png' style='width:18px;'/>";
						}
						//$returnOptions .= "<b class='caret'></b>";
						$returnOptions .= "</a>";

						$returnOptions .= '<ul class="dropdown-menu">';
						if (isset($config['system']['timezone']) || isset($config['system']['timeservers']) || isset($config['system']['language'])) {
							if (isset($_SESSION['lang']) && !empty($_SESSION['lang'])) {
								$returnOptions .= "<li style='padding: 5px;' >".gettext("Language").": " .resolverNameLanguage($_SESSION['lang']). "</li>";
							} else {
								if (isset($config['system']['language']) && !empty($config['system']['language'])) {
									$returnOptions .= "<li style='padding: 5px;' >".gettext("Language").": " .resolverNameLanguage($config['system']['language']). "</li>";
								} else {
									$returnOptions .= "<li style='padding: 5px;' >".gettext("Language").": Português</li>";
								}
							}
							if (isset($config['system']['timezone']) && !empty($config['system']['timezone'])) {
								$returnOptions .= "<li style='padding: 5px;' >".gettext("Time zone").": " .$config['system']['timezone']. "</li>";
							}
							if (isset($config['system']['timeservers']) && !empty($config['system']['timeservers'])) {
								$returnOptions .= "<li style='padding: 5px;' >".gettext("Time server").": " .explode(",",$config['system']['timeservers'])[0]. "</li>";
							}
						}
						$returnOptions .= "<li style='padding: 5px;' >".gettext("Hour").": " . Date("d/m/Y H:s:i") . "</li>";
						$returnOptions .= "<li><hr style='margin:0px;padding:0px;'></li>";
						$returnOptions .= "<li><a href='/system.php'><i class='fa fa-fw fa-gear'></i>".gettext('Settings')."</a></li>";
						$returnOptions .= "</ul>";

						echo $returnOptions;
						?>
					</li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
							<i class="icon-user"></i>
							<?php $username = $_SESSION['Username']; echo $username; ?>
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu">
							<li><a href="/system_usermanager.php"><i class="fa fa-fw fa-gear"></i> <?=gettext('Settings');?></a></li>
							<li class="divider"></li>
							<li><a href="/index.php?logout" usepost><i class="fa fa-fw fa-power-off"></i> <?=gettext('Logout');?></a></li>
						</ul>
					</li>
					<li>
						<a href="/index.php?logout" class="btn" usepost>
							<i class="fa fa-sign-out" title="Deslogar"></i>
						</a>
					</li>
				</ul>
			</div>
		</nav>
	</div>
	<div class="container-fluid">
		<div class="row">
			<nav class="p-0 col-lg-1 d-md-block color-white sidebar pt-xl-0 navbar-expand-lg">
				<div id="menu-vert">
					<ul id="menu-vert2" class="nav flex-column collapse navbar-collapse mt-3 p-0">
						<li class="nav-item">
							<a class="nav-link" href="#">
								<i class="fa fa-cogs"></i><br />
								<?=gettext('System');?>
							</a>
							<ul>
								<?php foreach($system_menu as $system_menu_item) { ?>
								<li><a href="<?php echo $system_menu_item[1];?>" usepost><i class="fa fa-cogs"></i> <?php echo $system_menu_item[0];?></a></li>
								<?php } ?>
							</ul>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#">
								<i class="fa fa-exchange"></i><br />
								Interfaces
							</a>
							<ul>
								<?php foreach($interfaces_menu as $interfaces_menu_item) { ?>
									<?php if ($interfaces_menu_item[0] != "-DIVIDER-") { ?>
									<li><a href="<?php echo $interfaces_menu_item[1];?>"><i class="fa fa-exchange"></i> <?php echo $interfaces_menu_item[0];?></a></li>
									<?php } ?>
								<?php } ?>
							</ul>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#">
								<i class="fa fa-fire"></i><br />
								Firewall
							</a>
							<ul>
								<?php foreach($firewall_menu as $firewall_menu_item) { ?>
									<?php if ($firewall_menu_item[0] != "-DIVIDER-") { ?>
								<li><a href="<?php echo $firewall_menu_item[1];?>"><i class="fa fa-fire"></i> <?php echo $firewall_menu_item[0];?></a></li>
									<?php } ?>
								<?php } ?>
							</ul>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#">
								<i class="fa fa-list"></i><br />
								<?=gettext('Services');?>
							</a>
							<ul>
							<?php foreach($services_menu as $services_menu_item) { ?>
								<?php if ($services_menu_item[0] != "-DIVIDER-") { ?>
								<li><a href="<?php echo $services_menu_item[1];?>"><i class="fa fa-list"></i> <?php echo $services_menu_item[0];?></a></li>
								<?php } ?>
							<?php } ?>
							</ul>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#">
								<i class="fa fa-globe"></i><br />
								VPN
							</a>
							<ul>
							<?php foreach($vpn_menu as $vpn_menu_item) { ?>
								<?php if ($vpn_menu_item[0] != "-DIVIDER-") { ?>
								<li><a href="<?php echo $vpn_menu_item[1];?>"><i class="fa fa-globe"></i> <?php echo $vpn_menu_item[0];?></a></li>
								<?php } ?>
							<?php } ?>
							</ul>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#">
								<i class="fa fa-refresh"></i><br />
								Status
							</a>
							<ul>
							<?php foreach($status_menu as $status_menu_item) { ?>
								<?php if ($status_menu_item[0] != "-DIVIDER-") { ?>
								<li><a href="<?php echo $status_menu_item[1];?>"><i class="fa fa-refresh"></i> <?php echo $status_menu_item[0];?></a></li>
								<?php } ?>
							<?php } ?>
							</ul>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="#">
								<i class="fa fa-search"></i><br />
								<?=gettext('Diagnostics');?>
							</a>
							<ul>
							<?php foreach($diagnostics_menu as $diagnostics_menu_item) { ?>
								<?php if ($diagnostics_menu_item[0] != "-DIVIDER-") { ?>
								<li><a href="<?php echo $diagnostics_menu_item[1];?>"><i class="fa fa-search"></i> <?php echo $diagnostics_menu_item[0];?></a></li>
								<?php } ?>
							<?php } ?>
							</ul>
						</li>
					</ul>
				</div>
			</nav>
			<main role="main" class="col-lg-11 col-md-12 float-left py-2">
				<ul class="menu-horiz nav nav-pills nav-fill my-xl-2 border-0">
					<li class="nav-item">
						<a class="nav-link" target="_blank" href="https://suite.bluepex.com">Suite</a>
					</li>
					<li class="nav-item">
					</li>
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#"><?=gettext('Active Protection');?></a>
						<div class="dropdown-menu">
							<a class="dropdown-item" href="#">
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/active_protection/ap_services.php") { ?>active<?php } ?>" href="/active_protection/ap_services.php">Dashboard</a>
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/active_protection/services_acp_rules.php") { ?>active<?php } ?>" href="/active_protection/services_acp_rules.php"><?=gettext("Settings")?> - <?=gettext("Rules")?></a>
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/active_protection/services_acp_ameacas.php") { ?>active<?php } ?>" href="/active_protection/services_acp_ameacas.php"><?=gettext("Settings")?> - <?=gettext("Threats")?></a>
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/active_protection/request_api_equipment.php") { ?>active<?php } ?>" href="/active_protection/request_api_equipment.php"><?=gettext("Reports")?></a>
							</a>
						</div>
					</li>
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#"><?=gettext('Firewall App');?></a>
						<div class="dropdown-menu">
							<a class="dropdown-item" href="#">
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/suricata/suricata/services.php") { ?>active<?php } ?>" href="/firewallapp/consumo_de_aplicacoes.php">Dashboard</a>
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/suricata/suricata/services.php") { ?>active<?php } ?>" href="/firewallapp/services.php"><?=gettext("Settings")?></a>
								<!--<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/suricata/suricata/status_sites_services.php") { ?>active<?php } ?>" href="/firewallapp/status_sites_services.php"><?=gettext("Status sites/services")?></a>-->
							</a>
						</div>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/firewall_rules.php") { ?>active<?php } ?>" href="/firewall_rules.php"><?=gettext('Firewall Rules');?></a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/firewall_nat.php") { ?>active<?php } ?>" href="/firewall_nat.php"><?=gettext('Nat Rules');?></a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/firewall_shaper.php") { ?>active<?php } ?>" href="/firewall_shaper.php">QoS</a>
					</li>
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">WebFilter</a>
						<div class="dropdown-menu">
							<a class="dropdown-item" href="#">
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/webfilter/wf_dashboard.php") { ?>active<?php } ?>" href="/webfilter/wf_dashboard.php">WebFilter</a>
								<!--<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/pkg_edit.php?xml=e2guardian.xml&id=0") { ?>active<?php } ?>" href="/pkg_edit.php?xml=e2guardian.xml&id=0">Inspector</a>-->
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/pkg_edit.php?xml=wpad.xml") { ?>active<?php } ?>" href="/pkg_edit.php?xml=wpad.xml">WPAD</a>
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "oc_services.php") { ?>active<?php } ?>" href="/one_click/oc_services.php">OneClick</a>
							</a>
						</div>
					</li>
					<li class="nav-item dropdown">
						<a class="nav-link" href="/relatorios-web/relatorios.php"><?=gettext('Reports');?></a>
						<!--<div class="dropdown-menu">
							<a class="dropdown-item" href="#" style="padding:0.25rem;">
								<?php if ($config['system']['webfilter']['bluepexdataclickagent']['config'][0]['enable'] == "on"): ?>
								<form action="/dataclick-web/" method="POST" class="nav-link" style="border:0px;margin:unset;padding:unset;" <?php if($_SERVER['PHP_SELF'] == "/dataclick-web/") { ?>active<?php } ?>>
									<input type="hidden" value="passTrue" name="passdata" id="passdata">
									<button style="margin:unset;padding:unset;width:100%;border:0px;background:transparent;padding: 4px;" class="nav-link">Dataclick</button>
								</form>
								<?php else: ?>
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/webfilter/wf_dataclick_settings.php") { ?>active<?php } ?>" href="/webfilter/wf_dataclick_settings.php">Dataclick</a>
								<?php endif; ?></a>
								<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/dataclick-web/") { ?>active<?php } ?>" href="/relatorios-web/relatorios.php">FirewallApp</a>
							</a>
						</div>-->
					</li>
					<li class="nav-item">
						<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/sdwan/availability_branch.php") { ?>active<?php } ?>" href="/sdwan/availability_branch_status.php">SD-WAN</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/snort/snort_interfaces.php") { ?>active<?php } ?>" href="<?php if (!is_package_installed("snort")) {?>/active_protection/ap_services.php<?php } else {?>/snort/snort_interfaces.php<?php } ?>">IDS/IPS</a>
					</li>
					<!--<li class="nav-item">
						<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/active_protection/ap_services.php") { ?>active<?php } ?>" href="/active_protection/ap_services.php">IDS/IPS</a>
					</li>-->
					<li class="nav-item">
						<a class="nav-link <?php if($_SERVER['PHP_SELF'] == "/snort/snort_interfaces.php") { ?>active<?php } ?>" href="<?php if (!is_package_installed("pfBlockerNG")) {?>/pkg_mgr_install.php?pkg=BluePexUTM-pkg-pfBlockerNG<?php } else {?>/pkg_edit.php?xml=pfblockerng.xml<?php } ?>"><?=gettext('Blocking of Countries');?></a>
					</li>
				</ul>

				<header class="header">

				<?php
					// If you set $notitle = true BEFORE including head.inc, the page title will be supressed
					if (isset($notitle)) {
						print('<br />');
						unset($notitle);
					} else {
						if (isset($pglinks)) {
							print(genhtmltitle($pgtitle, $pglinks));
						} else {
							print(genhtmltitle($pgtitle));
						}
					}
				?>
					<ul class="context-links">

					<?php if (isset($widgets)): ?>
						<li>
							<a href="#" title="<?=gettext("Save dashboard layout")?>" id="btnstore" class="invisible">
								<i class="fa fa-save icon-pointer"></i>
							</a>
						</li>
					<?php endif?>

					<?php if ($dashboard_available_widgets_hidden): ?>
						<li>
							<a onclick="$('#widget-available').toggle(360);" title="<?=gettext("Available widgets")?>">
								<i class="fa fa-plus icon-pointer"></i>
							</a>
						</li>
					<?php endif?>

					<?php if ($system_logs_filter_form_hidden): ?>
						<li>
							<a onclick="$('#filter-form').toggle(360)" title="<?=gettext("Log filter")?>">
								<i class="fa fa-filter icon-pointer"></i>
							</a>
						</li>
					<?php endif ?>

					<?php if ($system_logs_manage_log_form_hidden):
							/* If the user does not have access to status logs settings page, then exclude the manage log panel icon from the title bar. */
							if (isAllowedPage("status_logs_settings.php")) {
					?>
						<li>
							<a onclick="$('#manage-log-form').toggle(360)" title="<?=gettext("Manage log")?>">
								<i class="fa fa-wrench icon-pointer"></i>
							</a>
						</li>
					<?php	}
						endif
					?>

					<?php if ($monitoring_settings_form_hidden): ?>
						<li>
							<a onclick="$('#monitoring-settings-form').toggle(360);" title="<?=gettext("Settings")?>">
								<i class="fa fa-wrench icon-pointer"></i>
							</a>
						</li>
					<?php endif?>

					<?php if ($status_monitoring): ?>
						<li>
							<a class="update-graph" title="<?=gettext("Refresh Graph")?>">
								<i class="fa fa-repeat icon-pointer"></i>
							</a>
						</li>
						<li>
							<a class="export-graph" id="export-graph" title="<?=gettext("Export Graph")?>">
								<i class="fa fa-download icon-pointer"></i>
							</a>
						</li>
					<?php endif?>

					<div id="ajax_requests_icons_menubar" style='display:inline-flex;'></div>

					<?php if (!$g['disablehelpicon'] && isAllowedPage("help.php")): ?>
						<li>
							<a href="<?=$helpurl?>" target="_blank" title="<?=gettext("Help for items on this page")?>">
								<i class="fa fa-question-circle"></i>
							</a>
						</li>
					<?php endif?>
					</ul>
				</header>
				<?php
				/* if upgrade in progress, alert user */
				$warning_text = "";
				if (file_exists('/conf/needs_package_sync') && platform_booting()) {
					$warning_text = sprintf(gettext(
						'%1$s%3$s is booting, then packages will be reinstalled in the ' .
						'background.%2$s%1$sDo not make changes in the GUI until this is ' .
						'complete.%2$s'), '<p>', '</p>', $g['product_name']);
				} elseif (is_subsystem_dirty('packagelock')) {
					$pgtitle = array(gettext("System"), gettext("Package Manager"));
					$warning_text = sprintf(gettext('%1$sPackages are currently being ' .
						'reinstalled in the background.%2$s%1$sDo not make changes in ' .
						'the GUI until this is complete.%2$s'), '<p>', '</p>');
					$warning_text .= sprintf(gettext('%1$sIf the above message is still ' .
						'displayed after a couple of hours, use the \'Clear Package ' .
						'Lock\' button on the %3$s page and reinstall packages manually.' .
						'%2$s'), '<p>', '</p>', sprintf('<a href="diag_backup.php" ' .
						'title="%1$s &gt; %2$s">%1$s &gt; %2$s</a>', gettext('Diagnostics'),
						htmlspecialchars(gettext('Backup & Restore'))));
				}

				if (!empty($warning_text)) {
					print_info_box($warning_text);
				}

				/*	If this page is being remotely managed then do not allow the loading of the contents. */
				if ($config['remote_managed_pages']['item']) {
					foreach ($config['remote_managed_pages']['item'] as $rmp) {
						if ($rmp == $_SERVER['SCRIPT_NAME']) {
							print_info_box(gettext("This page is currently being managed by a remote machine."));
							include("foot.inc");
							exit;
						}
					}
				}

				// Modal notices window
				// The notices modal needs to be outside of the page display div or things get messy
				if ($display_notices):
				?>

				<div id="notices" class="modal fade" role="dialog">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>

								<h3 class="modal-title" id="myModalLabel"><?=gettext("Notices")?></h3>
							</div>

							<div class="modal-body">
								<?php
								$noticeCategories = array();

								if (is_array($notices)) {
									foreach ($notices as $time => $notice) {
										if (!isset($noticeCategories[ $notice['category'] ])) {
											$noticeCategories[ $notice['category'] ] = array();
										}

										$notice['time'] = $time;
										array_push($noticeCategories[ $notice['category'] ], $notice);
									}
								}

								foreach ($noticeCategories as $category => $catNotices):?>
								<h4><?=$category?></h4>
								<ul>
									<?php
										foreach ($catNotices as $notice):
									?>
									<li>
										<b>
											<?php if (!empty($notice['url'])):?>
											<a href="<?=htmlspecialchars($notice['url'])?>"><?=htmlspecialchars($notice['id'])?></a> -
											<?php endif;?>
										</b>
										<?=str_replace("\n", "<br/>", htmlspecialchars($notice['notice']))?>
										<i>@ <?=date('Y-m-d H:i:s', $notice['time'])?></i>
									</li>
									<?php endforeach;?>
								</ul>
								<?php endforeach;?>
							</div>

							<div class="modal-footer">
								<button type="button" class="btn btn-info" data-dismiss="modal"><i class="fa fa-times icon-embed-btn"></i><?=gettext("Close")?></button>
								<?php if ($allow_clear_notices && isAllowedPage("/index.php")):?>
								<button type="button" id="clearallnotices" class="btn btn-primary"><i class="fa fa-trash-o icon-embed-btn"></i><?=gettext("Mark All as Read")?></button>
								<?php endif;?>
							</div>
						</div>
					</div>
				</div>

				<script type="text/javascript">
				//<![CDATA[
					events.push(function() {
						$('#clearallnotices').click(function() {
							ajaxRequest = $.ajax({
								url: "/index.php",
								type: "post",
								data: { closenotice: "all"},
								success: function() {
									window.location = window.location.href;
								},
								failure: function() {
									alert("Error clearing notices!");
								}
							});
						});
					});
				//]]>
				</script>

				<?php endif;

				// Get the flash Messages
				get_flash_message();

				?>
