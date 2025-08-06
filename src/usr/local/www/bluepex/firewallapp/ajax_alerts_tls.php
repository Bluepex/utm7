<?php
require_once("service-utils.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

global $g, $config;
$supplist = array();
$suri_pf_table = SURICATA_PF_TABLE;
$filterlogentries = FALSE;

$filter = $_POST['ip'];
$hosts = $_POST['hosts'];
$instanceid = isset($_POST['interface']) ? $_POST['interface'] : 0;

function suricata_is_alert_globally_suppressed($list, $gid, $sid) {

	/************************************************/
	/* Checks the passed $gid:$sid to see if it has */
	/* been globally suppressed.  If true, then any */
	/* "track by_src" or "track by_dst" options are */
	/* disabled since they are overridden by the    */
	/* global suppression of the $gid:$sid.         */
	/************************************************/

	/* If entry has a child array, then it's by src or dst ip. */
	/* So if there is a child array or the keys are not set,   */
	/* then this gid:sid is not globally suppressed.           */
	if (is_array($list[$gid][$sid]))
		return false;
	elseif (!isset($list[$gid][$sid]))
		return false;
	else
		return true;
}

if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

$a_instance = &$config['installedpackages']['suricata']['rule'];
$suricata_uuid = $a_instance[$instanceid]['uuid'];
$if_real = get_real_interface($a_instance[$instanceid]['interface']);
$suricatalogdir = SURICATALOGDIR;

//$a_rule = &$config['installedpackages']['suricata']['rule'];

global $g, $config;

global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

//$ret = '';
//
//if (!is_array($config['installedpackages']['suricata']['rule']))
//$config['installedpackages']['suricata']['rule'] = array();
//
//$a_rule = &$config['installedpackages']['suricata']['rule'];
//
//for ($id = 0; $id <= count($a_rule)-1; $id++) {
//
//	$if_real = get_real_interface($a_rule[$id]['interface']);
//
//	$suricata_uuid = $a_rule[$id]['uuid'];
//
//	foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
//		$if = get_real_interface($suricatacfg['interface']);
//	    $uuid = $suricatacfg['uuid'];
//
//	    if ($suricatacfg['interface'] != 'wan') {
//				$ret = $if.$uuid;
//		}
//	}
//
//}

// Load up the arrays of force-enabled and force-disabled SIDs
$enablesid = suricata_load_sid_mods($a_instance[$instanceid]['rule_sid_on']);
$disablesid = suricata_load_sid_mods($a_instance[$instanceid]['rule_sid_off']);

// Load up the arrays of forced-alert, forced-drop or forced-reject
// rules as applicable to the current IPS mode.
if ($a_instance[$instanceid]['blockoffenders'] == 'on' && ($a_instance[$instanceid]['ips_mode'] == 'ips_mode_inline' || $a_instance[$instanceid]['block_drops_only'] == 'on')) {
	$alertsid = suricata_load_sid_mods($a_instance[$instanceid]['rule_sid_force_alert']);
	$dropsid = suricata_load_sid_mods($a_instance[$instanceid]['rule_sid_force_drop']);

	// REJECT forcing is only applicable to Inline IPS Mode
	if ($a_instance[$instanceid]['ips_mode'] == 'ips_mode_inline' ) {
		$rejectsid = suricata_load_sid_mods($a_instance[$instanceid]['rule_sid_force_reject']);
	}
}

$pconfig = array();
if (is_array($config['installedpackages']['suricata']['alertsblocks'])) {
	$pconfig['arefresh'] = $config['installedpackages']['suricata']['alertsblocks']['arefresh'];
	$pconfig['alertnumber'] = $config['installedpackages']['suricata']['alertsblocks']['alertnumber'];
}

if (empty($pconfig['alertnumber']))
	$pconfig['alertnumber'] = 20;
if (empty($pconfig['arefresh']))
	$pconfig['arefresh'] = 'on';
$anentries = $pconfig['alertnumber'];
if (!is_numeric($anentries)) {
	$anentries = 20;
}

//init_config_arr(array('installedpackages', 'suricata', 'rule'));
//$a_rule = &$config['installedpackages']['suricata']['rule'];

$list = array();

//foreach ($a_rule as $natent) {
//	$intf_key = "suricata_" . get_real_interface($natent['interface']) . $natent['uuid'];
//	if ($natent['enable'] == "on") {
//		if (suricata_is_running($natent['uuid'], get_real_interface($natent['interface']))) {
//			$list[$intf_key] = "RUNNING";
//		}
//		elseif (file_exists("{$g['varrun_path']}/{$intf_key}_starting.lck") || file_exists("{$g['varrun_path']}/suricata_pkg_starting.lck")) {
//			$list[$intf_key] = "STARTING";
//		}
//		else {
//			$list[$intf_key] = "STOPPED";
//		}
//		if (file_exists("{$g['varrun_path']}/suricata_updating.lck")) {
//				$list[$intf_key] = "UPDATING";
//		}
//	}
//	else {
//		$list[$intf_key] = "DISABLED";
//	}
//}

#foreach ($a_rule as $natent) {
$intf_key = "suricata_" . $if_real . $suricata_uuid;# . $natent['uuid'];
if ($a_instance[$instanceid]['enable'] == "on") {
	//if (suricata_is_running($natent['uuid'], get_real_interface($natent['interface']))) {
	if (suricata_is_running($suricata_uuid, $if_real)) {	
		$list[$intf_key] = "RUNNING";
	} elseif (file_exists("{$g['varrun_path']}/{$intf_key}_starting.lck") || file_exists("{$g['varrun_path']}/suricata_pkg_starting.lck")) {
		$list[$intf_key] = "STARTING";
	} else {
		$list[$intf_key] = "STOPPED";
	}
	if (file_exists("{$g['varrun_path']}/suricata_updating.lck")) {
		$list[$intf_key] = "UPDATING";
	}
} else {
	$list[$intf_key] = "DISABLED";
}
#}

//$interface_sur = "suricata_".$if_real.$suricata_uuid;

//$interface_sur = "suricata_".$ret;

foreach ($list as $key => $stats) {
	$status = $stats;
}

if ($status == "RUNNING") {

	exec("echo '' > {$g['tmp_path']}/tls_suricata{$suricata_uuid}");
	/* make sure alert file exists */
	if (file_exists("{$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/tls.log")) {
		exec("tail -20 -r {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/tls.log | grep SNI= > {$g['tmp_path']}/tls_suricata{$suricata_uuid}");
		if (file_exists("{$g['tmp_path']}/tls_suricata{$suricata_uuid}")) {
			$tmpblocked = array_flip(suricata_get_blocked_ips());
			$counter = 0;

			/*************** FORMAT without CSV patch -- ALERT -- ***********************************************************************************/
			/* Line format: timestamp  action[**] [gid:sid:rev] msg [**] [Classification: class] [Priority: pri] {proto} src:srcport -> dst:dstport */
			/*             0          1           2   3   4    5                         6                 7     8      9   10         11  12       */
			/****************************************************************************************************************************************/

			/**************** FORMAT without CSV patch -- DECODER EVENT -- **************************************************************************/
			/* Line format: timestamp  action[**] [gid:sid:rev] msg [**] [Classification: class] [Priority: pri] [**] [Raw pkt: ...]                */
			/*              0          1           2   3   4    5                         6                 7                                       */
			/************** *************************************************************************************************************************/

			$fd = @fopen("{$g['tmp_path']}/tls_suricata{$suricata_uuid}", "r");
			$buf_tls = "";
			$alert_descr_before = "";
			$alert_action_before = "";
			$alert_ip_before = "";
			$alert_ip_dst_before = "";
			while (($buf_tls = @fgets($fd)) !== FALSE) {			

				$fields = array();
				$tmp = array();
				$decoder_event = FALSE;

				/**************************************************************/
				/* Parse alert log entry to find the parts we want to display */
				/**************************************************************/

				// time
				preg_match('/(\d{2}\/\d{2}\/\d{4}-\d{2}:\d{2}:\d{2})/', $buf_tls, $tmp);
				$fields['time'] = trim($tmp[1]);
													 //05/04/2021-10:45:19	
				$time_zone1 = date_create_from_format("d/m/Y-H:i:s", $fields['time']);
					

				$time = date_format($time_zone1, "m/d/Y") . " - " . date_format($time_zone1, "H:i:s");



				// msg for SNI
				preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}).*SNI=\'([?!:\/\/a-zA-Z0-9-_.=&]+)\'/', $buf_tls, $tmp);
				$fields['msg'] = trim($tmp[2]); 
							
			

				// The regular expression match below looks for the PROTO, SRC and DST fields
				// and returns an array as follows:
				// [1] = PROTO, [2] => SRC:SPORT [3] => DST:DPORT
				
					

				if (preg_match('/\s(.*)\s->\s(...................)/', $buf_tls, $tmp1)) { //preg_match('/\s(.*)\s->\s([^a-zA-Z]*)/', $input_line, $output_array);
					// Get PROTO
					//$fields['proto'] = trim($tmp[1]);

					

					// Get SRC
					$fields['src'] = trim(substr($tmp1[1], 0, strrpos($tmp1[1], ':')));
					if (is_ipaddrv6($fields['src']))
						$fields['src'] = inet_ntop(inet_pton($fields['src']));

					// Get SPORT
					//$fields['sport'] = trim(substr($tmp[2], strrpos($tmp[2], ':') + 1));

					// Get DST
					$fields['dst'] = trim(substr($tmp1[2], 0, strrpos($tmp1[2], ':')));
					if (is_ipaddrv6($fields['dst']))
						$fields['dst'] = inet_ntop(inet_pton($fields['dst']));

					// Get DPORT
					//$fields['dport'] = trim(substr($tmp[3], strrpos($tmp[3], ':') + 1));
				}
				else {
					// If no PROTO nor IP ADDR, then this is a DECODER EVENT
					$decoder_event = TRUE;
					$fields['proto'] = gettext("n/a");
					$fields['sport'] = gettext("n/a");
					$fields['dport'] = gettext("n/a");
				}
		

				//print_r($fields['msg']);die;

				
				$alert_descr = $fields['msg'];

				$alert_ip_src = $fields['src'];

				$ipdest = $fields['dst'];

				
		?>
		<?php if ($fields['action']) : ?>
				<tr class="text-danger">
		<?php else : ?>

		<?php endif; ?>
		<?php

				$idhost = $fields['src'];
				$hostname = $fields['msg'];
				$ipdest = $fields['dst'];
				$color_line = !empty($fields['action']) ? "#ff9999" : "";

				echo "<tr style=\"background-color:$color_line\"><td>$time</td>";
				echo "<td>$alert_descr</td>";
				echo "<td>$alert_ip_src</td>";
				echo "<td>$ipdest</td>";
				echo "</tr>";
			

		?>

		<?php
				$counter++;
			}
			unset($fields, $buf_tls, $tmp);
			@fclose($fd);
			unlink_if_exists("{$g['tmp_path']}/tls_suricata{$suricata_uuid}");
		}
	}
}
	?>
