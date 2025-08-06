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
 
require_once("globals.inc");
require_once("config.inc");
require_once("pfsense-utils.inc");
require_once("squid.inc");
global $config;
if($config['theme'] <> "" && (is_dir($g["www_path"].'/themes/'.$config['theme'])))
	$g['theme'] = $config['theme'];
else
	$g['theme'] = "pfsense";
$settings = $config['system']['webfilter']['webfilter']['config'][0]['authsettings'];
$successful = NULL;
$input_errors = array();
$localauth = true;
if($settings['auth_method'] == 'local') {
	if($_POST) {
		if(empty($_POST['username']))
			$input_errors[] = dgettext("BluePexWebFilter","Enter a valid username");
		if(empty($_POST['currpassword']))
			$input_errors[] = dgettext("BluePexWebFilter","Enter your current password");
		if(empty($_POST['newpassword']))
			$input_errors[] = dgettext("BluePexWebFilter","Enter your new password");
		if(empty($_POST['rptnewpassword']))
			$input_errors[] = dgettext("BluePexWebFilter","Repeat your new password");
		$currpassword = crypt($_POST['currpassword'], base64_encode($_POST['currpassword']));
		$fpasswd = file_get_contents(SQUID_PASSWD);
		$fpasswd = explode("\n",$fpasswd);
		foreach($fpasswd as &$line) {
			$user = explode(":",$line);
			if($user[0] == $_POST["username"]) {
				if($user[1] != $currpassword) {
					$input_errors[] = dgettext("BluePexWebFilter","Current password doesn't match.");
					break;
				} elseif($_POST["newpassword"] != $_POST["rptnewpassword"]) {
					$input_errors[] = dgettext("BluePexWebFilter","'New password' and 'Repeat new password' fields don't match.");
					break;
				} else {
					$user[1] = crypt($_POST['newpassword'], base64_encode($_POST['newpassword']));
					$user = implode(":",$user);
					$line = $user;
					$username = $_POST['username'];
					$newpassword = $_POST['newpassword'];
					$cont = 0;
					foreach($config['system']['webfilter']['squidusers']['config'] as $users) {
					if ($username == $users["username"]) {
						$config['system']['webfilter']['squidusers']['config'][$cont]['password'] = $newpassword;
						write_config('Set new password in squid users of the webfilter');
						break;
					}
					$cont++;
					}
					$successful = dgettext("BluePexWebFilter","Your password was successfully changed");
				}
			}
		}
		if(!$successful && !$input_errors)
			$input_errors[] = dgettext("BluePexWebFilter","User {$_POST['username']} not found");
		$fpasswd = implode("\n",$fpasswd);
		file_put_contents(SQUID_PASSWD,$fpasswd);
	}
} else {
	$localauth = false;
	$successful = dgettext("BluePexWebFilter","If you want to change your password, please contact your administrator.");
}
function print_message($msg) {
	global $g;
	echo <<<EOF
	<table style=background class='infobox' id='redboxtable'>
		<tr>
			<td>
				<div class='infoboxnp' id='redbox'>
					<table style=background class='infoboxnptable2'>
						<tr>
							<td class='infoboxnptd'>
								&nbsp;&nbsp;&nbsp;<img class='infoboxnpimg' src="/themes/{$g['theme']}/images/icons/icon_exclam.gif" >
							</td>
							<td class='infoboxnptd2'>
								<b>{$msg}</b>
							</td>
						</tr>
					</table>
				</div>
				<div>
					<p/>
				</div>
			</td>
		</tr>
	</table>
EOF;
}
function print_input_errors($input_errors) {
	global $g;
	print <<<EOF
	<div id='inputerrorsdiv' name='inputerrorsdiv'>
	<p>
	<table style=background border="0" cellspacing="0" cellpadding="4" width="100%">
	<tr>
		<td class="inputerrorsleft">
			<img src="/themes/{$g['theme']}/images/icons/icon_error.gif">
		</td>
		<td class="inputerrorsright">
			<span class="errmsg"><p>
				The following input errors were detected:
				<ul>
EOF;
		foreach ($input_errors as $ierr) {
			echo "<li>" . htmlspecialchars($ierr) . "</li>";
		}
	print <<<EOF2
				</ul>
			</span>
		</td></tr>
	</table>
	</div>
	</p>&nbsp;<br>
EOF2;
	
}
$pgtitle = "BluePex Web Filter: " . dgettext("BluePexWebFilter","Change Password");
?>
<html>
	<head>
		<title><?php echo($config['system']['hostname'] . "." . $config['system']['domain'] . " - " . $pagetitle); ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<link rel="shortcut icon" href="/themes/<?php echo $g['theme']; ?>/images/icons/favicon.ico" />
        	<link rel="stylesheet" href="/themes/<?php echo $g['theme']; ?>/all.css" media="all" />
		<script type="text/javascript" src="/javascript/scriptaculous/prototype.js"></script>
		<script type="text/javascript" src="/javascript/scriptaculous/scriptaculous.js"></script>
		<script type="text/javascript">
			var theme = "<?php echo $g['theme']; ?>";
		</script>
		<?php echo "\t<script type=\"text/javascript\" src=\"/themes/{$g['theme']}/loader.js\"></script>\n"; ?>
		<script>document.observe('dom:loaded', function() { $('usernamefld').focus(); });</script>
	</head>
	<body>
		<div id="wrapper">
			<div id="header">
				<div id="header-left"><a href="index.php" id="status-link"><img src="/themes/<?= $g['theme']; ?>/images/transparent.gif" border="0"></a></div>
				<div id="header-right">
					<div class="container">
						<div class="left">webConfigurator</div>
						<div class="right">
							<div id="hostname">
								<? print $config['system']['hostname'] . "." . $config['system']['domain']; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="content">
				<div id="left"><div id="navigation" style="z-index:1000">&nbsp;</div></div>
				<div id="right">
					<div>
						<span class="pgtitle"><a href="<?= $_SERVER['SCRIPT_NAME'] ?>"><?php echo $pgtitle;?></a></span>
					</div>
					<br />
					<?php if ($input_errors) print_input_errors($input_errors); ?>
					<?php if ($successful) print_message($successful); ?>
					<form id="iform" name="login_iform" method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
						<table style=background width="100%" border="0" cellpadding="6" cellspacing="0">
							<tr>
								<td colspan="2" valign="top" class="listtopic"><?=dgettext("BluePexWebFilter","Change proxy user password");?></td>
							</tr>	
							<tr>
								<td width="22%" valign="top" class="vncellreq"><?php echo dgettext("BluePexWebFilter","Username:");?></td>
								<td width="78%" class="vtable">
									<input id="username" type="text" name="username" value="<?php echo $_POST['username'];?>" class="formfld user" tabindex="1" <?php echo (!$localauth ? "disabled":""); ?>/>
								</td>
							</tr>
							<tr>
								<td width="22%" valign="top" class="vncellreq"><?php echo dgettext("BluePexWebFilter","Current password:");?></td>
								<td width="78%" class="vtable">
									<input id="currpassword" type="password" name="currpassword" class="formfld pwd" tabindex="2" <?php echo (!$localauth ? "disabled":""); ?>/>
								</td>
							</tr>
							<tr>
								<td width="22%" valign="top" class="vncellreq"><?php echo dgettext("BluePexWebFilter","New password:");?></td>
								<td width="78%" class="vtable">
									<input id="newpassword" type="password" name="newpassword" class="formfld pwd" tabindex="3" <?php echo (!$localauth ? "disabled":""); ?>/>
								</td>
							</tr>
							<tr>
								<td width="22%" valign="top" class="vncellreq"><?php echo dgettext("BluePexWebFilter","Repeat new password:");?></td>
								<td width="78%" class="vtable">
									<input id="rptnewpassword" type="password" name="rptnewpassword" class="formfld pwd" tabindex="4" <?php echo (!$localauth ? "disabled":""); ?>/>
								</td>
							</tr>
							<tr>
								<td width="22%" valign="top">&nbsp;</td>
								<td width="78%">
									&nbsp;<br>&nbsp;
									<input name="Submit" type="submit" class="formbtn" value="<?=dgettext("BluePexWebFilter","Save"); ?>" <?php echo (!$localauth ? "disabled":""); ?>>
									<input type="reset" class="formbtn" value="<?=dgettext("BluePexWebFilter","Clear"); ?>" <?php echo (!$localauth ? "disabled":""); ?>>
								</td>
							</tr>
						</table>
					</form>
				</div> <!-- Right DIV -->
	
			</div> <!-- Content DIV -->
	
			<div id="footer">
				<a target="_blank" href="<?=$g['product_website_footer']?>" class="redlnk"><?=$g['product_name']?></a> is &copy;
				 <?=$g['product_copyright_years']?> by <a href="<?=$g['product_copyright_url']?>" class="tblnk"><?=$g['product_copyright']?></a>. All Rights Reserved.
				[<a href="/license.php" class="tblnk">view license</a>] 
			</div> <!-- Footer DIV -->
		</div> <!-- Wrapper Div -->
		<?php
			$javascript = "/usr/local/www/themes/{$g['theme']}/bottom-loader.js";
			if(file_exists($javascript)) {
				echo "<script type=\"text/javascript\">";
				include($javascript);
				echo "\n</script>\n";
			}
		?>
	</body>
</html>
