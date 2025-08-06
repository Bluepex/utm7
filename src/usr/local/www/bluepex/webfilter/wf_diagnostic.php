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
require_once("auth.inc");
require_once("wf_diagnostic.inc");
require('../classes/Form.class.php');

$input_erros = array();
$savemsg = "";
$diagnostic = array();
$configusers = &$config['system']['user'];

$type = "username";

if (!is_process_running("squid") || !is_process_running("interface")) {
	$input_errors[] = dgettext('BluePexWebFilter', gettext('Service WF Interface or WF Proxy server is not running!'));
}

if ($_GET) {
	$check_url = $_GET['url'];
	$ipaddress  = $_GET['ip'];
	$user = $_GET['user'];
	if (!empty($ipaddress)) {
		$if_physical = guess_interface_from_ip($ipaddress);
		$ifname = convert_real_interface_to_friendly_interface_name($if_physical);
	}
}

// Ajax Request
if (isset($_GET['load_ifaces'], $_GET['instance_id'])) {
	if (!isset($wf_instances[$_GET['instance_id']])) {
		return;
	}
	$ifaces = explode(",", $wf_instances[$_GET['instance_id']]['server']['active_interface']);
	if (count($ifaces) > 0) {
		echo json_encode($ifaces);
	}
	exit;
}

if (empty($input_errors) && $_POST) {
	$pconfig = $_POST;
	$instance_id = $pconfig['instance_id'];
	$interface = $pconfig['interface'];
	$type = $pconfig['type'];

	if (!isset($pconfig['url']) || !preg_match("#^(http|https):\/\/[a-z]*#", $pconfig['url'])) {
		$input_errors[] = dgettext('BluePexWebFilter', gettext('URL invalid. Please, to fill the correct url. Eg: http://www.bluepex.com'));
	}

	if (empty($input_errors) && $type == "username") {

		$authcfg = auth_get_authserver($pconfig['authmode']);
		if (!$authcfg) {
			$input_errors[] = $pconfig['authmode'] . " " . gettext("is not a valid authentication server");
		} else if (!isset($pconfig['username'], $pconfig['password'])) {
			$input_errors[] = dgettext('BluePexWebFilter', gettext('The fields "username" and "password" are required!'));
		} else {
			$user = trim($pconfig['username']);
			$password = $pconfig['password'];

			// If Using parent rules
			if (is_numeric($wf_instances[$instance_id]['server']['parent_rules'])) {
				$parent_instance_id = $wf_instances[$instance_id]['server']['parent_rules'];
			} else {
				$parent_instance_id = $instance_id;
			}

			if (authenticate_user($user, $password, $authcfg)) {
				$user = getUserEntry($user);
				$check_url = trim($pconfig['url']);

				$webfilter = array();

				foreach ($configusers as $cfuser) {
					if ($cfuser['name'] != $user['name'])
						continue;

					$access_control = !test_access_control($instance_id, $user['name'], $password, $check_url, "", $interface) ? "Blocked" : "Allowed";
					$contentrules   = test_content_rules($instance_id, $user['name'], $password, $check_url, "");
					$test_status    = "Allowed";

					//print_r($access_control);die;

					if ($contentrules != "HTTP/1.1 200 Connection established") {
						$reason = returnReason($contentrules);
						$categories = returnCategories($contentrules);
						$test_status = "Blocked";
					}
					foreach ($rules as $idx => $rule) {
						//print_r($rule);die;
						if ($rule['disabled'] != "off" || !isset($rule['instance_id']) || $rule['instance_id'] != $parent_instance_id) {
							continue;
						}
						if ($rule['type'] == "users") {
							foreach (explode(",", $rule['users']) as $user_rule) {
								if ($user_rule === $user['objectguid'] || $user_rule === $user['uid'])
									$webfilter[] = check_webfilter_rules($parent_instance_id, $check_url, $idx);
							}
						} elseif ($rule['type'] == "groups") {
							foreach(explode(",",$rule['groups']) as $ref) {
								$users = get_users_group($ref);
								if (is_array($users) && in_array($user['name'], $users))
									$webfilter[] = check_webfilter_rules($parent_instance_id, $check_url, $idx);
							}
						} elseif ($rule['type'] == "default")
							$webfilter[] = check_webfilter_rules($instance_id, $check_url, $idx);
							//print_r("$idx");die;
					}

					$squid = check_squid_rules($instance_id, $check_url, "");
					//print_r($webfilter);die;
					break;
				}
				if (!isset($webfilter, $squid)) {
					$input_errors[] = dgettext('BluePexWebFilter', gettext('User proxy not found.'));
				}
			} else {
				$input_errors[] = gettext("Authentication failed. Username or Password is incorrect.");
			}
		}
	} elseif (empty($input_errors) && $type = "ip") {
		if (empty($pconfig['ipaddress']) || !preg_match("#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#", $pconfig['ipaddress'])) {
			$input_errors[] = dgettext('BluePexWebFilter', gettext('Address IP invalid!'));
		} else {
			$ipaddress = trim($pconfig['ipaddress']);
			$check_url = trim($pconfig['url']);

			$access_control = !test_access_control($instance_id, "", "", $check_url, $ipaddress, $interface) ? "Blocked" : "Allowed";
			$contentrules   = test_content_rules($instance_id, "", $check_url, $ipaddress);
			$test_status    = "Allowed";

			if ($contentrules != 1) {
				$reason = returnReason($contentrules);
				$categories = returnCategories($contentrules);
				$test_status = "Blocked";
			}
			foreach ($config['system']['webfilter']['nf_content_rules']['element0']['item'] as $idx => $rule) {
				if ($rule['disabled'] != "off")
					continue;

				if ($rule['type'] == "ip" && $rule['ip'] == $ipaddress)
					$webfilter[] = check_webfilter_rules($instance_id, $check_url, $idx);
				elseif ($rule['type'] == "range" && netMatch($rule['range'], $ipaddress))
					$webfilter[] = check_webfilter_rules($instance_id, $check_url, $idx);
				elseif ($rule['type'] == "subnet" && checkipSubnet($ipaddress, $rule['subnet']))
					$webfilter[] = check_webfilter_rules($instance_id, $check_url, $idx);
				elseif ($rule['type'] == "default")
					$webfilter[] = check_webfilter_rules($instance_id, $check_url, $idx);
			}
			$squid = check_squid_rules($instance_id, $check_url, $ipaddress);
		}
	}
	if (empty($input_errors) && isset($access_control, $test_status)) {
		$diagnostic = array(
			array("name" => "ContentRules", "result" => $test_status),
			array("name" => "AccessControl", "result" => $access_control)
		);
	}
	$user = $_GET['user'];
} else {
	if (isset($config['system']['webgui']['authmode'])) {
		$pconfig['authmode'] = $config['system']['webgui']['authmode'];
	} else {
		$pconfig['authmode'] = "Local Database";
	}
}
$pgtitle = array(dgettext("BluePexWebFilter", "WebFilter"), dgettext("BluePexWebFilter", gettext("Diagnostic")));
include('head.inc');

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Dashboard'), false, '/webfilter/wf_dashboard.php');
$tab_array[] = array(dgettext('BluePexWebFilter', gettext('Diagnostic')), true, '/webfilter/wf_diagnostic.php');
$tab_array[] = array(dgettext('BluePexWebFilter', gettext('Port test')), false, '/webfilter/wf_nc.php');
display_top_tabs($tab_array);

if (!empty($wf_instances)) :
$form = new Form();

$section = new Form_Section('Tira Teima');

$instances_proxy = array();
foreach ($wf_instances as $instance_id => $instance_config) {
	$instances_proxy[$instance_id] = $instance_config['server']['name'];
}
$section->addInput(new Form_Select(
	'instance_id',
	'Proxy Instance',
	$pconfig['instance_id'],
	$instances_proxy
), true)->setHelp('Select the proxy instance.')->setWidth(4);

$section->addInput(new Form_Select(
	'interface',
	'Interface',
	(isset($interface) ? $interface : ""),
	array()
), true)->setHelp(dgettext('BluePexWebFilter', 'Select the interface to diagnosticate blocking or access in proxy.'))->setWidth(4);

$group = new Form_Group(dgettext('BluePexWebFilter', 'Type'), true);

$group->add(new Form_Checkbox(
	'type',
	'Type',
	dgettext('BluePexWebFilter', 'User'),
	(isset($type) && $type == "username"),
	'username'
),true)->displayAsRadio()->setWidth(2);

$group->add(new Form_Checkbox(
	'type',
	'Type',
	dgettext('BluePexWebFilter', 'IP Address'),
	(isset($type) && $type == "ip"),
	'ip'
), true)->displayAsRadio()->setWidth(2);

$section->add($group);

foreach (auth_get_authserver_list() as $idx => $auth_server) {
	$serverlist[$idx] = $auth_server['name'];
}

$section->addInput(new Form_Select(
	'authmode',
	'Authentication Server',
	$pconfig['authmode'],
	$serverlist
))->setHelp('Select the authentication server.');

$group = new Form_Group(dgettext('BluePexWebFilter', 'Username/Password'));

$group->add(new Form_Input(
	'username',
	gettext('Username'),
	'text',
	$user,
	['placeholder' => gettext('Username')]
))->setHelp(dgettext('BluePexWebFilter', 'Enter with username.'))->setWidth(3)->setPattern('[0-9, a-z, A-Z and .');

$group->add(new Form_Input(
	'password',
	gettext('Password'),
	'password',
	$pconfig['password'],
	['placeholder' => gettext('Password')]
))->setHelp(dgettext('BluePexWebFilter', 'Enter with Password'))->setWidth(3);

$section->add($group);

$section->addInput(new Form_Input(
	'ipaddress',
	'IP Address',
	'text',
	(!empty($ipaddress) ? $ipaddress : "")
))->setHelp('Enter with IP Address of the user.')->setPattern('[0-9, a-z, A-Z and .');

$section->addInput(new Form_Input(
	'url',
	dgettext('BluePexWebFilter', 'URL'),
	'text',
	(empty($check_url) ? "http://" : $check_url)
))->setHelp(dgettext('BluePexWebFilter', 'Enter with the URL to diagnostic. Ex: http://www.bluepex.com.'));

$form->add($section);

print $form;

$no_reference = dgettext('BluePexWebFilter', 'No reference');
if (!empty($diagnostic)) :
	foreach ($diagnostic as $diag) :
?>

<?php if ($diag['name'] == "ContentRules") : ?>
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h2 class="panel-title"><a href="/webfilter/wf_content_rules.php"><?=dgettext('BluePexWebFilter', 'Content Rules');?></a></h2>
		</div>
		<div class="panel-body">
			<div class="col-md-12">
				<table class="table">
				<tbody>
					<?php if ($type == "username") : ?>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Username');?></label></td>
						<td><?=$user;?></td>
					</tr>
					<?php elseif ($type == "ip") : ?>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'IP Address');?></label></td>
						<td><?=$ipaddress;?></td>
					</tr>
					<?php endif; ?>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'URL');?></label></td>
						<td><?=$check_url?></td>
					</tr>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Status')?></label></td>
						<td>
						<?php if ($diag['result'] == "Allowed") : ?>
							<i class="fa fa-check-circle fa-2x" style="color:#3AAB00"> Navegando...</i>
						<?php elseif ($diag['result'] == "Blocked") : ?>
							<i class="fa fa-times-circle fa-2x" style="color:#f00"> Bloqueado!</i>
						<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Blocked Categories');?></label></td>
						<td><?=($categories) ? $categories : $no_reference;?></td>
					</tr>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Reason');?></label></td>
						<td><?=($reason) ? $reason : $no_reference;?></td>
					</tr>
					<?php if (!empty($webfilter)) : ?>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Rules');?></label></td>
						<td>
							<table class="table" style="background: #FFFFFF">
							<thead>
								<tr>
									<th><?=dgettext('BluePexWebFilter', 'Description Rule');?></th>
									<th><?=dgettext('BluePexWebFilter', 'Whitelist');?></th>
									<th><?=dgettext('BluePexWebFilter', 'Blacklist');?></th>
									<th><?=dgettext('BluePexWebFilter', 'Custom List: Match');?></th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($webfilter as $wf) : ?>
								<tr>
									<td><?=($wf['descrule']) ? $wf['descrule'] : $no_reference;?></td>
									<td><?=($wf['whitelist']) ? $wf['whitelist'] : $no_reference;?></td>
									<td><?=($wf['blacklist']) ? $wf['blacklist']: $no_reference;?></td>
									<td>
									<?php
									if (isset($wf['customlist'])) :
										foreach ($wf['customlist'] as $custom) :
											if (!empty($custom['regex'])) :
									?>
									<b><?=$custom['name']?>: </b><?=$custom['regex']?><hr />
									<?php endif; endforeach; endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
							</table>
						</td>
					</tr>
					<?php else : ?>
					<tr>
						<td colspan="2"><?=$no_reference?></td>
					</tr>
					<?php endif; ?>
				</tbody>
				</table>
			</div>
		</div>
	</div>
<?php elseif ($diag['name'] == "AccessControl") : ?>
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h2 class="panel-title"><a href='/webfilter/wf_access_control.php'><?=dgettext('BluePexWebFilter', 'Access Control');?></a></h2>
		</div>
		<div class="panel-body">
			<div class="col-md-12">
				<table class="table">
				<tbody>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Status');?></label></td>
						<td>
						<?php if ($diag['result'] == "Allowed" || !empty($squid['bypass_source'])) : ?>
							<i class="fa fa-check-circle fa-2x" style="color:#3AAB00"></i>
						<?php elseif ($diag['result'] == "Blocked") : ?>
							<i class="fa fa-times-circle fa-2x" style="color:#F00"></i>
						<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Unrestricted IPs');?></label></td>
						<td><?=($squid['unrestricted_hosts']) ? $squid['unrestricted_hosts'] : $no_reference;?></td>
					</tr>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Banned host addresses');?></label></td>
						<td><?=($squid['banned_hosts']) ? $squid['banned_hosts'] : $no_reference;?></td>
					</tr>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Whitelist');?></label></td>
						<td><?=($squid['whitelist']) ? $squid['whitelist'] : $no_reference;?></td>
					</tr>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Blacklist');?></label></td>
						<td><?=($squid['blacklist']) ? $squid['blacklist'] : $no_reference;?></td>
					</tr>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Bypass source IPs');?></label></td>
						<td><?=($squid['bypass_source']) ? $squid['bypass_source'] : $no_reference;?></td>
					</tr>
					<tr>
						<td width="200"><label><?=dgettext('BluePexWebFilter', 'Bypass destination IPs');?></label></td>
						<td><?=($squid['bypass_dest']) ? $squid['bypass_dest'] : $no_reference;?></td>
					</tr>
				</tbody>
				</table>
			</div>
		</div>
	</div>
<?php endif; ?>
<?php endforeach; endif; ?>

<script type="text/javascript">
window.onload = function(){
	<?php if ($type == "ip") : ?>
		$("#username").parents(".form-group").hide();
	<?php else : ?>
		$("#ipaddress").parents(".form-group").hide();
	<?php endif; ?>

	$("input[name='type']").click(function(e) {
		if ($(this).val() == "username") {
			$("#ipaddress").parents(".form-group").hide();
			$("#username").parents(".form-group").show();
			hideInput('authmode', false);
		} else if ($(this).val() == "ip") {
			$("#username").parents(".form-group").hide();
			$("#ipaddress").parents(".form-group").show();
			hideInput('authmode', true);
		}
	});
	//Save button
	document.getElementById("#save").value = "Validar";

	// Onload page
	var instance_id = $('#instance_id').val();
	load_ifaces_instance_id(instance_id);

	$('#instance_id').change(function() {
		var instance_id = $(this).val();
		load_ifaces_instance_id(instance_id);
	});

	function load_ifaces_instance_id(instance_id) {
		if (instance_id == "" || instance_id == undefined) {
			return;
		}
		$.get("/webfilter/wf_diagnostic.php", { "load_ifaces": "true", "instance_id": instance_id }, function(ifaces) {
			var options = "";
			if (ifaces != "") {
				var ifaces = JSON.parse(ifaces);
				for (var i=0; i<ifaces.length; i++) {
					options += "<option value='"+ifaces[i]+"'>"+ifaces[i]+"</option>";
				}
			}
			$('#interface').html(options);
		});
	}
	$('#authmode').change(function () {
		$("#username").val("");
		$("#password").val("");
    });
};
</script>

<?php else : ?>
	<h2><?=dgettext('BluePexWebFilter', 'No proxy instance configured!');?></h2>
<?php endif; ?>

<?php include("foot.inc"); ?>
