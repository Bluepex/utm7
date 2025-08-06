<?php
# ====================================================================
# Copyright (C) BluePex Security Solutions - All rights reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# Written by Marcos Claudiano <marcos.claudiano@bluepex.com>, 2024
#
# ====================================================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Microsoft SSO</title>
</head>
<body>

<h1>Microsoft SSO</h1>

<?php

include('config.php');
require_once('config.inc');

require "vendor/autoload.php";

use myPHPnotes\Microsoft\Auth;
use myPHPnotes\Microsoft\Handlers\Session;
use myPHPnotes\Microsoft\Models\User;

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
	if (!isset($instance['server']['authsettings']['auth_method']) ||
	     $instance['server']['authsettings']['auth_method'] != "entraid") {
		continue;
	}

	// Find the corresponding 'server' tag
	$settings = $instance['server'];
	$ssl_ifaces = explode(",", $settings['active_interface']);

	foreach ($ssl_ifaces as $s_iface) { $s_iface_ip = squid_get_real_interface_address($s_iface); }

	break;
}

define(ENV_ENTRAID, '/usr/local/bin/.env');

$data_env = [
    "AUTHORITY" => "",
    "CLIENT_ID" => "",
    "CLIENT_SECRET" => "",
    "USERNAME" => "",
    "PASS" => "",
    "CALLBACK" => ""
];

if (file_exists(ENV_ENTRAID)) {
	$env = explode("\n",file_get_contents(ENV_ENTRAID));

	foreach (array_keys($data_env) as $keys_env) {
		foreach ($env as $values_env) {
			if (strpos($values_env,$keys_env) !== false) {
				$data_env[$keys_env] = trim(explode("{$keys_env}=",$values_env)[1]);
			}
		}
	}
}

$tenant = $data_env["AUTHORITY"];
$client_id = $data_env["CLIENT_ID"];
$client_secret = $data_env["CLIENT_SECRET"];
$callback = $data_env["CALLBACK"];
$scopes = ["User.Read"];

$auth = new Auth(
    $tenant,
    $client_id,
    $client_secret,
    $callback,
    $scopes
);

$tokens = $auth->getToken(
    $_REQUEST['code'],
    Session::get("state")
);

$accessToken = $tokens->access_token;

$auth->setAccessToken($accessToken);

$user = new User;

$unique_identifier = hash('sha256', session_id() . $accessToken);

file_put_contents('/var/log/squid/authenticated_hosts', $unique_identifier . "\n", FILE_APPEND);

echo "Logado com Sucesso: " . "<br>";
echo "Name: "  . $user->data->getDisplayName() . "<br>";
echo "Email: " . $user->data->getUserPrincipalName() . "";
$name = $user->data->getDisplayName();
$email = $user->data->getUserPrincipalName();

echo "<p>Redirecionando página...</p>";

$redirect_address = "http://{$s_iface_ip[0]}:59789/auth/valid.php?f=authenticated_users&e=" . urlencode($email) . "&u=" . urlencode($name);

?>
<script>
window.location.href = "<?=$redirect_address?>";
</script>

<hr>

<a href="logout.php">Login with a Different Account</a>

</body>
</html>
