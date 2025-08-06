<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("nf_config.inc");
require_once("util.inc");

$input_errors = array();
$savemsg = "";

init_config_arr(array('system', 'webfilter', 'instance', 'config'));
init_config_arr(array('system', 'webfilter', 'nf_content_custom', 'element0', 'item'));
init_config_arr(array('system', 'webfilter', 'nf_content_rules', 'element0', 'item'));

$wf_instance = &$config['system']['webfilter']['instance']['config'];

$customlist = &$config['system']['webfilter']['nf_content_custom']['element0']['item'];

$rules = &$config['system']['webfilter']['nf_content_rules']['element0']['item'];

if (isset($_GET['act'], $_GET['id'], $_GET['instance_id']) && $_GET['act'] == "del") {
	$customlist_id = $_GET['id'];
	$instance_id = $_GET['instance_id'];
	if (isset($customlist[$customlist_id]) && $customlist[$customlist_id]['instance_id'] == $instance_id) {
		//unset($customlist[$customlist_id]);
		$nameCustomInstance  = $customlist[$customlist_id]['name'];
		$counterRules=0;
		foreach($rules as $rule_now) {
			$rule_now['custom_lists'] = preg_replace("/{$customlist_id}:{$nameCustomInstance}/",'', $rule_now['custom_lists']);
			$rules[$counterRules] = $rule_now;
			$counterRules++;
		}
		file_put_contents("/etc/disableCustomListWebfilter", "{$customlist_id};", FILE_APPEND);
		$savemsg = dgettext('BluePexWebFilter', 'Custom List disable successfully!');
		write_config($savemsg);
		NetfilterCustomListsResync();
		header("Location: ./wf_custom_list.php?msg=r");
	} else {
		//$input_errors[] = dgettext('BluePexWebFilter', 'Could not to disable the Custom List!');
		header("Location: ./wf_custom_list.php?msg=e");
	}
}

if (isset($_GET['act'], $_GET['id'], $_GET['instance_id']) && $_GET['act'] == "ena") {
	$customlist_id = $_GET['id'];
	$instance_id = $_GET['instance_id'];
	if (isset($customlist[$customlist_id]) && $customlist[$customlist_id]['instance_id'] == $instance_id) {
		//unset($customlist[$customlist_id]);
		mwexec("sed 's/{$customlist_id};//g' /etc/disableCustomListWebfilter > /etc/disableCustomListWebfilter.tmp && mv /etc/disableCustomListWebfilter.tmp /etc/disableCustomListWebfilter");
		$savemsg = dgettext('BluePexWebFilter', 'Custom List disable successfully!');
		write_config($savemsg);
		NetfilterCustomListsResync();
		header("Location: ./wf_custom_list.php?msg=ena");
	} else {
		//$input_errors[] = dgettext('BluePexWebFilter', 'Could not to disable the Custom List!');
		header("Location: ./wf_custom_list.php?msg=ee");
	}
}

$disableCustomList = [];
if (file_exists('/etc/disableCustomListWebfilter')) {
	$disableCustomList = array_unique(explode(';',file_get_contents('/etc/disableCustomListWebfilter')));
}

if (isset($_POST['del_x'])) {
	$instance_id = (int)$_POST['instance_id'];
	if (is_numeric($instance_id) && is_array($_POST['customlist_ids'])) {
		foreach ($_POST['customlist_ids'] as $id) {
			if (!isset($customlist[$id]) || $customlist[$id]['instance_id'] != $instance_id) {
				continue;
			}
			unset($customlist[$id]);
		}
		$savemsg = dgettext('BluePexWebFilter', 'Custom Lists removed successfully!');
		NetfilterCustomListsResync();
		write_config($savemsg);
	} else {
		$input_errors[] = dgettext('BluePexWebFilter', 'Could not to remove the Custom Lists! Select the custom lists to remove it.');
	}
}

function get_custom_lists_instance($instance_id) {
	global $customlist;

	$instance_customlists = array();
	foreach ($customlist as $custom_id => $custom) {
		if (isset($custom['instance_id']) && $custom['instance_id'] == $instance_id) {
			$instance_customlists[$custom_id] = $custom;
		}
	}
	return $instance_customlists;
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Custom List'));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);

if ($savemsg)
	print_info_box($savemsg, 'success');

if (isset($_GET['msg']) && $_GET['msg'] == 'r')
	print_info_box(dgettext('BluePexWebFilter', 'Custom List disable successfully!'), 'success');

if (isset($_GET['msg']) && $_GET['msg'] == 'e')
	print_input_errors(dgettext('BluePexWebFilter', 'Could not to disable the Custom List!'));

if (isset($_GET['msg']) && $_GET['msg'] == 'ena')
	print_info_box(dgettext('BluePexWebFilter', 'Custom List enable successfully!'), 'success');

if (isset($_GET['msg']) && $_GET['msg'] == 'ee')
	print_input_errors(dgettext('BluePexWebFilter', 'Could not to enable the Custom List!'));

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Rules'), false, '/webfilter/wf_content_rules.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'White/Black lists'), false, '/webfilter/wf_whitelist_blacklist.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Custom lists'), true, '/webfilter/wf_custom_list.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Extensions'), false, '/webfilter/wf_block_ext.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Block ADS'), false, '/webfilter/wf_ad_block.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Settings'), false, '/webfilter/wf_content_settings.php');
display_top_tabs($tab_array);

if (isset($wf_instance) && !empty($wf_instance)) :
	foreach ($wf_instance as $instance_id => $instance_config) :
?>
<form action="" method="POST">
	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=sprintf(dgettext('BluePexWebFilter', 'Custom Lists enable (%s)'), $instance_config['server']['name'])?></h2></div>
		<div class="panel-body">
			<div style="width:30%!important;display:flex!important;margin-left:auto!important;">
				<input type="text" class="form-control find-values" style="background-color: #FFF!important; float:right; width: 100%; margin-left: auto;" id="searchEnableCustom" name="searchEnableCustom" placeholder="<?=gettext("Search for...")?>" onkeydown="searchDataEnableCustom()" onkeyup="searchDataEnableCustom()" >
				<button type="click" class="btn btn-danger form-control find-values" style="width: auto; margin:0px!important;" id="closeSearchEnableCustom" onclick="closeSearchEnableCustom()"><i class="fa fa-times"></i></button>
			</div>
			<div class="table-responsive">
				<?php 
					if (is_numeric($instance_config['server']['parent_rules'])) {
						echo "<h3 class='text-center'>" . sprintf(dgettext("BluePexWebFilter", "Using rules of the '%s' proxy instance."), $wf_instance[$instance_config['server']['parent_rules']]['server']['name']) . "</h3></div></div></div>";
						continue;
					}
					$instance_customlist = get_custom_lists_instance($instance_id);
					if (!empty($instance_customlist)) :
				?>
				<table class="table table-striped table-hover" id="searchEnableCustomTable">
				<thead>
					<tr>
						<!--<th></th>-->
						<th><?=dgettext('BluePexWebFilter', 'Name')?></th>
						<th><?=dgettext('BluePexWebFilter', 'URL\'s')?></th>
						<th><?=dgettext('BluePexWebFilter', 'Description')?></th>
						<th><?=dgettext('BluePexWebFilter', 'Actions')?></th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach ($instance_customlist as $id => $cl) : 
						if (!in_array(strval($id), $disableCustomList)):
							if (isset($cl['novisible']) || $cl['instance_id'] != $instance_id)
								continue;
					?>
						<tr>
							<!--<td><input type="checkbox" name="customlist_ids[]" value="<?=$id?>" /></td>-->
							<td><?=$cl['name']?></td>
							<td>
								<p style="display: none;"><?=base64_decode($cl['urls'])?></p>
								<span class="badge" data-toggle="popover" data-trigger="hover focus" title="<?=dgettext('BluePexWebFilter', 'Custom Lists details')?>" data-content="<?=base64_decode($cl['urls'])?>" data-html="true">URL's</span>
							</td>
							<td><?=$cl['descr']?></td>
							<td>
								<a class="fa fa-pencil" title="<?=dgettext('BluePexWebFilter', 'Edit customlist'); ?>" href="/webfilter/wf_custom_list_edit.php?act=edt&instance_id=<?=$instance_id?>&id=<?=$id?>"></a>
								<a class="fa fa-times"  title="<?=dgettext('BluePexWebFilter', 'Disable customlist')?>" href="/webfilter/wf_custom_list.php?act=del&instance_id=<?=$instance_id?>&id=<?=$id?>" onclick="return confirm('<?=dgettext('BluePexWebFilter', 'Do you really want to disable this Custom List?')?>')"></a>
							</td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
				</tbody>
				</table>
				<?php else : ?>
					<h3 class="text-center"><?=dgettext("BluePexwebFilter", gettext("No Custom Lists configured for this instance!"))?></h3>
				<?php endif; ?>
				<nav class="action-buttons">
					<input type="hidden" name="instance_id" value="<?=$instance_id?>" />
					<a href="/webfilter/wf_custom_list_edit.php?instance_id=<?=$instance_id?>" role="button" class="btn btn-sm btn-success">
						<i class="fa fa-plus icon-embed-btn"></i>
						<?=dgettext('BluePexWebFilter', 'Add');?>
					</a>
					<!--
					<button name="del_x" type="submit" class="btn btn-danger btn-sm" value="<?=dgettext('BluePexWebFilter', 'Delete selected customlist');?>">
						<i class="fa fa-trash icon-embed-btn"></i>
						<?=dgettext('BluePexWebFilter', 'Delete'); ?>
					</button>
					-->
				</nav>
			</div>
		</div>
	</div>
</form>
<?php
endforeach;

foreach ($wf_instance as $instance_id => $instance_config) :
?>
<form action="" method="POST">
	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=sprintf(dgettext('BluePexWebFilter', 'Custom Lists disable (%s)'), $instance_config['server']['name'])?></h2></div>
		<div style="width:30%!important;display:flex!important;margin-left:auto!important;">
			<input type="text" class="form-control find-values" style="background-color: #FFF!important; float:right; width: 100%; margin-left: auto;" id="searchDisableCustom" name="searchDisableCustom" placeholder="<?=gettext("Search for...")?>" onkeydown="searchDataDisableCustom()" onkeyup="searchDataDisableCustom()" >
			<button type="click" class="btn btn-danger form-control find-values" style="width: auto; margin:0px!important;" id="closeSearchDisableCustom" onclick="closeSearchDisableCustom()"><i class="fa fa-times"></i></button>
		</div>
		<div class="panel-body">
			<div class="table-responsive">
				<?php 
					if (is_numeric($instance_config['server']['parent_rules'])) {
						echo "<h3 class='text-center'>" . sprintf(dgettext("BluePexWebFilter", "Using rules of the '%s' proxy instance."), $wf_instance[$instance_config['server']['parent_rules']]['server']['name']) . "</h3></div></div></div>";
						continue;
					}
					$instance_customlist = get_custom_lists_instance($instance_id);
					if (!empty($instance_customlist)) :
				?>
				<table class="table table-striped table-hover" id="searchDisableCustomTable">
				<thead>
					<tr>
						<!--<th></th>-->
						<th><?=dgettext('BluePexWebFilter', 'Name')?></th>
						<th><?=dgettext('BluePexWebFilter', 'URL\'s')?></th>
						<th><?=dgettext('BluePexWebFilter', 'Description')?></th>
						<th><?=dgettext('BluePexWebFilter', 'Actions')?></th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach ($instance_customlist as $id => $cl) : 
						if (in_array(strval($id), $disableCustomList)):
							if (isset($cl['novisible']) || $cl['instance_id'] != $instance_id)
								continue;
					?>
						<tr>
							<!--<td><input type="checkbox" name="customlist_ids[]" value="<?=$id?>" /></td>-->
							<td><?=$cl['name']?></td>
							<td>
								<p style="display: none;"><?=base64_decode($cl['urls'])?></p>
								<span class="badge" data-toggle="popover" data-trigger="hover focus" title="<?=dgettext('BluePexWebFilter', 'Custom Lists details')?>" data-content="<?=base64_decode($cl['urls'])?>" data-html="true">URL's</span>
							</td>
							<td><?=$cl['descr']?></td>
							<td>
								<a class="fa fa-pencil" title="<?=dgettext('BluePexWebFilter', 'Edit customlist'); ?>" href="/webfilter/wf_custom_list_edit.php?act=edt&instance_id=<?=$instance_id?>&id=<?=$id?>"></a>
								<a class="fa fa-check"  title="<?=dgettext('BluePexWebFilter', 'Enable customlist')?>" href="/webfilter/wf_custom_list.php?act=ena&instance_id=<?=$instance_id?>&id=<?=$id?>" onclick="return confirm('<?=dgettext('BluePexWebFilter', 'Do you really want to enable this Custom List?')?>')"></a>
							</td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
				</tbody>
				</table>
				<?php else : ?>
					<h3 class="text-center"><?=dgettext("BluePexwebFilter", gettext("No Custom Lists configured for this instance!"))?></h3>
				<?php endif; ?>
			</div>
		</div>
	</div>
</form>
<?php
endforeach;
else:
	?>
	<div class="panel panel-default">
		<div class="panel-body">
			<br>
			<p><?=gettext("There are no instances of interfaces in the Webfilter, configure an instance on this <a href='../webfilter/wf_server.php'>page</a> to release the rules.")?></p>
		</div>
	</div>
	<?php
endif;
?>
<script>
function searchDataEnableCustom() {
	var $rows = $('#searchEnableCustomTable tbody tr');
	var val = $.trim($('#searchEnableCustom').val()).replace(/ +/g, ' ').toLowerCase();
	$rows.show().filter(function() {
		var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
		return !~text.indexOf(val);
	}).hide();
}

function searchDataDisableCustom() {
	var $rows = $('#searchDisableCustomTable tbody tr');
	var val = $.trim($('#searchDisableCustom').val()).replace(/ +/g, ' ').toLowerCase();
	$rows.show().filter(function() {
		var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
		return !~text.indexOf(val);
	}).hide();
}


$(document).ready(function () {
	$("#closeSearchEnableCustom").click(function (event) {
		event.preventDefault();
		$("#searchEnableCustom").val("");
		searchData();
    });
	$("#closeSearchDisableCustom").click(function (event) {
		event.preventDefault();
		$("#searchDisableCustom").val("");
		searchData();
    });
});
</script>
<?php include("foot.inc"); ?>
<script>
//Show column checkbox in table
/*
if ($('table thead th input').attr('type') == "checkbox") {	
	$('td:nth-child(1)').show();
	$('th:nth-child(1)').show();
} else {
	if ($('table tbody td input').attr('type') == "checkbox") {
		$('td:nth-child(1)').show();
		$('th:nth-child(1)').show();
	}
}
*/
</script>
