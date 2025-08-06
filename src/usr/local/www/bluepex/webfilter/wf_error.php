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
require_once("config.inc");
require_once("util.inc");

$timestamp = strftime('%c');
$server    = dgettext('BluePexWebFilter',"BluePex Web Filter running on ") . "{$_SERVER['SERVER_NAME']} ($timestamp)";
?>
<!DOCTYPE html>
<html>
<head>
	<title><?=dgettext('BluePexWebFilter', 'Internal Error!')?></title>
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
		#img-cloud { background: url("/webfilter/themes/BluePex-4.0/img/cloud-error.png"); width:100%; height:515px; background-size: 100%; background-repeat: no-repeat; }
		#footer { position: absolute; right: 0; bottom: 0; left: 0; padding: 25px; background-color: #007dc5; text-align: center; margin-top:10px; min-height:80px; }
		@media only screen and (max-width : 768px) {
			body { background: #fff; }
			#content { top:inherit; position:inherit; margin:0; width: 100%; font-size:14px; }
			#img-cloud { height:240px }
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
				<h1><?php echo dgettext('BluePexWebFilter',"Internal Error!"); ?></h1>
				<p><?php echo dgettext('BluePexWebFilter',"The page you tried to access can not be opened because BluePex Web Filter encountered an internal error. Please contact your system administrator for further information."); ?></p>
			</div>
		</div>
	</div>
</div>
<div id="footer">
	<img src="/webfilter/themes/BluePex-4.0/img/logotipo-tecnology.png" />
</div>
</body>
</html> 
