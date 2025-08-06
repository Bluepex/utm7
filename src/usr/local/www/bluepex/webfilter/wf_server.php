<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("nf_defines.inc");
require_once("squid.inc");
require_once("webfilter.inc");
require_once("bp_auditing.inc");

$input_errors = array();
$savemsg = "";
global $openssl_digest_algs;

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config']= array();
}
$wf_instances = &$config['system']['webfilter']['instance']['config'];

if (isset($_GET['act']) && $_GET['act'] == "del") {
	if (isset($_GET['id']) && is_numericint($_GET['id'])) {
		if (isset($wf_instances[$_GET['id']])) {
			bp_write_report_db("report_0008_webfilter_server_remove", $wf_instances[$_GET['id']]['server']['name']);
			bp_write_report_db("report_0008_webfilter_setting_remove", $wf_instances[$_GET['id']]['server']['name']);
			unset($wf_instances[$_GET['id']]);
			add_instances_info();
			remove_instance_rules($_GET['id']);
			$savemsg = dgettext("BluePexWebFilter", "Proxy Server removed successfully!");
			write_config($savemsg);
		}
	} else {
		$input_errors = dgettext("BluePexWebFilter", "Could not to remove the Proxy Server!");
	}

	mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');
}

if (isset($_GET['act']) && $_GET['act'] == 'expcert') {
	if(isset($_GET['id']) && isset($_GET['format'])) {
		$instance = $wf_instances[(int)$_GET['id']];
		$ca = "";
		$certificate = "";
		foreach($config['ca'] as $cert) {
			if($cert['refid'] == $instance['server']['dca']) {
					$ca = $cert;
			}
		}
		foreach((array)$config['cert'] as $cert) {
			if($cert['caref'] == $ca['refid']) {
				$certificate = $cert;
			}
		}
		if($_GET['format'] == 'crt') {
			$crt_data = base64_decode($certificate['crt']);
			$crt_name = urlencode($certificate['descr'].'.crt');
			$crt_size = strlen($crt_data);
		}
		elseif($_GET['format'] == 'p12') {
			$crt_name = urlencode($certificate['descr'].'.p12');
			$args = array();
			$args['friendly_name'] = $certificate['caref'];
			if ($ca) {
				$args['extracerts'] = openssl_x509_read(base64_decode($ca['crt']));
			}
			$res_crt = openssl_x509_read(base64_decode($certificate['crt']));
			$res_key = openssl_pkey_get_private(array(0 => base64_decode($certificate['prv']), 1 => ""));
			$crt_data = "";
			openssl_pkcs12_export($res_crt, $crt_data, $res_key, null, $args);
			$crt_size = strlen($crt_data);
		}
		else {
			exit;
		}
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename={$crt_name}");
		header("Content-Length: $crt_size");
		echo $crt_data;
		exit;
	}
}

if (isset($_POST['order_save'])) {
	if (isset($_POST['check_server']) && !empty($_POST['check_server'])) {
		$instance_ordered = array();
		foreach ($_POST['check_server'] as $id) {
			$instance_ordered[] = $wf_instances[$id];
		}
		$wf_instances = $instance_ordered;
		bp_write_report_db("report_0008_webfilter_server_reorder");
		$savemsg = dgettext("BluePexWebFilter", "The Proxy Server have been successfully ordered!");
		write_config($savemsg);
	} else {
		$input_errors[] = dgettext("BluePexWebfilter", "Could not to order the Proxy Server");
	}
}

if (isset($_POST['remove_instance'])) {
	if (isset($_POST['check_server']) && !empty($_POST['check_server'])) {
		foreach ($_POST['check_server'] as $id) {
			bp_write_report_db("report_0008_webfilter_server_remove", $wf_instances[$id]['server']['name']);
			bp_write_report_db("report_0008_webfilter_setting_remove", $wf_instances[$id]['server']['name']);
			unset($wf_instances[$id]);
			remove_instance_rules($id);
		}
		add_instances_info();
		$savemsg = dgettext("BluePexWebFilter", "The Proxy Server have been successfully removed!");
		write_config($savemsg);
	} else {
		$input_errors[] = dgettext("BluePexWebFilter", "Could not to remove the Proxy Server!");
	}

	mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');
}

$pgtitle = array("WebFilter", dgettext("BluePexWebFilter", "Proxy Server"));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors); 
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), true, '/webfilter/wf_server.php');
//$tab_array[] = array(dgettext('BluePexWebFilter', 'Upstream Proxy'), false, '/webfilter/wf_upstream.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Cache Mgmt'), false, '/webfilter/wf_cache_mgmt.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Access Control'), false, '/webfilter/wf_access_control.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Traffic Mgmt'), false, '/webfilter/wf_traffic_mgmt.php');
display_top_tabs($tab_array);
?>
<form action="/webfilter/wf_server.php" method="POST">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=dgettext("BluePexWebFilter", gettext("Proxy Server List"))?></h2></div>
	<div class="panel-body">
		<div class="table-responsive col-md-12">
			<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th></th>
					<th><?=dgettext("BluePexWebFilter", "Name"); ?></th>
					<th><?=dgettext("BluePexWebFilter", gettext("Parent Content Rules")); ?></th>
					<th><?=dgettext("BluePexWebFilter", "Interfaces");?></th>
					<th><?=dgettext("BluePexWebFilter", "Port");?></th>
					<th><?=dgettext("BluePexWebFilter", "Transparent Proxy");?></th>
					<th><?=dgettext("BluePexWebFilter", gettext("Auth Method"));?></th>
					<th><?=dgettext("BluePexWebFilter", "Status");?></th>
					<th></th>
				</tr>
			</thead>
			<tbody class="instance-entries">
			<?php 
				if (!empty($wf_instances)) : 
					foreach ($wf_instances as $id => $instance) : 
			?>
				<tr <?php if ($instance['server']['enable_squid'] != "on") echo "class='disabled'"; ?>>
					<td><input type="checkbox" name="check_server[]" value="<?=$id?>" /></td>
					<td><?=$instance['server']['name'];?></td>
					<td>
					<?php 
						if (isset($instance['server']['parent_rules']) && is_numeric($instance['server']['parent_rules'])) {
							echo $wf_instances[$instance['server']['parent_rules']]['server']['name'];
						}
					?>
					</td>
					<td><?=$instance['server']['active_interface'];?></td>
					<td><?=$instance['server']['proxy_port'];?></td>
					<td><?=$instance['server']['transparent_proxy'];?></td>
					<td>
					<?php
						if ($instance['server']['authsettings']['auth_method'] == "none") {
							echo dgettext("BluePexWebFilter", "Transparent");
						} elseif ($instance['server']['authsettings']['auth_method'] == "ntlm") { 
							if (isset($instance['server']['authsettings']['sso_parent'])) {
								echo sprintf(dgettext("BluePexWebFilter", "Parent (%s) Single Sign On (SSO)"), $wf_instances[$instance['server']['authsettings']['sso_parent']]['server']['name']);
							} else {
								echo dgettext("BluePexWebFilter", "Single Sign On (SSO)");
							}
						} elseif ($instance['server']['authsettings']['auth_method'] == "usermanager") {
							echo dgettext("BluePexWebFilter", "User Manager");
						} elseif ($instance['server']['authsettings']['auth_method'] == "cp") {
							echo dgettext("BluePexWebFilter", "Captive Portal");
						}
					?>
					</td>
					<td><?=$instance['server']['enable_squid'];?></td>
					<td>
						<a href="/webfilter/wf_server_edit.php?act=edit&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Edit Server Proxy"); ?>">
							<i class="fa fa-edit"></i>
						</a>
						&nbsp;
						<a href="/webfilter/wf_server.php?act=del&id=<?=$id?>" title="<?=dgettext("BluePexWebFilter", "Remove Server Proxy"); ?>">
							<i class="fa fa-trash"></i>
						</a>
						<?php if(isset($instance['server']['ssl_proxy']) && $instance['server']['ssl_proxy'] == "on") : ?>
							&nbsp;
							&nbsp;
							<a href="/webfilter/wf_server.php?act=expcert&id=<?=$id?>&format=crt" title="<?=dgettext("BluePexWebFilter", "Download CRT"); ?>">
								<i class="fa fa-certificate"></i>
							</a>
							&nbsp;
							<a href="/webfilter/wf_server.php?act=expcert&id=<?=$id?>&format=p12" title="<?=dgettext("BluePexWebFilter", "Download P12"); ?>">
								<i class="fa fa-archive"></i>
							</a>
							&nbsp;
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
			</table>
			<nav class="action-buttons">
				<a href="/webfilter/wf_server_edit.php" class="btn btn-xs btn-success" title="<?=dgettext("BluePexWebFilter", "Add Server Proxy")?>">
					<i class="fa fa-plus icon-embed-btn"></i> <?=dgettext("BluePexWebFilter", "Add");?>
				</a>
				<button type="submit" id="remove" name="remove_instance" class="btn btn-xs btn-danger" title="<?=dgettext("BluePexWebFilter", "Remove Server Proxy")?>">
					<i class="fa fa-trash icon-embed-btn"></i> <?=dgettext("BluePexWebFilter", "Remove");?>
				</button>
			</nav>
		</div>
	</div>
</div>
</form>
<?php include("foot.inc"); ?>
<script>
$($("#shortcuts-table tbody tr td")[0]).css({"display":"revert", "padding":"5px", "border":"3px solid #fff"});
</script>
