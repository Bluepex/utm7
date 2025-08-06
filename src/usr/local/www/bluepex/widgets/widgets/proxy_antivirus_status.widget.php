<?php
/*
	proxy_antivirus_status.widget.php
	part of pfSense (https://www.pfSense.org/)
	Copyright (C) 2010 Serg Dvoriancev <dv_serg@mail.ru>
	Copyright (C) 2015 ESF, LLC
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/
require_once("guiconfig.inc");
require_once("pfsense-utils.inc");
require_once("functions.inc");
require_once("pkg-utils.inc");
if (file_exists("/usr/local/pkg/squid.inc")) {
	require_once("/usr/local/pkg/squid.inc");
} else {
	echo dgettext('BluePexWebFilter',"No squid.inc found. You must have Squid3 package installed to use this widget.");
}

define('PATH_CLAMDB', '/var/db/clamav');
define('PATH_SQUID', SQUID_BASE . '/bin/squid');
define('PATH_AVLOG', '/var/log/c-icap/virus.log');
global $clamd_path, $cicap_cfg_path, $img;
$clamd_path = SQUID_BASE . "/sbin/clamd";
$cicap_cfg_path = SQUID_LOCALBASE . "/bin/c-icap-config";
$img = array();
$img['up'] = "<img src='data:image/gif;base64,R0lGODlhCwALAIABACPcMP///yH+FUNyZWF0ZWQgd2l0aCBUaGUgR0lNUAAh+QQBCgABACwAAAAACwALAAACFYwNpwi50eKK9NA722Puyf15GjgaBQA7' title='Service running' alt='' />";
$img['down'] = "<img src='data:image/gif;base64,R0lGODlhCwALAIABANwjI////yH+FUNyZWF0ZWQgd2l0aCBUaGUgR0lNUAAh+QQBCgABACwAAAAACwALAAACFowDeYvKlsCD7sXZ5Iq89kpdFshoRwEAOw==' title='Service not running' alt='' />";

function squid_avdb_info($filename) {
	$stl = "style='padding-top: 0px; padding-bottom: 0px; padding-left: 4px; padding-right: 4px; border-left: 1px solid #999999;'";
	$r = '';
	$path = PATH_CLAMDB . "/{$filename}";
	if (file_exists($path)) {
		$handle = '';
		if ($handle = fopen($path, "r")) {
			$s = fread($handle, 1024);
			$s = explode(':', $s);
			# datetime
			$dt = explode(" ", $s[1]);
			$s[1] = strftime("%d.%m.%Y", strtotime("{$dt[0]} {$dt[1]} {$dt[2]}"));
			if ($s[0] == 'ClamAV-VDB') {
				$r .= "<tr class='listr'><td>{$filename}</td><td {$stl}>{$s[1]}</td><td {$stl}>{$s[2]}</td><td $stl>{$s[7]}</td></tr>";
			}
			fclose($handle);
		}
		return $r;
	}
}

function squid_antivirus_bases_info() {
	$db = '<table width="100%" border="0" cellspacing="0" cellpadding="1"><tbody>';
	$db .= '<tr class="vncellt" ><td>'.dgettext('BluePexWebFilter','Database').'</td><td>'.dgettext('BluePexWebFilter','Date').'</td><td>'.dgettext('BluePexWebFilter','Version').'</td><td>'.dgettext('BluePexWebFilter','Builder').'</td></tr>';
	$avdbs = array("daily.cvd", "daily.cld", "bytecode.cvd", "bytecode.cld", "main.cvd", "main.cld", "safebrowsing.cvd", "safebrowsing.cld");
	foreach ($avdbs as $avdb) {
		$db .= squid_avdb_info($avdb);
	}
	$db .= '</tbody></table>';
	return $db;
}

function squid_clamav_version() {
	global $clamd_path, $cicap_cfg_path, $img;
	if (is_executable($clamd_path)) {
		$s = (is_service_running("clamd") ? $img['up'] : $img['down']);
		$version = preg_split("@/@", shell_exec("{$clamd_path} -V"));
		$s .= "&nbsp;&nbsp;{$version[0]}";
	} else {
		$s .= "&nbsp;&nbsp;ClamAV: N/A";
	}
	if (is_executable($cicap_cfg_path)) {
		$s .= "&nbsp;&nbsp;";
		$s .= (is_service_running("c-icap") ? $img['up'] : $img['down']);
		$s .= "&nbsp;&nbsp;C-ICAP " . shell_exec("{$cicap_cfg_path} --version");
	} else {
		$s .= "&nbsp;&nbsp;C-ICAP: N/A";
	}
	if (file_exists("/usr/local/www/squid_clwarn.php")) {
		preg_match("@(VERSION.*).(\d{1}).(\d{2})@", file_get_contents("/usr/local/www/squid_clwarn.php"), $squidclamav_version);
		$s .= "+&nbsp;&nbsp;SquidClamav " . str_replace("'", "", strstr($squidclamav_version[0], "'"));
	} else {
		$s .= "+&nbsp;&nbsp;SquidClamav: N/A";
	}
	return $s;
}

function squid_avupdate_status() {
	global $clamd_path;
	$s = "N/A";

	if (is_executable($clamd_path)) {
		$lastupd = preg_split("@/@", shell_exec("{$clamd_path} -V"));
		$s = $lastupd[2];
	}
	return $s;
}

// Compose the table contents and pass it back to the ajax caller
if ($_REQUEST && $_REQUEST['ajax']) {

	$rows = array("Date-Time", "Source", "Virus", "URL");

	if (file_exists(PATH_AVLOG)) {
		$log = file_get_contents(PATH_AVLOG);
		$detecteds = substr_count(strtolower($log), "virus found");

		print("<center><h5>".  sprintf(dgettext('BluePexWebFilter',"%s Virus(es) Detecteds."), $detecteds) . "</h5></center>");
		print("<table class=\"table\">");

		if ($detecteds != 0) {
			print(	"<tr>");

			foreach ($rows as $row) {
				print(		"<th>" . gettext($row) . "</th>");
			}

			print(		"</tr>");

			$logarr = fetch_log(PATH_AVLOG);
			foreach ($logarr as $logent) {
				// Split line by delimiter
				$logline = preg_split("/\|/", $logent);

				// Apply time format
				$logline[0] = date("d.m.Y H:i:s", strtotime($logline[0]));

				// Word wrap the URL
				$logline[3] = htmlentities($logline[3]);
				$logline[3] = html_autowrap($logline[3]);

				print("<tr>");
				print( "<td>{$logline[0]}</td>");
				print( "<td>{$logline[4]}</td>");
				print( "<td style='color:#ff0000'>{$logline[2]}</td>");
				print( "<td>{$logline[3]}</td>");
				print( "</tr>");
			}
		}

		print(	"</table>");
		print("<center>" . sprintf(dgettext('BluePexWebFilter',"Shows only the last 5 results. %s See more...%s"), "<a href='webfilter/wf_antivirus_status.php#virusdetections'>", "</a>") ."</center>");
	} else {
		print("<center>" . dgettext('BluePexWebFilter',"no virus detecteds (no log exists)") . "</center>");
	}
	exit;
}

// Show Squid Logs
function fetch_log($log) {
	$maxlines = '5';
	$parser = "";
	exec("/usr/bin/tail -r -n {$maxlines} {$log} {$parser}", $logarr);

	return $logarr;
};

function html_autowrap($cont) {
	// split strings
	$p = 0;
	$pstep = 25;
	$str = $cont;
	$cont = '';
	for ($p = 0; $p < strlen($str); $p += $pstep) {
		$s = substr($str, $p, $pstep);
		if (!$s) {
			break;
		}
		$cont .= $s . "<wbr />";
	}
	return $cont;
}

?>

<table class="table table-striped table-hover">
	<tbody>
		<tr>
			<td class="vncellt"><?= dgettext('BluePexWebFilter',"Antivirus Bases") ?></td>
			<td class="listr" width="75%">
				<?php echo squid_antivirus_bases_info(); ?>
			</td>
		</tr>
		<tr>
			<td class="vncellt"><?= dgettext('BluePexWebFilter',"Last Update") ?></td>
			<td class="listr" width="75%">
				<?php echo squid_avupdate_status(); ?>
			</td>
		</tr>
	</tbody>
</table>
<div id="CICIAPVirusView"></div>

<script type="text/javascript">
//<![CDATA[
	function get_virus_logs() {
		var ajaxRequest;

		ajaxRequest = $.ajax({
				url: "/widgets/widgets/proxy_antivirus_status.widget.php",
				type: "post",
				data: { ajax: "ajax"}
			});

		// Deal with the results of the above ajax call
		ajaxRequest.done(function (response, textStatus, jqXHR) {
			$('#CICIAPVirusView').html(response);

			// and do it again
			setTimeout(get_virus_logs, 25000);
		});
	}

	events.push(function(){
		get_virus_logs();
	});
//]]>
</script>
