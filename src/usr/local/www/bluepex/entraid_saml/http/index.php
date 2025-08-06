<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Marcos V. Claudiano <marcos.claudiano@bluepex.com>, 2024
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

/* if port is empty lets rely on the protocol selection */
$port = "";
if (isset($config['system']['webgui']['port']) &&
    !empty($config['system']['webgui']['port'])) {
	$port = $config['system']['webgui']['port'];
}
if ($port == "") {
	$port = ($config['system']['webgui']['protocol'] == "http") ? 80 : 443;
}

$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];

$url_utm = "https://{$s_iface_ip[0]}:{$port}/entraid_saml/signup.php";

?>

<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
		<title>BluePex WebFilter - Conteúdo Bloqueado!</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link href="../../../../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<script src="../../../../vendor/jquery/jquery-1.12.0.min.js"></script>
		<script src="../../../../vendor/bootstrap/js/bootstrap.min.js"></script>
		<style>
			html { height: 100%; box-sizing: border-box; }
			body { position: relative; margin: 0; min-height: 100%; }
			#wrap { min-height:100%; position:relative; padding-bottom:100px; }
			#content { margin-top:-300px; margin-left:50px; width: 450px; font-size:16px; }
			#content h1 { color:#007dc5; }
			#content p { text-align: justify; text-justify: inter-word; }
			#content textarea { border:2px solid #333; border-radius:0 }
			#content button { border-radius:0; width:100% }
			#img-cloud { background: url("../../../../images/cloud-access.png"); width:100%; height:515px; background-size: 100%; background-repeat: no-repeat; }
			#footer { position: absolute; right: 0; bottom: 0; left: 0; padding: 25px; background-color: #007dc5; text-align: center; margin-top:10px; min-height:80px; }
			@media only screen and (max-width : 768px) {
				body { background: #fff; }
				#content { top:inherit; position:inherit; margin:0; width: 100%; font-size:14px; }
				#img-cloud { height:240px }
			}
			@media only screen and (max-width : 480px) { #img-cloud { height:150px } }
			@media only screen and (max-width : 320px) { #img-cloud { height:100px } }
		</style>
	</head>
	<body>
		<div id="wrap" class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div id="img-cloud"></div>
					</div>
					<div id="content">
						<?php
						$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'];
						?>
						<h1>Logar com Microsoft SSO em: <?php echo $ip; ?></h1>
						<br>
						<a href="<?php echo $url_utm;?>">Sign Up using your Microsoft Account</a>
						<table>
							<tbody>
								<tr></tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div id="footer">
			<img src="../../../../images/logotipo-tecnology.png" />
		</div>
	</body>
</html>
