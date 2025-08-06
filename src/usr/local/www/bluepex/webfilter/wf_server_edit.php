<?php
/*
 *====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2016
 *
 * ====================================================================
 */

require_once("guiconfig.inc");
require_once("squid.inc");
require_once("webfilter.inc");
require_once("bp_auditing.inc");

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instances = &$config['system']['webfilter']['instance']['config'];

if (isset($_GET['id'])) {
	$instance_id = (int)$_GET['id'];
	$wf_config = $wf_instances[$instance_id]['server'];
}

if (isset($_POST['save'])) {
	squid_validate_general($_POST, $input_errors);
	$instance_id = isset($_POST['instance_id']) ? $_POST['instance_id'] : "";
	$wf_config = array();

	// General Config
	$wf_config['name'] = $_POST['instance_name'];
	$wf_config['enable_squid'] = $_POST['enable_squid'];
	$wf_config['enable_cache'] = $_POST['enable_cache'];
	$wf_config['parent_rules'] = $_POST['parent_rules'];
	$wf_config['keep_squid_data'] = $_POST['keep_squid_data'];
	$wf_config['active_interface'] = !empty($_POST['active_interface']) ? implode(",", $_POST['active_interface']) : "";
	$wf_config['proxy_port'] = $_POST['proxy_port'];
	if ($wf_config['proxy_port'] == "8080" || $wf_config['proxy_port'] == "8083") {
		$input_errors[] = gettext("Proxy port CANNOT be 8080 or 8083 neither.");
	}
	$wf_config['allow_interface'] = $_POST['allow_interface'];
	$wf_config['dns_v4_first'] = $_POST['dns_v4_first'];
	$wf_config['disable_pinger'] = $_POST['disable_pinger'];
	$wf_config['dns_nameservers'] = $_POST['dns_nameservers'];
	$wf_config['disabled_multilogin'] = $_POST['disabled_multilogin'];
	$wf_config['disabled_multilogin_ttl'] = $_POST['disabled_multilogin_ttl'];

	// Transparent Proxy Auth Settings
	$wf_config['transparent_proxy'] = $_POST['transparent_proxy'];
	$wf_config['transparent_active_interface'] = $wf_config['active_interface'];
	$wf_config['private_subnet_proxy_off'] = $_POST['private_subnet_proxy_off'];
	$wf_config['defined_ip_proxy_off'] = $_POST['defined_ip_proxy_off'];
	$wf_config['defined_ip_proxy_off_dest'] = $_POST['defined_ip_proxy_off_dest'];

	// SSL man in the middle Filtering
	$wf_config['ssl_proxy'] = $_POST['ssl_proxy'];
	$wf_config['ssl_active_interface'] = !empty($_POST['ssl_active_interface']) ? implode(",", $_POST['ssl_active_interface']) : "";
	$wf_config['ssl_proxy_port'] = $_POST['ssl_proxy_port'];
	if ($wf_config['ssl_proxy_port'] == "8080" || $wf_config['ssl_proxy_port'] == "8083") {
		$input_errors[] = gettext("SSL proxy port CANNOT be 8080 or 8083 neither.");
	}
	$wf_config['dca'] = $_POST['dca'];
	$wf_config['sslcrtd_children'] = $_POST['sslcrtd_children'];
	$wf_config['interception_checks'] = !empty($_POST['interception_checks']) ? implode(",", $_POST['interception_checks']) : "";
	$wf_config['interception_adapt'] = !empty($_POST['interception_adapt']) ? implode(",", $_POST['interception_adapt']) : "";

	// Logging Auth Settings
	$wf_config['log_enabled'] = $_POST['log_enabled'];
	$wf_config['log_dir'] = $_POST['log_dir'];
	$wf_config['log_rotate'] = $_POST['log_rotate'];
	$wf_config['visible_hostname'] = !empty($_POST['visible_hostname']) ? $_POST['visible_hostname'] : "localhost";
	$wf_config['admin_email'] = $_POST['admin_email'];
	$wf_config['error_language'] = !empty($_POST['error_language']) ? $_POST['error_language'] : "pt-br";
	$wf_config['disable_xforward'] = $_POST['disable_xforward'];
	$wf_config['sslproxy_mitm_mode'] = $_POST['sslproxy_mitm_mode'];
	$wf_config['dhparams_size'] = $_POST['dhparams_size'];
	$wf_config['sslproxy_compatibility_mode'] = $_POST['sslproxy_compatibility_mode'];
	$wf_config['xforward_mode'] = $_POST['xforward_mode'];
	$wf_config['disable_via'] = $_POST['disable_via'];
	$wf_config['uri_whitespace'] = $_POST['uri_whitespace'];
	$wf_config['disable_squidversion'] = $_POST['disable_squidversion'];

	// Custom Auth Settings
	$wf_config['custom_options'] = $_POST['custom_options'];
	$wf_config['custom_options_squid3'] = !empty($_POST['custom_options_squid3']) ? base64_encode($_POST['custom_options_squid3']) : "";
	$wf_config['custom_options2_squid3'] = !empty($_POST['custom_options2_squid3']) ? base64_encode($_POST['custom_options2_squid3']) : "";

	// Auth Settings
	$wf_config['authsettings']['auth_method'] = $_POST['auth_method'];
	if ($_POST['auth_method'] == "ntlm") {

		if (isset($_POST['sso_parent'])) {
			$wf_config['authsettings']['sso_parent'] = $_POST['sso_parent'];
		} else {
			$gc = exec("/usr/local/bin/samba-tool domain info {$_POST['auth_server']}", $get_domain_info);
			if (empty($get_domain_info)) {
				$input_errors[] = dgettext("BluePexWebFilter", gettext("Could not to get the ldap domain info!"));
			}
			if (empty($input_errors)) {
				$domain_info = array();
				foreach ($get_domain_info as $info) {
					array_push($domain_info, array_map('trim', explode(":", $info)));
				}
				foreach ($domain_info as $info) {
					if ($info[0] == "DC name") {
						$wf_config['authsettings']['name_server'] = $info[1];
						$wf_config['authsettings']['auth_dc_name'] = $info[1];
					} elseif ($info[0] == "Domain") {
						$wf_config['authsettings']['auth_ntdomain'] = $info[1];
					} elseif ($info[0] == "Netbios domain") {
						$wf_config['authsettings']['auth_workgroup'] = $info[1];
					}
				}
				$wf_config['authsettings']['auth_server'] = $_POST['auth_server'];
				$wf_config['authsettings']['ntlm_user'] = $_POST['ntlm_user'];
				$wf_config['authsettings']['ntlm_password'] = $_POST['ntlm_password'];
				$wf_config['authsettings']['auth_idmap_uid'] = (isset($_POST['auth_idmap_uid']) && !empty($_POST['auth_idmap_uid'])) ? $_POST['auth_idmap_uid'] : "10000-20000";
				$wf_config['authsettings']['log_detail'] = $_POST['log_detail'];
				$wf_config['authsettings']['keep_alive'] = isset($_POST['keep_alive']) ? "off" : "";
				if (isset($_POST['no_auth_hosts_sso'])) {
					$wf_config['authsettings']['no_auth_hosts'] = base64_encode(implode("\n", explode(",", $_POST['no_auth_hosts_sso'])));
				} else {
					$wf_config['authsettings']['no_auth_hosts'] = "";
				}
			}
		}
	}

	$wf_config['authsettings']['auth_processes'] = (isset($_POST['auth_processes']) && !empty($_POST['auth_processes'])) ? $_POST['auth_processes'] : "5";

	if (empty($input_errors)) {
		if (!is_numeric($instance_id)) {
			$wf_instances[]['server'] = $wf_config;
			$log_text = "report_0008_webfilter_server_new";
		} else {
			$wf_instances[$instance_id]['server'] = $wf_config;
			$log_text = "report_0008_webfilter_server_edit";
		}
		bp_write_report_db($log_text, $wf_config['name']);
		if ($log_text == "report_0008_webfilter_server_new") { bp_write_report_db("report_0008_webfilter_setting_new", $wf_config['name']); }
		add_instances_info();
		$savemsg = dgettext("BluePexWebFilter", gettext("General Settings applied successfully!"));
		write_config($savemsg);
		if (isset($instance_id)) {
			squid_resync($instance_id);
		} else {
			squid_resync(strval(count($wf_instances) + 1));
		}
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_server.php");
		exit;
	}

	mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');
}

function get_instance_with_sso_config() {
	global $wf_instances;

	foreach ($wf_instances as $instance_id => $instance_config) {
		if ($instance_config['server']['authsettings']['auth_method'] == "ntlm" &&
		    !isset($instance_config['server']['authsettings']['sso_parent'])) {
			return $instance_id;
		}
	}
}

function ntlm_result_table($wf_config) {
	$table .= "<table class='table'>";
	$table .= "<tr valign='center'>";
	$table .= "<td width='22%'>" . dgettext('BluePexWebFilter', 'Name server') . "</td>";
	$table .= "<td><b>" . $wf_config['authsettings']['name_server'] . "</b></td>";
	$table .= "</tr>";
	$table .= "<tr valign='top'>";
	$table .= "<td width='22%'>" . dgettext('BluePexWebFilter', 'NT domain') . "</td>";
	$table .= "<td><b>" . $wf_config['authsettings']['auth_ntdomain'] . "</b></td>";
	$table .= "</tr>";
	$table .= "<tr valign='top'>";
	$table .= "<td width='22%'>" . dgettext('BluePexWebFilter', 'Workgroup') . "</td>";
	$table .= "<td><b>" . $wf_config['authsettings']['auth_workgroup'] . "</b></td>";
	$table .= "</tr>";
	$table .= "<tr valign='top'>";
	$table .= "<td width='22%'>" . dgettext('BluePexWebFilter', 'DC Name Server') . "</td>";
	$table .= "<td><b>" . $wf_config['authsettings']['auth_dc_name'] . "</b></td>";
	$table .= "</tr>";
	$table .= "</table>";
	return $table;
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', gettext('Proxy Server Settings')));

include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');
?>
<div id="error_Message"></div>
<?php
include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), true, '/webfilter/wf_server.php');
//$tab_array[] = array(dgettext('BluePexWebFilter', 'Upstream Proxy'), false, '/webfilter/wf_upstream.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Cache Mgmt'), false, '/webfilter/wf_cache_mgmt.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Access Control'), false, '/webfilter/wf_access_control.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Traffic Mgmt'), false, '/webfilter/wf_traffic_mgmt.php');
display_top_tabs($tab_array);

$form = new Form();
$section = new Form_Section(dgettext("BluePexWebFilter", 'General Settings'));

$section->addInput(new Form_Input(
	'instance_name',
	'Instance Name',
	'text',
	(isset($wf_config['name']) ? $wf_config['name'] : "")
))->setHelp(gettext('Enter the instance name.'));

$section->addInput(new Form_Checkbox(
	'enable_squid',
	dgettext('BluePexWebFilter', 'Enable'),
	dgettext("BluePexWebFilter", 'Check to enable the webfilter proxy.'),
	(isset($wf_config['enable_squid']) && $wf_config['enable_squid'] == "on"),
	'on'
))->setHelp(dgettext('BluePexWebFilter', 'Note: If unchecked, ALL webfilter services will be disabled and stopped.'));

$section->addInput(new Form_Checkbox(
	'enable_cache',
	dgettext('BluePexWebFilter', 'Enable Cache'),
	dgettext("BluePexWebFilter", 'Check to enable the webfilter cache for instance.'),
	(isset($wf_config['enable_cache']) && $wf_config['enable_cache'] == "on"),
	'on'
))->setHelp(dgettext('BluePexWebFilter', 'Note: If unchecked, cache will not be set for this instance.'));

$instances = array("" => dgettext('BluePexWebFilter', "-- select --"));
foreach ($wf_instances as $_instance_id => $instance_config) {
	if (($instance_id != $_instance_id) || ($instance_id == "")) {
		$instances[$_instance_id] = $instance_config['server']['name'];
	}
}
$section->addInput(new Form_Select(
	'parent_rules',
	dgettext('BluePexWebFilter', 'Parent content rules'),
	(isset($wf_config['parent_rules']) ? $wf_config['parent_rules'] : ""),
	$instances
))->setHelp(dgettext('BluePexWebFilter', 'Use the same content rules of the proxy instance selected above.'));

$section->addInput(new Form_Checkbox(
	'keep_squid_data',
	dgettext('BluePexWebFilter', 'Keep Settings/Data'),
	dgettext("BluePexWebFilter", 'If enabled, the settings, logs, cache, AV defs and other data will be preserved across package reinstalls.'),
	(isset($wf_config['keep_squid_data']) && $wf_config['keep_squid_data'] == "on"),
	'on'
))->setHelp(dgettext('BluePexWebFilter', 'Note: If disabled, all settings and data will be wiped on package uninstall/reinstall/upgrade.'));

$ifaces = get_configured_interface_with_descr();
$ifaces["lo0"] = "loopback";
$section->addInput(new Form_Select(
	'active_interface',
	dgettext('BluePexWebFilter', 'Interface(s)'),
	(isset($wf_config['active_interface']) ? explode(",", $wf_config['active_interface']) : ""),
	$ifaces,
	true
))->setHelp(dgettext('BluePexWebFilter', 'The interface(s) the proxy server will bind to.'));

$section->addInput(new Form_Input(
	'proxy_port',
	dgettext('BluePexWebFilter', 'Proxy Port'),
	'text',
	(isset($wf_config['proxy_port']) ? $wf_config['proxy_port'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'This is the port the proxy server will listen on.'));

$section->addInput(new Form_Checkbox(
	'allow_interface',
	dgettext('BluePexWebFilter', 'Allow users on interface'),
	dgettext("BluePexWebFilter", "If this field is checked, the users connected to the interface selected in the 'Proxy interface' field will be allowed to use the proxy, i.e., there will be no need to add the interface's subnet to the list of allowed subnets. This is just a shortcut."),
	(isset($wf_config['allow_interface']) && $wf_config['allow_interface'] == "on"),
	'on'
));

$section->addInput(new Form_Checkbox(
	'dns_v4_first',
	dgettext('BluePexWebFilter', 'Resolv dns v4 first'),
	dgettext("BluePexWebFilter", "Enable this option to force dns v4 lookup first. This option is very usefull if you have problems to access https sites."),
	(isset($wf_config['dns_v4_first']) && $wf_config['dns_v4_first'] == "on"),
	'on'
));

$section->addInput(new Form_Checkbox(
	'disable_pinger',
	dgettext('BluePexWebFilter', 'Disable ICMP '),
	dgettext("BluePexWebFilter", "Enable this option to disable webfilter ICMP pinger helper."),
	(isset($wf_config['disable_pinger']) && $wf_config['disable_pinger'] == "on"),
	'on'
));

$section->addInput(new Form_Input(
	'dns_nameservers',
	dgettext('BluePexWebFilter', 'Use alternate DNS-servers for the proxy-server'),
	'text',
	(isset($wf_config['dns_nameservers']) ? $wf_config['dns_nameservers'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'If you want to use other DNS-servers than the DNS-forwarder, enter the IPs here, separated by semi-colons (;).'));

$section->addInput(new Form_Checkbox(
	'disabled_multilogin',
	dgettext('BluePexWebFilter', 'Disable Multiple Login'),
	dgettext("BluePexWebFilter", "Enable this option to disable users multiple login."),
	(isset($wf_config['disabled_multilogin']) && $wf_config['disabled_multilogin'] == "on"),
	'on'
));

$section->addInput(new Form_Input(
	'disabled_multilogin_ttl',
	dgettext('BluePexWebFilter', 'Disable Multi Login (TTL)'),
	'text',
	(isset($wf_config['disabled_multilogin_ttl']) ? $wf_config['disabled_multilogin_ttl'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'This will disable the multi users login.'));

$form ->add($section);

$section = new Form_Section(dgettext("BluePexWebFilter", 'Transparent Proxy'));

$section->addInput(new Form_Checkbox(
	'transparent_proxy',
	dgettext('BluePexWebFilter', 'Enable'),
	dgettext("BluePexWebFilter", "Enable transparent mode to forward all requests for destination port 80 to the proxy server without any additional configuration necessary."),
	(isset($wf_config['transparent_proxy']) && $wf_config['transparent_proxy'] == "on"),
	'on'
))->setHelp(dgettext('BluePexWebFilter', 'NOTE: Transparent mode will filter ssl(port 443) if enable men-in-the-middle options below. To filter both http and https protocol without intercepting ssl connections, enable WPAD/PAC options on your dns/dhcp.'));

/*$ifaces = get_configured_interface_with_descr();
$ifaces["lo0"] = "loopback";
$section->addInput(new Form_Select(
	'transparent_active_interface',
	dgettext('BluePexWebFilter', 'Interface(s)'),
	(isset($wf_config['transparent_active_interface']) ? explode(",", $wf_config['transparent_active_interface']) : ""),
	$ifaces,
	true
))->setHelp(dgettext('BluePexWebFilter', 'The interface(s) the proxy server will transparently intercept requests on.'));*/

$section->addInput(new Form_Checkbox(
	'private_subnet_proxy_off',
	dgettext('BluePexWebFilter', 'Bypass proxy for Private Address destination'),
	dgettext("BluePexWebFilter", "Do not forward traffic to Private Address Space (RFC 1918) destination through the proxy server but directly through the firewall."),
	(isset($wf_config['private_subnet_proxy_off']) && $wf_config['private_subnet_proxy_off'] == "on"),
	'on'
));

$section->addInput(new Form_Input(
	'defined_ip_proxy_off',
	dgettext('BluePexWebFilter', 'Bypass proxy for these source IPs'),
	'text',
	(isset($wf_config['defined_ip_proxy_off']) ? $wf_config['defined_ip_proxy_off'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Do not forward traffic from these source IPs, CIDR nets, hostnames, or aliases through the proxy server but directly through the firewall. Separate by semi-colons (;). [Applies only to transparent mode]'));

$section->addInput(new Form_Input(
	'defined_ip_proxy_off_dest',
	dgettext('BluePexWebFilter', 'Bypass proxy for these destination IPs'),
	'text',
	(isset($wf_config['defined_ip_proxy_off_dest']) ? $wf_config['defined_ip_proxy_off_dest'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Do not proxy traffic going to these destination IPs, CIDR nets, hostnames, or aliases, but let it pass directly through the firewall. Separate by semi-colons (;). [Applies only to transparent mode]'));

$form ->add($section);

$section = new Form_Section(dgettext('BluePexWebFilter', 'Auth Settings'));

$auth_methods = array(
	"none" => dgettext("BluePexWebFilter", "None"),
	"usermanager" => dgettext("BluePexWebFilter", "User Manager"),
	"cp" => dgettext("BluePexWebFilter", "Captive Portal"),
	"ntlm" => dgettext("BluePexWebFilter", "Single SignOn (SSO)")
);
$section->addInput(new Form_Select(
	'auth_method',
	dgettext('BluePexWebFilter', 'Authentication method'),
	$wf_config['authsettings']['auth_method'],
	$auth_methods
))->setHelp(dgettext('BluePexWebFilter', 'Select the authentication method.'));

$section->addInput(new Form_Input(
	'auth_processes',
	dgettext('BluePexWebFilter', 'Auth process number'),
	'text',
	!empty($wf_config['authsettings']['auth_processes']) ? $wf_config['authsettings']['auth_processes'] : 5
))->addClass("advanced")->setHelp(dgettext('BluePexWebFilter', 'Set the number of the authentication processes.'));

$form->add($section);

$section = new Form_Section(dgettext('BluePexWebFilter', 'User Manager'));
$section->addClass('usermanager');

$no_auth_hosts = "";
if (isset($wf_config['authsettings']['no_auth_hosts'])) {
	$no_auth_hosts = str_replace("\n", ",", base64_decode($wf_config['authsettings']['no_auth_hosts']));
}
$section->addInput(new Form_Input(
	'no_auth_hosts',
	dgettext('BluePexWebFilter', 'Allowed Subnets'),
	'text',
	$no_auth_hosts
))->setHelp(dgettext('BluePexWebFilter', 'Enter each subnet or IP address on a new line (in CIDR format, e.g.: 10.5.0.0/16, 192.168.1.50/32) that should not be asked for authentication to access the proxy.'));

$form->add($section);

$section = new Form_Section(dgettext('BluePexWebFilter', 'Single SignOn (SSO) Settings'));
$section->addClass('sso');

$instance_sso_id = get_instance_with_sso_config();
if (is_numeric($instance_sso_id) && $instance_sso_id != $instance_id) {
	$section->addInput(new Form_StaticText(
		'',
		"<h2>" . dgettext("BluePexWebFilter", "Only one instance SSO will can be used!") . "</h2><br />" .
		sprintf(dgettext("BluePexWebFilter", "If selected the Single SignOn (SSO) Settings for this instance, It will use the SSO settings of the instance '%s'."), $wf_instances[$instance_sso_id]['server']['name'])
	));

	$section->addInput(new Form_StaticText(
		dgettext('BluePexWebFilter', 'Domain Controller Info'),
		ntlm_result_table($wf_instances[$instance_sso_id]['server'])
	));

	$section->addInput(new Form_Input(
		'sso_parent',
		null,
		'hidden',
		$instance_sso_id
	));
} else {
	$section->addInput(new Form_Input(
		'auth_server',
		dgettext('BluePexWebFilter', 'Authentication server'),
		'text',
		$wf_config['authsettings']['auth_server']
	))->setHelp(dgettext('BluePexWebFilter', 'Select an authentication method. This will allow users to be authenticated by local or external services.'));

	$section->addInput(new Form_Input(
		'ntlm_user',
		dgettext('BluePexWebFilter', 'SSO Username'),
		'text',
		$wf_config['authsettings']['ntlm_user']
	))->setHelp(dgettext('BluePexWebFilter', 'Enter the username to connect to the domain server.'));

	$section->addInput(new Form_Input(
		'ntlm_password',
		dgettext('BluePexWebFilter', 'SSO Password'),
		'password',
		$wf_config['authsettings']['ntlm_password']
	))->setHelp(dgettext('BluePexWebFilter', 'Enter the password of the user inserted above to connect to the domain server.'));

	$section->addInput(new Form_Checkbox(
		'auth_advanced',
		dgettext('BluePexWebFilter', 'Advanced options'),
		null,
		(isset($wf_config['auth_advanced'])) ? $wf_config['auth_advanced'] : '',
		'on'
	))->setHelp(dgettext('BluePexWebFilter', 'Check this option to open advanced options.'));

	$section->addInput(new Form_Input(
		'auth_idmap_uid',
		dgettext('BluePexWebFilter', 'Idmap UID'),
		'text',
		$wf_config['authsettings']['auth_idmap_uid']
	))->addClass("advanced")->setHelp(sprintf(dgettext('BluePexWebFilter', 'The Idmap plugin provides a way for Winbind to read id mappings from an AD server that uses RFC2307/SFU schema extensions.s% Enter with uidNumber attributes for users and groups in the AD. E.g: 10000-20000.'), "<br />"));

	$section->addInput(new Form_Input(
		'no_auth_hosts_sso',
		dgettext('BluePexWebFilter', 'Allowed Subnets'),
		'text',
		$no_auth_hosts
	))->addClass("advanced")->setHelp(dgettext('BluePexWebFilter', 'Enter each subnet or IP address on a new line (in CIDR format, e.g.: 10.5.0.0/16, 192.168.1.50/32) that should not be asked for authentication to access the proxy.'));

	$section->addInput(new Form_Select(
		'log_detail',
		dgettext('BluePexWebFilter', 'Log details'),
		$wf_config['authsettings']['log_detail'],
		range(0,10)
	))->addClass("advanced")->setHelp(dgettext('BluePexWebFilter', 'Define the log details for authentication logs. Default is 3. The greater this value is, more detail will be put in the log file.'));

	$section->addInput(new Form_Checkbox(
		'keep_alive',
		dgettext('BluePexWebFilter', 'Dont Keep Connections Alive'),
		dgettext('BluePexWebFilter', 'Check this option to not keep conections alive.'),
		(isset($wf_config['authsettings']['keep_alive']) && $wf_config['authsettings']['keep_alive'] == "off"),
		'on'
	))->setHelp(dgettext('BluePexWebFilter', 'Check this option if machines out of domain keeping request authentication.'));

	if ($wf_config['authsettings']['auth_method'] == "ntlm") {
		$section->addInput(new Form_StaticText(
			dgettext('BluePexWebFilter', 'Domain Controller Info'),
			ntlm_result_table($wf_config)
		));
	}
}
$form->add($section);

$section = new Form_Section(dgettext("BluePexWebFilter", 'SSL Filtering'));

$section->addInput(new Form_Checkbox(
	'ssl_proxy',
	dgettext('BluePexWebFilter', 'HTTPS/SSL interception'),
	dgettext("BluePexWebFilter", "Enable SSL filtering."),
	(isset($wf_config['ssl_proxy']) && $wf_config['ssl_proxy'] == "on"),
	'on'
));

$section->addInput(new Form_StaticText(
	'',
	"<div class='alert alert-warning' id='alert-enable-ssl'><strong>" . dgettext('BluePexWebFilter', "Note: Maybe you need to use sticky connections to some sites work correctly with https enabled. enable in: ") .
	"<a href='/system_advanced_misc.php' target='_blank'>" . dgettext('BluePexWebFilter', "System/Advanced/Miscellaneous") . '</a></strong></div>'
));

$ssl_mode = array(
	'splicewhitelist' => dgettext("BluePexWebFilter", "splicewhitelist"),
	'spliceall' => dgettext("BluePexWebFilter", "spliceall"),
	'custom' => dgettext("BluePexWebFilter", "custom")
);

$section->addInput(new Form_Select(
	'sslproxy_mitm_mode',
	dgettext('BluePexWebFilter', 'SSL Mode'),
	(isset($wf_config['sslproxy_mitm_mode']) ? $wf_config['sslproxy_mitm_mode'] : ""),
	$ssl_mode
))->setHelp(dgettext('BluePexWebFilter', "The SSL/MITM mode determines how SSL interception is treated when 'SSL Man In the Middle Filtering' is enabled."));

$section->addInput(new Form_Select(
	'ssl_active_interface',
	'Interface(s)',
	(isset($wf_config['ssl_active_interface']) ? explode(",", $wf_config['ssl_active_interface']) : ""),
	$ifaces,
	true
))->setHelp(dgettext('BluePexWebFilter', "The interface(s) the proxy server will intercept ssl requests."));

$section->addInput(new Form_Input(
	'ssl_proxy_port',
	dgettext('BluePexWebFilter', 'SSL Proxy port'),
	'text',
	(isset($wf_config['ssl_proxy_port']) ? $wf_config['ssl_proxy_port'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'This is the port the proxy server will listen on to intercept ssl while using transparent proxy.<br />(Default: 3127)'));

$compatibility = array(
	'modern' => "modern",
	'intermediate' => "intermediate"
);
$section->addInput(new Form_Select(
	'sslproxy_compatibility_mode',
	dgettext('BluePexWebFilter', 'SSL Proxy Compatibility Mode'),
	(isset($wf_config['sslproxy_compatibility_mode']) ? $wf_config['sslproxy_compatibility_mode'] : ""),
	$compatibility
))->setHelp(dgettext('BluePexWebFilter', "The compatibility mode determines which cipher suites and TLS versions are supported."));

$dh = array(
	'2048' => "2048",
	'1024' => "1024",
	'4096' => "4096"
);
$section->addInput(new Form_Select(
	'dhparams_size',
	dgettext('BluePexWebFilter', 'DHParams Key Size'),
	(isset($wf_config['dhparams_size']) ? $wf_config['dhparams_size'] : ""),
	$dh
))->setHelp(dgettext('BluePexWebFilter', "DH parameters are used for temporary/ephemeral DH key exchanges and improve security by enabling the use of DHE ciphers."));

$cas = array();
if (isset($config['ca'])) {
	foreach ($config['ca'] as $cert) {
		if (!isset($cert['refid'])) {
			continue;
		}
		$description = $cert['descr'];
		if(is_webfilter_crl($cert['refid'])) {
			$description .= " (".dgettext("BluePexWebFilter", "In Use").")";
		}
		$cas[$cert['refid']] = $description;
	}
}
$cas['none'] = 'none';

function form_generate_cert(&$section)
{
	$section->addInput(new Form_Input(
		'dn_certName',
		dgettext("BluePexWebFilter", 'Certificate Name'),
		'text',
		$pconfig['dn_certName']
	));

	/*
	$dn_cc = array();
	if (file_exists("/etc/ca_countries")) {
		$dn_cc_file=file("/etc/ca_countries");
		foreach($dn_cc_file as $line) {
			if (preg_match('/^(\S*)\s(.*)$/', $line, $matches))
				$dn_cc[$matches[1]] = $matches[1];
		}
	}

	foreach( $dn_cc as $cc) {
		$selected = "";
		if ($pconfig['dn_country'] == $cc)
			$selected = "selected";
	}
	*/

	$section->addInput(new Form_Select(
		'dn_country',
		dgettext("BluePexWebFilter", 'Country Code'),
		$selected,
		get_cert_country_codes()
		//$dn_cc
	));

	$section->addInput(new Form_Input(
		'dn_state',
		dgettext("BluePexWebFilter", 'State or Province'),
		'text',
		$pconfig['dn_state']
	))->setHelp(dgettext('BluePexWebFilter', 'e. g. São Paulo'));

	$section->addInput(new Form_Input(
		'dn_city',
		dgettext("BluePexWebFilter", 'City'),
		'text',
		$pconfig['dn_city']
	))->setHelp(dgettext('BluePexWebFilter', 'e. g. Limeira'));

	$section->addInput(new Form_Input(
		'dn_organization',
		dgettext("BluePexWebFilter", 'Organization'),
		'text',
		$pconfig['dn_organization']
	))->setHelp(gettext('e. g. My Company Inc.'));

	$section->addInput(new Form_Input(
		'dn_email',
		dgettext("BluePexWebFilter", 'E-mail Address'),
		'text',
		$pconfig['dn_email']
	))->setHelp(gettext('e.g. admin@mycompany.com'));

	$section->addInput(new Form_Input(
		'dn_commonname',
		dgettext("BluePexWebFilter", 'Common Name'),
		'text',
		$pconfig['description']
	))->setHelp(gettext('e. g. www.example.com'));

	$group = new Form_Group(null);
	$group->add(new Form_Button(
		'save_cert',
		dgettext("BluePexWebFilter", 'Generate')
	));
	$section->add($group);

	if (isset($id) && $a_ca[$id]) {
		$section->addInput(new Form_Input(
			'id',
			null,
			'hidden',
			$id
		));
	}
}

$buttonGenerate = new Form_Button(
	'generatecertca',
	dgettext("BluePexWebFilter", 'Generate New Certificate')
);

//Save the number of server certs for use at run-time security validation
$servercerts = count($cas);

$certhelp = '<span id="certtype"></span>';
$groupCertAuth = new Form_Group('CA');
$groupCertAuth->add(new Form_Select(
	'dca',
	'CA',
	(isset($wf_config['dca']) ? $wf_config['dca'] : ""),
	$cas
))->setHelp($certhelp);

$groupCertAuth->add($buttonGenerate);
$section->add($groupCertAuth);
$section->addInput(new Form_StaticText('', "<div class='alert alert-warning' id='alert-crt-selected'><strong>".dgettext('BluePexWebFilter', "Note: For SSL filtering to work correctly, you must import the certificate into the 'Trusted Root Certification Authorities' folder (Windows). In Mozilla Firefox, you will need the p12 certificate.")."</strong></div>"));
form_generate_cert($section);

$section->addInput(new Form_Input(
	'sslcrtd_children',
	dgettext('BluePexWebFilter', 'sslcrtd children'),
	'text',
	(isset($wf_config['sslcrtd_children']) ? $wf_config['sslcrtd_children'] : "")
))->setHelp(dgettext('BluePexWebFilter', "This is the number of ssl crt deamon children to start. Default value is 5. if Proxy is used in busy environments this may need to be increased, as well as the number of 'sslcrtd_children'"));

$interception_checks = array(
	'sslproxy_cert_error' => dgettext("BluePexWebFilter", "Accept remote server certificate Erros"),
	'sslproxy_flags' => dgettext("BluePexWebFilter", "Do not verify remote certificate"),
);
$section->addInput(new Form_Select(
	'interception_checks',
	dgettext('BluePexWebFilter', 'Remote Cert checks'),
	(isset($wf_config['interception_checks']) ? explode(",", $wf_config['interception_checks']) : ""),
	$interception_checks,
	true
))->setHelp(dgettext('BluePexWebFilter', "Select remote ssl cert checks to do. Default is to do not select any of these options."));

$interception_adapt = array(
	'setValidAfter' => dgettext("BluePexWebFilter", "Sets the 'Not After' (setValidAfter)."),
	'setValidBefore' => dgettext("BluePexWebFilter", "Sets the 'Not Before' (setValidBefore)."),
	'setCommonName' => dgettext("BluePexWebFilter", "Sets CN property (setCommonName)")
);
$section->addInput(new Form_Select(
	'interception_adapt',
	dgettext('BluePexWebFilter', 'Certificate adapt'),
	(isset($wf_config['interception_adapt']) ? explode(",", $wf_config['interception_adapt']) : ""),
	$interception_adapt,
	true
))->setHelp(dgettext('BluePexWebFilter', "Pass original SSL server certificate information to the user. Allow the user to make an informed decision on whether to trust the server certificate. Hint: Set subject CN"));

$form ->add($section);
$section = new Form_Section(dgettext("BluePexWebFilter", 'Logging Auth Settings'));

$section->addInput(new Form_Checkbox(
	'log_enabled',
	dgettext('BluePexWebFilter', 'Enabled logging'),
	dgettext("BluePexWebFilter", "This will enable the access log. Don't switch this on if you don't have much disk space left."),
	(isset($wf_config['log_enabled']) && $wf_config['log_enabled'] == "on"),
	'on'
));

/*$section->addInput(new Form_Input(
	'log_dir',
	dgettext('BluePexWebFilter', 'Log Store Directory'),
	'text',
	(isset($wf_config['log_dir']) ? $wf_config['log_dir'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'The directory where the logs will be stored; also used for logs other than the Access Log above.'));*/

$section->addInput(new Form_Input(
	'visible_hostname',
	dgettext('BluePexWebFilter', 'Visible hostname'),
	'text',
	(isset($wf_config['visible_hostname']) ? $wf_config['visible_hostname'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'This is the URL to be displayed in proxy server error messages.'));

$section->addInput(new Form_Input(
	'admin_email',
	dgettext('BluePexWebFilter', 'Administrator email'),
	'text',
	(isset($wf_config['admin_email']) ? $wf_config['admin_email'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'This is the email address displayed in error messages to the users.'));

$languages = array(
	'en' => dgettext("BluePexWebFilter", "English"),
	'pt-br' => dgettext("BluePexWebFilter", "Portuguese")
);
$section->addInput(new Form_Select(
	'error_language',
	dgettext('BluePexWebFilter', 'Language'),
	(isset($wf_config['error_language']) ? $wf_config['error_language'] : ""),
	$languages
))->setHelp(dgettext('BluePexWebFilter', "Select the language in which the proxy server will display error messages to users."));

$section->addInput(new Form_Checkbox(
	'disable_xforward',
	dgettext('BluePexWebFilter', 'Disable X-Forward'),
	dgettext("BluePexWebFilter", "If not set, Proxy will include your system's IP address or name in the HTTP requests it forwards."),
	(isset($wf_config['disable_xforward']) && $wf_config['disable_xforward'] == "on"),
	'on'
));

$xf = array(
	'on' => "on",
	'off' => "off",
	'transparent' => "transparent",
	'delete' => "delete",
	'truncate' => "truncate"
);
$section->addInput(new Form_Select(
	'xforward_mode',
	dgettext('BluePexWebFilter', 'X-Forwarded Header Mode'),
	(isset($wf_config['xforward_mode']) ? $wf_config['xforward_mode'] : ""),
	$xf
))->setHelp(dgettext('BluePexWebFilter', "Choose how to handle X-Forwarded-For headers."));


$section->addInput(new Form_Checkbox(
	'disable_via',
	dgettext('BluePexWebFilter', 'Disable VIA'),
	dgettext("BluePexWebFilter", "If not set, Proxy will include a Via header in requests and replies as required by RFC2616."),
	(isset($wf_config['disable_via']) && $wf_config['disable_via'] == "on"),
	'on'
));

$uri = array(
	'strip' => "strip",
	'deny' => "deny",
	'allow' => "allow",
	'encode' => "encode",
	'chop' => "chop"
);
$section->addInput(new Form_Select(
	'uri_whitespace',
	dgettext('BluePexWebFilter', 'URI Whitespace Characters Handling'),
	(isset($wf_config['uri_whitespace']) ? $wf_config['uri_whitespace'] : ""),
	$uri
))->setHelp(dgettext('BluePexWebFilter', "Choose how to handle whitespace characters in URL."));

$section->addInput(new Form_Checkbox(
	'disable_squidversion',
	dgettext('BluePexWebFilter', 'Suppress Proxy Version'),
	dgettext("BluePexWebFilter", "If set, suppress Proxy version string info in HTTP headers and HTML error pages."),
	(isset($wf_config['disable_squidversion']) && $wf_config['disable_squidversion'] == "on"),
	'on'
));

$form ->add($section);
$section = new Form_Section(dgettext("BluePexWebFilter", 'Custom Auth Settings'));

$section->addInput(new Form_Textarea(
	'custom_options',
	dgettext('BluePexWebFilter', 'Integrations'),
	(isset($wf_config['custom_options']) ? $wf_config['custom_options'] : "")
))->setHelp(dgettext('BluePexWebFilter', "Proxy options added from packages like squidguard or havp for webfilter integration."));

$section->addInput(new Form_Textarea(
	'custom_options_squid3',
	dgettext('BluePexWebFilter', 'Custom ACLS (Before_Auth)'),
	(isset($wf_config['custom_options_squid3']) ? base64_decode($wf_config['custom_options_squid3']) : "")
))->setHelp(dgettext('BluePexWebFilter', "Put your own custom options here,one per line. They'll be added to the configuration before authetication acls(if any). They need to be squid.conf native options, otherwise webfilter will NOT work."));

$section->addInput(new Form_Textarea(
	'custom_options2_squid3',
	dgettext('BluePexWebFilter', 'Custom ACLS (After_Auth)'),
	(isset($wf_config['custom_options2_squid3']) ? base64_decode($wf_config['custom_options2_squid3']) : "")
))->setHelp(dgettext('BluePexWebFilter', "Put your own custom options here,one per line. They'll be added to the configuration after authetication definition(if any). They need to be squid.conf native options, otherwise webfilter will NOT work."));

if (isset($instance_id)) {
	$form->addGlobal(new Form_Input(
		'instance_id',
		'',
		'hidden',
		$instance_id
	));
}

$form ->add($section);
print $form;
?>
<script type="text/javascript">
window.onload = function() {
	$("#transparent_proxy").click(function() {
		auth_method_inputs($("#auth_method").val());
	});

	function hide_show_auth_settings() {
		var transparent_proxy = $("#transparent_proxy").is(":checked");

		if (transparent_proxy) {
			$("#auth_method option[value=usermanager]").attr("disabled", true);
			$("#auth_method option[value=ntlm]").attr("disabled", true);
		} else {
			$("#auth_method option[value=usermanager]").removeAttr("disabled");
			$("#auth_method option[value=ntlm]").removeAttr("disabled");
		}
		hideClass('sso', transparent_proxy);
		hideClass('usermanager', transparent_proxy);

		return transparent_proxy;
	}

	function auth_method_inputs(auth_method) {
		if (!hide_show_auth_settings()) {
			if (auth_method == "ntlm") {
				hideClass('sso', false);
				hideClass('usermanager', true);
			} else if (auth_method == "usermanager") {
				hideClass('usermanager', false);
				hideClass('sso', true);
			} else if (auth_method == "cp") {
				hideClass('usermanager', true);
				hideClass('sso', true);
			} else {
				hideClass('sso', true);
				hideClass('usermanager', true);
			}
		}
	}

	$("#auth_method").change(function() {
		auth_method_inputs($(this).val());
	});

	/* SSL make cert functions */
	function error_Message(msg) {
		var html;
		html = "<div class=\"alert alert-danger input-errors\">";
		html += "<?php echo '<p>' . dgettext("BluePexWebFilter", 'The following input errors was detected:') . '</p>'; ?>";
		html += "<ul>";
		html += "<li>" + msg + " </li>";
		html += "</ul>";
		html += "</div>";
		$("#error_Message").html(html);
		$(window).scrollTop(0);
	}

	function generate_certificateRevogation() {
		var caref = $("[name='caref']").val();
		var certName = $("[name='dn_certName']").val();
		make_certRevogation(certName, caref);
	}

	function validation_Form(certName, country, state, city, organization, email, commonname) {
		 if(!certName) {
			error_Message("<?=dgettext("BluePexWebFilter", 'The field Certificate Name is required.');?>");
			return false;
		} else if(!country) {
			error_Message("<?=dgettext("BluePexWebFilter", 'The field Country is required.');?>");
			return false;
		} else if (!state) {
			error_Message("<?=dgettext("BluePexWebFilter", 'The field State or Province is required.');?>");
			return false;
		} else if (!city) {
			error_Message("<?=dgettext("BluePexWebFilter", 'The field City is required.');?>");
			return false;
		} else if (!organization) {
			error_Message("<?=dgettext("BluePexWebFilter", 'The field Organization is required.');?>");
			return false;
		} else if (!email) {
			error_Message("<?=dgettext("BluePexWebFilter", 'The field Email address is required.');?>");
			return false;
		} else if (!commonname) {
			error_Message("<?=dgettext("BluePexWebFilter", 'The field Common name is required.');?>");
			return false;
		} else {
			return true;
		}
	}

	function save_certificateCA() {
		var url = "/system_camanager.php";
		var keylen = 2048;
		var digest_alg = 'sha256';
		var lifetime = 3650;
		var keytype = 'RSA';
		var ecname = 'prime256v1';
		var certName = $("[name='dn_certName']").val();
		var country = $("[name='dn_country']").val();
		var state = $("input[name='dn_state']").val();
		var city = $("input[name='dn_city']").val();
		var organization = $("input[name='dn_organization']").val();
		var email = $("input[name='dn_email']").val();
		var commonname = $("input[name='dn_commonname']").val();
		var save = "<?=dgettext("BluePexWebFilter", 'Save')?>";

		if(!validation_Form(certName, country, state, city, organization, email, commonname)) {
			return false;
		}

		var pars = 'descr=' + certName + '&method=internal&caref=&keylen=' + keylen + '&digest_alg=' + digest_alg + '&lifetime=' + lifetime + '&keytype=' + keytype + '&ecname=' + ecname + '&dn_country=' + country + '&dn_state=' + state + '&dn_city=' + city + '&dn_organization=' + organization + '&dn_email=' + email + '&dn_commonname=' + commonname + '&save=' + save;

		$.ajax(url, {
			type: 'post',
			data: pars,
			error: generateCA_failure,
			success: function(response) {
			make_certificate(response);
			}
		})
	}

	function generateCA_failure(error) {
		error_Message("Sorry, we could not create your CA certificate at this time.");
		return;
	}

	function make_certificate(carefID) {
		if(!carefID) {
			error_Message("Error to create Certificate.");
			return;
		}

		var url = "/system_certmanager.php";
		var keylen = 2048;
		var digest_alg = 'sha256';
		var lifetime = 3650;
		var keytype = 'RSA';
		var ecname = 'prime256v1';
		var certName = $("[name='dn_certName']").val();
		var country = $("[name='dn_country']").val();
		var state = $("input[name='dn_state']").val();
		var city = $("input[name='dn_city']").val();
		var organization = $("input[name='dn_organization']").val();
		var email = $("input[name='dn_email']").val();
		var commonname = $("input[name='dn_commonname']").val();
		var save = "<?=dgettext("BluePexWebFilter", 'Save')?>";

		var pars = 'method=internal&descr=' + certName + '&caref=' + carefID + '&keylen=' + keylen + '&digest_alg=' + digest_alg + '&keytype=' + keytype + '&ecname=' + ecname + '&type=server&lifetime=' + lifetime + '&dn_country=' + country + '&dn_state=' + state + '&dn_city=' + city + '&dn_organization=' + organization + '&dn_email=' + email + '&dn_commonname=' + commonname + '&csr_keylen=2048&csr_dn_country=' + country + '&csr_dn_state=&csr_dn_city=&csr_dn_organization=&csr_dn_email=&csr_dn_commonname=&certref=552cdbcc23617&save=' + save;

		$.ajax(
			url,
			{
				type: 'post',
				data: pars,
				error: generateCert_failure,
				success: function(response) {
					//Disable button Generate Certificate
					$("#generatecertca").prop("disabled",true);
					$("#generatecertca").css("background", "#ccc");

					// Inclued hidden form caref replaced button generatecert
					var CarefInput = "<input name=\"caref\" type=\"hidden\" value="+carefID+"  />";
					$("#iform").append(CarefInput);

					alert("<?=dgettext("BluePexWebFilter", 'Success to Certificate Generate!');?>");
					location.reload();
				}
			});
	}

	function generateCert_failure() {
		error_Message("Sorry, we could not create your certificate at this time.")
		return;
	}

	// Automation to generate CA certificate
	$('#generatecertca').on('click', function(e){
		e.preventDefault();
		show_cert_form(false);
	})

	$('#save_cert').on('click', function(e){
		e.preventDefault();
		$("#error_Message").html('');
		if (save_certificateCA()) {
			show_cert_form(true);
		}
	})

	$('#downloadca').on('click', function(e){
		e.preventDefault();
		download_ca();
	})

	function alert_crt_ssl(hide) {
		hide = hide || false;
		var dca = $('#dca')[0];
		if(hide) {
			hideInput('alert-crt-selected', true);
			return;
		}
		hideInput('alert-crt-selected', dca.selectedIndex == (dca.options.length - 1));
	}

	function show_cert_form(hide) {
		hideInput('generatecertca', !hide);
		hideInput('dn_certName', hide);
		hideInput('dn_country', hide);
		hideInput('dn_state', hide);
		hideInput('dn_city', hide);
		hideInput('dn_organization', hide);
		hideInput('dn_email', hide);
		hideInput('dn_commonname', hide);
		hideInput('save_cert', hide);
		alert_crt_ssl(!hide);
	}

	var dcamsg = "<?=dgettext("BluePexWebFilter", 'Select Certificate Authority to use when SSL interception is enabled.');?>";

	$('#dca').on('change', function() {
		if ($(this).find(":selected").index() >= "<?=$servercerts?>") {
			dcamsg = '<span class="text-danger">' + "<?=dgettext("BluePexWebFilter", 'Warning: The selected server certificate was not created as an SSL Server certificate and may not work as expected')?>" + '</span>';
		}
		$('#certtype').html(dcamsg);
	});

	$('#ssl_proxy').click(function() {
		hideInput('alert-enable-ssl', !$('#ssl_proxy').prop('checked'));
	});

	$('#dca').on('change', function() {
		alert_crt_ssl();
	});

	$('#auth_advanced').change(function() {
		if ($(this).is(":checked")) {
			$(".advanced").parents(".form-group").show();
		} else {
			$(".advanced").parents(".form-group").hide();
		}
	});

	auth_method_inputs("<?=$wf_config['authsettings']['auth_method']?>");
	show_cert_form(true);
	$('#certtype').html(dcamsg);

	// ---------- Set initial page display state ----------------------------------------------------------------------
	hideInput('alert-enable-ssl', true);
	hideInput('alert-crt-selected', true);
};
</script>
<?php include("foot.inc"); ?>
