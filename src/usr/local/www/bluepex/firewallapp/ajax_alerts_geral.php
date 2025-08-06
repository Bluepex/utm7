<?php
require_once('guiconfig.inc');
require_once("service-utils.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

global $g, $config;
$supplist = array();
$suri_pf_table = SURICATA_PF_TABLE;
$filterlogentries = FALSE;

$filter = $_POST['ip'];
$hosts = $_POST['hosts'];
$instanceid = isset($_POST['interface']) ? $_POST['interface'] : '';
if ($instanceid == '') {
	die();
}

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

$interface_sur = "suricata_".$if_real.$suricata_uuid;

foreach ($list as $key => $stats) {


	$status = $stats;

}

if ($status == "RUNNING") {

	/* make sure alert file exists */
	if (file_exists("{$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log")) {
		exec("tail -6 -r {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log > {$g['tmp_path']}/alerts_suricata{$suricata_uuid}");
		if (file_exists("{$g['tmp_path']}/alerts_suricata{$suricata_uuid}")) {
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

			$fd = @fopen("{$g['tmp_path']}/alerts_suricata{$suricata_uuid}", "r");
			$buf = "";
			$alert_descr_before = "";
			$alert_action_before = "";
			$alert_ip_before = "";
			$alert_ip_dst_before = "";
			while (($buf = @fgets($fd)) !== FALSE) {			

				$fields = array();
				$tmp = array();
				$decoder_event = FALSE;

				/**************************************************************/
				/* Parse alert log entry to find the parts we want to display */
				/**************************************************************/

				// Field 0 is the event timestamp
				$fields['time'] = substr($buf, 0, strpos($buf, '  '));

				// Field 1 is the rule action (value is '**' when mode is not inline IPS or 'block-drops-only')
				if (($a_instance[$instanceid]['ips_mode'] == 'ips_mode_inline'  || $a_instance[$instanceid]['block_drops_only'] == 'on') && preg_match('/\[([A-Z]+)\]\s/i', $buf, $tmp)) {
					$fields['action'] = trim($tmp[1]);
				}
				else {
					$fields['action'] = null;
				}

				// The regular expression match below returns an array as follows:
				// [2] => GID, [3] => SID, [4] => REV, [5] => MSG, [6] => CLASSIFICATION, [7] = PRIORITY
				preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s/', $buf, $tmp);
				$fields['gid'] = trim($tmp[2]);
				$fields['sid'] = trim($tmp[3]);
				$fields['rev'] = trim($tmp[4]);
				$fields['msg'] = trim($tmp[5]);
				$fields['class'] = trim($tmp[6]);
				$fields['priority'] = trim($tmp[7]);

				// The regular expression match below looks for the PROTO, SRC and DST fields
				// and returns an array as follows:
				// [1] = PROTO, [2] => SRC:SPORT [3] => DST:DPORT
				if (preg_match('/\{(.*)\}\s(.*)\s->\s(.*)/', $buf, $tmp)) {
					// Get PROTO
					$fields['proto'] = trim($tmp[1]);

					// Get SRC
					$fields['src'] = trim(substr($tmp[2], 0, strrpos($tmp[2], ':')));
					if (is_ipaddrv6($fields['src']))
						$fields['src'] = inet_ntop(inet_pton($fields['src']));

					// Get SPORT
					$fields['sport'] = trim(substr($tmp[2], strrpos($tmp[2], ':') + 1));

					// Get DST
					$fields['dst'] = trim(substr($tmp[3], 0, strrpos($tmp[3], ':')));
					if (is_ipaddrv6($fields['dst']))
						$fields['dst'] = inet_ntop(inet_pton($fields['dst']));

					// Get DPORT
					$fields['dport'] = trim(substr($tmp[3], strrpos($tmp[3], ':') + 1));
				}
				else {
					// If no PROTO nor IP ADDR, then this is a DECODER EVENT
					$decoder_event = TRUE;
					$fields['proto'] = gettext("n/a");
					$fields['sport'] = gettext("n/a");
					$fields['dport'] = gettext("n/a");
				}

				// Create a DateTime object from the event timestamp that
				// we can use to easily manipulate output formats.
				$event_tm = date_create_from_format("m/d/Y-H:i:s.u", $fields['time']);

				// Check the 'CATEGORY' field for the text "(null)" and
				// substitute "Not Assigned".
				if ($fields['class'] == "(null)")
					$fields['class'] = gettext("Not Assigned");

				// PHP date_format issues a bogus warning even though $event_tm really is an object
				// Suppress it with @
				@$fields['time'] = date_format($event_tm, "m/d/Y") . " " . date_format($event_tm, "H:i:s");

				if ($filterlogentries && !suricata_match_filter_field($fields, $filterfieldsarray)) {
					continue;
				}

				/* Time */
				@$alert_time = date_format($event_tm, "H:i:s");
				/* Date */
				@$alert_date = date_format($event_tm, "d/m/Y");
				/* Description */			
				$alert_descr = $fields['msg'];

				$alert_descr_url = urlencode($fields['msg']);
				/* Priority */
				$alert_priority = $fields['priority'];
				/* Protocol */
				$alert_proto = $fields['proto'];

				/* IP SRC */
				if ($decoder_event == FALSE) {
					$alert_ip_src = $fields['src'];
					/* Add zero-width space as soft-break opportunity after each colon if we have an IPv6 address */
					$alert_ip_src = str_replace(":", ":&#8203;", $alert_ip_src);		
				}
				else {
					if (preg_match('/\s\[Raw pkt:(.*)\]/', $buf, $tmp))
						$alert_ip_src = "<div title='[Raw pkt: {$tmp[1]}]'>" . gettext("Decoder Event") . "</div>";
					else
						$alert_ip_src = gettext("Decoder Event");
				}

				/* IP SRC Port */
				$alert_src_p = $fields['sport'];

				/* IP DST */
				if ($decoder_event == FALSE) {
					$alert_ip_dst = $fields['dst'];
					/* Add zero-width space as soft-break opportunity after each colon if we have an IPv6 address */
					$alert_ip_dst = str_replace(":", ":&#8203;", $alert_ip_dst);
				}
				else {
					$alert_ip_dst = gettext("n/a");
				}

				/* IP DST Port */
				$alert_dst_p = $fields['dport'];

				if ($fields['sid'] < 9000000)
					continue;

				/* SID */
				$alert_sid_str = "{$fields['gid']}:{$fields['sid']}";
				if (!suricata_is_alert_globally_suppressed($supplist, $fields['gid'], $fields['sid'])) {
					$sidsupplink = "<i class=\"fa fa-plus-square-o icon-pointer\" onClick=\"encRuleSig('{$fields['gid']}','{$fields['sid']}','','{$alert_descr}');$('#mode').val('addsuppress');$('#formalert_face').submit();\"";
					$sidsupplink .= ' title="' . gettext("Add this alert to the Suppress List") . '"></i>';
				}
				else {
					$sidsupplink = '&nbsp;<i class="fa fa-info-circle" ';
					$sidsupplink .= "title='" . gettext("This alert is already in the Suppress List") . "'></i>";
				}
				/* Add icon for toggling rule state */
				if (isset($disablesid[$fields['gid']][$fields['sid']])) {
					$sid_dsbl_link = "<i class=\"fa fa-times-circle icon-pointer text-warning\" onClick=\"encRuleSig('{$fields['gid']}','{$fields['sid']}','','');$('#mode').val('togglesid');$('#formalert_face').submit();\"";
					$sid_dsbl_link .= ' title="' . gettext("Rule is forced to a disabled state. Click to remove the force-disable action from this rule.") . '"></i>';
				}
				else {
					$sid_dsbl_link = "<i class=\"fa fa-times icon-pointer text-danger\" onClick=\"encRuleSig('{$fields['gid']}','{$fields['sid']}','','');$('#mode').val('togglesid');$('#formalert_face').submit();\"";
					$sid_dsbl_link .= ' title="' . gettext("Force-disable this rule and remove it from current rules set.") . '"></i>';
				}

				/* Add icon for toggling rule action if applicable to current mode */
				if ($a_instance[$instanceid]['blockoffenders'] == 'on') {
					if ($a_instance[$instanceid]['block_drops_only'] == 'on' || $a_instance[$instanceid]['ips_mode'] == 'ips_mode_inline') {
						$sid_action_link = "<i class=\"fa fa-pencil-square-o icon-pointer text-info\" onClick=\"toggleAction('{$fields['gid']}', '{$fields['sid']}');\"";
						$sid_action_link .= ' title="' . gettext("Click to force a different action for this rule.") . '"></i>';
						if (isset($alertsid[$fields['gid']][$fields['sid']])) {
							$sid_action_link = "<i class=\"fa fa-exclamation-triangle icon-pointer text-warning\" onClick=\"toggleAction('{$fields['gid']}', '{$fields['sid']}');\"";
							$sid_action_link .= ' title="' . gettext("Rule is forced to ALERT. Click to change the action for this rule.") . '"></i>';
						}
						if (isset($rejectsid[$fields['gid']][$fields['sid']])) {
							$sid_action_link = "<i class=\"fa fa-hand-paper-o icon-pointer text-warning\" onClick=\"toggleAction('{$fields['gid']}', '{$fields['sid']}');\"";
							$sid_action_link .= ' title="' . gettext("Rule is forced to REJECT. Click to change the action for this rule.") . '"></i>';
						}
						if (isset($dropsid[$fields['gid']][$fields['sid']])) {
							$sid_action_link = "<i class=\"fa fa-thumbs-down icon-pointer text-danger\" onClick=\"toggleAction('{$fields['gid']}', '{$fields['sid']}');\"";
							$sid_action_link .= ' title="' . gettext("Rule is forced to DROP. Click to change the action for this rule.") . '"></i>';
						}
					}
				}
				else {
					$sid_action_link = '';
				}

				/* DESCRIPTION */
				$alert_class = $fields['class'];

				$alert_action = !empty($fields['action']) ? 'Bloqueado' : "Liberado";

				if (($alert_descr == $alert_descr_before) && ($alert_action == $alert_action_before) && ($alert_ip_src == $alert_ip_before) && ($alert_ip_dst == $alert_ip_dst_before)) {
					continue;
				}

				$alert_descr_before = $fields['msg'];

				$alert_action_before = $alert_action;
				
				$alert_ip_before = $alert_ip_src;

				$alert_ip_dst_before = $alert_ip_dst;
		?>
		<?php if ($fields['action']) : ?>
				<tr class="text-danger">
		<?php else : ?>

		<?php endif; ?>
		<?php
				$idhost = array_search($alert_ip_src, array_column($hosts,'ip'));
				$hostname = str_replace('form_auth:', '', $hosts[$idhost][4]);
				$color_line = !empty($fields['action']) ? "#ff9999" : "";

				echo "<tr style=\"background-color:$color_line\">";
				echo "<td>$alert_date - $alert_time</td>";
				echo "<td>$alert_descr</td>";
				echo "<td>$alert_ip_src</td>";
				if ($hostname == '') {
					echo "<td>$alert_ip_src</td>";
				} else {
					if ($alert_ip_src == $hosts[$idhost][2]) {
						echo "<td>".$hostname."</td>";	
					} else {
						echo "<td>$alert_ip_src</td>";	
					}
				}
				if (!empty($fields['action']))
					echo "<td>".gettext("Blocked")."</td>";
				else
					echo "<td>".gettext("Released")."</td>";

					
				echo "<td>" . str_replace("_", " ", reset(explode(".rules", end(explode("/", shell_exec('/usr/bin/grep -rl "' . $fields['sid'] . '" /usr/local/share/suricata/rules_fapp/')))))) . "</td>";
				
				echo "</tr>";
			
		?>

		<?php
				$counter++;
			}
			unset($fields, $buf, $tmp);
			@fclose($fd);
			unlink_if_exists("{$g['tmp_path']}/alerts_suricata{$suricata_uuid}");
		}
	}
}
	?>
