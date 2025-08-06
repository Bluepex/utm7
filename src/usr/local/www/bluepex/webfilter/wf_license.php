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
 
$timestamp = strftime('%c');
$server_id = dgettext('BluePexWebFilter',"BluePex Web Filter running on") . " {$_SERVER['SERVER_NAME']} ($timestamp)";
?>
<html>
<head>
<title><?php echo dgettext('BluePexWebFilter',"License issue"); ?></title>
<style type="text/css">
<!--
body {
	background: url('bg.png') repeat fixed;
	font-family: sans-serif;
	font-size: 14px;
	margin: 0px auto;
	width: 610px;
}
#wrapper {
	background: url('bgw.png') repeat-y;
	margin: 0px auto;
	width: 574px;
	padding: 0px 18px;
}
#header {
	width: 610px;
	margin-left: -18px;
	text-align: center;
}
#content {
	margin: 25px 0px;
}
#footer {
	background: url('bgf.png') no-repeat;
	width: 574px;
	height: 42px;
	margin-left: -18px;
	padding: 0px 18px;
	padding-top: 0px;
	color: #77A;
	font-weight: bold;
	text-align: center;
}
h1 {
	color: #77A;
	margin-bottom: 18px;
}
th {
	text-align: left;
}
-->
</style>
</head>
<body>
	<div id='wrapper'>
		<div id='header'>
			<img src="logobig.png" />
			<h1><?php echo dgettext('BluePexWebFilter',"License issue"); ?></h1>
		</div>
		<div id='content'>
			<p><?php echo dgettext('BluePexWebFilter',"The page you tried to access can not be opened because the BluePex Web Filter content filter license has expired or the server is unable to authenticate. Please contact your system administrator for further information."); ?></p>
		</div>
		<div id="footer">
			<p><?=$server_id?></p>
		</div>
	</div>
</body>
</html>
