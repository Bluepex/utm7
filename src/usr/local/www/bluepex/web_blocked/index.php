 <?php
 /* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Neriberto C. Prado <neriberto.prado@bluepex.com>, 2017
 *
 * ====================================================================
 *
 */
require_once("config.inc");

function endsWith($haystack, $needle) {
	$length = strlen($needle);
	if ($length == 0) {
		return true;
	}

	return (substr($haystack, -$length) === $needle);
}

function is_blacklisted($option) {
	global $config, $g;

	if (!isset($config[$option]['enable']) || ($config[$option]['enable'] != "on") ||
	    !isset($config[$option]['services']['item']) || empty($config[$option]['services']['item'])) {
		return;
	}

	$items = $config[$option]['services']['item'];

	$ar_result = array("found" => false, "module" => "", "rule" => "");

	foreach($items as $ocs) {
		if($ar_result["found"] || $ocs['action'] !== "block") {
			continue;
		}

		$filepath = $g['www_path'] . "/one_click/lists/{$ocs['name']}";
		$a_names = array($filepath . ".urls", $filepath . ".ips");

		foreach($a_names as $filename) {
			if (!file_exists($filename) || (filesize($filename) <= 0)) {
				continue;
			}

			$file = fopen($filename, "r");
			if ($file) {
				while(($line = fgets($file)) !== false) {
					$domain = trim($line);
					if ($domain !== '') {
						$host = $_SERVER['HTTP_HOST'];
						if (filter_var($host, FILTER_VALIDATE_IP)) {
							$a_host = explode(".", $host);
							$host = $a_host[0] . "." . $a_host[1] . "." . $a_host[2];
							$a_host = null;
							if (strpos($domain, $host) === 0) {
								$ar_result["found"] = true;
								$ar_result["module"] = $option;
								$ar_result["rule"] = $ocs['name'];
								break;
							}
						} else {
							if (endsWith($host, $domain)) {
							$ar_result["found"] = true;
							$ar_result["module"] = $option;
							$ar_result["rule"] = $ocs['name'];
							break;
							}
						}
					}
				}
				fclose($file);
			}
			$file = null;
		}
		$a_names = null;
		$filepath = null;
	}
	return $ar_result;
}

$result = is_blacklisted('one_click');
$gif_name = '"velocimetro-baixo.gif"';

if (!$result["found"]) {
	$result = is_blacklisted('active_protection');
}

if ($result["found"]) {

	switch($result['rule']) {
	case "ransomware_distribution":
		$gif_name = '"velocimetro-critico.gif"';
		break;
	case "ransomware_payment":
	case "ransomware_cc":
		$gif_name = '"velocimetro-alto.gif"';
		break;
	case "phishing":
		$gif_name = '"velocimetro-baixo.gif"';
		break;
	}

	$rules = explode("_", $result['rule']);
	foreach($rules as $rule) {
		$result['rule'] = str_replace($rule, ucfirst($rule), $result['rule']);
	}

	$result['rule'] = str_replace("_", " ", $result['rule']);

	if ($result["module"] == "one_click") {
		$result['module'] = gettext("BluePex OneClick");
		$pgtitle = array(gettext("BluePex OneClick"), gettext("Blocked Page"));
	} else {
		$result['module'] = gettext("BluePex Active Protection");
		$pgtitle = array(gettext("BluePex Active Protection"), gettext("Blocked Page"));
	}
	?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title><?php echo $pgtitle[0] . " - " . $pgtitle[1]; ?></title>
	<meta name="viewport" content="witdh=device-width, initial-scale=1">
	<link rel="stylesheet" href="vendor/bootstrap/css/bootstrap.min.css">
	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>

<style type="text/css">
		#content {
			margin-top: -280px;
			font-size: 16px;
		}

		#content h1 {
			color: #007dc5;
		}

		#content p {
			text-align: justify;
			text-justify: inter-word;
		}

		#content textarea {
			border: 2px solid #333;
			border-radius: 0
		}
		#img-cloud {
			background: url("cloud.png");
			width: 100%;
			height: 515px;
			background-size: 100%;
			background-repeat: no-repeat;
		}

		#img-velocimetro {
			background: url(<?php echo $gif_name; ?>);
			width: 540px;
			height: 476px;
			background-size: 50%;
			background-repeat: no-repeat;
		}

	@media screen and (max-width: 480px) {
		#img-cloud {
			height: 350px;
			}
		#content {
			font-size: 12px;
			}
		#content h1 {
			font-size: 12px;
		}

		#content p {
			font-size: 12px;
		}
		}

	</style>
</head>
<body>
	<div id="img-cloud"></div>
	<div id="content" class="container">
		<div class="row">
			<div class="col-md-6 col-sm-6">
				<h1><?php echo gettext("Blocked Page!"); ?></h1>
				<h2><?php echo gettext("Rule: ") . $result['rule']; ?></h2>
				<p><?php echo gettext("The page you tried to access was blocked by <b>") . $result['module']. gettext("</b>. Please contact the BluePex Support Team for further information."); ?></p>
				<button type="button" class="btn btn-default" onclick="javascript:window.history.back();"><?=gettext('Voltar');?></button>
			</div>
			<div class="col-md-6 col-sm-6">
				<div id="img-velocimetro"></div>
			</div>
		</div>
	</div>

</body>
</html>
	<?php
} else {
	?>

	<?php
}
?>