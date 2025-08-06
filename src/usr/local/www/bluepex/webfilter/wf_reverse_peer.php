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

if (!isset($config['system']['webfilter']['squidreversepeer'])) {
	$config['system']['webfilter']['squidreversepeer']['config'] = array();
}
$wf_revPeerConf = $config['system']['webfilter']['squidreversepeer']['config'];

if (isset($config['system']['webfilter']['squidreversepeer']) && !isset($config['system']['webfilter']['squidreversepeer']['config'])) {
	unset($config['system']['webfilter']['squidreversepeer']);
	write_config("Remove tag if there are no rules set - Webfilter");
	header("Location: wf_reverse_peer.php");
	exit;
}

if ($_GET['act'] == "del") {
	if (isset($_GET['id']) && is_numericint($_GET['id'])) {
		$wf_revPeerConf = &$config['system']['webfilter']['squidreversepeer']['config'];
		if (isset($wf_revPeerConf[$_GET['id']])) {
			unset($wf_revPeerConf[$_GET['id']]);
			$savemsg = dgettext("BluePexWebFilter", "WF reserver web server removed successfully!");
			write_config($savemsg);
			header("Location: wf_reverse_peer.php");
			exit;
		}
	} else {
		$input_errors = dgettext("BluePexWebFilter", "Could not to remove the WF reverse web server!");
	}
}

$pgtitle = array(
	dgettext('BluePexWebFilter', 'WebFilter'), 
	dgettext('BluePexWebFilter', 'Reverse Proxy'),
	dgettext('BluePexWebFilter', 'Web Servers')
);

include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), false, '/webfilter/wf_reverse_general.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Web Servers'), true, '/webfilter/wf_reverse_peer.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Mappings'), false, '/webfilter/wf_reverse_uri.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Redirects'), false, '/webfilter/wf_reverse_redir.php');
display_top_tabs($tab_array);
?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('Reverse Proxy Server: Web Servers')?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
				<thead>
					<tr>
						<th><?=dgettext("BluePexWebFilter", "Status"); ?></th>
						<th><?=dgettext("BluePexWebFilter", "Alias"); ?></th>
						<th><?=dgettext("BluePexWebFilter", "IP Address"); ?></th>
						<th><?=dgettext("BluePexWebFilter", "Port")?></th>
						<th><?=dgettext("BluePexWebFilter", "Protocol")?></th>
						<th><?=dgettext("BluePexWebFilter", "Description")?></th>
						<th><?=dgettext("BluePexWebFilter", "Actions")?></th>
					</tr>
				</thead>
				<tbody>
				<?php
					$i = 0;
					if (isset($wf_revPeerConf)) :
						foreach ($wf_revPeerConf as $peer) :
				?>
					<tr>
						<td>
							<?=(isset($peer['enable']) && $peer['enable'] == 'on') ? dgettext('BluePexWebFilter', 'Enabled') : dgettext('BluePexWebFilter', 'Disabled');?>
						</td>
						<td>
							<?=htmlspecialchars($peer['name'])?>
						</td>
						<td>
							<?=htmlspecialchars($peer['ip'])?>
						</td>
						<td>
							<?=htmlspecialchars($peer['port'])?>
						</td>
						<td>
							<?=htmlspecialchars($peer['protocol'])?>
						</td>
						<td>
							<?=htmlspecialchars($peer['description'])?>
						</td>
						<td>
							<a class="fa fa-pencil" title="<?=dgettext("BluePexWebFilter", 'Edit reverse web server')?>" href="wf_reverse_peer_edit.php?id=<?=$i?>"></a>
							<a class="fa fa-trash" title="<?=dgettext("BluePexWebFilter", 'Delete reverse web server')?>" href="wf_reverse_peer.php?act=del&amp;id=<?=$i?>"></a>
						</td>
					</tr>
				<?php 
					$i++; 
					endforeach;
					endif;
				?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<nav class="action-buttons">
	<a href="wf_reverse_peer_edit.php" class="btn btn-success btn-sm">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=dgettext("BluePexWebFilter", "Add")?>
	</a>
</nav>
<?php include("foot.inc");
