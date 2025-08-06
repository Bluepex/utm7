<?php
/*
 * firewallapp_interfaces.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2006-2016 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2003-2004 Manuel Kasper
 * Copyright (c) 2005 Bill Marquette
 * Copyright (c) 2009 Robert Zelaya Sr. Developer
 * Copyright (c) 2018 Bill Meeks
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("guiconfig.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("bp_cron_control.inc");
require_once("bp_auditing.inc");

global $g, $rebuild_rules;

$suricatadir = SURICATADIR;
$suricatalogdir = SURICATALOGDIR;
$rcdir = RCFILEPREFIX;
$suri_starting = array();
$by2_starting = array();

if ($_POST['id']) {
	$id = $_POST['id'];
} else {
	$id = 0;
}

if (!is_array($config['installedpackages']['suricata']['rule'])) {
	$config['installedpackages']['suricata']['rule'] = array();
}

$a_nat = &$config['installedpackages']['suricata']['rule'];
$id_gen = count($config['installedpackages']['suricata']['rule']);
$all_gtw = getInterfacesInGatewaysWithNoExceptions();

// Get list of configured firewall interfaces
$ifaces = get_configured_interface_list();

if (isset($_POST['del_x'])) {
	/* verify captive portal */
	require_once("captiveportal.inc");

	global $cpzone;
	global $cpzoneid;

	$cpzoneid = 2;

	init_config_arr(array('captiveportal'));

	if (isset($config['captiveportal']['firewallapp_lan'])) {
		log_error("Unable to delete instance firewallapp_lan, it is being used by firewallapp by users.");
		header("Location: /firewallapp/firewallapp_interfaces.php");
		exit;
	}

	/* delete selected interfaces */
	if (is_array($_POST['rule']) && count($_POST['rule'])) {
		foreach ($_POST['rule'] as $rulei) {
			$if_real = get_real_interface($a_nat[$rulei]['interface']);
			$if_friendly = convert_friendly_interface_to_friendly_descr($a_nat[$rulei]['interface']);
			$suricata_uuid = $a_nat[$rulei]['uuid'];
			$suricata_eve_redis = $a_nat[$rulei]['eve_redis_key'];
			suricata_stop($a_nat[$rulei], $if_real);

			unlink_if_exists("/etc/suricata_{$if_real}{$suricata_uuid}_stop.lck");
			rmdir_recursive("{$suricatalogdir}suricata_{$if_real}{$suricata_uuid}");
			rmdir_recursive("{$suricatadir}suricata_{$suricata_uuid}_{$if_real}");
			suricata_clean_destroy_tables_pfctl($suricata_uuid, $if_real, 'fapp2c');
			bp_write_report_db("report_0008_acp_fapp_removed", "FirewallApp|{$if_friendly}|{$if_real}{$suricata_uuid}");

			unset($a_nat[$rulei]);
			mwexec("/usr/local/bin/redis-cli del {$suricata_eve_redis}");

			if ($natent['ips_mode'] == "ips_mode_mix") {
				mwexec("kill -9 `ps ax | grep suricata_{$suricata_uuid}_{$if_real} | grep 'suricata_heuristic.yaml' | grep -v grep | awk -F\" \" '{print $1}'`");
			}
	
			suricata_clean_destroy_tables_pfctl($suricata_uuid, $if_real, 'fapp2c');
		}


		/* If all the Firewallapp interfaces are removed, then unset the config array. */
		if (empty($a_nat)) {
			unset($a_nat);
		}

		write_config("Firewallapp pkg: deleted one or more Firewallapp interfaces.");
		bp_cron_acp_fapp_action();
		sleep(2);

		sync_suricata_package_config();

		mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');

		file_put_contents('/etc/interfaces_suricata_fapp', getInterfaceNewFapp());

		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
		header("Location: /firewallapp/firewallapp_interfaces.php");
		exit;
	}

} else {
	unset($delbtn_list);
	foreach ($_POST as $pn => $pd) {
		if (preg_match("/ldel_(\d+)/", $pn, $matches)) {
			$delbtn_list = $matches[1];
		}
	}

	if (is_numeric($delbtn_list) && $a_nat[$delbtn_list]) {
		$if_real = get_real_interface($a_nat[$delbtn_list]['interface']);
		$if_friendly = convert_friendly_interface_to_friendly_descr($a_nat[$delbtn_list]['interface']);
		$suricata_uuid = $a_nat[$delbtn_list]['uuid'];
		$suricata_eve_redis = $a_nat[$rulei]['eve_redis_key'];
		log_error("Stopping Firewallapp on {$if_friendly}({$if_real}) due to interface deletion...");
		suricata_stop($a_nat[$delbtn_list], $if_real);
		unlink_if_exists("{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck");
		unlink_if_exists("/etc/suricata_{$if_real}{$suricata_uuid}_stop.lck");

		rmdir_recursive("{$suricatalogdir}suricata_{$if_real}{$suricata_uuid}");
		rmdir_recursive("{$suricatadir}suricata_{$suricata_uuid}_{$if_real}");

		if ($natent['ips_mode'] == "ips_mode_mix") {
			mwexec("kill -9 `ps ax | grep suricata_{$suricata_uuid}_{$if_real} | grep 'suricata_heuristic.yaml' | grep -v grep | awk -F\" \" '{print $1}'`");
		}

		suricata_clean_destroy_tables_pfctl($suricata_uuid, $if_real, 'fapp2c');
		bp_write_report_db("report_0008_acp_fapp_removed", "FirewallApp|{$if_friendly}|{$if_real}{$suricata_uuid}");

		// Finally delete the interface's config entry entirely
		unset($a_nat[$delbtn_list]);
		log_error("Deleted Firewallapp instance on {$if_friendly}({$if_real}) per user request...");

		// Save updated configuration
		write_config("Firewallapp pkg: deleted one or more Firewallapp interfaces.");
		bp_cron_acp_fapp_action();
		sleep(2);
		sync_suricata_package_config();

		mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');

		file_put_contents('/etc/interfaces_suricata_fapp', getInterfaceNewFapp());

		mwexec("/usr/local/bin/redis-cli del {$suricata_eve_redis}");

		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
		header("Location: /firewallapp/firewallapp_interfaces.php");
		exit;
	}

}

/* start/stop Barnyard2 */
if ($_POST['by2toggle']) {
	$suricatacfg = $config['installedpackages']['suricata']['rule'][$id];
	$if_real = get_real_interface($suricatacfg['interface']);
	$if_friendly = convert_friendly_interface_to_friendly_descr($suricatacfg['interface']);

	if (!suricata_is_running($suricatacfg['uuid'], $if_real, 'barnyard2')) {
		// No need to rebuild Firewallapp rules for Barnyard2,
		// so flag that task as "off" to save time.
		$rebuild_rules = false;
		sync_suricata_package_config();
		suricata_barnyard_start($suricatacfg, $if_real);
		$by2_starting[$id] = 'TRUE';
	} else {
		suricata_barnyard_stop($suricatacfg, $if_real);
		unset($by2_starting[$id]);
	}

	$desc_log = ($if_friendly == $suricatacfg['descr']) ? $if_friendly : "{$if_friendly}({$suricatacfg['descr']})";
	log_error("Toggle (barnyard stopping) for {$desc_log}...");
}

/* start/stop Firewallapp */
if ($_POST['toggle']) {
	$suricatacfg = $config['installedpackages']['suricata']['rule'][$_POST['id']];
	$if_real = get_real_interface($suricatacfg['interface']);
	$if_friendly = convert_friendly_interface_to_friendly_descr($suricatacfg['interface']);
	$id = $_POST['id'];
	$suricata_uuid = $suricatacfg['uuid'];

	// Firewallapp can take several seconds to startup, so to
	// make the GUI more responsive, startup commands are
	// executed as a background process.  The commands
	// are written to a PHP file in the 'tmp_path' which
	// is executed by a PHP command line session launched
	// as a background task.

	// Create steps for the background task to start Firewallapp.
	// These commands will be handed off to a CLI PHP session
	// for background execution as a self-deleting PHP file.
	$start_lck_file = "{$g['varrun_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_starting.lck";
	$suricata_start_cmd = <<<EOD
	<?php
	require_once('/usr/local/pkg/suricata/suricata.inc');
	require_once('service-utils.inc');
	global \$g, \$rebuild_rules, \$config;
	\$suricatacfg = \$config['installedpackages']['suricata']['rule'][{$id}];
	\$rebuild_rules = true;
	suricata_generate_yaml(\$suricatacfg);
	touch('{$start_lck_file}');
	sync_suricata_package_config();
	\$rebuild_rules = false;
	suricata_reload_config(\$suricatacfg);
	suricata_start(\$suricatacfg, '{$if_real}');
	unlink_if_exists('{$start_lck_file}');
	unlink(__FILE__);
	?>
	EOD;

	switch ($_POST['toggle']) {
		case 'start':

			global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

			unlink_if_exists("{$g['varrun_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_stop.lck");
			unlink_if_exists("/etc/suricata_{$if_real}{$suricatacfg['uuid']}_stop.lck");

			copy("/usr/local/pkg/suricata/yalm/fapp/suricata_yaml_template.inc", "/usr/local/pkg/suricata/suricata_yaml_template.inc");

			unlink_if_exists("/usr/local/share/suricata/rules_fapp/_emerging.rules");
			unlink_if_exists("/usr/local/share/suricata/rules_fapp/_ameacas_ext.rules"); 
			unlink_if_exists("/usr/local/share/suricata/rules_fapp/_ameacas.rules");

			foreach (glob("/usr/local/share/suricata/rules/") as $files) {
				unlink_if_exists($files);
			}
			foreach (glob("/usr/local/share/suricata/rules_fapp/*") as $files) {
				copy($files, "/usr/local/share/suricata/rules/".basename($files));
			}

			unlink_if_exists("/usr/local/share/suricata/rules/_ameacas.rules");
			unlink_if_exists("/usr/local/share/suricata/rules/_ameacas_ext.rules");
			unlink_if_exists("/usr/local/share/suricata/rules/_emerging.rules");

			file_put_contents("{$g['tmp_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_startcmd.php", $suricata_start_cmd);

			$text_log_start = 'report_0008_acp_fapp_start';

			if (suricata_is_running($suricatacfg['uuid'], $if_real)) {
				log_error("Stop Firewallapp on {$if_friendly}({$if_real}) per user request...");
				suricata_stop($suricatacfg, $if_real);
				$text_log_start = 'report_0008_acp_fapp_restart';
			}

			foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
				$if = get_real_interface(strtolower($suricatacfg['interface']));
				$uuid = $suricatacfg['uuid'];
				if ($suricatacfg['interface'] == $if_real ||
				    $if_real != $if ||
				    in_array($if, $all_gtw,true) ||
				    $suricatacfg['enable'] != 'on' ||
				    $if == "" ||
				    (file_exists("{$g['varrun_path']}/suricata_{$if}{$uuid}.pid") && isvalidpid("{$g['varrun_path']}/suricata_{$if}{$uuid}.pid"))) {
					continue;
				}

				unlink_if_exists("{$g['varrun_path']}/suricata_{$if}{$uuid}.pid");

				$dh  = opendir("/usr/local/share/suricata/rules");
				$rulesetsfile = "";
				while (false !== ($filename = readdir($dh))) {
					if (substr($filename, -5) != "rules")
						continue;
					$rulesetsfile .= basename($filename) . "||";
				}

				$config['installedpackages']['suricata']['rule'][$key]['rulesets'] = rtrim($rulesetsfile, "||");

				file_put_contents("{$g['varrun_path']}/suricata_{$if}{$uuid}_starting", '');

				// Save configuration changes
				write_config("FirewallApp pkg: modified ruleset configuration");

				$ruledir = "{$suricata_rules_dir}";
				$currentfile = $_POST['currentfile'];
				$rulefile = "{$ruledir}{$currentfile}";
				$id = $key;
				$a_rule = &$config['installedpackages']['suricata']['rule'][$id];
				$rules_map = suricata_load_rules_map($rulefile);
				suricata_modify_sids_action($rules_map, $a_rule);
				$rebuild_rules = true;
				suricata_generate_yaml($a_rule);
				$rebuild_rules = false;
				/* Signal Suricata to "live reload" the rules */
				suricata_reload_config($a_rule);
				// Sync to configured CARP slaves if any are enabled
				suricata_sync_on_changes();
				sleep(2);
				suricata_start($suricatacfg, get_real_interface($suricatacfg['interface']));
				bp_write_report_db($text_log_start, "FirewallApp|{$if_friendly}|{$if_real}{$suricata_uuid}");
				sleep(28);
				break;
			}
			bp_cron_acp_fapp_action();
			mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');

			break;
		case 'stop':

			unlink_if_exists("{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_starting");

			if ($suricatacfg['ips_mode'] == 'ips_mode_legacy') {
				mwexec("/sbin/pfctl -t fapp2c_{$if_real}{$suricata_uuid} -T flush");
			}

			if (suricata_is_running($suricata_uuid, $if_real)) {
				log_error("Stopping Firewallapp on {$if_friendly}({$if_real}) per user request...");
				unlink_if_exists("{$g['varrun_path']}/suricata_start_all.lck");
				bp_write_report_db("report_0008_acp_fapp_stop", "FirewallApp|{$if_friendly}|{$if_real}{$suricata_uuid}");
				suricata_stop($suricatacfg, $if_real);
			}

			unset($suri_starting[$id]);
			unset($by2_starting[$id]);
			unlink_if_exists($start_lck_file);

			mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');

			break;
		default:
			unset($suri_starting[$id]);
			unset($by2_starting[$id]);
			unlink_if_exists($start_lck_file);
			break;
	}
	unset($suricata_start_cmd);

}

/* Ajax call to periodically check Firewallapp status */
/* on each configured interface.                   */
if ($_POST['status'] == 'check') {
	$list = array();

	// Iterate configured Firewallapp interfaces and get status of each
	// into an associative array.  Return the array to the Ajax
	// Iterate configured Barnyard2 interfaces and add status of each
	// caller as a JSON object.
	foreach ($a_nat as $natent) {
		$intf_key_suricata = "suricata_" . get_real_interface($natent['interface']) . $natent['uuid'];
		$list[$intf_key_suricata] = "DISABLED";
		if ($natent['enable'] == "on") {
			if (suricata_is_running($natent['uuid'], get_real_interface($natent['interface']))) {
				$list[$intf_key_suricata] = "RUNNING";
			} elseif (file_exists("{$g['varrun_path']}/{$intf_key_suricata}_starting.lck") || file_exists("{$g['varrun_path']}/suricata_pkg_starting.lck")) {
				$list[$intf_key_suricata] = "STARTING";
			} else {
				$list[$intf_key_suricata] = "STOPPED";
			}
		}

		$intf_key_barnyard = "barnyard2_" . get_real_interface($natent['interface']) . $natent['uuid'];
		$list[$intf_key_barnyard] = "DISABLED";
		if ($natent['barnyard_enable'] == "on") {
			if (suricata_is_running($natent['uuid'], get_real_interface($natent['interface']), 'barnyard2')) {
				$list[$intf_key_barnyard] = "RUNNING";
			} elseif (file_exists("{$g['varrun_path']}/{$intf_key_barnyard}_starting.lck") || file_exists("{$g['varrun_path']}/suricata_pkg_starting.lck")) {
				$list[$intf_key_barnyard] = "STARTING";
			} else {
				$list[$intf_key_barnyard] = "STOPPED";
			}
		}
	}

	// Return a JSON encoded array as the page output
	echo json_encode($list);
	exit;
}

// May decide to use these again for display, but for now they are not used
$suri_bin_ver = SURICATA_BIN_VERSION;
$suri_pkg_ver = SURICATA_PKG_VER;

$pgtitle = array(gettext("Services"), gettext("FirewallApp"), gettext("Interfaces"));
$pglinks = array("", "/firewallapp/services.php", "@self");

include_once("head.inc");

/* Display Alert message */
if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg);
}

$tab_array = array();
$tab_array[] = array(gettext("Interfaces"), true, "/firewallapp/firewallapp_interfaces.php");
display_top_tabs($tab_array, true);
?>

<form action="firewallapp_interfaces.php" method="post" enctype="multipart/form-data" name="iform" id="iform">
<input type="hidden" name="id" id="id" value="">
<input type="hidden" name="toggle" id="toggle" value="">
<input type="hidden" name="by2toggle" id="by2toggle" value="">

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Interfaces Viewer")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table id="maintable" class="table table-striped table-hover table-condensed">
				<thead>
				<tr id="frheader">
					<th>&nbsp;</th>
					<th><?=gettext("Interface"); ?></th>
					<th><?=gettext("FirewallApp"); ?></th>
					<th><?=gettext("Description"); ?></th>
					<th><?=gettext("Method of executation"); ?></th>
					<th><?=gettext("Actions")?></th>
				</tr>
				</thead>
				<tbody>
				<?php $nnats = $i = 0;

				// Turn on buffering to speed up rendering
				ini_set('output_buffering','true');

				// Start buffering to fix display lag issues in IE9 and IE10
				ob_start(null, 0);

				/* If no interfaces are defined, then turn off the "no rules" warning */
				$no_rules_footnote = false;
				$no_rules = ($id_gen == 0) ? false : true;

				foreach ($a_nat as $natent):

					$if_r = get_real_interface($natent['interface']);

					if (!in_array($if_r, $all_gtw, true)) {

						echo "<tr id='fr{$nnats}'>";

					/* convert fake interfaces to real and check if iface is up */
					/* There has to be a smarter way to do this */
					$if_real = get_real_interface($natent['interface']);
					$natend_friendly= convert_friendly_interface_to_friendly_descr($natent['interface']);
					$suricata_uuid = $natent['uuid'];

					/* See if interface has any rules defined and set boolean flag */
					$no_rules = true;

					if (isset($natent['customrules']) && !empty($natent['customrules'])) {
						$no_rules = false;
					}

					if (isset($natent['rulesets']) && !empty($natent['rulesets'])) {
						$no_rules = false;
					}

					if (isset($natent['ips_policy']) && !empty($natent['ips_policy'])) {
						$no_rules = false;
					}

					/* Do not display the "no rules" warning if interface disabled */
					if ($natent['enable'] == "off") {
						$no_rules = false;
					}

					if ($no_rules) {
						$no_rules_footnote = true;
					}
?>
					<td>
						<input type="checkbox" id="frc<?=$nnats?>" name="rule[]" value="<?=$i?>" onClick="fr_bgcolor('<?=$nnats?>')" style="margin: 0; padding: 0;">
					</td>
					<td id="frd<?=$nnats?>">
						<?php echo $natend_friendly; ?>
					</td>

					<td id="frd<?=$nnats?>">
					<?php $check_suricata_info = $config['installedpackages']['suricata']['rule'][$nnats]['enable']; ?>
					<?php if ($check_suricata_info == "on") : ?>
						<?php if (suricata_is_running($suricata_uuid, $if_real)) : ?>
							<i id="suricata_<?=$if_real.$suricata_uuid;?>" class="fa fa-check-circle text-success icon-primary" title="<?=gettext('firewallapp is running on this interface');?>"></i>
							&nbsp;
							<i name="click_ativa" class="fa fa-play-circle icon-pointer icon-primary text-info hidden" onclick="javascript:suricata_iface_toggle('start', '<?=$nnats?>');" title="<?=gettext('Start firewallapp on this interface');?>"></i>
							<i name="click_disabled" id="suricata_<?=$if_real.$suricata_uuid;?>_stop" class="fa fa-stop-circle-o icon-pointer icon-primary text-info" onclick="javascript:suricata_iface_toggle('stop', '<?=$nnats?>');" title="<?=gettext('Stop firewallapp on this interface');?>"></i>
						<?php elseif ($suri_starting[$nnats] == 'TRUE' || file_exists("{$g['varrun_path']}/suricata_pkg_starting.lck")) : ?>
							<i id="suricata_<?=$if_real.$suricata_uuid;?>" class="fa fa-cog fa-spin text-success icon-primary" title="<?=gettext('firewallapp is starting on this interface');?>"></i>
							&nbsp;
							<i name="click_ativa" class="fa fa-play-circle icon-pointer icon-primary text-info hidden" onclick="javascript:suricata_iface_toggle('start', '<?=$nnats?>');" title="<?=gettext('Start firewallapp on this interface');?>"></i>
							<i name="click_disabled" id="suricata_<?=$if_real.$suricata_uuid;?>_stop" class="fa fa-stop-circle-o icon-pointer icon-primary text-info" onclick="javascript:suricata_iface_toggle('stop', '<?=$nnats?>');" title="<?=gettext('Stop firewallapp on this interface');?>"></i>
						<?php else: ?>
							<i class="fa fa-times-circle text-danger icon-primary" title="<?=gettext('firewallapp is stopped on this interface');?>"></i>
							&nbsp;
							<i name="click_ativa" class="fa fa-play-circle icon-pointer icon-primary text-info" onclick="javascript:suricata_iface_toggle('start', '<?=$nnats?>');" title="<?=gettext('Start firewallapp on this interface');?>"></i>
							<i name="click_disabled" id="suricata_<?=$if_real.$suricata_uuid;?>_stop" class="fa fa-stop-circle-o icon-pointer icon-primary text-info hidden" onclick="javascript:suricata_iface_toggle('stop', '<?=$nnats?>');" title="<?=gettext('Stop firewallapp on this interface');?>"></i>
						<?php endif; ?>
					<?php else : ?>
						<?=gettext('DISABLED');?>&nbsp;
					<?php endif; ?>

					</td>
					<td>
						<?=htmlspecialchars($natent['descr'])?>
					</td>
					<td>
						<?php
						$text_mode = "Performance";
						if ($natent['ips_mode'] == "ips_mode_legacy" &&
						    file_exists("/etc/performance_extends") &&
						    trim(file_get_contents("/etc/performance_extends")) == "true") {
							$text_mode .= " (With exceptions)";
						} elseif ($natent['ips_mode'] == "ips_mode_mix") {
							$text_mode = "Performance (Mixed Mode)";
						} else if ($natent['ips_mode'] == "ips_mode_inline") {
							$text_mode = "Heuristic";
						}
						echo gettext($text_mode);
						?>
					</td>
					<td>
						<a href="firewallapp_interfaces_edit.php?id=<?=$nnats;?>" class="fa fa-pencil icon-primary" title="<?=gettext('Edit this FirewallApp interface mapping');?>"></a>
						<?php if ($id_gen < count($ifaces)): ?>
							<a href="firewallapp_interfaces_edit.php?id=<?=$nnats?>&action=dup" class="fa fa-clone" title="<?=gettext('Clone this FirewallApp instance to an available interface');?>"></a>
						<?php endif; ?>
						<a href="firewallapp_flow_stream.php?id=<?=$nnats?>" class="fa fa-cog" title="<?=gettext('Advanced Config to Memory Flow');?>"></a>
						<a style="cursor:pointer;" class="fa fa-trash no-confirm icon-primary" id="Xldel_<?=$nnats?>" title="<?=gettext('Delete this Firewallapp interface mapping'); ?>"></a>
						<button style="display: none;" class="btn btn-xs btn-warning" type="submit" id="ldel_<?=$nnats?>" name="ldel_<?=$nnats?>" value="ldel_<?=$nnats?>" title="<?=gettext('Delete this Firewallapp interface mapping'); ?>"><?=gettext("Delete this Firewallapp interface mapping")?></button>
						<?php
						$line_info_interface = "";
						$line_info_interface .= gettext("Interface:")  . " " . $natent['interface'];
						$line_info_interface .= "&#013;";
						$line_info_interface .= gettext("Real Interface:") . " " . get_real_interface($natent['interface']);
						$line_info_interface .= "&#013;";
						$line_info_interface .= gettext("Identification:") . " " . $natent['uuid']?>
						<i class="fa fa-info-circle icon-pointer" data-placement="right" style="color: #337AB7; font-size:20px; margin-bottom: 10px;" title="<?=$line_info_interface?>"></i>
					</td>

				</tr>
				<?php } $i++; $nnats++; endforeach; ob_end_flush(); unset($suri_starting); unset($by2_starting); ?>
				<tr>
					<td></td>
					<td colspan="7">
						<?php if ($no_rules_footnote): ?><span class="text-danger"><?=gettext("WARNING: Marked interface currently has no rules defined for Firewallapp"); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- Modal Ativa -->
<div class="modal fade" id="modal_ativa" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_ativa" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_ativa" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

</form>

<div class="infoblock">
	<?=print_info_box('<div class="row">
		<div class="col-md-12">
			<p>' . gettext("This is where you can see an overview of all your UI settings.") . '</p>
			<p><strong>' . gettext("Warning: New settings will not take effect until the interface restarts.") . '</strong></p>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<p>
				' . gettext("Click on the") . '<i class="fa fa-lg fa-pencil" alt="Edit Icon"></i> ' . gettext("To edit an interface and settings.") . '<br/>
				' . gettext("Click on the") . '<i class="fa fa-lg fa-trash" alt="Delete Icon"></i> ' . gettext("To delete an interface and settings. You must stop the interface to be able to delete.") . '<br/>
				' . gettext("Click on the") . '<i class="fa fa-lg fa-clone" alt="Clone Icon"></i> ' . gettext("To clone an existing interface.") . '.
			</p>
		</div>
		<div class="col-md-6">
			<p>
				<i class="fa fa-lg fa-check-circle" alt="Running"></i> <i class="fa fa-lg fa-times" alt="Not Running"></i> ' . gettext("Display the current status of Firewallapp and") . '<br/>
				' . gettext("Click on the") . '<i class="fa fa-lg fa-play-circle" alt="Start"></i> ' . gettext("or") . ' <i class="fa fa-lg fa-repeat" alt="Restart"></i> ' . gettext("or") . ' <i class="fa fa-lg fa-stop-circle-o" alt="Stop"></i> ' . gettext("to Start/Stop and Restart.") . '
			</p>
		</div>
	</div>', 'info')?>
</div>

<script type="text/javascript">
//<![CDATA[
	function check_status() {

		// This function uses Ajax to post a query to
		// this page requesting the status of each
		// configured interface.  The result is returned
		// as a JSON array object.  A timer is set upon
		// completion to call the function again in
		// 2 seconds.  This allows dynamic updating
		// of interface status in the GUI.
		$.ajax(
			"<?=$_SERVER['SCRIPT_NAME'];?>",
			{
				type: 'post',
				data: {
					status: 'check'
				},
				success: showStatus,
				complete: function() {
					setTimeout(check_status, 2000);
				}
			}
		);
	}

	function showStatus(responseData) {

		// The JSON object returned by check_status() is an associative array
		// of interface unique IDs and corresponding service status.  The
		// "key" is the service name followed by the physical interface and a UUID.
		// The "value" of the key is either "DISABLED, STOPPED, STARTING, or RUNNING".
		//
		// Example keys:  suricata_em1998 or barnyard2_em1998
		//
		// Within the HTML of this page, icon controls for displaying status
		// and for starting/restarting/stopping the service are tagged with
		// control IDs using "key" followed by the icon's function.  These
		// control IDs are used in the code below to alter icon appearance
		// depending on the service status.

		var data = jQuery.parseJSON(responseData);

		// Iterate the associative array and update interface status icons
		for(var key in data) {
			var service_name = 'firewallapp';
			if (data[key] != 'DISABLED') {
				if (data[key] == 'STOPPED') {
					$('#' + key).removeClass('fa-check-circle fa-cog fa-spin text-success text-info');
					$('#' + key).addClass('fa-times-circle text-danger');
					$('#' + key).prop('title', service_name + "<?=gettext(' is stopped on this interface')?>");
					$('#' + key + '_restart').addClass('hidden');
					$('#' + key + '_stop').addClass('hidden');
					$('#' + key + '_start').removeClass('hidden');
				}
				if (data[key] == 'STARTING') {
					$('#' + key).removeClass('fa-check-circle fa-times-circle text-info text-danger');
					$('#' + key).addClass('fa-cog fa-spin text-success');
					$('#' + key).prop('title', service_name + "<?=gettext(' is starting on this interface')?>");
					$('#' + key + '_restart').addClass('hidden');
					$('#' + key + '_start').addClass('hidden');
					$('#' + key + '_stop').removeClass('hidden');
				}
				if (data[key] == 'RUNNING') {
					$('#' + key).addClass('fa-check-circle text-success');
					$('#' + key).removeClass('fa-times-circle fa-cog fa-spin text-danger text-info');
					$('#' + key).prop('title', service_name + "<?=gettext(' is running on this interface')?>");
					$('#' + key + '_restart').removeClass('hidden');
					$('#' + key + '_stop').removeClass('hidden');
					$('#' + key + '_start').addClass('hidden');
				}
			}
		}
	}

	function suricata_iface_toggle(action, id) {
		$('#toggle').val(action);
		$('#id').val(id);
		$('#iform').submit();
	}

	function by2_iface_toggle(action, id) {
		$('#by2toggle').val(action);
		$('#id').val(id);
		$('#iform').submit();
	}

	function intf_del() {
		var isSelected = false;
		var inputs = document.iform.elements;
		for (var i = 0; i < inputs.length; i++) {
			if (inputs[i].type == "checkbox") {
				if (inputs[i].checked) {
					isSelected = true;
				}
			}
		}
		if (isSelected) {
			return confirm("<?=gettext('Do you really want to delete the selected Firewallapp mapping?')?>");
		} else {
			alert("<?=gettext("There is no Firewallapp mapping selected for deletion.  Click the checkbox beside the Firewallapp mapping(s) you wish to delete.")?>");
		}
	}

	events.push(function() {
		$('[id^=Xldel_]').click(function (event) {
			if (confirm("<?=gettext('Delete this Firewallapp interface mapping?')?>")) {
				$('#' + event.target.id.slice(1)).click();
			}
		});

	});

	// Set a timer to call the check_status()
	// function in two seconds.
	setTimeout(check_status, 2000);

//]]>
</script>

<?php
include("foot.inc");
?>
