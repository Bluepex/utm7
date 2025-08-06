<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 * Written by Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */

require("wf_quarantine.inc");
require("guiconfig.inc");

$input_errors = array();
$savemsg = "";

init_config_arr(array('sytem', 'webfilter', 'quarantine', 'config'));

$quarantine = &$config['system']['webfilter']['quarantine']['config'][0];

if (isset($_POST['save'])) {
	$enable = isset($_POST['enable']) ? $_POST['enable'] : "no";
	$type   = $_POST['type'];
	$notices = $_POST['notices'];
	$instances_id = $_POST['instance_id'];

	$quarantine = array(
		"enable" => $enable, 
		"instances" => !empty($instances_id) ? implode(",", $instances_id) : "",
		"type" => $type, 
		"notices" => $notices
	);

	foreach ($instances_id as $instance_id) {
		if (!isset($wf_instances[$instance_id])) {
			continue;
		}
		if (!verify_custom_quarantine($instance_id)) {
			$customlist[] = array(
				"instance_id" => $instance_id,
				"name" => "quarantine",
				"urls" => "",
				"descr" => "Webfilter Quarantine Auto",
			);
		}
		if ($type == "enabled_auto") {
			$rules_selected = (isset($_POST['rules'][$instance_id])) ? $_POST['rules'][$instance_id] : array();
			add_del_custom_quarantine($instance_id, $rules_selected);
		}
	}

	$savemsg = dgettext('BluePexWebFilter', 'Settings applied successfully!');
	apply_resync($savemsg);
}

if (isset($_POST['remove_reason'])) {
	if (!empty($_POST['remove'])) {
		foreach ($_POST['remove'] as $id)
			update_status_justification($id);
		$savemsg = dgettext("BluePexWebFilter", "Justifications removed successfully!");
	} else {
		$input_errors[] = dgettext("BluePexWebFilter", "Justification not selected.");
	}
}

// Ajax Request
if (isset($_POST['allowrequest'])) {
	$customlists = $_POST['customlists'];
	$data = json_decode($_POST['data'], true);

	if (!isset($data['proxy_instance_id'])) {
		return;
	}
	if (preg_match("#^\b(http:\/\/|https:\/\/)#", $data['url_blocked'])) {
		$data['url_blocked'] = parse_url($data['url_blocked'], PHP_URL_HOST);
	}
	$instance_id = $data['proxy_instance_id'];
	// Check Parent Rules
	if ($wf_instances[$instance_id]['server']['parent_rules'] != "") {
		$instance_id = $wf_instances[$instance_id]['server']['parent_rules'];
	}
	foreach($customlists as $custom) {
		set_url_to_customlist($instance_id, $custom, $data['url_blocked']);
	}
	if (update_status_justification($data['id'])) {
		$msg = dgettext("BluePexWebFilter","URL allowed successfully!");
		apply_resync($msg);
		set_flash_message("success", $msg);
		echo "ok";
	}
	exit;
}

// Ajax Request
if (isset($_GET['getmodalcontent'], $_GET['id'])) {
	$justification = get_justification_by_id($_GET['id']);
	if (is_array($justification)) {
		$instance_id = $justification['proxy_instance_id'];
		// Check Parent Rules
		if ($wf_instances[$instance_id]['server']['parent_rules'] != "") {
			$instance_id = $wf_instances[$instance_id]['server']['parent_rules'];
		}
		$html  = "";
		$html .= "<h3>" . dgettext('BluePexWebFilter', 'Choose the custom lists to allow this solicitation') . "</h3>";
		$html .= "<div id='msg'></div>";
		$html .= "<table class='table'>";
		$html .= "<tr><td width='100'>URL:</td><td>{$justification['url_blocked']}</td></tr>";
		if (!empty($justification['username'])) {
			$html .= "<tr><td width='100'>" . htmlentities(dgettext('BluePexWebFilter', 'Username:')) . "</td><td>{$justification['username']}</td>";
		}
		$html .= "<tr><td width='100'>" . dgettext('BluePexWebFilter', 'Ipaddress:') . "</td><td>{$justification['ip']}</td></tr>";
		$html .= "</table>";
		$html .= "<br />";
		$html .= "<table>";
		$html .= "<tr><td width='200' valign='top'><b>" . dgettext('BluePexWebFilter', 'Select Custom List:') . "</b><br><br>";
		$html .= "<table>";
		$html .= "<tr><td>";
		$i=0;
		foreach($customlist as $clist) {
			if (isset($clist['novisible']) || !isset($clist['instance_id']) || $clist['instance_id'] != $instance_id) {
				continue;
			}
			$html .= "<input type='checkbox' name='customlist[]' value='{$clist['name']}'>{$clist['name']}<br>";
			$i++;
		}
		$html .= "</td></tr></table></td></tr>";
		$html .= "</table>";
		$html .= "<input type='hidden' id='data_request' value='" . json_encode($justification) . "' />";
		echo $html;
	}
	exit;
}

if (isset($_GET['act'], $_GET['id'])) {
	if (!empty($_GET['id']) && $_GET['act'] == "reject") {
		if (reject_justification($_GET['id'])) {
			$savemsg = dgettext('BluePexWebFilter', 'Justification rejected successfully!');
		} else {
			$input_errors[] = dgettext('BluePexWebFilter', 'Could not to reject the Justification!');
		}
	} 
}

$requests = isset($_POST['filter']) ? get_justifications($_POST['filter']) : get_justifications();

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Quarantine'));
include('head.inc');

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Quarantine'), true, '/webfilter/wf_quarantine.php');
display_top_tabs($tab_array);

$form = new Form;
$section = new Form_Section('Settings');
 
$section->addInput(new Form_Checkbox(
	'enable',
	'Enable',
	dgettext('BluePexWebFilter', 'Check this option to enable the Quarantine.'),
	($quarantine['enable'] == "yes")
));

$instances_selected = ($quarantine['instances'] != "") ? explode(",", $quarantine['instances']) : array();
$instances_proxy = array();
foreach ($wf_instances as $instance_id => $instance_config) {
	$instances_proxy[$instance_id] = $instance_config['server']['name'];
}
$section->addInput(new Form_Select(
	'instance_id',
	'Proxy Instance',
	$instances_selected,
	$instances_proxy,
	true
))->setHelp('Select the proxy instance.')->setWidth(4);

$section->addInput(new Form_Checkbox(
	'notices',
	'Notices',
	dgettext('OpenVPNControl', 'Check to receive notice by email.'),
	isset($quarantine['notices']) ? $quarantine['notices'] : '',
	'on'
))->setHelp(sprintf(dgettext("BluePexWebFilter", "To receive notices by email, %s"), "<a href='../system_advanced_notifications.php'>" . dgettext('OpenVPNControl', 'click here to configure.') . "</a>"));

$type_list = array(
	"enabled_quarantine" => dgettext('BluePexWebFilter', 'Quarantine'), 
	"enabled_auto" => dgettext('BluePexWebFilter', 'Auto')
);
$section->addInput(new Form_Select(
	'type',
	dgettext('BluePexWebFilter', 'Server type'),
	$quarantine['type'],
	$type_list
));

$form->add($section);

foreach ($wf_instances as $instance_id => $instance_config) {
	$section = new Form_Section(
		sprintf(dgettext('BluePexWebFilter', 'Rules Users (%s)'), $instance_config['server']['name']), 
		'box-rules'
	);

	if ($instance_config['server']['parent_rules'] != "") {
		$section->addInput(new Form_StaticText(
			"",
			"<h3 class='text-center'>" . sprintf(dgettext("BluePexWebFilter", "Using rules of the '%s' proxy instance."), $wf_instances[$instance_config['server']['parent_rules']]['server']['name']) . "</h3>"
		));
		$form->add($section);
		continue;
	}

	$group = new Form_Group('Rules');

	$rules_selected = get_rules_quarantine($instance_id);
	$_rules = array();
	$_rules_selected = array();

	foreach ($rules as $id => $rule) {
		if ($rule['instance_id'] != $instance_id) {
			continue;
		}
		if (!in_array($id, $rules_selected)) {
			$_rules[$id] = $rule['description'];
		} else {
			$_rules_selected[$id] = $rule['description'];
		}
	}
	$group->add(new Form_Select(
		'rulesdisabled[' . $instance_id . ']',
		null,
		"",
		$_rules,
		true
	))->setHelp('Disabled');

	$group->add(new Form_Select(
		'rules[' . $instance_id . ']',
		null,
		$_rules_selected,
		$_rules_selected,
		true
	))->setHelp('Enabled (Default)');

	$section->add($group);

	$group = new Form_Group('');

	$group->add(new Form_Button(
		'movetoenabled',
		dgettext('BluePexWebFilter', 'Move to enabled list >')
	))->removeClass('btn-primary')->addClass('btn-default btn-sm');

	$group->add(new Form_Button(
		'movetodisabled',
		dgettext('BluePexWebFilter', '< Move to disabled list')
	))->removeClass('btn-primary')->addClass('btn-default btn-sm');

	$section->add($group);
	$form->add($section);
}
print $form;
?>
<style>
.panel.panel-default.form-horizontal {
    width: 91.75%;
    margin-left: auto;
    margin-bottom: 100px;
}
h3.text-center {
    padding-top: 50px;
    padding-bottom: 50px;
}

@media (max-width: 991px) {
	.panel.panel-default.form-horizontal {
		width: 100%;
		margin: auto;
		margin-top: 100px;
		margin-bottom: 100px;
	}
}

form:not([class]), main > div.panel.panel-default {
    border: 1px solid #c5c5c5;
    padding-right: 10px;
    padding-left: 10px;
    margin: auto;
    margin-top: 35px;
    /* margin-bottom: 15px; */
    width: 96%;
    /* margin: auto; */
    padding: 10px;
}

.col-lg-4 {
    display: contents;
}

</style>

<div class="clearfix"></div>
<div class="panel panel-default form-horizontal">
	<div class="panel-heading"><h2 class="panel-title" style="display: contents;"><?=dgettext('BluePexWebFilter', 'Requests');?></h2></div>
	<div class="panel-body border-box mt-5 pt-22">
		<p>
		<form action="wf_quarantine.php" method="POST">
			<div class="col-lg-4">
				<div class="input-group">
					<input type="text" name="filter" class="form-control" placeholder="<?=dgettext('BluePexWebFilter', 'Enter the filter to search.');?>" value="<?=isset($_POST['filter']) ? $_POST['filter'] : '';?>" />
					<span class="input-group-btn">
						<button class="btn btn-default" style="height:28px; padding: 3px 5px" type="submit"><i class="fa fa-search"></i> <?=dgettext('BluePexWebFilter', 'Filter');?></button>
					</span>
				</div>
			</div>
		</form>
		</p>
		<div class="col-md-12">
			<hr />
			<?php if (!empty($requests)) : ?>
			<form action="wf_quarantine.php" method="POST">
				<table class="table table-striped">
				<thead>
					<tr>
						<th class="text-center"><button type="image" name="remove_reason" onClick="return confirm('<?=dgettext('BluePexWebFilter', 'Do you sure want to remove this solicitations?');?>');" title="<?=dgettext('BluePexWebFilter','reject reason selected')?>"><i class="fa fa-trash"></i></button></th>
						<th><?=dgettext("BluePexWebFilter","Username")?></th>
						<th><?=dgettext("BluePexWebFilter","IP Address")?></th>
						<th><?=dgettext("BluePexWebFilter","URL Blocked")?></th>
						<th><?=dgettext("BluePexWebFilter","Justification")?></th>
						<th><?=dgettext("BluePexWebFilter","Date/Time")?></th>
						<th><?=dgettext("BluePexWebFilter","Proxy Instance")?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach($requests as $result) : ?>
					<tr id="res<?=$result['id']?>">
						<td class="text-center"><input type="checkbox" name="remove[]" value="<?=$result['id']?>"></td>
						<td><?=$result['username']?></td>
						<td><?=$result['ipaddress']?></td>
						<td><?=$result['url_blocked']?></td>
						<td><?=$result['reason']?></td>
						<td><?=$result['time_date']?></td>
						<td><?=$result['proxy_instance_name']?></td>
						<td valign="middle">
							<?php $value = array("allow", $result['username'], $result['ipaddress'], base64_encode($result['url_blocked']), $result['id']); ?>
							<a href="wf_quarantine.php?act=reject&id=<?=$result['id']?>" class="btn btn-xs btn-default" onclick="return confirm('<?=dgettext("BluePexWebFilter","Do you want really to reject this justification?")?>')" title="<?=dgettext("BluePexWebFilter","reject reason")?>">
								<i class="fa fa-user-times"></i>
							</a>
							<button type="button" class="btn btn-xs btn-default allow" value="<?=$result['id'];?>"><i class="fa fa-check"></i></button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
				</table>
				<br />
			</form>
			<?php else : ?>
				<h3 class="text-center"><?=dgettext('BluePexWebFilter', 'No data to the filter specified!');?></h3>
			<?php endif; ?>
		</div>
	</div>
</div>
<!-- Modal Quarantine -->
<div class="modal fade" id="ModalQuarantine" tabindex="-1" role="dialog" aria-labelledby="ModalQuarantineLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="ModalQuarantineLabel"></h4>
			</div>
			<div class="modal-body">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?=dgettext('BluePexWebFilter', 'Close');?></button>
				<button type="button" id="btn-save" class="btn btn-primary"><?=dgettext('BluePexWebFilter', 'Save');?></button>
			</div>
		</div>
	</div>
</div>
<script>
//<![CDATA[
events.push(function(){
	// Select every option in the specified multiselect
	function AllRules(id, selectAll) {
		for (i = 0; i < id.length; i++) {
			id.eq(i).prop('selected', selectAll);
		}
	}
	// Move all selected options from one multiselect to another
	function moveOptions(From, To) {
		var len = From.length;
		var option;
		if (len > 0) {
			for (i=0; i<len; i++) {
				if (From.eq(i).is(':selected')) {
					text = From.eq(i).text();
					option = From.eq(i).val();
					To.append(new Option(text, option));
					From.eq(i).remove();
				}
			}
		}
	}
	// Make buttons plain buttons, not submit
	$("[id='movetodisabled']").prop('type','button');
	$("[id='movetoenabled']").prop('type','button');

	$("[id='movetodisabled']").click(function() {
		var element_enabled = $(this).parents(".panel-body").find("select:eq(1) option");
		var element_disabled = $(this).parents(".panel-body").find("select:eq(0)");
		moveOptions(element_enabled, element_disabled);
	});

	$("[id='movetoenabled']").click(function() {
		var element_enabled = $(this).parents(".panel-body").find("select:eq(1)");
		var element_disabled = $(this).parents(".panel-body").find("select:eq(0) option");
		moveOptions(element_disabled, element_enabled);
	});

	$(".allow").click(function() {
		var id = $(this).val();
		$.get("/webfilter/wf_quarantine.php", { "getmodalcontent": true, "id": id } ).done(function( data ) {
			$('.modal-title').text("<?=dgettext('BluePexWebFilter', 'Allow Request of the user')?>");
			$('#ModalQuarantine .modal-body').html(data);
			$('#ModalQuarantine').modal();
		});
	});
	$("#btn-save").click(function() {
		var data = $("#ModalQuarantine #data_request").val();
		if (data != "") {
			var customlists = [];
			$("#ModalQuarantine").find("input[name='customlist[]']:checked").each(function() {
				customlists.push($(this).val());
			});
			if (customlists.length > 0) {
				$.post("/webfilter/wf_quarantine.php", { "allowrequest": true, "data": data, "customlists": customlists } ).done(function( status ) {
					if (status == "ok") {
						window.location.href = "/webfilter/wf_quarantine.php";
					}
				});
			} else{
				$("#msg").html("<div class='alert alert-warning'><?=dgettext('BluePexWebFilter', 'Please, select the custom lists!');?></div>");
			}
		} else {
			$("#msg").html("<div class='alert alert-warning'><?=dgettext('BluePexWebFilter', 'Could not to allow request of the user!');?></div>");
		}
	});

	<?php if (empty($quarantine) || $quarantine['type'] == "enabled_quarantine") : ?>
		$("[id='box-rules']").hide();
	<?php endif; ?>

	$("#type").change(function() {
		if ($(this).val() == "enabled_auto") {
			$("[id='box-rules']").fadeIn();
		} else {
			$("[id='box-rules']").hide();
		}
	});

	// On submit mark all the rules as "selected"
	$('form').submit(function(){
		$("[id='box-rules']").each(function() {
			AllRules($(this).find('select:eq(1) option'), true);
		});
	});
});
//]]>
</script>
<?php include('../foot.inc'); ?>
