<?php
require_once("util.inc");
$theme = "BluePex-4.0";
?>
<style type="text/css">
#shortcuts-table tr td { background-color: #007dc5; padding:5px; border:3px solid #fff; }
#shortcuts-table tr td:hover { opacity: 0.8; }
#shortcuts-table tr td:first-child { border-left: 0; }
#shortcuts-table tr td:last-child { border-right: 0; }
#shortcuts-table tr td a { color:#fff; outline:none; }
</style>
<div class="table-responsive">
	<table class="text-center" id="shortcuts-table">
	<tbody>
		<tr>
			<td>
				<a href="/webfilter/wf_dashboard.php">
					<img src="/webfilter/themes/<?=$theme?>/img/icon_dashboard.png" border="0"/><br><?=dgettext('BluePexWebFilter','Dashboard')?>
				</a>
			</td>
			<td>
				<a href="/webfilter/wf_content_rules.php">
					<img src="/webfilter/themes/<?=$theme?>/img/icon_content.png" border="0"/><br><?=dgettext('BluePexWebFilter','Rules')?>
				</a>
			</td>
			<td>
				<a href="/webfilter/wf_realtime.php">
					<img src="/webfilter/themes/<?=$theme?>/img/icon_tools.png" border="0"/><br><?=dgettext('BluePexWebFilter','Tools')?>
				</a>
			</td>
			<td>
				<a href="/webfilter/wf_quarantine.php">
					<img src="/webfilter/themes/<?=$theme?>/img/quarantine.png" border="0"/><br><?=dgettext('BluePexWebFilter','Quarantine')?>
				</a>
			</td>
			<td>
				<a href="/webfilter/wf_dataclick_settings.php">
					<img src="/webfilter/themes/<?=$theme?>/img/icon_reports.png" border="0"/><br><?=dgettext('BluePexWebFilter','Reports')?>
				</a>
			</td>
			<td>
				<a href="/webfilter/wf_server.php">
					<img src="/webfilter/themes/<?=$theme?>/img/icon_proxyserver.png" border="0"/><br><?=dgettext('BluePexWebFilter','Server')?>
				</a>
			</td>
			<td>
				<a href="/webfilter/wf_reverse_general.php">
					<img src="/webfilter/themes/<?=$theme?>/img/icon_reverseproxy.png" border="0"/><br><?=dgettext('BluePexWebFilter','Reverse')?>
				</a>
			</td>
			<!--<td>
				<a href="/webfilter/wf_antivirus.php">
					<img src="/webfilter/themes/<?=$theme?>/img/icon_antivirus.png" border="0"/><br><?=dgettext('BluePexWebFilter','Antivirus')?>
				</a>
			</td>-->
		</tr>
	</tbody>
	</table>
</div>
<hr />
