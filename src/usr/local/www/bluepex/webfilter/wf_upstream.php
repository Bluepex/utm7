<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Mariana Amorim <mariana.souza@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("classes/Form.class.php");
require_once("nf_defines.inc");
require_once("squid.inc");

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['squidremote']['config'])) {
	$config['system']['webfilter']['squidremote']['config']= array();
}
$rules = &$config['system']['webfilter']['squidremote']['config'];

if (isset($_GET['act']) && $_GET['act'] == "del") {
	if (isset($_GET['id']) && is_numericint($_GET['id'])) {
		if (isset($rules[$_GET['id']])) {
			unset($rules[$_GET['id']]);
			$savemsg = dgettext("BluePexWebFilter", "Upstream Proxy removed successfully!");
			write_config($savemsg);
		}
	} else {
		$input_errors = dgettext("BluePexWebFilter", "Could not to remove the Upstream Proxy!");
	}
}

if (isset($_POST['order_save'])) {
	if (isset($_POST['check_rule']) && !empty($_POST['check_rule'])) {
		$rules_ordered = array();
		foreach ($_POST['check_rule'] as $id)
			$rules_ordered[] = $rules[$id];

		$rules = $rules_ordered;
		$savemsg = dgettext("BluePexWebFilter", "The Upstream Proxy have been successfully ordered!");
		write_config($savemsg);
	} else {
		$input_errors[] = dgettext("BluePexWebfilter", "Could not to order the Upstream Proxy");
	}
}

if (isset($_POST['remove_rules'])) {
	if (isset($_POST['check_rule']) && !empty($_POST['check_rule'])) {
		foreach ($_POST['check_rule'] as $id)
			unset($rules[$id]);
		$savemsg = dgettext("BluePexWebFilter", "The Upstream Proxy have been successfully removed!");
		write_config($savemsg);
	} else {
		$input_errors[] = dgettext("BluePexWebFilter", "Could not to remove the Upstream Proxy!");
	}
}

$pgtitle = array("WebFilter", dgettext("BluePexWebFilter", "Upstream Proxy"));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors); 
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), false, '/webfilter/wf_server.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Upstream Proxy'), true, '/webfilter/wf_upstream.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Cache Mgmt'), false, '/webfilter/wf_cache_mgmt.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Access Control'), false, '/webfilter/wf_access_control.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Traffic Mgmt'), false, '/webfilter/wf_traffic_mgmt.php');
display_top_tabs($tab_array);
?>
<form action="/webfilter/wf_upstream.php" method="POST">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=dgettext("BluePexWebFilter", "Upstream Proxy List")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive col-md-12">
			<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th></th>
					<th><?=dgettext("BluePexWebFilter", "Status");?></th>
					<th><?=dgettext("BluePexWebFilter", "Name");?></th>
					<th><?=dgettext("BluePexWebFilter", "Address");?></th>
					<th><?=dgettext("BluePexWebFilter", "Port"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "ICP"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Peer Type"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Method"); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody class="rules-entries">
				<?php 
					if(!empty($rules)) : 
						foreach ($rules as $id => $rule) : 
				?>
				<tr>
					<td><input type="checkbox" name="check_rule[]" value="<?=$id?>" /></td>
					<td><?=$rule['enable'];?></td>
					<td><?=$rule['proxyname'];?></td>
				        <td><?=$rule['proxyaddr'];?></td>
					<td><?=$rule['proxyport'];?></td>
					<td><?=$rule['icpport'];?></td>
					<td><?=$rule['hierarchy'];?></td>
					<td><?=$rule['peermethod'];?></td>
					<td>
						<a href="wf_upstream_edit.php?act=edit&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Edit Upstream Proxy"); ?>">
							<i class="fa fa-edit"></i>
						</a>
						&nbsp;
						<a href="wf_upstream.php?act=del&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Remove Upstream Proxy"); ?>">
							<i class="fa fa-trash"></i>
						</a>
					</td>
				</tr>
				<?php endforeach; endif; ?>
			</tbody>
			</table>
			<nav class="action-buttons">
				<a href="wf_upstream_edit.php" class="btn btn-xs btn-success" title="<?=dgettext("BluePexWebFilter", "Add Upstream Proxy")?>">
					<i class="fa fa-plus icon-embed-btn"></i> <?=dgettext("BluePexWebFilter", "Add");?>
				</a>
				<button type="submit" id="remove" name="remove_rules" class="btn btn-xs btn-danger" title="<?=dgettext("BluePexWebFilter", "Remove Upstream Proxy")?>">
					<i class="fa fa-trash icon-embed-btn"></i> <?=dgettext("BluePexWebFilter", "Remove");?>
				</button>
				<button type="submit" id="order-save" name="order_save" class="btn btn-xs btn-primary" disabled="disabled" title="<?=dgettext("BluePexWebFilter", "Save And Order Upstream Proxy")?>">
					<i class="fa fa-save icon-embed-btn"></i> <?=dgettext("BluePexWebFilter", "Save/Sort");?>
				</button>
			</nav>
		</div>
	</div>
</div>
</form>
<script>
events.push(function() {
	$('table tbody.rules-entries').sortable({
		cursor: 'grabbing',
		update: function(event, ui) {
			$('#order-save').removeAttr('disabled');
		}
	});
	$('#order-save').click(function() {
		$("input[name='check_rule[]']").prop('checked', true);
	});
});
</script>
<?php include("foot.inc"); ?>
