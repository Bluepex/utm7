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
require_once("classes/Form.class.php");
require_once("nf_defines.inc");
require_once("nf_config.inc");

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instance = &$config['system']['webfilter']['instance']['config'];

if (!isset($config['system']['webfilter']['nf_content_rules']['element0']) || !is_array($config['system']['webfilter']['nf_content_rules']['element0'])) {
        $config['system']['webfilter']['nf_content_rules']['element0'] = array();
}

if (!isset($config['system']['webfilter']['nf_content_rules']['element0']['item'])) {
	$config['system']['webfilter']['nf_content_rules']['element0']['item'] = array();
}
$rules = &$config['system']['webfilter']['nf_content_rules']['element0']['item'];

$type_rule = array(
	"default" => dgettext("BluePexWebFilter", "All"),
	"users" => dgettext("BluePexWebFilter", "User names"),
	"groups" => dgettext("BluePexWebFilter", "User groups"),
	"ip" => dgettext("BluePexWebFilter", "IP address"),
	"range" => dgettext("BluePexWebFilter", "IP range"),
	"subnet" => dgettext("BluePexWebFilter", "Subnet")
);

$actions_rule = array(
	"allow" => dgettext("BluePexWebFilter", "Allow all content"),
	"block" => dgettext("BluePexWebFilter", "Block all content"),
	"selected" => dgettext("BluePexWebFilter", "Block the selected categories")
);

if (isset($_GET['act'], $_GET['instance_id'], $_GET['id'])) {
	$instance_id = (int)$_GET['instance_id'];
	$rule_id = (int)$_GET['id'];
	if (isset($rules[$rule_id]) && $rules[$rule_id]['instance_id'] == $instance_id) {
		if ($_GET['act'] == "del") {
			unset($rules[$rule_id]);
			$savemsg = dgettext("BluePexWebFilter", "Content Rule removed successfully!");
		} elseif ($_GET['act'] == "clone") {
			$rules[] = $rules[$rule_id];
			$savemsg = dgettext("BluePexWebFilter", "Content Rule cloned successfully!");
		}
		write_config($savemsg);
		mark_subsystem_dirty("nf_sync_rules");
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_content_rules.php");
		exit;
	} else {
		$input_errors[] = dgettext("BluePexWebFilter", "Could not to remove the Content Rule!");
	}
}

if (isset($_POST['order_save'], $_POST['instance_id'])) {
	$instance_id = (int)$_POST['instance_id'];
	if (isset($_POST['check_rule']) && !empty($_POST['check_rule'])) {
		$rules_ordered = array();
		foreach ($_POST['check_rule'] as $id) {
			$rules_ordered[] = $rules[$id];
			unset($rules[$id]);
		}
		foreach ($rules_ordered as $ro) {
			$rules[] = $ro;
		}
		$savemsg = dgettext("BluePexWebFilter", "The Content Rules have been successfully ordered!");
		write_config($savemsg);
		mark_subsystem_dirty("nf_sync_rules");
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_content_rules.php");
		exit;
	} else {
		$input_errors[] = dgettext("BluePexWebfilter", "Could not to order the Content Rules!");
	}
}

if (isset($_POST['remove_rules'], $_POST['instance_id'])) {
	$instance_id = (int)$_POST['instance_id'];
	if (isset($_POST['check_rule']) && !empty($_POST['check_rule'])) {
		foreach ($_POST['check_rule'] as $id) {
			if (isset($rules[$id])) {
				unset($rules[$id]);
			}
		}
		$savemsg = dgettext("BluePexWebFilter", "The Content Rules have been successfully removed!");
		write_config($savemsg);
		mark_subsystem_dirty("nf_sync_rules");
		set_flash_message("success", $savemsg);
		header("Location: /webfilter/wf_content_rules.php");
		exit;
	} else {
		$input_errors[] = dgettext("BluePexWebFilter", "Could not to remove the Content Rules!");
	}
}

if (isset($_POST['apply'])) {
	NetfilterContentRulesResync();
	clear_subsystem_dirty('nf_sync_rules');
	set_flash_message("success", dgettext("BluePexWebFilter", "Content rules applied successfully!"));
	header("Location: /webfilter/wf_content_rules.php");
	exit;
}

function get_users_groups_by_uid_or_objectguid($type, $uid_objectguid) {
	global $config;
 
	if (!is_array($config['system'][$type]))
		return;
	foreach ($config['system'][$type] as $user_group) {
		if ($user_group['uid'] == $uid_objectguid || 
		    $user_group['objectguid'] == $uid_objectguid ||
		    $user_group['gid'] == $uid_objectguid) {
			return $user_group;
		}
	}
	return false;
}

function get_rules_instance($instance_id) {
	global $rules;

	$instance_rules = array();
	foreach($rules as $rule_id => $rule) {
		if (isset($rule['instance_id']) && $rule['instance_id'] == $instance_id) {
			$instance_rules[$rule_id] = $rule;
		}
	}
	return $instance_rules;
}

$pgtitle = array("WebFilter", dgettext("BluePexWebFilter", "Control Rules"));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors); 
if ($savemsg)
	print_info_box($savemsg, 'success');
if (is_subsystem_dirty('nf_sync_rules'))
	print_apply_box(dgettext("BluePexWebFilter", "Click to sincronize content rules..."));

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Rules'), true, '/webfilter/wf_content_rules.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'White/Black lists'), false, '/webfilter/wf_whitelist_blacklist.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Custom lists'), false, '/webfilter/wf_custom_list.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Extensions'), false, '/webfilter/wf_block_ext.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Block ADS'), false, '/webfilter/wf_ad_block.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Settings'), false, '/webfilter/wf_content_settings.php');
display_top_tabs($tab_array);

if (isset($wf_instance) && !empty($wf_instance)) :
	foreach ($wf_instance as $instance_id => $_instance_config) :
?>
<form action="/webfilter/wf_content_rules.php" method="POST">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=sprintf(dgettext("BluePexWebFilter", gettext("Configured Rules (%s)")), $_instance_config['server']['name'])?></h2></div>
	<div class="panel-body">
		<div class="table-responsive col-md-12">
			<?php 
				if (is_numeric($_instance_config['server']['parent_rules'])) {
					echo "<h3 class='text-center'>" . sprintf(dgettext("BluePexWebFilter", "Using rules of the '%s' proxy instance."), $wf_instance[$_instance_config['server']['parent_rules']]['server']['name']) . "</h3></div></div></div>";
					continue;
				}
				$instance_rules = get_rules_instance($instance_id);
				if (!empty($instance_rules)) :
			?>
			<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th></th>
					<th><?=dgettext("BluePexWebFilter", "Match");?></th>
					<th><?=dgettext("BluePexWebFilter", "Target");?></th>
					<th><?=dgettext("BluePexWebFilter", "Action"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Categories"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Activation Periods"); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Description"); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody class="rules-entries">
				<?php 
					$categories = NetfilterGetContentCategories();
					foreach ($instance_rules as $id => $rule) : 
				?>
				<tr <?php if ($rule['disabled'] == "on") echo 'class="disabled"'; ?>>
					<td><input type="checkbox" name="check_rule[]" value="<?=$id?>" /></td>
					<td><?=$type_rule[$rule['type']]?></td>
					<td>
					<?
						if ($rule['type'] == "users" || $rule['type'] == "groups") {
							$type = preg_replace("/s$/", "", $rule['type']);
							$users_groups = array();
							foreach (explode(",", $rule[$rule['type']]) as $uid_objectguid) {
								$user_group = get_users_groups_by_uid_or_objectguid($type, $uid_objectguid);
								$users_groups[] = $user_group['name'];
							}
							echo implode(", ", $users_groups);
						} elseif ($rule['type'] == "ip") {
							echo $rule['ip'];
						} elseif ($rule['type'] == "range") {
							echo $rule['range'];
						} elseif ($rule['type'] == "subnet") {
							echo $rule['subnet'];
						} else {
							echo dgettext("BluePexWebFilter", "All");
						}
					?>
					</td>
					<td><?=$actions_rule[$rule['action']];?></td>
					<td>
					<?php
						$selected = array();
						foreach (explode(",", $rule['categories']) as $cat) {
							$selected[] = $categories[$cat];
						}
						echo implode(", ", $selected);
					?>
					</td>
					<td><?=isset($rule['time_match']) ? dgettext("BluePexWebFilter", "Enabled") : dgettext("BluePexWebFilter", "Disabled");?></td>
					<td><?=$rule['description'];?></td>
					<td>
						<a href="wf_content_rules.php?act=clone&instance_id=<?=$instance_id?>&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Clone rule"); ?>">
							<i class="fa fa-copy"></i>
						</a>
						<a href="wf_content_rules_edit.php?act=edit&instance_id=<?=$instance_id?>&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Edit rule"); ?>">
							<i class="fa fa-edit"></i>
						</a>
						&nbsp;
						<a href="wf_content_rules.php?act=del&instance_id=<?=$instance_id?>&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Remove rule"); ?>">
							<i class="fa fa-trash"></i>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
			</table>
			<?php else : ?>
				<h3 class="text-center"><?=dgettext("BluePexwebFilter", gettext("No Rules configured for this instance!"))?></h3>
			<?php endif; ?>
			<nav class="action-buttons">
				<input type="hidden" name="instance_id" value="<?=$instance_id?>" />
				<a href="/webfilter/wf_content_rules_edit.php?instance_id=<?=$instance_id?>" class="btn btn-xs btn-success" title="<?=dgettext("BluePexWebFilter", "add rule")?>">
					<i class="fa fa-plus icon-embed-btn"></i> <?=dgettext("BluePexWebFilter", "add");?>
				</a>
				<button type="submit" id="remove" name="remove_rules" class="btn btn-xs btn-danger" title="<?=dgettext("BluePexWebFilter", "remove rule")?>">
					<i class="fa fa-trash icon-embed-btn"></i> <?=dgettext("BluePexWebFilter", "remove");?>
				</button>
				<button type="submit" name="order_save" class="btn btn-xs btn-primary order-save" disabled="disabled" title="<?=dgettext("BluePexWebFilter", "Save/Sort")?>">
					<i class="fa fa-save icon-embed-btn"></i> <?=dgettext("BluePexWebFilter", "Save/Sort");?>
				</button>
			</nav>
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
events.push(function() {
	$('table tbody.rules-entries').sortable({
		cursor: 'grabbing',
		update: function(event, ui) {
			$(ui.item).parents(".panel").find(".order-save").removeAttr('disabled');
		}
	});
	$('.order-save').click(function() {
		$(this).parents(".panel").find("input[name='check_rule[]']").prop('checked', true);
	});
});
</script>
<?php include("foot.inc"); ?>
<script>
	//Show column checkbox in table
	if ($('table thead th input').attr('type') == "checkbox") {	
		$('td:nth-child(1)').show();
		$('th:nth-child(1)').show();
	} else {
		if ($('table tbody td input').attr('type') == "checkbox") {
			$('td:nth-child(1)').show();
			$('th:nth-child(1)').show();
		}
	}
</script>