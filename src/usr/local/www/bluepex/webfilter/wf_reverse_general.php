<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */
require_once("guiconfig.inc");
require_once("squid.inc");
require_once('squid_reverse.inc');

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['squidreversegeneral'])) {
	$config['system']['webfilter']['squidreversegeneral']['config'] = array();
}
$wf_revconfig = &$config['system']['webfilter']['squidreversegeneral']['config'][0];

if (isset($_POST['save'])) {
	squid_validate_reverse($_POST, $input_errors);
	if (empty($input_errors)) {
		$wf_revconfig = array();
		// General Config
		$wf_revconfig['reverse_interface'] = !empty($_POST['reverse_interface']) ? implode(",", $_POST['reverse_interface']) : "";
		$wf_revconfig['reverse_ip'] = $_POST['reverse_ip'];
		$wf_revconfig['reverse_external_fqdn'] = $_POST['reverse_external_fqdn'];
		$wf_revconfig['deny_info_tcp_reset'] = $_POST['deny_info_tcp_reset'];

		// HTTP Settings
		$wf_revconfig['reverse_http'] = $_POST['reverse_http'];
		$wf_revconfig['reverse_http_port'] = $_POST['reverse_http_port'];
		$wf_revconfig['reverse_http_defsite'] = $_POST['reverse_http_defsite'];

		// HTTPS Settings
		$wf_revconfig['reverse_https'] = $_POST['reverse_https'];
		$wf_revconfig['reverse_https_port'] = $_POST['reverse_https_port'];
		$wf_revconfig['reverse_https_defsite'] = $_POST['reverse_https_defsite'];
		$wf_revconfig['reverse_ssl_cert'] = $_POST['reverse_ssl_cert'];
		$wf_revconfig['reverse_ignore_ssl_valid'] = $_POST['reverse_ignore_ssl_valid'];
		if (isset($_POST['reverse_int_ca'])) {
			$wf_revconfig['reverse_int_ca'] = base64_encode(implode("\n", explode(",", $_POST['reverse_int_ca'])));
		}
		$wf_revconfig['reverse_check_clientca'] = $_POST['reverse_check_clientca'];
		$wf_revconfig['reverse_ssl_clientca'] = $_POST['reverse_ssl_clientca'];
		$wf_revconfig['reverse_ssl_clientcrl'] = $_POST['reverse_ssl_clientcrl'];

		//Security Settings
		$wf_revconfig['reverse_compatibility_mode'] = $_POST['reverse_compatibility_mode'];
		$wf_revconfig['dhparams_size'] = $_POST['dhparams_size'];
		$wf_revconfig['disable_session_reuse'] = $_POST['disable_session_reuse'];

		// OWA General Config
		$wf_revconfig['reverse_owa'] = $_POST['reverse_owa'];
		$wf_revconfig['reverse_owa_ip'] = $_POST['reverse_owa_ip'];
		$wf_revconfig['reverse_owa_activesync'] = $_POST['reverse_owa_activesync'];
		$wf_revconfig['reverse_owa_rpchttp'] = $_POST['reverse_owa_rpchttp'];
		$wf_revconfig['reverse_owa_mapihttp'] = $_POST['reverse_owa_mapihttp'];
		$wf_revconfig['reverse_owa_webservice'] = $_POST['reverse_owa_webservice'];
		$wf_revconfig['reverse_owa_autodiscover'] = $_POST['reverse_owa_autodiscover'];

		$config['system']['webfilter']['squidreversegeneral']['config'][0] = $wf_revconfig;
		$savemsg = dgettext("BluePexWebFilter", "Reverse General Settings applied successfully!");
		write_config($savemsg);
		squid_resync();
	}
}

$pgtitle = array(
	dgettext('BluePexWebFilter', 'WebFilter'), 
	dgettext('BluePexWebFilter', 'Reverse Proxy'),
	dgettext('BluePexWebFilter', 'General')
);

include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), true, '/webfilter/wf_reverse_general.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Web Servers'), false, '/webfilter/wf_reverse_peer.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Mappings'), false, '/webfilter/wf_reverse_uri.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Redirects'), false, '/webfilter/wf_reverse_redir.php');
display_top_tabs($tab_array);

$form = new Form();
$section = new Form_Section(dgettext("BluePexWebFilter", 'WF Reverse proxy General Settings'));

$ifaces = get_configured_interface_with_descr();
$ifaces["lo0"] = "loopback";
$section->addInput(new Form_Select(
	'reverse_interface',
	dgettext('BluePexWebFilter', 'Reverse Proxy Interface(s)'),
	(isset($wf_revconfig['reverse_interface']) ? explode(",", $wf_revconfig['reverse_interface']) : 'wan'),
	$ifaces,
	true
))->setHelp(dgettext('BluePexWebFilter', 'The interface(s) the reverse proxy server will bind to (usually WAN).'));

$section->addInput(new Form_Input(
	'reverse_ip',
	dgettext('BluePexWebFilter', 'User defined reverse proxy IPs'),
	'text',
	(isset($wf_revconfig['reverse_ip']) ? $wf_revconfig['reverse_ip'] : "")
))->setHelp(sprintf("%s<br />%s<br /><br /><strong><span class=\"errmsg\">%s</span> %s</strong>",
dgettext('BluePexWebFilter', "WF will additionally bind to this user defined IPs for reverse proxy operation. Useful for virtual IPs such as CARP."),
dgettext('BluePexWebFilter', "Note: Separate by semi-colons (;)."),
dgettext('BluePexWebFilter', "Important:"),
dgettext('BluePexWebFilter', "Any entry here must be a valid, locally configured IP address.")
));

$section->addInput(new Form_Input(
	'reverse_external_fqdn',
	dgettext('BluePexWebFilter', 'external FQDN'),
	'text',
	(isset($wf_revconfig['reverse_external_fqdn']) ? $wf_revconfig['reverse_external_fqdn'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'The external full qualified domain name of the WAN address.'));

$section->addInput(new Form_Checkbox(
	'deny_info_tcp_reset',
	dgettext('BluePexWebFilter', 'Reset TCP connections if request is unauthorized'),
	dgettext('BluePexWebFilter', 'If this field is checked, the reverse proxy will reset the TCP connection if the request is unauthorized.'),
	(isset($wf_revconfig['deny_info_tcp_reset'])) ? $wf_revconfig['deny_info_tcp_reset'] : 'on',
	'on'
));

$form ->add($section);

$section = new Form_Section(dgettext("BluePexWebFilter", 'WF Reverse HTTP Settings'));

$section->addInput(new Form_Checkbox(
	'reverse_http',
	dgettext('BluePexWebFilter', 'Enable HTTP reverse mode'),
	dgettext('BluePexWebFilter', 'If checked, the proxy server will act in HTTP reverse mode.'),
	(isset($wf_revconfig['reverse_http'])) ? $wf_revconfig['reverse_http'] : '',
	'on'
))->setHelp(sprintf("<strong><span class=\"errmsg\">%s</span> %s</strong>",
dgettext('BluePexWebFilter', "Important:"),
dgettext('BluePexWebFilter', "You must add a proper firewall rule with destination matching the 'Reverse Proxy Interface(s)' address.")
));

$section->addInput(new Form_Input(
	'reverse_http_port',
	dgettext('BluePexWebFilter', 'Reverse HTTP Port'),
	'text',
	(isset($wf_revconfig['reverse_http_port']) ? $wf_revconfig['reverse_http_port'] : ""),
	['placeholder' => '80']
))->setHelp(dgettext('BluePexWebFilter', 'This is the port the HTTP reverse proxy will listen on. (leave empty to use 80)'));

$section->addInput(new Form_Input(
	'reverse_http_defsite',
	dgettext('BluePexWebFilter', 'Reverse HTTP Default Site'),
	'text',
	(isset($wf_revconfig['reverse_http_defsite']) ? $wf_revconfig['reverse_http_defsite'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'This is the HTTP reverse default site. (leave empty to use the external fqdn)'));

$form ->add($section);

$section = new Form_Section(dgettext("BluePexWebFilter", 'WF Reverse HTTPS Settings'));

$section->addInput(new Form_Checkbox(
	'reverse_https',
	dgettext('BluePexWebFilter', 'Enable HTTPS reverse mode'),
	dgettext('BluePexWebFilter', 'If checked, the proxy server will act in HTTPS reverse mode.'),
	(isset($wf_revconfig['reverse_https'])) ? $wf_revconfig['reverse_https'] : '',
	'on'
))->setHelp(sprintf(dgettext('BluePexWebFilter', "%s Important: You must add a proper firewall rule with destination matching the 'Reverse Proxy Interface(s)' address. %s"), '<strong>', '</strong>'));

$section->addInput(new Form_Input(
	'reverse_https_port',
	dgettext('BluePexWebFilter', 'Reverse HTTPS Port'),
	'text',
	(isset($wf_revconfig['reverse_https_port']) ? $wf_revconfig['reverse_https_port'] : ""),
	['placeholder' => '443']
))->setHelp(dgettext('BluePexWebFilter', 'This is the port the HTTPS reverse proxy will listen on. (leave empty to use 443)'));

$section->addInput(new Form_Input(
	'reverse_https_defsite',
	dgettext('BluePexWebFilter', 'Reverse HTTPS Default Site'),
	'text',
	(isset($wf_revconfig['reverse_https_defsite']) ? $wf_revconfig['reverse_https_defsite'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'This is the HTTPS reverse default site. (leave empty to use the external fqdn)'));

$cert = array();
if (isset($config['cert'])) {
	foreach ($config['cert'] as $cert) {
		if (!isset($cert['refid'])) {
			continue;
		}
		$cert[$cert['refid']] = $cert['descr'];
	}
}
$cert['none'] = 'none';

$section->addInput(new Form_Select(
	'reverse_ssl_cert',
	dgettext('BluePexWebFilter', 'Reverse SSL certificate'),
	(isset($wf_revconfig['reverse_ssl_cert']) ? $wf_revconfig['reverse_ssl_cert'] : ""),
	$cert
))->setHelp(dgettext('BluePexWebFilter', "Choose the SSL Server Certificate here."));

$reverse_int_ca = "";
if (isset($wf_revconfig['reverse_int_ca'])) {
	$reverse_int_ca = str_replace("\n", ",", base64_decode($wf_revconfig['reverse_int_ca']));
}

$section->addInput(new Form_Textarea(
	'reverse_int_ca',
	dgettext('BluePexWebFilter', 'Intermediate CA certificate (if needed)'),
	$reverse_int_ca
))->setHelp(sprintf(dgettext('BluePexWebFilter', 'Paste a signed certificate in X.509 %sPEM format%s here.'), '<strong>', '</strong>'));

$section->addInput(new Form_Checkbox(
	'reverse_ignore_ssl_valid',
	dgettext('BluePexWebFilter', 'Ignore internal Certificate validation'),
	dgettext('BluePexWebFilter', 'If this field is checked, internal certificate validation will be ignored.'),
	(isset($wf_revconfig['reverse_ignore_ssl_valid'])) ? $wf_revconfig['reverse_ignore_ssl_valid'] : 'on',
	'on'
));

$section->addInput(new Form_Checkbox(
	'reverse_check_clientca',
	dgettext('BluePexWebFilter', 'Check Client Certificate'),
	dgettext('BluePexWebFilter', 'If this field is checked, clients need a client certificate to authenticate.'),
	(isset($wf_revconfig['reverse_check_clientca'])) ? $wf_revconfig['reverse_check_clientca'] : '',
	'on'
));

$certca = array();
if (isset($config['ca'])) {
	foreach ($config['ca'] as $cert) {
		if (!isset($cert['refid'])) {
			continue;
		}
		$certca[$cert['refid']] = $cert['descr'];
	}
}
$certca['none'] = 'none';

$section->addInput(new Form_Select(
	'reverse_ssl_clientca',
	dgettext('BluePexWebFilter', 'Client Certificate CA'),
	(isset($wf_revconfig['reverse_ssl_clientca']) ? $wf_revconfig['reverse_ssl_clientca'] : "none"),
	$certca
))->setHelp(dgettext('BluePexWebFilter', "Choose the CA used to issue client authentication certificates."));

$certcrl = array();
if (isset($config['crl'])) {
	foreach ($config['crl'] as $cert) {
		if (!isset($cert['refid'])) {
			continue;
		}
		$certcrl[$cert['refid']] = $cert['descr'];
	}
}
$certcrl['none'] = 'none';

$section->addInput(new Form_Select(
	'reverse_ssl_clientcrl',
	dgettext('BluePexWebFilter', 'Client Certificate Revocation List'),
	(isset($wf_revconfig['reverse_ssl_clientcrl']) ? $wf_revconfig['reverse_ssl_clientcrl'] : "none"),
	$certcrl
))->setHelp(sprintf("%s<br/><strong>%s</strong><br/><br/><strong><span class=\"errmsg\">%s</span></strong> %s<br/>%s<br/><br/><button class=\"btn btn-primary btn-sm\" name='refresh_crl' id='refresh_crl' type='submit' value='Refresh CRL'><i class=\"fa fa-refresh icon-embed-btn\"></i>%s</button>",
dgettext('BluePexWebFilter', "Choose the CRL used for client certificates revocation. If set to 'none', no CRL validation will be performed."),
dgettext('BluePexWebFilter', "Note: This must match the 'Client Certificate CA' selected above!"),
dgettext('BluePexWebFilter', "Important:"),
dgettext('BluePexWebFilter', "After updating the CRL in System - Cert Manager - Certificate Revocation, remember to press the 'Refresh CRL' button below."),
dgettext('BluePexWebFilter', "Otherwise, the updated CRL will not have any effect on Squid reverse proxy users!"),
dgettext('BluePexWebFilter', "Refresh CRL")
));

$form ->add($section);

$section = new Form_Section(dgettext("BluePexWebFilter", 'WF Reverse Security Settings'));

$section->addInput(new Form_Select(
	'reverse_compatibility_mode',
	dgettext('BluePexWebFilter', 'Compatibility mode'),
	(isset($wf_revconfig['reverse_compatibility_mode']) ? $wf_revconfig['reverse_compatibility_mode'] : "modern"),
	array("modern" => dgettext('BluePexWebFilter', "Modern"), "intermediate" => dgettext('BluePexWebFilter', "Intermediate"))
))->setHelp(sprintf("%s<br/>%s<br/><br/><strong><span class=\"errmsg\">%s</span> %s</strong><br/>%s <a href=\"https://wiki.mozilla.org/Security/Server_Side_TLS\" target=\"_blank\">Mozilla's</a> %s", 
dgettext('BluePexWebFilter', "The compatibility mode determines which ciphersuite and TLS versions are supported."),
dgettext('BluePexWebFilter', "Modern is for modern clients only (post FF 27, Chrome 22, IE 11 etc.). If you need to support older clients use the Intermediate setting."),
dgettext('BluePexWebFilter', "Warning:"),
dgettext('BluePexWebFilter', "Clients like IE 6 and Java 6 are not supported anymore!"),
dgettext('BluePexWebFilter', "The compatibility mode is based on"),
dgettext('BluePexWebFilter', "documentation")
));

$section->addInput(new Form_Select(
	'dhparams_size',
	dgettext('BluePexWebFilter', 'DHParams key size'),
	(isset($wf_revconfig['dhparams_size']) ? $wf_revconfig['dhparams_size'] : "2048"),
	array("2048" => "2048", "4096" => "4096")
))->setHelp(dgettext('BluePexWebFilter', "DH parameters are used for temporary/ephemeral DH key exchanges. They improve security by enabling the use of DHE ciphers."));

$section->addInput(new Form_Checkbox(
	'disable_session_reuse',
	dgettext('BluePexWebFilter', 'Disable session resumption (caching)'),
	dgettext('BluePexWebFilter', "Don't allow session reuse."),
	(isset($wf_revconfig['disable_session_reuse'])) ? $wf_revconfig['disable_session_reuse'] : '',
	'on'
))->setHelp(sprintf("%s<br/><br/><strong><span class=\"errmsg\">%s</span> %s</strong>",
dgettext('BluePexWebFilter', "The current recommendation for web servers is to enable session resumption and benefit from the performance improvement, but to restart servers daily when possible. This ensure that sessions get purged and ticket keys get renewed on a regular basis."),
dgettext('BluePexWebFilter', "Warning:"),
dgettext('BluePexWebFilter', "Disabling session resumption will increase the clients latency and the server load but can improve security for Perfect Forward Secrecy (DHE and ECDH).")
));

$form ->add($section);

$section = new Form_Section(dgettext("BluePexWebFilter", 'OWA Reverse proxy General Settings'));

$section->addInput(new Form_Checkbox(
	'reverse_owa',
	dgettext('BluePexWebFilter', 'Enable OWA reverse proxy'),
	dgettext('BluePexWebFilter', 'If this field is checked, webfilter will act as an accelerator/ SSL offloader for Outlook Web App.'),
	(isset($wf_revconfig['reverse_owa'])) ? $wf_revconfig['reverse_owa'] : '',
	'on'
));

$section->addInput(new Form_Input(
	'reverse_owa_ip',
	dgettext('BluePexWebFilter', 'CAS-Array / OWA frontend IP address'),
	'text',
	(isset($wf_revconfig['reverse_owa_ip']) ? $wf_revconfig['reverse_owa_ip'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'These are the internal IPs of the CAS-Array (OWA frontend servers). Separate by semi-colons (;).'));

$section->addInput(new Form_Checkbox(
	'reverse_owa_activesync',
	dgettext('BluePexWebFilter', 'Enable ActiveSync'),
	dgettext('BluePexWebFilter', 'If this field is checked, ActiveSync will be enabled.'),
	(isset($wf_revconfig['reverse_owa_activesync'])) ? $wf_revconfig['reverse_owa_activesync'] : '',
	'on'
));

$section->addInput(new Form_Checkbox(
	'reverse_owa_mapihttp',
	dgettext('BluePexWebFilter', 'Enable Outlook Anywhere'),
	dgettext('BluePexWebFilter', 'If this field is checked, RPC over HTTP will be enabled'),
	(isset($wf_revconfig['reverse_owa_mapihttp'])) ? $wf_revconfig['reverse_owa_mapihttp'] : '',
	'on'
));

$section->addInput(new Form_Checkbox(
	'reverse_owa_rpchttp',
	dgettext('BluePexWebFilter', 'Enable MAPI HTTP'),
	dgettext('BluePexWebFilter', 'If this field is checked, MAPI over HTTP will be enabled.'),
	(isset($wf_revconfig['reverse_owa_rpchttp'])) ? $wf_revconfig['reverse_owa_rpchttp'] : '',
	'on'
))->setHelp(sprintf(dgettext('BluePexWebFilter', "%s This feature is only available with at least Microsoft Exchange 2013 SP1 %s"), '<strong>', '</strong>'));

$section->addInput(new Form_Checkbox(
	'reverse_owa_webservice',
	dgettext('BluePexWebFilter', 'Enable Exchange WebServices'),
	dgettext('BluePexWebFilter', 'If this field is checked, Exchange WebServices will be enabled.'),
	(isset($wf_revconfig['reverse_owa_webservice'])) ? $wf_revconfig['reverse_owa_webservice'] : '',
	'on'
))->setHelp(sprintf(dgettext('BluePexWebFilter', "%s There are potential DoS side effects to its use, please avoid unless you must. %s"), '<strong>', '</strong>'));

$section->addInput(new Form_Checkbox(
	'reverse_owa_autodiscover',
	dgettext('BluePexWebFilter', 'Enable AutoDiscover'),
	dgettext('BluePexWebFilter', 'If this field is checked, AutoDiscover will be enabled.'),
	(isset($wf_revconfig['reverse_owa_autodiscover'])) ? $wf_revconfig['reverse_owa_autodiscover'] : '',
	'on'
))->setHelp(sprintf(dgettext('BluePexWebFilter', "%s You also should set up the autodiscover DNS record to point to you WAN IP. %s"), '<strong>', '</strong>'));

$form ->add($section);
print $form;
?>
<script type="text/javascript">
function http_reverse_fields(status) {
	disableInput('reverse_http_port', status);
	disableInput('reverse_http_defsite', status);
}

function https_reverse_fields(status) {
	disableInput('reverse_https_port', status);
	disableInput('reverse_https_defsite', status);
	disableInput('reverse_ssl_cert', status);
	disableInput('reverse_int_ca', status);
	disableInput('reverse_ignore_ssl_valid', status);
	disableInput('reverse_check_clientca', status);
	disableInput('reverse_ssl_clientca', status);
	disableInput('reverse_ssl_clientcrl', status);
}

function owa_reverse_fields(status) {
	disableInput('reverse_owa_ip', status);
	disableInput('reverse_owa_activesync', status);
	disableInput('reverse_owa_rpchttp', status);
	disableInput('reverse_owa_mapihttp', status);
	disableInput('reverse_owa_webservice', status);
	disableInput('reverse_owa_autodiscover', status);
}

window.onload = function(){
	$('#reverse_http').click(function() {
		http_reverse_fields(!$('#reverse_http').prop('checked'));
	});

	$('#reverse_https').click(function() {
		https_reverse_fields(!$('#reverse_https').prop('checked'));
	});

	$('#reverse_owa').click(function() {
		owa_reverse_fields(!$('#reverse_owa').prop('checked'));
	});

	// ---------- Set initial page display state ----------------------------------------------------------------------
	http_reverse_fields(!$('#reverse_http').prop('checked'));
	https_reverse_fields(!$('#reverse_https').prop('checked'));
	owa_reverse_fields(!$('#reverse_owa').prop('checked'));
};
</script>
<?php include("foot.inc");
