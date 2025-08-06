<?php
 /* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Desenvolvimento <desenvolvimento@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */
require_once('nf_defines.inc');
require_once('nf_util.inc');
require_once('auth.inc');
require_once("cg2_util.inc");

$VERSION = '6.10';
$g['theme'] = "BluePex-4.0";
$url = $_GET['blocked_url'];
$timestamp = strftime('%c');
$server_id = dgettext('BluePexWebFilter', "BluePex Web Filter running on ") . "{$_SERVER['SERVER_NAME']} ($timestamp)";

$url = $_REQUEST['url'];
$virus = ($_REQUEST['virus'] ? $_REQUEST['virus'] : $_REQUEST['malware']);
$source = preg_replace("@/-@", "", $_REQUEST['source']);
$user = $_REQUEST['user'];

$TITLE_VIRUS = dgettext('BluePexWebFilter',"Bluepex Antivírus $VERSION: Virus detected!");
$subtitle = dgettext('BluePexWebFilter','Virus name');
$errorreturn = dgettext('BluePexWebFilter','This file cannot be downloaded.');
$urlerror = dgettext('BluePexWebFilter','contains a virus');
if (preg_match("/Safebrowsing/", $virus)) {
	$TITLE_VIRUS = dgettext('BluePexWebFilter',"Bluepex Antivírus $VERSION: Unsafe Browsing detected");
	$subtitle = dgettext('BluePexWebFilter','Malware / phishing type');
	$urlerror = dgettext('BluePexWebFilter','is listed as suspicious');
	$errorreturn = dgettext('BluePexWebFilter','This page cannot be displayed');
}

// Remove clamd infos
$vp[0]="/stream: /";
$vp[1]="/ FOUND/";
$vr[0]="";
$vr[1]="";

$virus = preg_replace($vp, $vr, $virus);
error_log(date("Y-m-d H:i:s") . " | VIRUS FOUND | " . $virus . " | " . $url . " | " . $source . " | " . $user . "\n", 3, "/var/log/c-icap/virus.log");
?>
<!DOCTYPE html>
<html>
<head>
	<title><?=dgettext('BluePexWebFilter', 'Antivirus - Blocked Content!')?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/vendor/jquery/jquery-1.12.0.min.js"></script>
	<script src="/vendor/bootstrap/js/bootstrap.min.js"></script>
	<style>
		html { height: 100%; box-sizing: border-box; }
		body { position: relative; margin: 0; min-height: 100%; }
		#wrap { min-height:100%; position:relative; padding-bottom:100px; }
		#content { margin-top:-300px; margin-left:50px; width: 450px; font-size:16px; }
		#content h1 { color:#007dc5; }
		#content p { text-align: justify; text-justify: inter-word; }
		#content textarea { border:2px solid #333; border-radius:0 }
		#content button { border-radius:0; width:100% }
		#img-cloud { background: url("/webfilter/themes/BluePex-4.0/img/cloud-virus.png"); width:100%; height:515px; background-size: 100%; background-repeat: no-repeat; }
		#footer { position: absolute; right: 0; bottom: 0; left: 0; padding: 25px; background-color: #007dc5; text-align: center; margin-top:10px; min-height:80px; }
		@media only screen and (max-width : 768px) {
			body { background: #fff; }
			#content { top:inherit; position:inherit; margin:0; width: 100%; font-size:14px; }
			#img-cloud { height:200px }
		}
		@media only screen and (max-width : 480px) {
			#img-cloud { height:150px }
		}
		@media only screen and (max-width : 320px) {
			#img-cloud { height:100px }   
		}
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
				<h1><?=dgettext('BluePexWebFilter', 'Virus Detected!')?></h1>
				<h3><?=dgettext('BluePexWebFilter', 'Blocked Content!')?></h3>
				<br />
				<p><?=dgettext('BluePexWebFilter','The Access to this page was denied because the virus below was detected.')?></p>
				<br />
				<table>
				<tbody>
					<tr>
						<td><b><?=dgettext('BluePexWebFilter','VIRUS NAME')?>: </b></td>
						<td><font color="#FF0000"><?=$virus?></font></td>
					</tr>
					<tr>
						<td><b><?=dgettext('BluePexWebFilter','BLOCKED URL')?>: </b></td>
						<td><?=$url?></td>
					</tr>
					<tr>
						<td><b><?=dgettext('BluePexWebFilter','SOURCE ')?>: </b></td>
						<td><?=$source?> <?php if (!empty($user) && $user !== "-") echo " / " . $user?></td>
					</tr>
				</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<div id="footer">
	<img src="/webfilter/themes/BluePex-4.0/img/logotipo-tecnology.png" />
</div>
</body>
</html> 
